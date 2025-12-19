# SIDIS - Sistema de Gestión Médica Frontend

## Arquitectura del Proyecto

Este es un **sistema médico híbrido Laravel 10 + SQLite offline** diseñado para funcionar con/sin conectividad. El frontend Laravel se comunica con una API REST externa (`sidis.nacerparavivir.org/api/v1`) pero puede operar offline usando SQLite local.

### Estructura de Capas

**Controllers → Services → API/OfflineService**

- **Controllers** ([app/Http/Controllers](app/Http/Controllers)): Solo validación y respuestas HTTP
- **Services** ([app/Services](app/Services)): Toda la lógica de negocio
  - `ApiService.php`: Cliente HTTP para API REST (con cache de conectividad)
  - `OfflineService.php`: Persistencia SQLite + sincronización (12k+ líneas)
  - `AuthService.php`: Login online/offline + gestión de sesión
  - `*Service.php`: Lógica de dominio por módulo

**NO usar modelos Eloquent** - Este proyecto NO tiene modelos de base de datos local. Los datos vienen de la API o de SQLite mediante `DB::connection('offline')`.

## Funcionamiento Offline/Online

### Patrón de Sincronización

Todos los servicios siguen este patrón:

```php
// 1. Verificar conectividad (cacheada 30s)
if ($this->apiService->isOnline()) {
    // 2. Llamar API y guardar en SQLite
    $response = $this->apiService->post('/endpoint', $data);
    if ($response['success']) {
        $this->offlineService->storeData($response['data']);
    }
} else {
    // 3. Guardar en SQLite con sync_status='pending'
    DB::connection('offline')->table('agendas')->insert([
        'uuid' => Str::uuid(),
        'sync_status' => 'pending',
        'data' => json_encode($payload)
    ]);
}
```

### Archivos Críticos

- [config/api.php](config/api.php): Configuración de endpoints y conectividad
- [app/Services/OfflineService.php](app/Services/OfflineService.php#L26-L32): Inicialización SQLite dinámica
- [storage/app/offline/offline_data.sqlite](CxampphtdocsFrontSidisstorageapp): Base de datos SQLite (creada en runtime)
- Scripts de debug: `clean_error_agendas.php`, `debug_sync_error.php` - ejecutar con `php archivo.php`

## Módulos Principales

### 1. Autenticación Multi-Sede

**Usuarios autentican contra API + seleccionan sede específica**

```php
// app/Services/AuthService.php - Línea ~26-106
public function login(array $credentials) {
    // 1. Intentar login API
    // 2. Si falla por conectividad → login offline con hash guardado
    // 3. Guardar usuario + sede_id en sesión
    // 4. Redirigir según rol: admin → /admin, médico → /dashboard
}
```

Ver [routes/web.php](routes/web.php#L19-L42) para rutas públicas sin middleware.

### 2. Pacientes

- `PacienteService::syncPendingPacientes()`: Sincroniza pacientes creados offline
- `PacienteService::exportPacientes()` / `importPacientes()`: Backup JSON

### 3. Agendas y Citas

- **Agendas** (calendario médico) se crean offline → `sync_status='pending'`
- **Citas** (asignación paciente) dependen de agendas
- Sincronización: `OfflineService::syncPendingAgendas()` (línea ~4506)

### 4. Historias Clínicas

- JSON exports en `CxampphtdocsFrontSidisstorageapp/export_*.json`
- **Firmas QR**: Ruta pública `/firma-movil/{token}` para firmar desde móvil sin autenticación

## Patrones de Código

### Services - Inyección de Dependencias

```php
class MiService {
    protected $apiService;
    protected $offlineService;
    
    public function __construct(ApiService $api, OfflineService $offline) {
        $this->apiService = $api;
        $this->offlineService = $offline;
    }
}
```

### Logging Estructurado

```php
Log::info('✅ Operación exitosa', [
    'usuario' => $user->login,
    'sede_id' => $sedeId,
    'offline' => !$this->apiService->isOnline()
]);

Log::error('❌ Error crítico', [
    'error' => $e->getMessage(),
    'file' => $e->getFile(),
    'line' => $e->getLine()
]);
```

### Respuestas Consistentes

```php
return response()->json([
    'success' => true,
    'data' => $resultado,
    'message' => 'Operación exitosa'
]);

// O para errores
return response()->json([
    'success' => false,
    'error' => 'Descripción del error'
], 400);
```

## Workflows Críticos

### Desarrollo Local (XAMPP)

```bash
# Instalar dependencias
composer install
npm install

# Copiar .env
cp .env.example .env
php artisan key:generate

# Assets (Vite)
npm run dev          # Desarrollo con hot-reload
npm run build        # Producción

# Servidor (XAMPP ya provee Apache+PHP)
# Acceder: http://localhost/Sidis-Front/public
```

### Debugging SQLite

```bash
# Ver agendas con errores
php clean_error_agendas.php

# Debuggear sincronización
php debug_sync_error.php

# Acceder SQLite directamente
sqlite3 storage/app/offline/offline_data.sqlite
.tables
SELECT * FROM agendas WHERE sync_status='error';
```

### Testing

PHPUnit configurado ([phpunit.xml](phpunit.xml)) pero sin tests implementados actualmente.

## Convenciones del Proyecto

1. **NO usar DB facade sin especificar conexión** - Laravel default no se usa
   ```php
   DB::connection('offline')->table('agendas')->where(...) // ✅
   DB::table('agendas')->where(...)                        // ❌ Wrong DB
   ```

2. **Verificar modo offline antes de mostrar features**
   ```php
   $isOnline = $this->apiService->isOnline(); // Cache 30s
   return view('dashboard', compact('isOnline'));
   ```

3. **UUIDs para sincronización** - Todas las entidades usan UUID v4, no IDs autoincrementales

4. **Sesión sin base de datos** - `SESSION_DRIVER=file` (ver [config/session.php](config/session.php))

5. **Middleware personalizado** - `custom.auth` y `custom.guest` (no usar `auth` de Laravel)

## Decisiones Arquitectónicas

### ¿Por qué SQLite en runtime?

- **Portabilidad**: Copiar `storage/app/offline/` = copiar toda la DB offline
- **Sin configuración**: No requiere MySQL en cada máquina
- **Inicialización dinámica**: [OfflineService.php línea ~35-66](app/Services/OfflineService.php#L35-L66) crea tablas si no existen

### ¿Por qué no Eloquent?

- Los datos maestros vienen de API externa con estructura fija
- SQLite es cache temporal, no fuente de verdad
- Simplicidad: Query Builder es suficiente para operaciones CRUD simples

### API Health Check

```php
// Cache agresivo (30s) para evitar latencia
Cache::remember('api_online_status', 30, function () {
    $response = Http::timeout(5)->get($this->baseUrl . '/health');
    return $response->successful() && 
           $response->json()['data']['status'] === 'ok';
});
```

## Trampas Comunes

1. **SQLite no existe al inicio** - `OfflineService::ensureSQLiteExists()` se llama en constructor
2. **Sync status no se actualiza** - Siempre actualizar `sync_status` después de sincronizar
3. **Cache de `isOnline()` desactualizado** - Usar `ApiService::checkConnection()` para forzar recheck
4. **Rutas públicas después de middleware** - Ver [routes/web.php línea 19-42](routes/web.php#L19-L42) - rutas públicas DEBEN ir antes del middleware group

## Referencias Clave

- **Estructura de respuesta API**: Ver `ApiService::get()`, `post()`, etc.
- **Datos maestros**: Departamentos, municipios, tipos_documento, etc. en `/master-data/all`
- **Frontend**: Blade templates con JS vanilla + axios (sin framework JS)
- **QR Generation**: `simplesoftwareio/simple-qrcode` para códigos QR de firmas
