<?php
// app/Services/OfflineService.php (CORREGIDO PARA COMPATIBILIDAD)
namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OfflineService
{
    protected $storagePath;
    protected $offlineConnection = 'offline';
    protected $sqlitePath;
    protected $pdo;

    public function __construct()
    {
        $this->storagePath = config('api.offline.storage_path', storage_path('app/offline'));
        $this->ensureStorageExists();
        
        // ✅ SOLO inicializar SQLite si no hay error
        try {
            $this->ensureSQLiteExists();
        } catch (\Exception $e) {
            Log::warning('⚠️ SQLite no disponible, usando archivos JSON', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * ✅ CORREGIDO: Asegurar que existe la base de datos SQLite
     */
    public function ensureSQLiteExists(): void
    {
        try {
            $dbPath = storage_path('app/offline/offline_data.sqlite');
            
            if (!file_exists($dbPath)) {
                // Crear archivo SQLite vacío
                touch($dbPath);
                Log::info('✅ Archivo SQLite creado', ['path' => $dbPath]);
            }
            
            // Configurar conexión dinámicamente
            config(['database.connections.offline' => [
                'driver' => 'sqlite',
                'database' => $dbPath,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]]);
            
            // Probar la conexión
            DB::connection('offline')->getPdo();
            
            // Crear tablas si no existen
            $this->createTablesIfNotExist();
            
        } catch (\Exception $e) {
            Log::error('❌ Error configurando SQLite offline', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    /**
     * ✅ CORREGIDO: Verificar si SQLite está disponible
     */
    public function isSQLiteAvailable(): bool
    {
        try {
            DB::connection('offline')->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * ✅ OBTENER CONEXIÓN A LA BASE DE DATOS OFFLINE (SQLite)
     */
    public function getDbConnection()
    {
        return DB::connection('offline');
    }

    /**
     * ✅ NUEVO: Crear tablas SQLite dinámicamente
     */
  private function createTablesIfNotExist(): void
{
    try {
        // Verificar si las tablas ya existen
        $tables = DB::connection('offline')->select("SELECT name FROM sqlite_master WHERE type='table'");
        $existingTables = array_column($tables, 'name');
        
        if (in_array('departamentos', $existingTables)) {
            Log::info('✅ Tablas SQLite ya existen');
            
            // ✅ VERIFICAR Y CREAR TABLAS DE AGENDAS Y CITAS SI NO EXISTEN
            if (!in_array('agendas', $existingTables)) {
                $this->createAgendasTable();
                Log::info('✅ Tabla agendas creada');
            }
            
            if (!in_array('citas', $existingTables)) {
                $this->createCitasTable();
                Log::info('✅ Tabla citas creada');
            }
            
            // ✅ AGREGAR VERIFICACIÓN DE PROCESOS
            if (!in_array('procesos', $existingTables)) {
                $this->createProcesosTable();
                Log::info('✅ Tabla procesos creada');
            }

             if (!in_array('usuarios', $existingTables)) {
                $this->createUsuariosTable();
                Log::info('✅ Tabla usuarios creada');
            }
              // ✅ NUEVA: VERIFICAR Y CREAR TABLA CUPS
            if (!in_array('cups', $existingTables)) {
                $this->createCupsTable();
                Log::info('✅ Tabla cups creada');
            }
            if (!in_array('pacientes', $existingTables)) {
                $this->createPacientesTable();
                Log::info('✅ Tabla pacientes creada');
            }
            if (!in_array('cups_contratados', $existingTables)) {
                $this->createCupsContratadosTable();
                Log::info('✅ Tabla cups_contratados creada');
            }

            if (!in_array('categorias_cups', $existingTables)) {
                $this->createCategoriasCupsTable();
                Log::info('✅ Tabla categorias_cups creada');
            }

            if (!in_array('contratos', $existingTables)) {
                $this->createContratosTable();
                Log::info('✅ Tabla contratos creada');
            }
                if (!in_array('medicamentos', $existingTables)) {
                    $this->createMedicamentosTable();
                    Log::info('✅ Tabla medicamentos creada');
                }
                if (!in_array('diagnosticos', $existingTables)) {
                    $this->createDiagnosticosTable();
                    Log::info('✅ Tabla diagnosticos creada');
                }
                if (!in_array('remisiones', $existingTables)) {
                    $this->createRemisionesTable();
                    Log::info('✅ Tabla remisiones creada');
                }

                if (!in_array('historias_clinicas', $existingTables)) {
                    $this->createHistoriasClinicasTable();
                    Log::info('✅ Tabla historias_clinicas creada');
                }




            
            return;
        }
        
        Log::info('🔧 Creando tablas SQLite offline...');
        
        // Crear todas las tablas existentes
        $this->createDepartamentosTable();
        $this->createMunicipiosTable();
        $this->createEmpresasTable();
        $this->createRegimenesTable();
        $this->createTiposAfiliacionTable();
        $this->createZonasResidencialesTable();
        $this->createRazasTable();
        $this->createEscolaridadesTable();
        $this->createTiposParentescoTable();
        $this->createTiposDocumentoTable();
        $this->createOcupacionesTable();
        $this->createNovedadesTable();
        $this->createAuxiliaresTable();
        $this->createBrigadasTable();
        $this->createProcesosTable();
        $this->createUsuariosTable();
        $this->createAgendasTable();
        $this->createCitasTable();
        $this->createCupsTable();
        $this->createPacientesTable();
        $this->createCupsContratadosTable();
        $this->createCategoriasCupsTable(); 
        $this->createContratosTable();
        $this->createMedicamentosTable();
        $this->createDiagnosticosTable();
        $this->createRemisionesTable();
        $this->createHistoriasClinicasTable();   
        $this->createSyncStatusTable();
        
        Log::info('✅ Todas las tablas SQLite creadas exitosamente');
        
    } catch (\Exception $e) {
        Log::error('❌ Error creando tablas SQLite', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        throw $e;
    }
}

/**
 * ✅ Asegurar que la base de datos SQLite existe con todas las tablas
 */
public function ensureDatabaseExists(): void
{
    try {
        Log::info('🔄 Verificando existencia de base de datos SQLite');
        
        if (!$this->isSQLiteAvailable()) {
            Log::warning('⚠️ SQLite no está disponible');
            return;
        }
        
        // Llamar al método privado que crea las tablas
        $this->createTablesIfNotExist();
        
        Log::info('✅ Base de datos SQLite verificada/creada exitosamente');
        
    } catch (\Exception $e) {
        Log::error('❌ Error verificando base de datos SQLite', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}



        public function createAgendasTable(): void
{
      DB::connection('offline')->statement('
        CREATE TABLE IF NOT EXISTS agendas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            sede_id INTEGER NOT NULL,
            modalidad TEXT NOT NULL,
            fecha DATE NOT NULL,
            consultorio TEXT,
            hora_inicio TIME NOT NULL,
            hora_fin TIME NOT NULL,
            intervalo TEXT,
            etiqueta TEXT,
            estado TEXT DEFAULT "ACTIVO",
            proceso_id TEXT NULL,
            proceso_uuid TEXT NULL, 
            usuario_id INTEGER,
            brigada_id TEXT NULL,
            brigada_uuid TEXT NULL,
            usuario_medico_id TEXT NULL,
            usuario_medico_uuid TEXT NULL,
            medico_uuid TEXT NULL,
            cupos_disponibles INTEGER DEFAULT 0,
            sync_status TEXT DEFAULT "synced",
            error_message TEXT NULL,
            synced_at DATETIME NULL,
            operation_type TEXT DEFAULT "create",  
            original_data TEXT NULL,               
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL
        )
    ');
    
    // ✅ AGREGAR COLUMNAS SI NO EXISTEN (para bases de datos existentes)
    try {
        DB::connection('offline')->statement('ALTER TABLE agendas ADD COLUMN usuario_medico_uuid TEXT NULL');
    } catch (\Exception $e) {
        // Columna ya existe
    }
    
    try {
        DB::connection('offline')->statement('ALTER TABLE agendas ADD COLUMN medico_uuid TEXT NULL');
    } catch (\Exception $e) {
        // Columna ya existe
    }
}


private function createCitasTable(): void
{
    DB::connection('offline')->statement('
        CREATE TABLE IF NOT EXISTS citas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            sede_id INTEGER NOT NULL,
            fecha DATE NOT NULL,
            fecha_inicio DATETIME NOT NULL,
            fecha_final DATETIME NOT NULL,
            fecha_deseada DATE,
            motivo TEXT,
            nota TEXT,
            estado TEXT NOT NULL,
            patologia TEXT,
            paciente_id INTEGER,
            paciente_uuid TEXT,
            agenda_id INTEGER,
            agenda_uuid TEXT,
            cups_contratado_id TEXT,
            cups_contratado_uuid TEXT,
            usuario_creo_cita_id INTEGER,
            sync_status TEXT DEFAULT "synced",
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL
        )
    ');
}

    // ✅ MÉTODOS DE CREACIÓN DE TABLAS (SIN CAMBIOS)
    private function createDepartamentosTable(): void
    {
        DB::connection('offline')->statement('
            CREATE TABLE IF NOT EXISTS departamentos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                codigo TEXT,
                nombre TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    private function createMunicipiosTable(): void
    {
        DB::connection('offline')->statement('
            CREATE TABLE IF NOT EXISTS municipios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                codigo TEXT,
                nombre TEXT NOT NULL,
                departamento_id INTEGER,
                departamento_uuid TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    private function createEmpresasTable(): void
    {
        DB::connection('offline')->statement('
            CREATE TABLE IF NOT EXISTS empresas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                nombre TEXT NOT NULL,
                nit TEXT,
                codigo_eapb TEXT,
                telefono TEXT,
                direccion TEXT,
                estado TEXT DEFAULT "ACTIVO",
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    private function createRegimenesTable(): void
    {
        DB::connection('offline')->statement('
            CREATE TABLE IF NOT EXISTS regimenes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                nombre TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    private function createTiposAfiliacionTable(): void
    {
        DB::connection('offline')->statement('
            CREATE TABLE IF NOT EXISTS tipos_afiliacion (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                nombre TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    private function createZonasResidencialesTable(): void
    {
        DB::connection('offline')->statement('
            CREATE TABLE IF NOT EXISTS zonas_residenciales (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                nombre TEXT NOT NULL,
                abreviacion TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    private function createRazasTable(): void
    {
        DB::connection('offline')->statement('
            CREATE TABLE IF NOT EXISTS razas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                nombre TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    private function createEscolaridadesTable(): void
    {
        DB::connection('offline')->statement('
            CREATE TABLE IF NOT EXISTS escolaridades (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                nombre TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    private function createTiposParentescoTable(): void
    {
        DB::connection('offline')->statement('
            CREATE TABLE IF NOT EXISTS tipos_parentesco (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                nombre TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    private function createTiposDocumentoTable(): void
    {
        DB::connection('offline')->statement('
            CREATE TABLE IF NOT EXISTS tipos_documento (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                abreviacion TEXT,
                nombre TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    private function createOcupacionesTable(): void
    {
        DB::connection('offline')->statement('
            CREATE TABLE IF NOT EXISTS ocupaciones (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                codigo TEXT,
                nombre TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    private function createNovedadesTable(): void
    {
        DB::connection('offline')->statement('
            CREATE TABLE IF NOT EXISTS novedades (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                tipo_novedad TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    private function createAuxiliaresTable(): void
    {
        DB::connection('offline')->statement('
            CREATE TABLE IF NOT EXISTS auxiliares (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                nombre TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    private function createBrigadasTable(): void
    {
        DB::connection('offline')->statement('
            CREATE TABLE IF NOT EXISTS brigadas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                nombre TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

     private function createProcesosTable(): void
    {
        DB::connection('offline')->statement('
            CREATE TABLE IF NOT EXISTS procesos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                nombre TEXT NOT NULL,
                n_cups TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
        
        // ✅ TABLA proceso_cups (relación proceso -> cups)
        DB::connection('offline')->statement('
            CREATE TABLE IF NOT EXISTS proceso_cups (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                proceso_uuid TEXT NOT NULL,
                cups_uuid TEXT NOT NULL,
                cups_codigo TEXT,
                cups_nombre TEXT,
                orden INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(proceso_uuid, cups_uuid)
            )
        ');
        
        DB::connection('offline')->statement('
            CREATE INDEX IF NOT EXISTS idx_proceso_cups_proceso ON proceso_cups(proceso_uuid)
        ');
        
        DB::connection('offline')->statement('
            CREATE INDEX IF NOT EXISTS idx_proceso_cups_cups ON proceso_cups(cups_uuid)
        ');
    }



    private function createSyncStatusTable(): void
    {
        DB::connection('offline')->statement('
            CREATE TABLE IF NOT EXISTS sync_status (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                table_name TEXT UNIQUE NOT NULL,
                last_sync DATETIME,
                records_count INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    private function createUsuariosTable(): void
{
 DB::connection('offline')->statement('
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            documento TEXT,
            nombre TEXT NOT NULL,
            apellido TEXT,
            nombre_completo TEXT,
            login TEXT,
            especialidad_id INTEGER,
            especialidad_uuid TEXT,
            especialidad_nombre TEXT,
            sede_id INTEGER,
            sede_nombre TEXT,
            estado TEXT DEFAULT "ACTIVO",
            firma TEXT NULL,
            tiene_firma INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');
}
private function createCupsTable(): void
{
    DB::connection('offline')->statement('
        CREATE TABLE IF NOT EXISTS cups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            origen TEXT,
            nombre TEXT NOT NULL,
            codigo TEXT UNIQUE NOT NULL,
            estado TEXT DEFAULT "ACTIVO",
            categoria TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');
    
    // Crear índices para búsqueda rápida
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_cups_codigo ON cups(codigo)
    ');
    
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_cups_nombre ON cups(nombre)
    ');
    
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_cups_estado ON cups(estado)
    ');
        // ✅ CREAR ÍNDICES
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_usuarios_uuid ON usuarios(uuid)
    ');
    
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_usuarios_login ON usuarios(login)
    ');
}
// En OfflineService.php - REEMPLAZAR createPacientesTable
private function createPacientesTable(): void
{
    DB::connection('offline')->statement('
        CREATE TABLE IF NOT EXISTS pacientes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            sede_id INTEGER NOT NULL,
            
            -- Datos básicos
            primer_nombre TEXT NOT NULL,
            segundo_nombre TEXT,
            primer_apellido TEXT NOT NULL,
            segundo_apellido TEXT,
            nombre_completo TEXT,
            documento TEXT NOT NULL,
            fecha_nacimiento DATE,
            sexo TEXT DEFAULT "M",
            telefono TEXT,
            direccion TEXT,
            correo TEXT,
            estado_civil TEXT,
            observacion TEXT,
            registro TEXT,
            estado TEXT DEFAULT "ACTIVO",
            
            -- IDs de relaciones
            tipo_documento_id TEXT,
            empresa_id TEXT,
            regimen_id TEXT,
            tipo_afiliacion_id TEXT,
            zona_residencia_id TEXT,
            depto_nacimiento_id TEXT,
            depto_residencia_id TEXT,
            municipio_nacimiento_id TEXT,
            municipio_residencia_id TEXT,
            raza_id TEXT,
            escolaridad_id TEXT,
            parentesco_id TEXT,
            ocupacion_id TEXT,
            novedad_id TEXT,
            auxiliar_id TEXT,
            brigada_id TEXT,
            
            -- Nombres de relaciones para mostrar
            tipo_documento_nombre TEXT,
            tipo_documento_abreviacion TEXT,
            empresa_nombre TEXT,
            empresa_codigo_eapb TEXT,
            regimen_nombre TEXT,
            tipo_afiliacion_nombre TEXT,
            zona_residencia_nombre TEXT,
            zona_residencia_abreviacion TEXT,
            depto_nacimiento_nombre TEXT,
            depto_residencia_nombre TEXT,
            municipio_nacimiento_nombre TEXT,
            municipio_residencia_nombre TEXT,
            raza_nombre TEXT,
            escolaridad_nombre TEXT,
            parentesco_nombre TEXT,
            ocupacion_nombre TEXT,
            ocupacion_codigo TEXT,
            novedad_tipo TEXT,
            auxiliar_nombre TEXT,
            brigada_nombre TEXT,
            
            -- Datos de acudiente
            nombre_acudiente TEXT,
            parentesco_acudiente TEXT,
            telefono_acudiente TEXT,
            direccion_acudiente TEXT,
            
            -- Datos de acompañante
            acompanante_nombre TEXT,
            acompanante_telefono TEXT,
            
            -- Control
            fecha_registro DATE,
            fecha_actualizacion DATETIME,
            sync_status TEXT DEFAULT "synced",
            stored_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL
        )
    ');
    
    // Crear índices
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_pacientes_documento ON pacientes(documento)
    ');
    
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_pacientes_sede ON pacientes(sede_id)
    ');
    
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_pacientes_uuid ON pacientes(uuid)
    ');
}


private function createCupsContratadosTable(): void
{
    DB::connection('offline')->statement('
        CREATE TABLE IF NOT EXISTS cups_contratados (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            contrato_id INTEGER,
            contrato_uuid TEXT,
            categoria_cups_id INTEGER,
            cups_id INTEGER,
            cups_uuid TEXT,
            cups_codigo TEXT,
            cups_nombre TEXT,
            tarifa TEXT,
            estado TEXT DEFAULT "ACTIVO",
            contrato_fecha_inicio DATE,
            contrato_fecha_fin DATE,
            contrato_estado TEXT,
            empresa_nombre TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');
    
    // Crear índices
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_cups_contratados_cups_uuid ON cups_contratados(cups_uuid)
    ');
    
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_cups_contratados_estado ON cups_contratados(estado)
    ');
    
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_cups_contratados_cups_codigo ON cups_contratados(cups_codigo)
    ');
}
/**
 * ✅ CREAR TABLA DE CONTRATOS COMPLETA
 */
private function createContratosTable(): void
{
    DB::connection('offline')->statement('
        CREATE TABLE IF NOT EXISTS contratos (
            id INTEGER PRIMARY KEY,
            uuid TEXT UNIQUE NOT NULL,
            empresa_id INTEGER,
            empresa_uuid TEXT,
            empresa_nombre TEXT,
            numero TEXT,
            descripcion TEXT,
            plan_beneficio TEXT,
            poliza TEXT,
            por_descuento TEXT,
            fecha_inicio DATE,
            fecha_fin DATE,
            valor TEXT,
            fecha_registro DATE,
            tipo TEXT,
            copago TEXT,
            estado TEXT DEFAULT "ACTIVO",
            created_at DATETIME,
            updated_at DATETIME,
            deleted_at DATETIME
        )
    ');
    
    // Crear índices
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_contratos_uuid ON contratos(uuid)
    ');
    
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_contratos_empresa_id ON contratos(empresa_id)
    ');
    
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_contratos_estado ON contratos(estado)
    ');
    
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_contratos_fechas ON contratos(fecha_inicio, fecha_fin)
    ');
}

    /**
     * ✅ CORREGIDO: Sincronizar todos los datos maestros desde la API
     */
    public function syncMasterDataFromApi(array $masterData): bool
    {
        try {
            Log::info('🔄 Iniciando sincronización de datos maestros offline');

            // ✅ INTENTAR SQLite PRIMERO, FALLBACK A JSON
            if ($this->isSQLiteAvailable()) {
                return $this->syncMasterDataToSQLite($masterData);
            } else {
                return $this->syncMasterDataToJSON($masterData);
            }

        } catch (\Exception $e) {
            Log::error('❌ Error sincronizando datos maestros offline', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // ✅ FALLBACK: Intentar con JSON
            try {
                return $this->syncMasterDataToJSON($masterData);
            } catch (\Exception $jsonError) {
                Log::error('❌ Error también con JSON fallback', [
                    'error' => $jsonError->getMessage()
                ]);
                return false;
            }
        }
    }
  private function syncUsuariosConEspecialidad(array $data): void
{
    try {
        Log::info('🔄 Sincronizando usuarios con especialidad', [
            'total' => count($data)
        ]);

        DB::connection('offline')->table('usuarios')->delete();
        
        $syncCount = 0;
        foreach ($data as $item) {
            // ✅ EXTRAER Y LIMPIAR FIRMA
            $firma = null;
            $tieneFirma = 0;
            
            if (!empty($item['firma'])) {
                $firma = $item['firma'];
                $tieneFirma = 1;
                
                // ✅ VALIDAR QUE LA FIRMA TENGA PREFIJO
                if (strpos($firma, 'data:image/') !== 0) {
                    $firma = 'data:image/png;base64,' . $firma;
                }
                
                Log::debug('✅ Firma procesada para usuario', [
                    'usuario_uuid' => $item['uuid'],
                    'login' => $item['login'] ?? 'N/A',
                    'tiene_firma' => $tieneFirma,
                    'longitud_firma' => strlen($firma),
                    'prefijo' => substr($firma, 0, 30)
                ]);
            } else {
                Log::debug('ℹ️ Usuario sin firma', [
                    'usuario_uuid' => $item['uuid'],
                    'login' => $item['login'] ?? 'N/A'
                ]);
            }

            DB::connection('offline')->table('usuarios')->insert([
                'uuid' => $item['uuid'],
                'documento' => $item['documento'] ?? null,
                'nombre' => $item['nombre'],
                'apellido' => $item['apellido'] ?? null,
                'nombre_completo' => $item['nombre_completo'],
                'login' => $item['login'] ?? null,
                'especialidad_id' => $item['especialidad']['id'] ?? null,
                'especialidad_uuid' => $item['especialidad']['uuid'] ?? null,
                'especialidad_nombre' => $item['especialidad']['nombre'] ?? null,
                'sede_id' => $item['sede']['id'] ?? null,
                'sede_nombre' => $item['sede']['nombre'] ?? null,
                'estado' => 'ACTIVO',
                'firma' => $firma, // ✅ FIRMA CON PREFIJO
                'tiene_firma' => $tieneFirma, // ✅ BOOLEANO
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $syncCount++;
        }
        
        $this->updateSyncStatus('usuarios', $syncCount);
        
        Log::info('✅ Usuarios sincronizados con firma', [
            'total_sincronizados' => $syncCount,
            'con_firma' => DB::connection('offline')->table('usuarios')
                ->where('tiene_firma', 1)->count()
        ]);
        
    } catch (\Exception $e) {
        Log::error('❌ Error sincronizando usuarios con especialidad', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}


    /**
     * ✅ NUEVO: Sincronizar a SQLite
     */
    private function syncMasterDataToSQLite(array $masterData): bool
    {
        $syncResults = [];

        // 1. Departamentos
        if (isset($masterData['departamentos'])) {
            $this->syncDepartamentos($masterData['departamentos']);
            $syncResults['departamentos'] = count($masterData['departamentos']);
        }

        // 2. Municipios (extraer de departamentos)
        if (isset($masterData['departamentos'])) {
            $municipios = [];
            foreach ($masterData['departamentos'] as $depto) {
                if (isset($depto['municipios'])) {
                    foreach ($depto['municipios'] as $municipio) {
                        $municipios[] = array_merge($municipio, [
                            'departamento_id' => $depto['id'] ?? 0,
                            'departamento_uuid' => $depto['uuid']
                        ]);
                    }
                }
            }
            if (!empty($municipios)) {
                $this->syncMunicipios($municipios);
                $syncResults['municipios'] = count($municipios);
            }
        }

        // Continuar con el resto de datos...
        if (isset($masterData['empresas'])) {
            $this->syncEmpresas($masterData['empresas']);
            $syncResults['empresas'] = count($masterData['empresas']);
        }

        if (isset($masterData['regimenes'])) {
            $this->syncRegimenes($masterData['regimenes']);
            $syncResults['regimenes'] = count($masterData['regimenes']);
        }

        if (isset($masterData['tipos_afiliacion'])) {
            $this->syncTiposAfiliacion($masterData['tipos_afiliacion']);
            $syncResults['tipos_afiliacion'] = count($masterData['tipos_afiliacion']);
        }

        if (isset($masterData['zonas_residenciales'])) {
            $this->syncZonasResidenciales($masterData['zonas_residenciales']);
            $syncResults['zonas_residenciales'] = count($masterData['zonas_residenciales']);
        }

        if (isset($masterData['razas'])) {
            $this->syncRazas($masterData['razas']);
            $syncResults['razas'] = count($masterData['razas']);
        }

        if (isset($masterData['escolaridades'])) {
            $this->syncEscolaridades($masterData['escolaridades']);
            $syncResults['escolaridades'] = count($masterData['escolaridades']);
        }

        if (isset($masterData['tipos_parentesco'])) {
            $this->syncTiposParentesco($masterData['tipos_parentesco']);
            $syncResults['tipos_parentesco'] = count($masterData['tipos_parentesco']);
        }

        if (isset($masterData['tipos_documento'])) {
            $this->syncTiposDocumento($masterData['tipos_documento']);
            $syncResults['tipos_documento'] = count($masterData['tipos_documento']);
        }

        if (isset($masterData['ocupaciones'])) {
            $this->syncOcupaciones($masterData['ocupaciones']);
            $syncResults['ocupaciones'] = count($masterData['ocupaciones']);
        }

        if (isset($masterData['novedades'])) {
            $this->syncNovedades($masterData['novedades']);
            $syncResults['novedades'] = count($masterData['novedades']);
        }

        if (isset($masterData['auxiliares'])) {
            $this->syncAuxiliares($masterData['auxiliares']);
            $syncResults['auxiliares'] = count($masterData['auxiliares']);
        }

        if (isset($masterData['brigadas'])) {
            $this->syncBrigadas($masterData['brigadas']);
            $syncResults['brigadas'] = count($masterData['brigadas']);
        }

        if (isset($masterData['procesos'])) {
            $this->syncProcesos($masterData['procesos']);
            $syncResults['procesos'] = count($masterData['procesos']);
            
            // ✅ SINCRONIZAR CUPS DE CADA PROCESO
            $totalProcesoCups = 0;
            foreach ($masterData['procesos'] as $proceso) {
                if (!empty($proceso['cups']) && is_array($proceso['cups'])) {
                    $this->syncProcesoCups($proceso['uuid'], $proceso['cups']);
                    $totalProcesoCups += count($proceso['cups']);
                }
            }
            if ($totalProcesoCups > 0) {
                $syncResults['proceso_cups'] = $totalProcesoCups;
            }
        }

        if (isset($masterData['categorias_cups'])) {
            $this->syncCategoriasCups($masterData['categorias_cups']);
            $syncResults['categorias_cups'] = count($masterData['categorias_cups']);
        }

         if (isset($masterData['usuarios_con_especialidad'])) {
        $this->syncUsuariosConEspecialidad($masterData['usuarios_con_especialidad']);
        $syncResults['usuarios_con_especialidad'] = count($masterData['usuarios_con_especialidad']);
        }

        Log::info('✅ Sincronización SQLite completada', [
            'results' => $syncResults,
            'total_tables' => count($syncResults)
        ]);

        return true;
    }

    /**
     * ✅ NUEVO: Sincronizar a JSON (FALLBACK)
     */
    private function syncMasterDataToJSON(array $masterData): bool
    {
        // Guardar todo el array de datos maestros en un archivo JSON
        $this->storeData('master_data.json', $masterData);
        
        // Guardar también información de sincronización
        $syncInfo = [
            'last_sync' => now()->toISOString(),
            'tables_synced' => array_keys($masterData),
            'total_records' => array_sum(array_map('count', array_filter($masterData, 'is_array')))
        ];
        
        $this->storeData('sync_info.json', $syncInfo);
        
        Log::info('✅ Sincronización JSON completada', [
            'tables' => count($masterData),
            'total_records' => $syncInfo['total_records']
        ]);

        return true;
    }

    // ✅ MÉTODOS DE SINCRONIZACIÓN INDIVIDUALES PARA SQLITE (SIN CAMBIOS)
    private function syncDepartamentos(array $data): void
    {
        DB::connection('offline')->table('departamentos')->delete();
        foreach ($data as $item) {
            DB::connection('offline')->table('departamentos')->insert([
                'uuid' => $item['uuid'],
                'codigo' => $item['codigo'] ?? null,
                'nombre' => $item['nombre'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $this->updateSyncStatus('departamentos', count($data));
    }

    private function syncMunicipios(array $data): void
    {
        DB::connection('offline')->table('municipios')->delete();
        foreach ($data as $item) {
            DB::connection('offline')->table('municipios')->insert([
                'uuid' => $item['uuid'],
                'codigo' => $item['codigo'] ?? null,
                'nombre' => $item['nombre'],
                'departamento_id' => $item['departamento_id'] ?? null,
                'departamento_uuid' => $item['departamento_uuid'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $this->updateSyncStatus('municipios', count($data));
    }

    private function syncEmpresas(array $data): void
    {
        DB::connection('offline')->table('empresas')->delete();
        foreach ($data as $item) {
            DB::connection('offline')->table('empresas')->insert([
                'uuid' => $item['uuid'],
                'nombre' => $item['nombre'],
                'nit' => $item['nit'] ?? null,
                'codigo_eapb' => $item['codigo_eapb'] ?? null,
                'telefono' => $item['telefono'] ?? null,
                'direccion' => $item['direccion'] ?? null,
                'estado' => $item['estado'] ?? 'ACTIVO',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $this->updateSyncStatus('empresas', count($data));
    }

    private function syncRegimenes(array $data): void
    {
        DB::connection('offline')->table('regimenes')->delete();
        foreach ($data as $item) {
            DB::connection('offline')->table('regimenes')->insert([
                'uuid' => $item['uuid'],
                'nombre' => $item['nombre'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $this->updateSyncStatus('regimenes', count($data));
    }

    private function syncTiposAfiliacion(array $data): void
    {
        DB::connection('offline')->table('tipos_afiliacion')->delete();
        foreach ($data as $item) {
            DB::connection('offline')->table('tipos_afiliacion')->insert([
                'uuid' => $item['uuid'],
                'nombre' => $item['nombre'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $this->updateSyncStatus('tipos_afiliacion', count($data));
    }

    private function syncZonasResidenciales(array $data): void
    {
        DB::connection('offline')->table('zonas_residenciales')->delete();
        foreach ($data as $item) {
            DB::connection('offline')->table('zonas_residenciales')->insert([
                'uuid' => $item['uuid'],
                'nombre' => $item['nombre'],
                'abreviacion' => $item['abreviacion'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $this->updateSyncStatus('zonas_residenciales', count($data));
    }

    private function syncRazas(array $data): void
    {
        DB::connection('offline')->table('razas')->delete();
        foreach ($data as $item) {
            DB::connection('offline')->table('razas')->insert([
                'uuid' => $item['uuid'],
                'nombre' => $item['nombre'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $this->updateSyncStatus('razas', count($data));
    }

    private function syncEscolaridades(array $data): void
    {
        DB::connection('offline')->table('escolaridades')->delete();
        foreach ($data as $item) {
            DB::connection('offline')->table('escolaridades')->insert([
                'uuid' => $item['uuid'],
                'nombre' => $item['nombre'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $this->updateSyncStatus('escolaridades', count($data));
    }

    private function syncTiposParentesco(array $data): void
    {
        DB::connection('offline')->table('tipos_parentesco')->delete();
        foreach ($data as $item) {
            DB::connection('offline')->table('tipos_parentesco')->insert([
                'uuid' => $item['uuid'],
                'nombre' => $item['nombre'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $this->updateSyncStatus('tipos_parentesco', count($data));
    }

    private function syncTiposDocumento(array $data): void
    {
        DB::connection('offline')->table('tipos_documento')->delete();
        foreach ($data as $item) {
            DB::connection('offline')->table('tipos_documento')->insert([
                'uuid' => $item['uuid'],
                'abreviacion' => $item['abreviacion'] ?? null,
                'nombre' => $item['nombre'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $this->updateSyncStatus('tipos_documento', count($data));
    }

    private function syncOcupaciones(array $data): void
    {
        DB::connection('offline')->table('ocupaciones')->delete();
        foreach ($data as $item) {
            DB::connection('offline')->table('ocupaciones')->insert([
                'uuid' => $item['uuid'],
                'codigo' => $item['codigo'] ?? null,
                'nombre' => $item['nombre'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $this->updateSyncStatus('ocupaciones', count($data));
    }

    private function syncNovedades(array $data): void
    {
        DB::connection('offline')->table('novedades')->delete();
        foreach ($data as $item) {
            DB::connection('offline')->table('novedades')->insert([
                'uuid' => $item['uuid'],
                'tipo_novedad' => $item['tipo_novedad'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $this->updateSyncStatus('novedades', count($data));
    }

    private function syncAuxiliares(array $data): void
    {
        DB::connection('offline')->table('auxiliares')->delete();
        foreach ($data as $item) {
            DB::connection('offline')->table('auxiliares')->insert([
                'uuid' => $item['uuid'],
                'nombre' => $item['nombre'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $this->updateSyncStatus('auxiliares', count($data));
    }

    private function syncBrigadas(array $data): void
    {
        DB::connection('offline')->table('brigadas')->delete();
        foreach ($data as $item) {
            DB::connection('offline')->table('brigadas')->insert([
                'uuid' => $item['uuid'],
                'nombre' => $item['nombre'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $this->updateSyncStatus('brigadas', count($data));
    }

    private function syncProcesos(array $data): void
    {
        DB::connection('offline')->table('procesos')->delete();
        foreach ($data as $item) {
            DB::connection('offline')->table('procesos')->insert([
                'uuid' => $item['uuid'],
                'nombre' => $item['nombre'],
                'n_cups' => $item['n_cups'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $this->updateSyncStatus('procesos', count($data));
    }

    /**
     * ✅ SINCRONIZAR CUPS DE UN PROCESO
     */
    private function syncProcesoCups(string $procesoUuid, array $cups): void
    {
        // Eliminar CUPS previos de este proceso
        DB::connection('offline')->table('proceso_cups')
            ->where('proceso_uuid', $procesoUuid)
            ->delete();
        
        // Insertar CUPS del proceso
        foreach ($cups as $index => $cup) {
            DB::connection('offline')->table('proceso_cups')->insert([
                'proceso_uuid' => $procesoUuid,
                'cups_uuid' => $cup['uuid'] ?? $cup['id'],
                'cups_codigo' => $cup['codigo'] ?? '',
                'cups_nombre' => $cup['nombre'] ?? '',
                'orden' => $index,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        Log::info('✅ CUPS del proceso sincronizados', [
            'proceso_uuid' => $procesoUuid,
            'cups_count' => count($cups)
        ]);
    }

    

    private function updateSyncStatus(string $tableName, int $count): void
    {
        if (!$this->isSQLiteAvailable()) return;
        
        try {
            DB::connection('offline')->table('sync_status')->updateOrInsert(
                ['table_name' => $tableName],
                [
                    'last_sync' => now(),
                    'records_count' => $count,
                    'updated_at' => now()
                ]
            );
        } catch (\Exception $e) {
            Log::warning('⚠️ No se pudo actualizar sync_status', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * ✅ CORREGIDO: Obtener datos maestros desde SQLite o JSON
     */
    public function getMasterDataOffline(): array
    {
        try {
            // ✅ INTENTAR SQLite PRIMERO
            if ($this->isSQLiteAvailable()) {
                return $this->getMasterDataFromSQLite();
            } else {
                return $this->getMasterDataFromJSON();
            }
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo datos maestros offline', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // ✅ FALLBACK: Intentar JSON
            try {
                return $this->getMasterDataFromJSON();
            } catch (\Exception $jsonError) {
                Log::error('❌ Error también con JSON fallback', [
                    'error' => $jsonError->getMessage()
                ]);
                return $this->getDefaultMasterData();
            }
        }
    }

    /**
     * ✅ NUEVO: Obtener datos desde SQLite
     */
    private function getMasterDataFromSQLite(): array
    {
        $masterData = [];

        // 1. Departamentos con municipios
        $departamentos = DB::connection('offline')->table('departamentos')->get();
        $masterData['departamentos'] = $departamentos->map(function ($depto) {
            $municipios = DB::connection('offline')
                ->table('municipios')
                ->where('departamento_uuid', $depto->uuid)
                ->get();

            return [
                'uuid' => $depto->uuid,
                'codigo' => $depto->codigo,
                'nombre' => $depto->nombre,
                'municipios' => $municipios->map(function ($municipio) {
                    return [
                        'uuid' => $municipio->uuid,
                        'codigo' => $municipio->codigo,
                        'nombre' => $municipio->nombre
                    ];
                })->toArray()
            ];
        })->toArray();

        // 2. Empresas
        $empresas = DB::connection('offline')->table('empresas')->get();
        $masterData['empresas'] = $empresas->map(function ($empresa) {
            return [
                'uuid' => $empresa->uuid,
                'nombre' => $empresa->nombre,
                'nit' => $empresa->nit,
                'codigo_eapb' => $empresa->codigo_eapb,
                'telefono' => $empresa->telefono,
                'direccion' => $empresa->direccion
            ];
        })->toArray();

        // 3. Regímenes
        $regimenes = DB::connection('offline')->table('regimenes')->get();
        $masterData['regimenes'] = $regimenes->map(function ($regimen) {
            return [
                'uuid' => $regimen->uuid,
                'nombre' => $regimen->nombre
            ];
        })->toArray();

        // 4. Tipos de Afiliación
        $tiposAfiliacion = DB::connection('offline')->table('tipos_afiliacion')->get();
        $masterData['tipos_afiliacion'] = $tiposAfiliacion->map(function ($tipo) {
            return [
                'uuid' => $tipo->uuid,
                'nombre' => $tipo->nombre
            ];
        })->toArray();

        // 5. Zonas Residenciales
        $zonasResidenciales = DB::connection('offline')->table('zonas_residenciales')->get();
        $masterData['zonas_residenciales'] = $zonasResidenciales->map(function ($zona) {
            return [
                'uuid' => $zona->uuid,
                'nombre' => $zona->nombre,
                'abreviacion' => $zona->abreviacion
            ];
        })->toArray();

        // 6. Razas
        $razas = DB::connection('offline')->table('razas')->get();
        $masterData['razas'] = $razas->map(function ($raza) {
            return [
                'uuid' => $raza->uuid,
                'nombre' => $raza->nombre
            ];
        })->toArray();

        // 7. Escolaridades
        $escolaridades = DB::connection('offline')->table('escolaridades')->get();
        $masterData['escolaridades'] = $escolaridades->map(function ($escolaridad) {
            return [
                'uuid' => $escolaridad->uuid,
                'nombre' => $escolaridad->nombre
            ];
        })->toArray();

        // 8. Tipos de Parentesco
        $tiposParentesco = DB::connection('offline')->table('tipos_parentesco')->get();
        $masterData['tipos_parentesco'] = $tiposParentesco->map(function ($tipo) {
            return [
                                'uuid' => $tipo->uuid,
                'nombre' => $tipo->nombre
            ];
        })->toArray();

        // 9. Tipos de Documento
        $tiposDocumento = DB::connection('offline')->table('tipos_documento')->get();
        $masterData['tipos_documento'] = $tiposDocumento->map(function ($tipo) {
            return [
                'uuid' => $tipo->uuid,
                'abreviacion' => $tipo->abreviacion,
                'nombre' => $tipo->nombre
            ];
        })->toArray();

        // 10. Ocupaciones
        $ocupaciones = DB::connection('offline')->table('ocupaciones')->get();
        $masterData['ocupaciones'] = $ocupaciones->map(function ($ocupacion) {
            return [
                'uuid' => $ocupacion->uuid,
                'codigo' => $ocupacion->codigo,
                'nombre' => $ocupacion->nombre
            ];
        })->toArray();

        // 11. Novedades
        $novedades = DB::connection('offline')->table('novedades')->get();
        $masterData['novedades'] = $novedades->map(function ($novedad) {
            return [
                'uuid' => $novedad->uuid,
                'tipo_novedad' => $novedad->tipo_novedad
            ];
        })->toArray();

        // 12. Auxiliares
        $auxiliares = DB::connection('offline')->table('auxiliares')->get();
        $masterData['auxiliares'] = $auxiliares->map(function ($auxiliar) {
            return [
                'uuid' => $auxiliar->uuid,
                'nombre' => $auxiliar->nombre
            ];
        })->toArray();

        // 13. Brigadas
        $brigadas = DB::connection('offline')->table('brigadas')->get();
        $masterData['brigadas'] = $brigadas->map(function ($brigada) {
            return [
                'uuid' => $brigada->uuid,
                'nombre' => $brigada->nombre
            ];
        })->toArray();

         $procesos = DB::connection('offline')->table('procesos')->get();
        $masterData['procesos'] = $procesos->map(function ($proceso) {
            return [
                'uuid' => $proceso->uuid,
                'nombre' => $proceso->nombre,
                'n_cups' => $proceso->n_cups,
            ];
        })->toArray();

         try {
    $usuarios = DB::connection('offline')->table('usuarios')->get();
    
    if ($usuarios->isEmpty()) {
        Log::warning('⚠️ No hay usuarios en SQLite offline');
        $masterData['usuarios_con_especialidad'] = [];
    } else {
        $masterData['usuarios_con_especialidad'] = $usuarios->map(function ($usuario) {
            return [
                'id' => $usuario->id,
                'uuid' => $usuario->uuid,
                'documento' => $usuario->documento ?? null,
                'nombre' => $usuario->nombre,
                'apellido' => $usuario->apellido ?? null,
                'nombre_completo' => $usuario->nombre_completo,
                'login' => $usuario->login ?? null,
                'email' => $usuario->email ?? null,
                'especialidad_id' => $usuario->especialidad_id ?? null,
                'especialidad' => [
                    'id' => $usuario->especialidad_id ?? null,
                    'uuid' => $usuario->especialidad_uuid ?? null,
                    'nombre' => $usuario->especialidad_nombre ?? 'Sin especialidad'
                ],
                'sede_id' => $usuario->sede_id ?? null,
                'sede' => [
                    'id' => $usuario->sede_id ?? null,
                    'nombre' => $usuario->sede_nombre ?? 'Sin sede'
                ],
                'estado' => $usuario->estado ?? 'ACTIVO',
                'firma' => $usuario->firma ?? null,
                'tiene_firma' => $usuario->tiene_firma ?? 0,
                'created_at' => $usuario->created_at ?? null,
                'updated_at' => $usuario->updated_at ?? null
            ];
        })->toArray();
        
        Log::info('✅ Usuarios cargados desde SQLite', [
            'total_usuarios' => count($masterData['usuarios_con_especialidad'])
        ]);
    }
} catch (\Exception $e) {
    Log::error('❌ Error obteniendo usuarios desde SQLite', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    $masterData['usuarios_con_especialidad'] = [];
}




        // Agregar datos estáticos
        $masterData['estados_civiles'] = [
            'SOLTERO' => 'Soltero(a)',
            'CASADO' => 'Casado(a)',
            'UNION_LIBRE' => 'Unión Libre',
            'DIVORCIADO' => 'Divorciado(a)',
            'VIUDO' => 'Viudo(a)'
        ];

        $masterData['sexos'] = [
            'M' => 'Masculino',
            'F' => 'Femenino'
        ];

        $masterData['estados'] = [
            'ACTIVO' => 'Activo',
            'INACTIVO' => 'Inactivo'
        ];

        Log::info('✅ Datos maestros obtenidos desde SQLite offline', [
            'tables_count' => count($masterData),
            'departamentos' => count($masterData['departamentos'] ?? []),
            'empresas' => count($masterData['empresas'] ?? [])
        ]);

        return $masterData;
    }

    /**
     * ✅ NUEVO: Obtener datos desde JSON (FALLBACK)
     */
    private function getMasterDataFromJSON(): array
    {
        $masterData = $this->getData('master_data.json', []);
        
        if (empty($masterData)) {
            Log::warning('⚠️ No hay datos maestros en JSON, usando datos por defecto');
            return $this->getDefaultMasterData();
        }

        // Agregar datos estáticos si no están presentes
        if (!isset($masterData['estados_civiles'])) {
            $masterData['estados_civiles'] = [
                'SOLTERO' => 'Soltero(a)',
                'CASADO' => 'Casado(a)',
                'UNION_LIBRE' => 'Unión Libre',
                'DIVORCIADO' => 'Divorciado(a)',
                'VIUDO' => 'Viudo(a)'
            ];
        }

        if (!isset($masterData['sexos'])) {
            $masterData['sexos'] = [
                'M' => 'Masculino',
                'F' => 'Femenino'
            ];
        }

        if (!isset($masterData['estados'])) {
            $masterData['estados'] = [
                'ACTIVO' => 'Activo',
                'INACTIVO' => 'Inactivo'
            ];
        }

        Log::info('✅ Datos maestros obtenidos desde JSON offline', [
            'tables_count' => count($masterData)
        ]);

        return $masterData;
    }

    /**
     * ✅ CORREGIDO: Verificar si hay datos maestros offline
     */
    public function hasMasterDataOffline(): bool
    {
        try {
            // ✅ VERIFICAR SQLite PRIMERO
            if ($this->isSQLiteAvailable()) {
                $departamentosCount = DB::connection('offline')->table('departamentos')->count();
                $empresasCount = DB::connection('offline')->table('empresas')->count();
                return $departamentosCount > 0 && $empresasCount > 0;
            } else {
                // ✅ VERIFICAR JSON
                $masterData = $this->getData('master_data.json', []);
                return !empty($masterData) && 
                       isset($masterData['departamentos']) && 
                       isset($masterData['empresas']) &&
                       count($masterData['departamentos']) > 0 &&
                       count($masterData['empresas']) > 0;
            }
        } catch (\Exception $e) {
            Log::error('❌ Error verificando datos maestros offline', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * ✅ CORREGIDO: Datos por defecto en caso de error
     */
    private function getDefaultMasterData(): array
    {
        return [
            'departamentos' => [
                ['uuid' => 'dept-cauca', 'codigo' => '19', 'nombre' => 'Cauca', 'municipios' => [
                    ['uuid' => 'mun-popayan', 'codigo' => '19001', 'nombre' => 'Popayán']
                ]]
            ],
            'empresas' => [
                ['uuid' => 'emp-nueva-eps', 'nombre' => 'NUEVA EPS', 'nit' => '900156264-1', 'codigo_eapb' => 'EPS037']
            ],
            'regimenes' => [
                ['uuid' => 'reg-contributivo', 'nombre' => 'Contributivo']
            ],
            'tipos_afiliacion' => [
                ['uuid' => 'taf-cotizante', 'nombre' => 'Cotizante']
            ],
            'zonas_residenciales' => [
                ['uuid' => 'zr-urbana', 'nombre' => 'Urbana', 'abreviacion' => 'U']
            ],
            'razas' => [
                ['uuid' => 'rz-mestizo', 'nombre' => 'Mestizo']
            ],
            'escolaridades' => [
                ['uuid' => 'esc-primaria-com', 'nombre' => 'Primaria Completa']
            ],
            'tipos_parentesco' => [
                ['uuid' => 'tp-titular', 'nombre' => 'Titular']
            ],
            'tipos_documento' => [
                ['uuid' => 'td-cc', 'abreviacion' => 'CC', 'nombre' => 'Cédula de Ciudadanía']
            ],
            'ocupaciones' => [
                ['uuid' => 'oc-empleado', 'codigo' => '5000', 'nombre' => 'Empleado']
            ],
            'novedades' => [
                ['uuid' => 'nov-ingreso', 'tipo_novedad' => 'Ingreso']
            ],
            'auxiliares' => [
                ['uuid' => 'aux-general', 'nombre' => 'Auxiliar General']
            ],
            'brigadas' => [
                ['uuid' => 'bri-general', 'nombre' => 'Brigada General']
            ],
            'estados_civiles' => [
                'SOLTERO' => 'Soltero(a)',
                'CASADO' => 'Casado(a)',
                'UNION_LIBRE' => 'Unión Libre',
                'DIVORCIADO' => 'Divorciado(a)',
                'VIUDO' => 'Viudo(a)'
            ],
            'sexos' => [
                'M' => 'Masculino',
                'F' => 'Femenino'
            ],
            'estados' => [
                'ACTIVO' => 'Activo',
                'INACTIVO' => 'Inactivo'
            ]
        ];
    }

    /**
     * ✅ CORREGIDO: Limpiar todos los datos offline
     */
    public function clearAllOfflineData(): bool
    {
        try {
            $success = true;

            // ✅ LIMPIAR SQLite SI ESTÁ DISPONIBLE
            if ($this->isSQLiteAvailable()) {
                $tables = [
                    'departamentos', 'municipios', 'empresas', 'regimenes',
                    'tipos_afiliacion', 'zonas_residenciales', 'razas',
                    'escolaridades', 'tipos_parentesco', 'tipos_documento',
                    'ocupaciones', 'novedades', 'auxiliares', 'brigadas', 'sync_status'
                ];

                foreach ($tables as $table) {
                    try {
                        DB::connection('offline')->table($table)->delete();
                    } catch (\Exception $e) {
                        Log::warning("⚠️ Error limpiando tabla {$table}", [
                            'error' => $e->getMessage()
                        ]);
                        $success = false;
                    }
                }
            }

            // ✅ LIMPIAR ARCHIVOS JSON
            $jsonFiles = [
                'master_data.json',
                'sync_info.json'
            ];

            foreach ($jsonFiles as $file) {
                if ($this->hasData($file)) {
                    $this->deleteData($file);
                }
            }

            Log::info('✅ Datos offline limpiados', ['success' => $success]);
            return $success;

        } catch (\Exception $e) {
            Log::error('❌ Error limpiando datos offline', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * ✅ CORREGIDO: Obtener estadísticas de datos offline
     */
    public function getOfflineStats(): array
    {
        try {
            // ✅ INTENTAR SQLite PRIMERO
            if ($this->isSQLiteAvailable()) {
                return $this->getStatsFromSQLite();
            } else {
                return $this->getStatsFromJSON();
            }
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo estadísticas offline', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * ✅ NUEVO: Obtener estadísticas desde SQLite
     */
    private function getStatsFromSQLite(): array
    {
        $stats = [];
        
        $tables = [
            'departamentos', 'municipios', 'empresas', 'regimenes',
            'tipos_afiliacion', 'zonas_residenciales', 'razas',
            'escolaridades', 'tipos_parentesco', 'tipos_documento',
            'ocupaciones', 'novedades', 'auxiliares', 'brigadas', 'procesos'
        ];

        foreach ($tables as $table) {
            try {
                $count = DB::connection('offline')->table($table)->count();
                $stats[$table] = $count;
            } catch (\Exception $e) {
                $stats[$table] = 0;
            }
        }

        // Obtener información de sincronización
        try {
            $syncStatus = DB::connection('offline')->table('sync_status')->get();
            $stats['sync_info'] = $syncStatus->mapWithKeys(function ($item) {
                return [$item->table_name => [
                    'last_sync' => $item->last_sync,
                    'records_count' => $item->records_count
                ]];
            })->toArray();
        } catch (\Exception $e) {
            $stats['sync_info'] = [];
        }

        return $stats;
    }

    /**
     * ✅ NUEVO: Obtener estadísticas desde JSON
     */
    private function getStatsFromJSON(): array
    {
        $masterData = $this->getData('master_data.json', []);
        $syncInfo = $this->getData('sync_info.json', []);
        
        $stats = [];
        
        $tables = [
            'departamentos', 'municipios', 'empresas', 'regimenes',
            'tipos_afiliacion', 'zonas_residenciales', 'razas',
            'escolaridades', 'tipos_parentesco', 'tipos_documento',
            'ocupaciones', 'novedades', 'auxiliares', 'brigadas',  'procesos'
        ];

        foreach ($tables as $table) {
            if (isset($masterData[$table]) && is_array($masterData[$table])) {
                $stats[$table] = count($masterData[$table]);
            } else {
                $stats[$table] = 0;
            }
        }

        // Información de sincronización desde JSON
        if (!empty($syncInfo)) {
            $stats['sync_info'] = [
                'master_data' => [
                    'last_sync' => $syncInfo['last_sync'] ?? null,
                    'records_count' => $syncInfo['total_records'] ?? 0
                ]
            ];
        } else {
            $stats['sync_info'] = [];
        }

        return $stats;
    }

    // ✅ MANTENER MÉTODOS EXISTENTES PARA COMPATIBILIDAD (SIN CAMBIOS)
    
    /**
     * Verificar si el login offline está habilitado
     */
    public function isOfflineLoginEnabled(): bool
    {
        return config('api.offline.enabled', true) && $this->hasStoredUsers();
    }

    /**
 * Almacenar datos de usuario para uso offline
 */
public function storeUserData(array $userData): void
{
    try {
        Log::info('💾 Guardando datos de usuario offline', [
            'usuario_uuid' => $userData['uuid'] ?? 'N/A',
            'login' => $userData['login'] ?? 'N/A',
            'tiene_firma' => !empty($userData['firma'])
        ]);

        // ✅ EXTRAER Y LIMPIAR FIRMA
        $firma = null;
        $tieneFirma = 0;
        
        if (!empty($userData['firma'])) {
            $firma = $userData['firma'];
            $tieneFirma = 1;
            
            // ✅ VALIDAR QUE LA FIRMA TENGA PREFIJO DATA URI
            if (strpos($firma, 'data:image/') !== 0) {
                // Si no tiene prefijo, agregarlo (asumiendo PNG por defecto)
                $firma = 'data:image/png;base64,' . $firma;
            }
            
            Log::info('✅ Firma incluida en datos offline', [
                'usuario_uuid' => $userData['uuid'],
                'login' => $userData['login'],
                'longitud_firma' => strlen($firma),
                'tiene_prefijo' => strpos($firma, 'data:image/') === 0,
                'primeros_30_caracteres' => substr($firma, 0, 30)
            ]);
        } else {
            Log::info('ℹ️ Usuario sin firma digital', [
                'usuario_uuid' => $userData['uuid'],
                'login' => $userData['login']
            ]);
        }

        $offlineData = [
            'id' => $userData['id'],
            'uuid' => $userData['uuid'],
            'documento' => $userData['documento'],
            'nombre' => $userData['nombre'],
            'apellido' => $userData['apellido'],
            'nombre_completo' => $userData['nombre_completo'],
            'correo' => $userData['correo'],
            'telefono' => $userData['telefono'],
            'login' => $userData['login'],
            'sede_id' => $userData['sede_id'],
            'sede' => $userData['sede'],
            'rol_id' => $userData['rol_id'],
            'rol' => $userData['rol'],
            'especialidad_id' => $userData['especialidad_id'] ?? null,
            'especialidad' => $userData['especialidad'] ?? null,
            'estado_id' => $userData['estado_id'],
            'estado' => $userData['estado'],
            'permisos' => $userData['permisos'] ?? [],
            'tipo_usuario' => $userData['tipo_usuario'] ?? [],
            'firma' => $firma, // ✅ FIRMA CON PREFIJO DATA URI
            'tiene_firma' => $tieneFirma, // ✅ BOOLEANO (1 = tiene, 0 = no tiene)
            'stored_at' => now()->toISOString()
        ];

        // ✅ GUARDAR EN JSON
        $this->storeData('users/' . $userData['login'] . '.json', $offlineData);
        
        // ✅ GUARDAR HASH DE CONTRASEÑA PARA VALIDACIÓN OFFLINE
        $this->savePasswordHash($userData['login'], session('temp_password_for_offline'));
        
        Log::info('✅ Datos de usuario guardados offline con firma', [
            'login' => $userData['login'],
            'tiene_firma' => $tieneFirma,
            'archivo_guardado' => 'users/' . $userData['login'] . '.json'
        ]);
        
    } catch (\Exception $e) {
        Log::error('❌ Error guardando datos de usuario offline', [
            'error' => $e->getMessage(),
            'login' => $userData['login'] ?? 'N/A',
            'trace' => $e->getTraceAsString()
        ]);
    }
}


    /**
     * Guardar hash de contraseña para validación offline
     */
    public function savePasswordHash(string $login, ?string $password): void
    {
        if (!$password) return;
        
        $passwordData = [
            'login' => $login,
            'password_hash' => Hash::make($password),
            'created_at' => now()->toISOString()
        ];
        
        $this->storeData('passwords/' . $login . '.json', $passwordData);
    }

    /**
 * Obtener usuario offline
 */
public function getOfflineUser(string $login): ?array
{
    try {
        $userFile = 'users/' . $login . '.json';
        
        if (!$this->hasData($userFile)) {
            Log::warning('⚠️ Usuario no encontrado offline', ['login' => $login]);
            return null;
        }

        $userData = $this->getData($userFile);
        
        // ✅ LOGGING DE FIRMA
        Log::info('✅ Usuario offline cargado', [
            'login' => $login,
            'uuid' => $userData['uuid'] ?? 'N/A',
            'tiene_firma' => $userData['tiene_firma'] ?? 0,
            'firma_presente' => !empty($userData['firma'])
        ]);

        return $userData;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo usuario offline', [
            'login' => $login,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

/**
 * ✅ Almacenar usuario completo (para sincronización masiva)
 */
public function storeUsuarioCompleto(array $userData): void
{
    try {
        Log::info('💾 Almacenando usuario completo offline', [
            'uuid' => $userData['uuid'] ?? 'N/A',
            'login' => $userData['login'] ?? 'N/A',
            'tiene_firma' => !empty($userData['firma'])
        ]);

        // ✅ Procesar firma
        $firma = null;
        $tieneFirma = 0;
        
        if (!empty($userData['firma'])) {
            $firma = $userData['firma'];
            $tieneFirma = 1;
            
            // Validar prefijo data:image/
            if (strpos($firma, 'data:image/') !== 0) {
                $firma = 'data:image/png;base64,' . $firma;
            }
            
            Log::info('✅ Firma incluida en sincronización', [
                'uuid' => $userData['uuid'],
                'longitud' => strlen($firma)
            ]);
        }

        $offlineData = [
            'id' => $userData['id'],
            'uuid' => $userData['uuid'],
            'documento' => $userData['documento'],
            'nombre' => $userData['nombre'],
            'apellido' => $userData['apellido'],
            'nombre_completo' => $userData['nombre_completo'],
            'correo' => $userData['correo'],
            'telefono' => $userData['telefono'],
            'login' => $userData['login'],
            'sede_id' => $userData['sede_id'],
            'sede' => $userData['sede'],
            'rol_id' => $userData['rol_id'],
            'rol' => $userData['rol'],
            'especialidad_id' => $userData['especialidad_id'] ?? null,
            'especialidad' => $userData['especialidad'] ?? null,
            'estado_id' => $userData['estado_id'],
            'estado' => $userData['estado'], // ✅ CORREGIDO: era $usuario['estado']
            'permisos' => $userData['permisos'] ?? [],
            'tipo_usuario' => $userData['tipo_usuario'] ?? [],
            'es_medico' => $userData['es_medico'] ?? false,
            'registro_profesional' => $userData['registro_profesional'] ?? null,
            'firma' => $firma,
            'tiene_firma' => $tieneFirma,
            'synced_at' => now()->toISOString()
        ];

        // ✅ Guardar en JSON por UUID (para búsquedas por UUID)
        $this->storeData('usuarios/' . $userData['uuid'] . '.json', $offlineData);
        
        // ✅ También guardar por login (para login offline)
        $this->storeData('users/' . $userData['login'] . '.json', $offlineData);
        
        Log::info('✅ Usuario completo almacenado offline', [
            'uuid' => $userData['uuid'],
            'login' => $userData['login'],
            'tiene_firma' => $tieneFirma
        ]);
        
    } catch (\Exception $e) {
        Log::error('❌ Error almacenando usuario completo offline', [
            'uuid' => $userData['uuid'] ?? 'N/A',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}


/**
 * ✅ Obtener usuario por UUID (offline)
 */
public function getUsuarioByUuid(string $uuid): ?array
{
    try {
        $userFile = 'usuarios/' . $uuid . '.json';
        
        if (!$this->hasData($userFile)) {
            Log::warning('⚠️ Usuario no encontrado offline por UUID', ['uuid' => $uuid]);
            return null;
        }

        $userData = $this->getData($userFile);
        
        Log::info('✅ Usuario offline cargado por UUID', [
            'uuid' => $uuid,
            'login' => $userData['login'] ?? 'N/A',
            'tiene_firma' => $userData['tiene_firma'] ?? 0
        ]);

        return $userData;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo usuario offline por UUID', [
            'uuid' => $uuid,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

/**
 * ✅ Obtener UUID de usuario a partir de su ID
 */
private function getUserUuidFromId($id): ?string
{
    try {
        if (!$this->isSQLiteAvailable()) {
            Log::warning('⚠️ SQLite no disponible para buscar usuario por ID', ['id' => $id]);
            return null;
        }

        // Buscar usuario en SQLite por su ID numérico (que se guarda en el campo 'documento' o 'id')
        $usuario = DB::connection('offline')
            ->table('usuarios')
            ->where(function($query) use ($id) {
                $query->where('id', $id)
                      ->orWhere('documento', $id);
            })
            ->first();

        if ($usuario && !empty($usuario->uuid)) {
            Log::info('✅ UUID de usuario encontrado por ID', [
                'id' => $id,
                'uuid' => $usuario->uuid,
                'nombre' => $usuario->nombre_completo ?? 'N/A'
            ]);
            return $usuario->uuid;
        }

        Log::warning('⚠️ No se encontró UUID para usuario con ID', ['id' => $id]);
        return null;

    } catch (\Exception $e) {
        Log::error('❌ Error buscando UUID de usuario por ID', [
            'id' => $id,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

/**
 * ✅ Obtener todos los usuarios offline
 */
public function getAllUsuariosOffline(array $filters = []): array
{
    try {
        $usuariosPath = storage_path('app/offline/usuarios');
        
        if (!is_dir($usuariosPath)) {
            return [];
        }

        $usuarios = [];
        $files = glob($usuariosPath . '/*.json');

        foreach ($files as $file) {
            $userData = json_decode(file_get_contents($file), true);
            
            if ($userData) {
                // Aplicar filtros
                if (!empty($filters['sede_id']) && $userData['sede_id'] != $filters['sede_id']) {
                    continue;
                }
                
                if (!empty($filters['rol_id']) && $userData['rol_id'] != $filters['rol_id']) {
                    continue;
                }
                
                if (!empty($filters['estado_id']) && $userData['estado_id'] != $filters['estado_id']) {
                    continue;
                }
                
                if (!empty($filters['search'])) {
                    $search = strtolower($filters['search']);
                    $searchable = strtolower(
                        $userData['nombre_completo'] . ' ' . 
                        $userData['documento'] . ' ' . 
                        $userData['login']
                    );
                    
                    if (strpos($searchable, $search) === false) {
                        continue;
                    }
                }
                
                $usuarios[] = $userData;
            }
        }

        Log::info('✅ Usuarios offline obtenidos', [
            'total' => count($usuarios),
            'filtros' => $filters
        ]);

        return $usuarios;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo usuarios offline', [
            'error' => $e->getMessage()
        ]);
        return [];
    }
}


    /**
     * Validar credenciales offline con contraseña
     */
    public function validateOfflineCredentials(array $credentials): ?array
    {
        $userFile = 'users/' . $credentials['login'] . '.json';
        $passwordFile = 'passwords/' . $credentials['login'] . '.json';
        
        if (!$this->hasData($userFile)) {
            Log::warning('Usuario no encontrado para login offline', ['login' => $credentials['login']]);
            return null;
        }

        $userData = $this->getData($userFile);
        $passwordData = $this->getData($passwordFile);

        // Verificar sede
        if ($userData['sede_id'] != $credentials['sede_id']) {
            Log::warning('Sede incorrecta para login offline', [
                'login' => $credentials['login'],
                'expected_sede' => $userData['sede_id'],
                'provided_sede' => $credentials['sede_id']
            ]);
            return null;
        }

        // Verificar contraseña si está disponible
        if ($passwordData && isset($passwordData['password_hash'])) {
            if (!Hash::check($credentials['password'], $passwordData['password_hash'])) {
                Log::warning('Contraseña incorrecta para login offline', ['login' => $credentials['login']]);
                return null;
            }
        }

        // Verificar que no haya expirado
        $storedAt = Carbon::parse($userData['stored_at']);
        $maxOfflineDays = config('api.offline.max_offline_days', 7);
        
        if ($storedAt->addDays($maxOfflineDays)->isPast()) {
            Log::warning('Intento de login offline con datos expirados', [
                'login' => $credentials['login'],
                'stored_at' => $userData['stored_at'],
                'max_days' => $maxOfflineDays
            ]);
            return null;
        }

        Log::info('Login offline exitoso', ['login' => $credentials['login']]);
        return $userData;
    }

    /**
     * Almacenar cambios pendientes de sincronización
     */
    public function storePendingChange(string $action, string $endpoint, array $data): void
    {
        $changes = $this->getPendingChanges();
        
        $changes[] = [
            'id' => uniqid(),
            'action' => $action,
            'endpoint' => $endpoint,
            'data' => $data,
            'created_at' => now()->toISOString(),
            'attempts' => 0
        ];

        $this->storeData('pending_changes.json', $changes);
    }

    /**
     * Obtener cambios pendientes
     */
    public function getPendingChanges(): array
    {
        return $this->getData('pending_changes.json', []);
    }

    /**
     * Sincronizar cambios pendientes
     */
    public function syncPendingChanges(): array
    {
        $changes = $this->getPendingChanges();
        $results = [];

        foreach ($changes as $index => $change) {
            try {
                $apiService = app(ApiService::class);
                
                $response = $apiService->{$change['action']}(
                    $change['endpoint'],
                    $change['data']
                );

                if ($response['success']) {
                    unset($changes[$index]);
                    $results[] = [
                        'id' => $change['id'],
                        'status' => 'success'
                    ];
                } else {
                    $changes[$index]['attempts']++;
                    $changes[$index]['last_error'] = $response['error'];
                    $results[] = [
                        'id' => $change['id'],
                        'status' => 'failed',
                        'error' => $response['error']
                    ];
                }

            } catch (\Exception $e) {
                $changes[$index]['attempts']++;
                $changes[$index]['last_error'] = $e->getMessage();
                $results[] = [
                    'id' => $change['id'],
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }

        // Guardar cambios actualizados
        $this->storeData('pending_changes.json', array_values($changes));

        return $results;
    }

    /**
     * Verificar si hay usuarios almacenados
     */
    protected function hasStoredUsers(): bool
    {
        $usersPath = $this->storagePath . '/users';
        return is_dir($usersPath) && count(glob($usersPath . '/*.json')) > 0;
    }

    /**
     * Almacenar datos
     */
    public function storeData(string $path, $data): void
    {
        $fullPath = $this->storagePath . '/' . $path;
        $directory = dirname($fullPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($fullPath, json_encode($data, JSON_PRETTY_PRINT));
        
        Log::debug('Datos almacenados offline', [
            'path' => $path,
            'size' => strlen(json_encode($data))
        ]);
    }

    /**
     * Obtener datos almacenados
     */
    public function getData(string $path, $default = null)
    {
        $fullPath = $this->storagePath . '/' . $path;
        
        if (!file_exists($fullPath)) {
            return $default;
        }

        $content = file_get_contents($fullPath);
        return json_decode($content, true) ?? $default;
    }

    /**
     * Verificar si existen datos
     */
    public function hasData(string $path): bool
    {
        return file_exists($this->storagePath . '/' . $path);
    }

    /**
     * Eliminar datos
     */
    public function deleteData(string $path): bool
    {
        $fullPath = $this->storagePath . '/' . $path;
        
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }

    /**
     * Obtener ruta de almacenamiento
     */
    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    /**
     * Obtener datos pendientes de sincronización
     */
    public function getPendingSyncData(string $type): array
    {
        return $this->getData("pending_sync_{$type}.json", []);
    }

    /**
     * Limpiar datos sincronizados
     */
    public function clearSyncedData(string $type, array $syncedIds): void
    {
        $pendingData = $this->getPendingSyncData($type);
        
        // Filtrar los datos que ya se sincronizaron
        $remainingData = array_filter($pendingData, function($item) use ($syncedIds) {
            return !in_array($item['id'] ?? $item['uuid'] ?? '', $syncedIds);
        });
        
        $this->storeData("pending_sync_{$type}.json", array_values($remainingData));
    }

    /**
     * Asegurar que existe el directorio de almacenamiento
     */
    protected function ensureStorageExists(): void
    {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }
public function storeAgendaOffline(array $agendaData, bool $needsSync = false): void
{
    try {
        if (empty($agendaData['uuid'])) {
            Log::warning('⚠️ Intentando guardar agenda sin UUID');
            return;
        }

        // ✅ ASEGURAR SEDE_ID
        if (empty($agendaData['sede_id'])) {
            $user = auth()->user() ?? session('usuario');
            $agendaData['sede_id'] = $user['sede_id'] ?? 1;
        }

        // ✅ NORMALIZAR USUARIO MÉDICO - BUSCAR EN MÚLTIPLES CAMPOS
        $usuarioMedicoUuid = null;
        $usuarioMedicoId = null;
        
        // 1. Intentar extraer del objeto usuario_medico si existe
        if (!empty($agendaData['usuario_medico']) && is_array($agendaData['usuario_medico'])) {
            $usuarioMedicoUuid = $agendaData['usuario_medico']['uuid'] ?? null;
            $usuarioMedicoId = $agendaData['usuario_medico']['id'] ?? null;
            Log::info('🔍 Usuario médico encontrado en objeto usuario_medico', [
                'uuid' => $usuarioMedicoUuid,
                'id' => $usuarioMedicoId
            ]);
        }
        
        // 2. Si no, intentar del campo directo usuario_medico_uuid
        if (empty($usuarioMedicoUuid) && !empty($agendaData['usuario_medico_uuid'])) {
            $usuarioMedicoUuid = $agendaData['usuario_medico_uuid'];
            Log::info('🔍 Usuario médico encontrado en usuario_medico_uuid', [
                'value' => $usuarioMedicoUuid
            ]);
        }
        
        // 3. Si tampoco, intentar medico_uuid
        if (empty($usuarioMedicoUuid) && !empty($agendaData['medico_uuid'])) {
            $usuarioMedicoUuid = $agendaData['medico_uuid'];
            Log::info('🔍 Usuario médico encontrado en medico_uuid', [
                'value' => $usuarioMedicoUuid
            ]);
        }
        
        // 4. ✅ CORREGIDO: usuario_medico_id puede contener UUID (enviado desde AgendaService)
        if (empty($usuarioMedicoUuid) && !empty($agendaData['usuario_medico_id']) && $agendaData['usuario_medico_id'] !== 'null') {
            // Verificar si el valor en usuario_medico_id es un UUID
            if ($this->isValidUuid($agendaData['usuario_medico_id'])) {
                $usuarioMedicoUuid = $agendaData['usuario_medico_id'];
                Log::info('🔍 Usuario médico UUID encontrado en usuario_medico_id', [
                    'value' => $usuarioMedicoUuid
                ]);
            } else {
                // Es un ID numérico
                $usuarioMedicoId = $agendaData['usuario_medico_id'];
                Log::info('🔍 Usuario médico ID numérico encontrado', [
                    'value' => $usuarioMedicoId
                ]);
            }
        }
        
        // 5. ✅ NUEVO: PRESERVAR USUARIO MÉDICO EXISTENTE SI LA API NO LO ENVÍA
        // Esto previene que datos de la API (que puede no devolver usuario_medico) sobrescriban datos locales
        if (empty($usuarioMedicoUuid)) {
            $agendaExistente = $this->getAgendaOffline($agendaData['uuid']);
            if ($agendaExistente) {
                // Buscar usuario_medico en los datos existentes
                $existingMedicoUuid = $agendaExistente['usuario_medico_uuid'] 
                    ?? $agendaExistente['medico_uuid'] 
                    ?? null;
                
                // Verificar también en usuario_medico_id si es UUID
                if (empty($existingMedicoUuid) && !empty($agendaExistente['usuario_medico_id'])) {
                    if ($this->isValidUuid($agendaExistente['usuario_medico_id'])) {
                        $existingMedicoUuid = $agendaExistente['usuario_medico_id'];
                    }
                }
                
                // Verificar en el objeto usuario_medico anidado
                if (empty($existingMedicoUuid) && !empty($agendaExistente['usuario_medico']) && is_array($agendaExistente['usuario_medico'])) {
                    $existingMedicoUuid = $agendaExistente['usuario_medico']['uuid'] ?? null;
                }
                
                if (!empty($existingMedicoUuid)) {
                    $usuarioMedicoUuid = $existingMedicoUuid;
                    Log::info('🔄 Usuario médico preservado de datos existentes', [
                        'uuid' => $usuarioMedicoUuid,
                        'agenda_uuid' => $agendaData['uuid']
                    ]);
                }
            }
        }
        
        // ✅ NUEVO: Cargar datos completos del médico si tenemos UUID
        $usuarioMedicoCompleto = null;
        if (!empty($usuarioMedicoUuid)) {
            $usuarioMedicoCompleto = $this->getUsuarioByUuid($usuarioMedicoUuid);
            if ($usuarioMedicoCompleto) {
                Log::info('✅ Datos completos del médico cargados', [
                    'uuid' => $usuarioMedicoUuid,
                    'nombre' => $usuarioMedicoCompleto['nombre'] ?? $usuarioMedicoCompleto['nombre_completo'] ?? 'Sin nombre'
                ]);
            } else {
                Log::warning('⚠️ No se encontraron datos del médico', [
                    'uuid' => $usuarioMedicoUuid
                ]);
            }
        }
        
        Log::info('✅ Usuario médico final', [
            'uuid' => $usuarioMedicoUuid,
            'id' => $usuarioMedicoId,
            'tiene_datos_completos' => !empty($usuarioMedicoCompleto)
        ]);

        // ✅ CONVERTIR proceso_id A INTEGER O MANTENER UUID
        $procesoId = null;
        $procesoUuid = null;
        
        if (isset($agendaData['proceso_id']) && 
            !empty($agendaData['proceso_id']) && 
            $agendaData['proceso_id'] !== 'null') {
            
            // ✅ VERIFICAR SI ES UUID O ID NUMÉRICO
            if ($this->isValidUuid($agendaData['proceso_id'])) {
                // Es UUID - guardarlo como UUID
                $procesoUuid = $agendaData['proceso_id'];
                
                Log::info('✅ proceso_id es UUID', [
                    'uuid' => $procesoUuid
                ]);
            } else {
                // Es ID numérico
                $procesoId = (int) $agendaData['proceso_id'];
                
                Log::info('✅ proceso_id es numérico', [
                    'original' => $agendaData['proceso_id'],
                    'convertido' => $procesoId
                ]);
            }
        }
        
        // ✅ EXTRAER proceso_uuid SI EXISTE (y no se extrajo arriba)
        if (empty($procesoUuid) && !empty($agendaData['proceso_uuid']) && $agendaData['proceso_uuid'] !== 'null') {
            $procesoUuid = $agendaData['proceso_uuid'];
            Log::info('✅ proceso_uuid extraído de campo separado', ['proceso_uuid' => $procesoUuid]);
        }

        // ✅ CONVERTIR brigada_id A INTEGER O MANTENER UUID
        $brigadaId = null;
        $brigadaUuid = null;
        
        if (isset($agendaData['brigada_id']) && 
            !empty($agendaData['brigada_id']) && 
            $agendaData['brigada_id'] !== 'null') {
            
            // ✅ VERIFICAR SI ES UUID O ID NUMÉRICO
            if ($this->isValidUuid($agendaData['brigada_id'])) {
                // Es UUID - guardarlo como UUID
                $brigadaUuid = $agendaData['brigada_id'];
                
                Log::info('✅ brigada_id es UUID', [
                    'uuid' => $brigadaUuid
                ]);
            } else {
                // Es ID numérico
                $brigadaId = (int) $agendaData['brigada_id'];
                
                Log::info('✅ brigada_id es numérico', [
                    'original' => $agendaData['brigada_id'],
                    'convertido' => $brigadaId
                ]);
            }
        }
        
        // ✅ EXTRAER brigada_uuid SI EXISTE (y no se extrajo arriba)
        if (empty($brigadaUuid) && !empty($agendaData['brigada_uuid']) && $agendaData['brigada_uuid'] !== 'null') {
            $brigadaUuid = $agendaData['brigada_uuid'];
            Log::info('✅ brigada_uuid extraído de campo separado', ['brigada_uuid' => $brigadaUuid]);
        }

        // ✅ NUEVO: OBTENER DATOS COMPLETOS DEL PROCESO
        $procesoCompleto = null;
        
        if ($procesoUuid) {
            // Buscar por UUID primero
            $procesoCompleto = $this->getProcesoByUuid($procesoUuid);
            
            if ($procesoCompleto) {
                Log::info('✅ Proceso encontrado por UUID', [
                    'uuid' => $procesoUuid,
                    'nombre' => $procesoCompleto['nombre'] ?? 'N/A'
                ]);
            }
        } elseif ($procesoId) {
            // Si no hay UUID, buscar por ID
            $procesoCompleto = $this->getProcesoById($procesoId);
            
            if ($procesoCompleto) {
                Log::info('✅ Proceso encontrado por ID', [
                    'id' => $procesoId,
                    'nombre' => $procesoCompleto['nombre'] ?? 'N/A'
                ]);
            }
        }
        
        // Si no encontramos el proceso, usar datos del input
        if (!$procesoCompleto && isset($agendaData['proceso'])) {
            $procesoCompleto = $agendaData['proceso'];
            Log::info('ℹ️ Usando proceso de input', [
                'nombre' => $procesoCompleto['nombre'] ?? 'N/A'
            ]);
        }
        
        // Si aún no tenemos proceso, crear uno básico
        if (!$procesoCompleto) {
            $procesoCompleto = [
                'id' => $procesoId,
                'uuid' => $procesoUuid,
                'nombre' => 'Proceso desconocido',
                'n_cups' => null
            ];
            Log::warning('⚠️ No se pudo obtener información del proceso, usando datos por defecto');
        }

        // ✅ PREPARAR DATOS PARA SQLITE
        $sqliteData = [
            'uuid' => $agendaData['uuid'],
            'sede_id' => (int) $agendaData['sede_id'],
            'modalidad' => $agendaData['modalidad'] ?? 'Ambulatoria',
            'fecha' => $agendaData['fecha'],
            'consultorio' => $agendaData['consultorio'] ?? '',
            'hora_inicio' => $agendaData['hora_inicio'],
            'hora_fin' => $agendaData['hora_fin'],
            'intervalo' => $agendaData['intervalo'] ?? '15',
            'etiqueta' => $agendaData['etiqueta'] ?? '',
            'estado' => $agendaData['estado'] ?? 'ACTIVO',
            'proceso_id' => $procesoId,
            'proceso_uuid' => $procesoUuid,
            'usuario_id' => (int) ($agendaData['usuario_id'] ?? 1),
            'usuario_medico_id' => $usuarioMedicoUuid, // ✅ CAMBIO CRÍTICO: USAR UUID EN LUGAR DE ID
            'usuario_medico_uuid' => $usuarioMedicoUuid,
            'medico_uuid' => $usuarioMedicoUuid,
            'brigada_id' => $brigadaId,
            'brigada_uuid' => $brigadaUuid,
            'cupos_disponibles' => (int) ($agendaData['cupos_disponibles'] ?? 0),
            'sync_status' => $needsSync ? 'pending' : 'synced',
            'operation_type' => $needsSync ? 'create' : 'sync',
            
            // ✅ GUARDAR DATOS COMPLETOS EN original_data (INCLUYENDO PROCESO Y MÉDICO)
            'original_data' => json_encode([
                'uuid' => $agendaData['uuid'],
                'fecha' => $agendaData['fecha'],
                'consultorio' => $agendaData['consultorio'] ?? '',
                'hora_inicio' => $agendaData['hora_inicio'],
                'hora_fin' => $agendaData['hora_fin'],
                'intervalo' => $agendaData['intervalo'] ?? '15',
                'etiqueta' => $agendaData['etiqueta'] ?? '',
                'modalidad' => $agendaData['modalidad'] ?? 'Ambulatoria',
                'estado' => $agendaData['estado'] ?? 'ACTIVO',
                'proceso_id' => $procesoId,
                'proceso_uuid' => $procesoUuid,
                
                // ✅ INCLUIR OBJETO PROCESO COMPLETO REAL
                'proceso' => $procesoCompleto,
                
                'brigada_id' => $brigadaId,
                'brigada_uuid' => $brigadaUuid,
                'usuario_medico_id' => $usuarioMedicoUuid, // ✅ CAMBIO CRÍTICO: UUID EN ORIGINAL_DATA
                'usuario_medico_uuid' => $usuarioMedicoUuid,
                'medico_uuid' => $usuarioMedicoUuid,
                
                // ✅ NUEVO: INCLUIR OBJETO USUARIO MÉDICO COMPLETO
                'usuario_medico' => $usuarioMedicoCompleto,
                
                'usuario_id' => (int) ($agendaData['usuario_id'] ?? 1),
                'sede_id' => (int) $agendaData['sede_id']
            ]),
            
            'created_at' => $agendaData['created_at'] ?? now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'deleted_at' => $agendaData['deleted_at'] ?? null
        ];

        // ✅ LOG DE VERIFICACIÓN
        Log::info('✅ Datos SQLite preparados para agenda', [
            'agenda_uuid' => $agendaData['uuid'],
            'usuario_medico_id' => $sqliteData['usuario_medico_id'],
            'usuario_medico_uuid' => $sqliteData['usuario_medico_uuid'],
            'es_uuid_correcto' => $this->isValidUuid($sqliteData['usuario_medico_id'] ?? '')
        ]);

        // ✅ GUARDAR EN SQLITE
        if ($this->isSQLiteAvailable()) {
            DB::connection('offline')->table('agendas')->updateOrInsert(
                ['uuid' => $agendaData['uuid']],
                $sqliteData
            );
        }

        // ✅ ENRIQUECER JSON CON PROCESO COMPLETO REAL Y USUARIO MÉDICO
        $jsonData = array_merge($agendaData, [
            'usuario_medico_uuid' => $usuarioMedicoUuid,
            'usuario_medico_id' => $usuarioMedicoUuid, // ✅ CAMBIO CRÍTICO: UUID EN JSON TAMBIÉN
            'sync_status' => $sqliteData['sync_status'],
            
            // ✅ USAR EL PROCESO COMPLETO OBTENIDO
            'proceso' => $procesoCompleto,
            
            // ✅ NUEVO: INCLUIR OBJETO USUARIO_MEDICO COMPLETO
            'usuario_medico' => $usuarioMedicoCompleto
        ]);
        
        $this->storeData('agendas/' . $agendaData['uuid'] . '.json', $jsonData);

        Log::debug('✅ Agenda almacenada offline con proceso completo', [
            'uuid' => $agendaData['uuid'],
            'fecha' => $agendaData['fecha'],
            'consultorio' => $agendaData['consultorio'],
            'proceso_id' => $procesoId,
            'proceso_uuid' => $procesoUuid,
            'proceso_nombre' => $procesoCompleto['nombre'] ?? 'N/A',
            'proceso_n_cups' => $procesoCompleto['n_cups'] ?? 'N/A',
            'brigada_id' => $brigadaId,
            'brigada_uuid' => $brigadaUuid,
            'usuario_medico_uuid' => $usuarioMedicoUuid,
            'usuario_medico_id_guardado' => $usuarioMedicoUuid, // ✅ CONFIRMAR QUE ES UUID
            'sync_status' => $sqliteData['sync_status']
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error almacenando agenda offline', [
            'error' => $e->getMessage(),
            'uuid' => $agendaData['uuid'] ?? 'sin-uuid',
            'trace' => $e->getTraceAsString()
        ]);
    }
}


public function storeCitaOffline(array $citaData, bool $needsSync = false): void
{
    try {
        // ✅ DEBUG CRÍTICO - AGREGAR AL INICIO
        $user = auth()->user() ?? session('usuario');
        $loginSedeId = session('sede_id') ?? $user['sede_id'] ?? 1; // ← SEDE DEL LOGIN
        $citaSedeOriginal = $citaData['sede_id'] ?? 'NO_SEDE_CITA';
        
        Log::info('🧪 DEBUG CRÍTICO: Guardando cita con información de sedes', [
            'cita_uuid' => $citaData['uuid'] ?? 'NO_UUID',
            'sede_login' => $loginSedeId,
            'cita_sede_original' => $citaSedeOriginal,
            'usuario_sede' => $user['sede_id'] ?? 'NO_SEDE_USUARIO',
            'session_sede_id' => session('sede_id') ?? 'NO_SESSION_SEDE',
            'usuario_nombre' => $user['nombre_completo'] ?? 'NO_NOMBRE'
        ]);

        if (empty($citaData['uuid'])) {
            Log::warning('⚠️ Intentando guardar cita sin UUID');
            return;
        }

        // ✅ FORZAR SEDE DEL LOGIN (NO DEL USUARIO)
        $citaData['sede_id'] = $loginSedeId;
        
        Log::info('🔧 Sede FORZADA a sede del login', [
            'cita_uuid' => $citaData['uuid'],
            'sede_final' => $citaData['sede_id'],
            'era_diferente' => $citaSedeOriginal != $loginSedeId
        ]);

        // ✅ CORREGIR FECHA ANTES DE GUARDAR
        if (isset($citaData['fecha'])) {
            $fechaOriginal = $citaData['fecha'];
            
            if (strpos($fechaOriginal, 'T') !== false) {
                $fechaLimpia = explode('T', $fechaOriginal)[0];
                $citaData['fecha'] = $fechaLimpia;
                
                Log::info('✅ Fecha de cita corregida al guardar offline', [
                    'cita_uuid' => $citaData['uuid'],
                    'fecha_original' => $fechaOriginal,
                    'fecha_corregida' => $fechaLimpia
                ]);
            }
        }

        // ✅ CORREGIR fecha_inicio Y fecha_final
        if (isset($citaData['fecha_inicio'])) {
            $fechaInicio = $citaData['fecha_inicio'];
            if (strpos($fechaInicio, 'T') !== false) {
                $fechaLimpia = explode('T', $fechaInicio)[0];
                $horaLimpia = explode('T', $fechaInicio)[1];
                $horaLimpia = substr($horaLimpia, 0, 8);
                
                $citaData['fecha_inicio'] = $fechaLimpia . 'T' . $horaLimpia;
            }
        }

        if (isset($citaData['fecha_final'])) {
            $fechaFinal = $citaData['fecha_final'];
            if (strpos($fechaFinal, 'T') !== false) {
                $fechaLimpia = explode('T', $fechaFinal)[0];
                $horaLimpia = explode('T', $fechaFinal)[1];
                $horaLimpia = substr($horaLimpia, 0, 8);
                
                $citaData['fecha_final'] = $fechaLimpia . 'T' . $horaLimpia;
            }
        }

        // ✅ LOGGING ESPECÍFICO DE CUPS
        if (!empty($citaData['cups_contratado_uuid'])) {
            Log::info('💾 Guardando cita CON CUPS contratado', [
                'cita_uuid' => $citaData['uuid'],
                'fecha_corregida' => $citaData['fecha'],
                'cups_contratado_uuid' => $citaData['cups_contratado_uuid']
            ]);
        } else {
            Log::info('💾 Guardando cita SIN CUPS contratado', [
                'cita_uuid' => $citaData['uuid'],
                'fecha_corregida' => $citaData['fecha']
            ]);
        }

        // ✅ PREPARAR DATOS LIMPIOS PARA SQLITE
        $offlineData = [
            'uuid' => $citaData['uuid'],
            'sede_id' => (int) $citaData['sede_id'],
            'fecha' => $citaData['fecha'],
            'fecha_inicio' => $citaData['fecha_inicio'],
            'fecha_final' => $citaData['fecha_final'],
            'fecha_deseada' => $citaData['fecha_deseada'] ?? null,
            'motivo' => $citaData['motivo'] ?? null,
            'nota' => $citaData['nota'] ?? '',
            'estado' => $citaData['estado'] ?? 'PROGRAMADA',
            'patologia' => $citaData['patologia'] ?? null,
            'paciente_id' => null,
            'paciente_uuid' => $citaData['paciente_uuid'] ?? null,
            'agenda_id' => null,
            'agenda_uuid' => $citaData['agenda_uuid'] ?? null,
            'cups_contratado_id' => null,
            'cups_contratado_uuid' => $citaData['cups_contratado_uuid'] ?? null,
            'usuario_creo_cita_id' => (int) ($citaData['usuario_creo_cita_id'] ?? 1),
            'sync_status' => $needsSync ? 'pending' : 'synced',
            'created_at' => $citaData['created_at'] ?? now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'deleted_at' => $citaData['deleted_at'] ?? null
        ];

        if ($this->isSQLiteAvailable()) {
            // ✅ VERIFICAR SI LA CITA YA EXISTE Y ESTÁ PENDIENTE
            $citaExistente = DB::connection('offline')
                ->table('citas')
                ->where('uuid', $citaData['uuid'])
                ->first();
            
            // ✅ SI LA CITA EXISTE Y ESTÁ PENDIENTE, SOLO PERMITIR ACTUALIZACIÓN DE ESTADO
            if ($citaExistente && $citaExistente->sync_status === 'pending') {
                // ✅ PERMITIR ACTUALIZACIÓN SI EL ESTADO CAMBIÓ (ej: PROGRAMADA → ATENDIDA)
                if ($citaExistente->estado !== $offlineData['estado']) {
                    Log::info('🔄 Actualizando SOLO el estado de cita pendiente', [
                        'cita_uuid' => $citaData['uuid'],
                        'estado_anterior' => $citaExistente->estado,
                        'estado_nuevo' => $offlineData['estado'],
                        'sync_status' => 'pending (preservado)'
                    ]);
                    
                    // ✅ ACTUALIZAR SOLO EL ESTADO, PRESERVAR sync_status='pending'
                    DB::connection('offline')->table('citas')
                        ->where('uuid', $citaData['uuid'])
                        ->update([
                            'estado' => $offlineData['estado'],
                            'updated_at' => now()->toISOString()
                        ]);
                    return;
                }
                
                Log::info('⚠️ Cita ya existe con sync_status=pending y mismo estado, NO sobrescribir', [
                    'cita_uuid' => $citaData['uuid'],
                    'sync_status_existente' => $citaExistente->sync_status,
                    'needsSync_nuevo' => $needsSync ? 'pending' : 'synced',
                    'accion' => 'SALTADA - preservando estado pending'
                ]);
                return; // ← SALIR SIN SOBRESCRIBIR
            }
            
            DB::connection('offline')->table('citas')->updateOrInsert(
                ['uuid' => $citaData['uuid']],
                $offlineData
            );
            
            // ✅ DEBUG FINAL: VERIFICAR QUE SE GUARDÓ CORRECTAMENTE
            $citaGuardada = DB::connection('offline')->table('citas')
                ->where('uuid', $citaData['uuid'])
                ->first();
                
            Log::info('🔍 DEBUG: Verificación de cita guardada en SQLite', [
                'cita_uuid' => $citaData['uuid'],
                'guardada_correctamente' => $citaGuardada ? 'SÍ' : 'NO',
                'sede_guardada' => $citaGuardada->sede_id ?? 'NO_ENCONTRADA',
                'fecha_guardada' => $citaGuardada->fecha ?? 'NO_ENCONTRADA',
                'sync_status' => $citaGuardada->sync_status ?? 'NO_ENCONTRADA'
            ]);
        }

        // ✅ GUARDAR EN JSON COMPLETO (CON DATOS ENRIQUECIDOS)
        $this->storeData('citas/' . $citaData['uuid'] . '.json', $citaData);

        Log::debug('✅ Cita almacenada offline con fecha corregida', [
            'uuid' => $citaData['uuid'],
            'fecha_final' => $citaData['fecha'],
            'sede_final' => $citaData['sede_id'],
            'paciente_uuid' => $citaData['paciente_uuid'],
            'cups_contratado_uuid' => $citaData['cups_contratado_uuid'] ?? 'null',
            'has_agenda_data' => isset($citaData['agenda']),
            'agenda_etiqueta' => $citaData['agenda']['etiqueta'] ?? 'No disponible'
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error almacenando cita offline', [
            'error' => $e->getMessage(),
            'uuid' => $citaData['uuid'] ?? 'sin-uuid',
            'trace' => $e->getTraceAsString()
        ]);
    }
}


public function getAgendasOffline(int $sedeId, array $filters = []): array
{
    try {
        $agendas = [];

        if ($this->isSQLiteAvailable()) {
            $query = DB::connection('offline')->table('agendas')
                ->where('sede_id', $sedeId)
                ->whereNull('deleted_at');

            // Aplicar filtros
            if (!empty($filters['fecha_desde'])) {
                $query->where('fecha', '>=', $filters['fecha_desde']);
            }
            if (!empty($filters['fecha_hasta'])) {
                $query->where('fecha', '<=', $filters['fecha_hasta']);
            }
            if (!empty($filters['estado'])) {
                $query->where('estado', $filters['estado']);
            }
            if (!empty($filters['modalidad'])) {
                $query->where('modalidad', $filters['modalidad']);
            }

            $agendasRaw = $query->orderBy('fecha', 'desc')
                ->orderBy('hora_inicio', 'asc')
                ->get();
            
            // ✅ ENRIQUECER CADA AGENDA CON PROCESO DESDE ORIGINAL_DATA
            foreach ($agendasRaw as $agenda) {
                $agendaArray = (array) $agenda;
                
                // ✅ SI TIENE original_data, EXTRAER PROCESO DE AHÍ (MÁS SIMPLE)
                if (isset($agendaArray['original_data']) && is_string($agendaArray['original_data'])) {
                    $originalData = json_decode($agendaArray['original_data'], true);
                    
                    if ($originalData && isset($originalData['proceso'])) {
                        $agendaArray['proceso'] = $originalData['proceso'];
                        $agendaArray['proceso_nombre'] = $originalData['proceso']['nombre'] ?? null;
                        
                        Log::info('✅ Proceso extraído de original_data', [
                            'agenda_uuid' => $agendaArray['uuid'],
                            'proceso_nombre' => $agendaArray['proceso_nombre']
                        ]);
                    }
                }
                
                // ✅ FALLBACK: Buscar en tabla procesos solo si no se encontró en original_data
                if (!isset($agendaArray['proceso'])) {
                    if (!empty($agendaArray['proceso_uuid'])) {
                        $proceso = $this->getProcesoByUuid($agendaArray['proceso_uuid']);
                        if ($proceso) {
                            $agendaArray['proceso'] = $proceso;
                            $agendaArray['proceso_nombre'] = $proceso['nombre'] ?? null;
                        }
                    } elseif (!empty($agendaArray['proceso_id'])) {
                        $proceso = $this->getProcesoById($agendaArray['proceso_id']);
                        if ($proceso) {
                            $agendaArray['proceso'] = $proceso;
                            $agendaArray['proceso_nombre'] = $proceso['nombre'] ?? null;
                        }
                    }
                }
                
                // Enriquecer con brigada desde original_data
                if (isset($agendaArray['original_data']) && is_string($agendaArray['original_data'])) {
                    $originalData = json_decode($agendaArray['original_data'], true);
                    if ($originalData && isset($originalData['brigada'])) {
                        $agendaArray['brigada'] = $originalData['brigada'];
                    }
                }
                
                $agendas[] = $agendaArray;
            }
        } else {
            // Fallback a JSON
            $agendasPath = $this->getStoragePath() . '/agendas';
            if (is_dir($agendasPath)) {
                $files = glob($agendasPath . '/*.json');
                foreach ($files as $file) {
                    $data = json_decode(file_get_contents($file), true);
                    if ($data && $data['sede_id'] == $sedeId && !$data['deleted_at']) {
                        // Ya viene enriquecido del JSON
                        $agendas[] = $data;
                    }
                }
            }
        }

        return $agendas;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo agendas offline', [
            'error' => $e->getMessage(),
            'sede_id' => $sedeId
        ]);
        return [];
    }
}
public function getCitasOffline($sedeId, array $filters = [])
{
    try {
        Log::info('📋 getCitasOffline iniciado', [
            'sede_id' => $sedeId,
            'filters' => $filters
        ]);

        $citas = [];

        if ($this->isSQLiteAvailable()) {
            $query = DB::connection('offline')
                ->table('citas')
                ->leftJoin('pacientes', 'citas.paciente_uuid', '=', 'pacientes.uuid')
                ->where('citas.sede_id', $sedeId)
                ->whereNull('citas.deleted_at');

            // ✅ APLICAR FILTROS
            if (!empty($filters['agenda_uuid'])) {
                $query->where('citas.agenda_uuid', $filters['agenda_uuid']);
                Log::info('🔍 Filtro agenda_uuid aplicado', [
                    'agenda_uuid' => $filters['agenda_uuid']
                ]);
            }

            if (!empty($filters['paciente_uuid'])) {
                $query->where('citas.paciente_uuid', $filters['paciente_uuid']);
                Log::info('🔍 Filtro paciente_uuid aplicado', [
                    'paciente_uuid' => $filters['paciente_uuid']
                ]);
            }
            // ✅ AGREGAR AQUÍ EL NUEVO FILTRO
            if (!empty($filters['exclude_agenda_uuid'])) {
                $query->where('citas.agenda_uuid', '!=', $filters['exclude_agenda_uuid']);
                Log::info('🔍 Filtro exclude_agenda_uuid aplicado', [
                    'agenda_uuid_excluida' => $filters['exclude_agenda_uuid']
                ]);
            }

            if (!empty($filters['fecha'])) {
                // ✅ LIMPIAR FECHA
                $fechaLimpia = $filters['fecha'];
                if (strpos($fechaLimpia, 'T') !== false) {
                    $fechaLimpia = explode('T', $fechaLimpia)[0];
                }
                
                // ✅ USAR MÚLTIPLES MÉTODOS DE FILTRADO PARA ASEGURAR COMPATIBILIDAD
                $query->where(function($q) use ($fechaLimpia) {
                    $q->whereDate('citas.fecha', $fechaLimpia)
                      ->orWhere('citas.fecha', $fechaLimpia)
                      ->orWhere('citas.fecha', 'LIKE', $fechaLimpia . '%');
                });
                
                Log::info('🔍 Filtro fecha aplicado', [
                    'fecha_original' => $filters['fecha'],
                    'fecha_limpia' => $fechaLimpia
                ]);
            }

            if (!empty($filters['estado'])) {
                $query->where('citas.estado', $filters['estado']);
            }

            if (!empty($filters['paciente_documento'])) {
                $query->where('pacientes.documento', 'LIKE', '%' . $filters['paciente_documento'] . '%');
            }

            $query->select(
                'citas.*',
                'pacientes.nombre_completo as paciente_nombre_completo',
                'pacientes.documento as paciente_documento',
                'pacientes.telefono as paciente_telefono',
                'pacientes.fecha_nacimiento as paciente_fecha_nacimiento',
                'pacientes.sexo as paciente_sexo'
            )->orderBy('citas.fecha_inicio');

            $results = $query->get();

            Log::info('📊 Consulta SQLite ejecutada', [
                'total_resultados' => $results->count(),
                'sql_filters_aplicados' => array_keys($filters)
            ]);

            $citas = $results->map(function ($cita) {
                $citaArray = (array) $cita;
                
                // ✅ ENRIQUECER CON AGENDA COMPLETA (INCLUYENDO PROCESO)
                if (!empty($citaArray['agenda_uuid'])) {
                    $agenda = $this->getAgendaOffline($citaArray['agenda_uuid']);
                    
                    if ($agenda) {
                        $citaArray['agenda'] = $agenda;
                        
                        Log::debug('✅ Agenda cargada para cita', [
                            'cita_uuid' => $citaArray['uuid'] ?? 'N/A',
                            'agenda_uuid' => $citaArray['agenda_uuid'],
                            'proceso_nombre' => $agenda['proceso']['nombre'] ?? 'N/A'
                        ]);
                    } else {
                        Log::warning('⚠️ Agenda no encontrada para cita', [
                            'cita_uuid' => $citaArray['uuid'] ?? 'N/A',
                            'agenda_uuid' => $citaArray['agenda_uuid']
                        ]);
                    }
                }
                
                // ✅ CONSTRUIR OBJETO PACIENTE CON FALLBACK
                if ($citaArray['paciente_nombre_completo']) {
                    $citaArray['paciente'] = [
                        'uuid' => $citaArray['paciente_uuid'],
                        'nombre_completo' => $citaArray['paciente_nombre_completo'],
                        'documento' => $citaArray['paciente_documento'],
                        'telefono' => $citaArray['paciente_telefono'],
                        'fecha_nacimiento' => $citaArray['paciente_fecha_nacimiento'],
                        'sexo' => $citaArray['paciente_sexo']
                    ];
                } else {
                    // ✅ FALLBACK: BUSCAR PACIENTE SI EL JOIN FALLÓ
                    if (!empty($citaArray['paciente_uuid'])) {
                        $paciente = $this->getPacienteOffline($citaArray['paciente_uuid']);
                        if ($paciente) {
                            $citaArray['paciente'] = [
                                'uuid' => $paciente['uuid'],
                                'nombre_completo' => $paciente['nombre_completo'],
                                'documento' => $paciente['documento'] ?? 'N/A',
                                'telefono' => $paciente['telefono'] ?? 'N/A',
                                'fecha_nacimiento' => $paciente['fecha_nacimiento'] ?? null,
                                'sexo' => $paciente['sexo'] ?? 'M'
                            ];
                            Log::info('✅ Paciente cargado via fallback', [
                                'cita_uuid' => $citaArray['uuid'],
                                'paciente_nombre' => $paciente['nombre_completo']
                            ]);
                        } else {
                            // ✅ PACIENTE POR DEFECTO SI NO SE ENCUENTRA
                            $citaArray['paciente'] = [
                                'uuid' => $citaArray['paciente_uuid'],
                                'nombre_completo' => 'Paciente no encontrado',
                                'documento' => 'N/A',
                                'telefono' => 'N/A',
                                'fecha_nacimiento' => null,
                                'sexo' => 'M'
                            ];
                            Log::warning('⚠️ Paciente no encontrado, usando datos por defecto', [
                                'paciente_uuid' => $citaArray['paciente_uuid']
                            ]);
                        }
                    }
                }

                // ✅ LIMPIAR CAMPOS DUPLICADOS
                unset(
                    $citaArray['paciente_nombre_completo'], 
                    $citaArray['paciente_documento'],
                    $citaArray['paciente_telefono'], 
                    $citaArray['paciente_fecha_nacimiento'], 
                    $citaArray['paciente_sexo']
                );

                // ✅ AGREGAR HORA EXTRAÍDA DE fecha_inicio
                if (isset($citaArray['fecha_inicio'])) {
                    $fechaInicio = $citaArray['fecha_inicio'];
                    if (strpos($fechaInicio, 'T') !== false) {
                        $hora = explode('T', $fechaInicio)[1];
                        $citaArray['hora'] = substr($hora, 0, 5); // "09:00:00" -> "09:00"
                    } else {
                        $citaArray['hora'] = date('H:i', strtotime($fechaInicio));
                    }
                }

                return $citaArray;
            })->toArray();

            Log::info('✅ Citas SQLite procesadas', [
                'total_procesadas' => count($citas),
                'primera_cita_uuid' => $citas[0]['uuid'] ?? 'N/A',
                'primera_cita_hora' => $citas[0]['hora'] ?? 'N/A'
            ]);

        } else {
            // ✅ FALLBACK A JSON
            Log::info('📱 Usando fallback JSON');
            
            $citasPath = $this->getStoragePath() . '/citas';
            if (is_dir($citasPath)) {
                $files = glob($citasPath . '/*.json');
                foreach ($files as $file) {
                    $data = json_decode(file_get_contents($file), true);
                    if ($data && 
                        $data['sede_id'] == $sedeId &&
                        empty($data['deleted_at'])) {
                        
                        // ✅ APLICAR FILTROS JSON
                        $cumpleFiltros = true;
                        
                        if (!empty($filters['agenda_uuid']) && 
                            $data['agenda_uuid'] !== $filters['agenda_uuid']) {
                            $cumpleFiltros = false;
                        }

                        if (!empty($filters['paciente_uuid']) && 
                            $data['paciente_uuid'] !== $filters['paciente_uuid']) {
                            $cumpleFiltros = false;
                        }
                        // ✅ AGREGAR ESTE NUEVO FILTRO
                        if (!empty($filters['exclude_agenda_uuid']) && 
                            $data['agenda_uuid'] === $filters['exclude_agenda_uuid']) {
                            $cumpleFiltros = false;
                            Log::info('🚫 Cita excluida por exclude_agenda_uuid (JSON)', [
                                'cita_uuid' => $data['uuid'] ?? 'N/A',
                                'agenda_uuid' => $data['agenda_uuid']
                            ]);
                        }
                        if (!empty($filters['fecha'])) {
                            $fechaLimpia = $filters['fecha'];
                            if (strpos($fechaLimpia, 'T') !== false) {
                                $fechaLimpia = explode('T', $fechaLimpia)[0];
                            }
                            
                            $fechaCita = $data['fecha'] ?? '';
                            if (strpos($fechaCita, 'T') !== false) {
                                $fechaCita = explode('T', $fechaCita)[0];
                            }
                            
                            if ($fechaCita !== $fechaLimpia) {
                                $cumpleFiltros = false;
                            }
                        }
                        
                        if ($cumpleFiltros) {
                            // ✅ ENRIQUECER CON AGENDA (INCLUYENDO PROCESO)
                            if (!empty($data['agenda_uuid'])) {
                                $agenda = $this->getAgendaOffline($data['agenda_uuid']);
                                if ($agenda) {
                                    $data['agenda'] = $agenda;
                                    Log::debug('✅ Agenda JSON cargada', [
                                        'cita_uuid' => $data['uuid'] ?? 'N/A',
                                        'proceso_nombre' => $agenda['proceso']['nombre'] ?? 'N/A'
                                    ]);
                                }
                            }

                            // ✅ ENRIQUECER CON PACIENTE SI NO ESTÁ
                            if (!isset($data['paciente']) && !empty($data['paciente_uuid'])) {
                                $paciente = $this->getPacienteOffline($data['paciente_uuid']);
                                if ($paciente) {
                                    $data['paciente'] = $paciente;
                                }
                            }
                            
                            // ✅ AGREGAR HORA
                            if (isset($data['fecha_inicio']) && !isset($data['hora'])) {
                                $fechaInicio = $data['fecha_inicio'];
                                if (strpos($fechaInicio, 'T') !== false) {
                                    $hora = explode('T', $fechaInicio)[1];
                                    $data['hora'] = substr($hora, 0, 5);
                                } else {
                                    $data['hora'] = date('H:i', strtotime($fechaInicio));
                                }
                            }
                            
                            $citas[] = $data;
                        }
                    }
                }

                // ✅ ORDENAR POR HORA
                usort($citas, function ($a, $b) {
                    return strcmp($a['fecha_inicio'] ?? '', $b['fecha_inicio'] ?? '');
                });
            }

            Log::info('✅ Citas JSON procesadas', [
                'total_procesadas' => count($citas)
            ]);
        }

        Log::info('✅ getCitasOffline completado', [
            'sede_id' => $sedeId,
            'total_citas_retornadas' => count($citas),
            'filters_aplicados' => $filters
        ]);

        return $citas;

    } catch (\Exception $e) {
        Log::error('❌ Error en getCitasOffline', [
            'error' => $e->getMessage(),
            'sede_id' => $sedeId,
            'filters' => $filters,
            'trace' => $e->getTraceAsString()
        ]);
        return [];
    }
}


public function actualizarEstadoCitaOffline(string $uuid, string $nuevoEstado, int $sedeId): bool
{
    try {
        Log::info('📱 Actualizando estado de cita offline', [
            'uuid' => $uuid,
            'nuevo_estado' => $nuevoEstado,
            'sede_id' => $sedeId
        ]);

        // ✅ USAR SOLO CAMPOS QUE EXISTEN EN LA TABLA
        $affected = DB::connection('offline')
            ->table('citas')
            ->where('uuid', $uuid)
            ->where('sede_id', $sedeId)
            ->update([
                'estado' => $nuevoEstado,
                'sync_status' => 'pending',
                'updated_at' => now()->toISOString()
                // ✅ NO USAR 'offline_modificado' - NO EXISTE EN LA TABLA
            ]);

        if ($affected > 0) {
            Log::info('✅ Estado actualizado en SQLite', [
                'uuid' => $uuid,
                'nuevo_estado' => $nuevoEstado,
                'filas_afectadas' => $affected
            ]);

            // ✅ TAMBIÉN ACTUALIZAR EL ARCHIVO JSON
            $cita = $this->getCitaOffline($uuid);
            if ($cita) {
                $cita['estado'] = $nuevoEstado;
                $cita['sync_status'] = 'pending';
                $cita['updated_at'] = now()->toISOString();
                $this->storeCitaOffline($cita, false); // ✅ NO MARCAR COMO PENDIENTE
            }
              // ✅ REGISTRAR CAMBIO PENDIENTE
            $this->registrarCambioPendiente([
                'entidad_uuid' => $uuid,
                'entidad_tipo' => 'cita',
                'tipo_operacion' => 'estado_actualizado',
                'datos' => [
                    'nuevo_estado' => $nuevoEstado,
                    'fecha_cambio' => now()->toISOString()
                ],
                'sede_id' => $sedeId
            ]);

            return true;
       
        } else {
            Log::warning('⚠️ No se encontró la cita para actualizar', [
                'uuid' => $uuid,
                'sede_id' => $sedeId
            ]);
            return false;
        }

    } catch (\Exception $e) {
        Log::error('❌ Error actualizando estado offline', [
            'error' => $e->getMessage(),
            'uuid' => $uuid,
            'nuevo_estado' => $nuevoEstado
        ]);
        
        return false;
    }
}
/**
 * ✅ MÉTODO FALTANTE: REGISTRAR CAMBIO PENDIENTE
 */
public function registrarCambioPendiente(array $cambioData)
{
    try {
        // ✅ VALIDAR DATOS OBLIGATORIOS
        if (empty($cambioData['entidad_uuid']) || empty($cambioData['tipo_operacion'])) {
            Log::error('❌ Datos incompletos para registrar cambio pendiente', [
                'cambio_data' => $cambioData
            ]);
            return false;
        }

        $cambio = [
            'uuid' => \Str::uuid()->toString(),
            'entidad_uuid' => trim($cambioData['entidad_uuid']),
            'entidad_tipo' => $cambioData['entidad_tipo'] ?? 'cita',
            'tipo_operacion' => $cambioData['tipo_operacion'],
            'datos' => json_encode($cambioData['datos'] ?? []),
            'sede_id' => $cambioData['sede_id'] ?? 1,
            'timestamp' => now()->toISOString(),
            'sincronizado' => false,
            'intentos' => 0,
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString()
        ];

        if ($this->isSQLiteAvailable()) {
            // ✅ CREAR TABLA SI NO EXISTE
            $this->createCambiosPendientesTable();
            
            DB::connection('offline')
                ->table('cambios_pendientes')
                ->insert($cambio);
                
            Log::info('✅ Cambio registrado en SQLite', [
                'cambio_uuid' => $cambio['uuid'],
                'entidad_uuid' => $cambio['entidad_uuid']
            ]);
        } else {
            // ✅ GUARDAR EN JSON
            $cambiosPath = $this->getStoragePath() . '/cambios_pendientes';
            if (!is_dir($cambiosPath)) {
                mkdir($cambiosPath, 0755, true);
            }
            
            $archivo = $cambiosPath . '/' . $cambio['uuid'] . '.json';
            file_put_contents($archivo, json_encode($cambio, JSON_PRETTY_PRINT));
            
            Log::info('✅ Cambio registrado en JSON', [
                'cambio_uuid' => $cambio['uuid'],
                'archivo' => basename($archivo)
            ]);
        }

        return true;

    } catch (\Exception $e) {
        Log::error('❌ Error registrando cambio pendiente', [
            'error' => $e->getMessage(),
            'cambio_data' => $cambioData
        ]);
        return false;
    }
}

/**
 * ✅ MÉTODO 2: GUARDAR CAMBIO PARA SINCRONIZAR
 */
private function guardarCambioParaSincronizar($entidadUuid, $tipoOperacion, $datos)
{
    try {
        $cambio = [
            'uuid' => \Str::uuid()->toString(),
            'entidad_uuid' => $entidadUuid,
            'tipo_operacion' => $tipoOperacion,
            'datos' => $datos,
            'timestamp' => now()->toISOString(),
            'sincronizado' => false
        ];

        if ($this->isSQLiteAvailable()) {
            // ✅ CREAR TABLA SI NO EXISTE
            $this->createCambiosPendientesTable();
            
            DB::connection('offline')
                ->table('cambios_pendientes')
                ->insert($cambio);
        } else {
            // ✅ GUARDAR EN JSON
            $cambiosPath = $this->getStoragePath() . '/cambios_pendientes';
            if (!is_dir($cambiosPath)) {
                mkdir($cambiosPath, 0755, true);
            }
            
            $archivo = $cambiosPath . '/' . $cambio['uuid'] . '.json';
            file_put_contents($archivo, json_encode($cambio, JSON_PRETTY_PRINT));
        }

        Log::info('✅ Cambio guardado para sincronización', [
            'cambio_uuid' => $cambio['uuid'],
            'entidad_uuid' => $entidadUuid,
            'tipo' => $tipoOperacion
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error guardando cambio para sincronizar', [
            'error' => $e->getMessage(),
            'entidad_uuid' => $entidadUuid
        ]);
    }
}

/**
 * ✅ MÉTODO 3: CREAR TABLA DE CAMBIOS PENDIENTES
 */
private function createCambiosPendientesTable(): void
{
    try {
        DB::connection('offline')->statement('
            CREATE TABLE IF NOT EXISTS cambios_pendientes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                entidad_uuid TEXT NOT NULL,
                tipo_operacion TEXT NOT NULL,
                datos TEXT,
                timestamp DATETIME NOT NULL,
                sincronizado BOOLEAN DEFAULT FALSE,
                fecha_sincronizacion DATETIME NULL,
                intentos INTEGER DEFAULT 0,
                ultimo_error TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
        
        // Crear índices
        DB::connection('offline')->statement('
            CREATE INDEX IF NOT EXISTS idx_cambios_pendientes_entidad ON cambios_pendientes(entidad_uuid)
        ');
        
        DB::connection('offline')->statement('
            CREATE INDEX IF NOT EXISTS idx_cambios_pendientes_sincronizado ON cambios_pendientes(sincronizado)
        ');
        
    } catch (\Exception $e) {
        Log::error('❌ Error creando tabla cambios_pendientes', [
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * ✅ MÉTODO 4: OBTENER CAMBIOS PENDIENTES DE SINCRONIZACIÓN
 */
public function getCambiosPendientesSincronizacion()
{
    try {
        $cambios = [];

        if ($this->isSQLiteAvailable()) {
            $results = DB::connection('offline')
                ->table('cambios_pendientes')
                ->where('sincronizado', false)
                ->orderBy('timestamp')
                ->get();
                
            $cambios = $results->map(function($item) {
                $cambio = (array) $item;
                if (isset($cambio['datos']) && is_string($cambio['datos'])) {
                    $cambio['datos'] = json_decode($cambio['datos'], true);
                }
                return $cambio;
            })->toArray();
        } else {
            // ✅ LEER DESDE JSON
            $cambiosPath = $this->getStoragePath() . '/cambios_pendientes';
            if (is_dir($cambiosPath)) {
                $files = glob($cambiosPath . '/*.json');
                foreach ($files as $file) {
                    $data = json_decode(file_get_contents($file), true);
                    if ($data && !($data['sincronizado'] ?? false)) {
                        $cambios[] = $data;
                    }
                }
                
                // ✅ ORDENAR POR TIMESTAMP
                usort($cambios, function($a, $b) {
                    return strcmp($a['timestamp'] ?? '', $b['timestamp'] ?? '');
                });
            }
        }

        Log::info('📋 Cambios pendientes obtenidos', [
            'total' => count($cambios)
        ]);

        return $cambios;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo cambios pendientes', [
            'error' => $e->getMessage()
        ]);
        return [];
    }
}

/**
 * ✅ MÉTODO 5: MARCAR CAMBIO COMO SINCRONIZADO
 */
public function marcarCambioComoSincronizado($cambioUuid)
{
    try {
        if ($this->isSQLiteAvailable()) {
            DB::connection('offline')
                ->table('cambios_pendientes')
                ->where('uuid', $cambioUuid)
                ->update([
                    'sincronizado' => true,
                    'fecha_sincronizacion' => now()->toISOString(),
                    'updated_at' => now()->toISOString()
                ]);
        } else {
            // ✅ ACTUALIZAR JSON
            $cambiosPath = $this->getStoragePath() . '/cambios_pendientes';
            $archivo = $cambiosPath . '/' . $cambioUuid . '.json';
            
            if (file_exists($archivo)) {
                $data = json_decode(file_get_contents($archivo), true);
                $data['sincronizado'] = true;
                $data['fecha_sincronizacion'] = now()->toISOString();
                file_put_contents($archivo, json_encode($data, JSON_PRETTY_PRINT));
            }
        }

        Log::info('✅ Cambio marcado como sincronizado', [
            'cambio_uuid' => $cambioUuid
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error marcando cambio como sincronizado', [
            'error' => $e->getMessage(),
            'cambio_uuid' => $cambioUuid
        ]);
    }
}

/**
 * ✅ MÉTODO CORREGIDO: SINCRONIZAR CAMBIOS DE ESTADO PENDIENTES
 */
public function sincronizarCambiosEstadoPendientes(): array
{
    try {
        Log::info('🔄 Iniciando sincronización de cambios de estado');
        
        $results = [
            'success' => 0,
            'errors' => 0,
            'details' => []
        ];

        $cambiosPendientes = $this->getCambiosPendientesSincronizacion();
        
        if (empty($cambiosPendientes)) {
            return [
                'success' => true,
                'message' => 'No hay cambios pendientes',
                'synced_count' => 0,
                'failed_count' => 0
            ];
        }

        $apiService = app(ApiService::class);
        
        foreach ($cambiosPendientes as $cambio) {
            try {
                if ($cambio['tipo_operacion'] === 'estado_actualizado') {
                    $citaUuid = $cambio['entidad_uuid'];
                      // ✅ OBTENER UUID REAL DE LA CITA (puede haber cambiado)
                    $citaReal = DB::connection('offline')
                        ->table('citas')
                        ->where('uuid', $citaUuidOffline)
                        ->first();
                          if (!$citaReal) {
                        Log::error('❌ Cita no encontrada en SQLite', [
                            'uuid_offline' => $citaUuidOffline
                        ]);
                        $results['errors']++;
                        continue;
                    }
                    
                    $citaUuid = $citaReal->uuid; // ✅ Usar UUID actualizado
                    
                    Log::info('📡 Sincronizando cambio de estado', [
                        'cita_uuid_offline' => $citaUuidOffline,
                        'cita_uuid_real' => $citaUuid,
                        'nuevo_estado' => $nuevoEstado
                    ]);
                    
                    // ✅ VALIDAR UUID CRÍTICO
                    if (empty($citaUuid) || !is_string($citaUuid) || strlen(trim($citaUuid)) === 0) {
                        Log::error('❌ UUID de cita vacío o inválido en sincronización', [
                            'cambio_uuid' => $cambio['uuid'] ?? 'N/A',
                            'cita_uuid' => $citaUuid,
                            'type' => gettype($citaUuid),
                            'length' => is_string($citaUuid) ? strlen($citaUuid) : 'N/A'
                        ]);
                        
                        $results['errors']++;
                        $results['details'][] = [
                            'cambio_uuid' => $cambio['uuid'] ?? 'N/A',
                            'cita_uuid' => $citaUuid,
                            'status' => 'error',
                            'error' => 'UUID de cita vacío o inválido'
                        ];
                        continue;
                    }
                    
                    // ✅ LIMPIAR UUID
                    $citaUuid = trim($citaUuid);
                    
                    // ✅ OBTENER DATOS DEL CAMBIO
                    $datos = $cambio['datos'];
                    if (is_string($datos)) {
                        $datos = json_decode($datos, true) ?? [];
                    }
                    
                    $nuevoEstado = $datos['nuevo_estado'] ?? null;
                    
                    if (empty($nuevoEstado)) {
                        Log::error('❌ Estado nuevo vacío en sincronización', [
                            'cambio_uuid' => $cambio['uuid'],
                            'datos' => $datos
                        ]);
                        
                        $results['errors']++;
                        continue;
                    }
                    
                    Log::info('📡 Sincronizando cambio de estado', [
                        'cita_uuid' => $citaUuid,
                        'nuevo_estado' => $nuevoEstado,
                        'cambio_uuid' => $cambio['uuid']
                    ]);

                    // ✅ INTENTAR SINCRONIZAR CON DIFERENTES ENDPOINTS
                    $success = false;
                    $lastError = null;
                    
                    // ✅ ENDPOINT 1: PUT /citas/{uuid}/estado
                    try {
                        Log::info('🔄 Probando endpoint PUT /citas/{uuid}/estado');
                        
                        $response = $apiService->put("/citas/{$citaUuid}/estado", [
                            'estado' => $nuevoEstado
                        ]);

                        if ($response['success']) {
                            $success = true;
                            Log::info('✅ Sincronización exitosa con PUT /estado');
                        } else {
                            $lastError = $response['error'] ?? 'Error desconocido';
                            Log::warning('⚠️ PUT /estado falló', ['error' => $lastError]);
                        }
                        
                    } catch (\Exception $e) {
                        $lastError = $e->getMessage();
                        Log::warning('⚠️ Excepción en PUT /estado', ['error' => $lastError]);
                    }
                    
                    // ✅ ENDPOINT 2: PATCH /citas/{uuid}
                    if (!$success) {
                        try {
                            Log::info('🔄 Probando endpoint PATCH /citas/{uuid}');
                            
                            $response = $apiService->patch("/citas/{$citaUuid}", [
                                'estado' => $nuevoEstado
                            ]);

                            if ($response['success']) {
                                $success = true;
                                Log::info('✅ Sincronización exitosa con PATCH /citas');
                            } else {
                                $lastError = $response['error'] ?? 'Error desconocido';
                                Log::warning('⚠️ PATCH /citas falló', ['error' => $lastError]);
                            }
                            
                        } catch (\Exception $e) {
                            $lastError = $e->getMessage();
                            Log::warning('⚠️ Excepción en PATCH /citas', ['error' => $lastError]);
                        }
                    }

                    if ($success) {
                        $this->marcarCambioComoSincronizado($cambio['uuid']);
                        $results['success']++;
                        $results['details'][] = [
                            'cambio_uuid' => $cambio['uuid'],
                            'cita_uuid' => $citaUuid,
                            'status' => 'success'
                        ];
                        
                        Log::info('✅ Cambio de estado sincronizado', [
                            'cita_uuid' => $citaUuid,
                            'estado' => $nuevoEstado
                        ]);
                    } else {
                        $results['errors']++;
                        $results['details'][] = [
                            'cambio_uuid' => $cambio['uuid'],
                            'cita_uuid' => $citaUuid,
                            'status' => 'error',
                            'error' => $lastError ?? 'Todos los endpoints fallaron'
                        ];
                        
                        Log::error('❌ Error sincronizando cambio de estado', [
                            'cita_uuid' => $citaUuid,
                            'error' => $lastError ?? 'Todos los endpoints fallaron'
                        ]);
                    }
                }

            } catch (\Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'cambio_uuid' => $cambio['uuid'] ?? 'unknown',
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
                
                Log::error('❌ Excepción sincronizando cambio', [
                    'cambio_uuid' => $cambio['uuid'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'success' => true,
            'message' => "Sincronización completada: {$results['success']} exitosas, {$results['errors']} errores",
            'synced_count' => $results['success'],
            'failed_count' => $results['errors'],
            'details' => $results['details']
        ];

    } catch (\Exception $e) {
        Log::error('💥 Error crítico en sincronización de cambios', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'error' => 'Error crítico: ' . $e->getMessage(),
            'synced_count' => 0,
            'failed_count' => 0
        ];
    }
}

/**
 * ✅ MÉTODO 7: OBTENER CONTEO DE CAMBIOS PENDIENTES
 */
public function getConteoEstadosPendientes(): int
{
    try {
        $count = 0;

        if ($this->isSQLiteAvailable()) {
            $count = DB::connection('offline')
                ->table('cambios_pendientes')
                ->where('sincronizado', false)
                ->where('tipo_operacion', 'estado_actualizado')
                ->count();
        } else {
            $cambiosPath = $this->getStoragePath() . '/cambios_pendientes';
            if (is_dir($cambiosPath)) {
                $files = glob($cambiosPath . '/*.json');
                foreach ($files as $file) {
                    $data = json_decode(file_get_contents($file), true);
                    if ($data && 
                        !($data['sincronizado'] ?? false) && 
                        ($data['tipo_operacion'] ?? '') === 'estado_actualizado') {
                        $count++;
                    }
                }
            }
        }

        return $count;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo conteo de estados pendientes', [
            'error' => $e->getMessage()
        ]);
        return 0;
    }
}
/**
 * ✅ CORREGIDO: Obtener agenda offline CON DATOS DE PROCESO ENRIQUECIDOS
 */
public function getAgendaOffline(string $uuid): ?array
{
    try {
        Log::info('🔍 OfflineService: Buscando agenda offline', [
            'agenda_uuid' => $uuid
        ]);

        $agenda = null;

        // ✅ BUSCAR EN SQLite PRIMERO
        if ($this->isSQLiteAvailable()) {
            $result = DB::connection('offline')->table('agendas')
                ->where('uuid', $uuid)
                ->whereNull('deleted_at')
                ->first();

            if ($result) {
                $agenda = (array) $result;
                
                // ✅ DECODIFICAR original_data SI EXISTE
                if (isset($agenda['original_data']) && is_string($agenda['original_data'])) {
                    $originalData = json_decode($agenda['original_data'], true);
                    
                    if ($originalData) {
                        // ✅ SI original_data TIENE EL PROCESO, USARLO
                        if (isset($originalData['proceso'])) {
                            $agenda['proceso'] = $originalData['proceso'];
                            
                            Log::info('✅ Proceso extraído de original_data', [
                                'proceso_id' => $agenda['proceso']['id'] ?? 'N/A',
                                'proceso_nombre' => $agenda['proceso']['nombre'] ?? 'N/A',
                                'proceso_n_cups' => $agenda['proceso']['n_cups'] ?? 'N/A'
                            ]);
                        }
                    }
                }
                
                Log::info('✅ OfflineService: Agenda encontrada en SQLite', [
                    'agenda_uuid' => $uuid,
                    'agenda_fecha' => $agenda['fecha'] ?? 'NO_FECHA',
                    'etiqueta' => $agenda['etiqueta'] ?? 'NO_ETIQUETA',
                    'proceso_id' => $agenda['proceso_id'] ?? 'NO_PROCESO_ID',
                    'proceso_uuid' => $agenda['proceso_uuid'] ?? 'NO_PROCESO_UUID',
                    'tiene_proceso' => isset($agenda['proceso'])
                ]);
            }
        }

        // ✅ FALLBACK A JSON
        if (!$agenda) {
            $filePath = $this->storagePath . '/agendas/' . $uuid . '.json';
            
            if (file_exists($filePath)) {
                $agenda = json_decode(file_get_contents($filePath), true);
                
                Log::info('✅ OfflineService: Agenda encontrada en JSON', [
                    'agenda_uuid' => $uuid,
                    'tiene_proceso' => isset($agenda['proceso'])
                ]);
            }
        }

        if (!$agenda) {
            Log::warning('⚠️ OfflineService: Agenda no encontrada offline', [
                'agenda_uuid' => $uuid
            ]);
            return null;
        }

        // ✅ SI AÚN NO TIENE PROCESO, INTENTAR BUSCARLO
        if (!isset($agenda['proceso'])) {
            $proceso = null;
            
            // ✅ CONVERTIR proceso_id A INTEGER
            $procesoId = !empty($agenda['proceso_id']) && $agenda['proceso_id'] !== 'null' 
                ? (int) $agenda['proceso_id'] 
                : null;
            
            $procesoUuid = !empty($agenda['proceso_uuid']) && $agenda['proceso_uuid'] !== 'null' 
                ? $agenda['proceso_uuid'] 
                : null;
            
            Log::info('🔍 Intentando enriquecer proceso', [
                'proceso_id_original' => $agenda['proceso_id'] ?? 'null',
                'proceso_id_convertido' => $procesoId,
                'proceso_uuid' => $procesoUuid
            ]);
            
            // ✅ PRIORIDAD 1: Buscar por UUID
            if ($procesoUuid) {
                $proceso = $this->getProcesoByUuid($procesoUuid);
                
                if ($proceso) {
                    Log::info('✅ Proceso encontrado por UUID', [
                        'proceso_uuid' => $procesoUuid,
                        'proceso_nombre' => $proceso['nombre']
                    ]);
                } else {
                    Log::warning('⚠️ Proceso no encontrado por UUID', [
                        'proceso_uuid' => $procesoUuid
                    ]);
                }
            }
            
            // ✅ PRIORIDAD 2: Buscar por ID (FALLBACK)
            if (!$proceso && $procesoId) {
                Log::info('🔍 Buscando proceso por ID', [
                    'proceso_id' => $procesoId
                ]);
                
                $proceso = $this->getProcesoById($procesoId);
                
                if ($proceso) {
                    Log::info('✅ Proceso encontrado por ID', [
                        'proceso_id' => $procesoId,
                        'proceso_nombre' => $proceso['nombre']
                    ]);
                } else {
                    Log::error('❌ Proceso no encontrado por ID', [
                        'proceso_id' => $procesoId
                    ]);
                }
            }
            
            // ✅ AGREGAR PROCESO A LA AGENDA
            if ($proceso) {
                $agenda['proceso'] = $proceso;
                Log::info('✅ Proceso agregado a la agenda', [
                    'proceso_uuid' => $proceso['uuid'],
                    'proceso_nombre' => $proceso['nombre'],
                    'proceso_n_cups' => $proceso['n_cups']
                ]);
            } else {
                Log::error('❌ NO SE PUDO ENCONTRAR EL PROCESO', [
                    'agenda_uuid' => $uuid,
                    'proceso_id' => $procesoId,
                    'proceso_uuid' => $procesoUuid
                ]);
            }
        }

        // ✅ ENRIQUECER CON BRIGADA (PRIORIDAD: UUID)
        if (!isset($agenda['brigada']) && !empty($agenda['brigada_uuid']) && $agenda['brigada_uuid'] !== 'null') {
            $brigada = $this->getBrigadaByUuid($agenda['brigada_uuid']);
            if ($brigada) {
                $agenda['brigada'] = $brigada;
                Log::info('✅ Brigada agregada a la agenda', [
                    'brigada_uuid' => $brigada['uuid'],
                    'brigada_nombre' => $brigada['nombre']
                ]);
            }
        }

        // ✅ ENRIQUECER CON DATOS DE USUARIO (MÉDICO)
        if (!empty($agenda['usuario_medico_id']) && !isset($agenda['usuario_medico'])) {
            $usuarioMedico = $this->getUsuarioOffline($agenda['usuario_medico_id']);
            if ($usuarioMedico) {
                $agenda['usuario_medico'] = $usuarioMedico;
                Log::info('✅ Usuario médico agregado a la agenda', [
                    'usuario_uuid' => $usuarioMedico['uuid'],
                    'usuario_nombre' => $usuarioMedico['nombre_completo']
                ]);
            }
        }

        return $agenda;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo agenda offline', [
            'error' => $e->getMessage(),
            'uuid' => $uuid,
            'trace' => $e->getTraceAsString()
        ]);
        
        return null;
    }
}
public function getProcesoByUuid(string $uuid): ?array
{
    try {
        if ($this->isSQLiteAvailable()) {
            $result = DB::connection('offline')->table('procesos')
                ->where('uuid', $uuid)
                ->first();
            
            if ($result) {
                return [
                    'id' => $result->id,
                    'uuid' => $result->uuid,
                    'nombre' => $result->nombre,
                    'n_cups' => $result->n_cups, // ← AGREGADO
                    'created_at' => $result->created_at,
                    'updated_at' => $result->updated_at,
                    'deleted_at' => $result->deleted_at ?? null
                ];
            }
        }
        
        // Fallback a JSON
        $procesosPath = $this->storagePath . '/procesos';
        if (is_dir($procesosPath)) {
            $files = glob($procesosPath . '/*.json');
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($data && ($data['uuid'] ?? null) === $uuid) {
                    return $data;
                }
            }
        }
        
        return null;
        
    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo proceso por UUID', [
            'uuid' => $uuid,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

/**
 * ✅ CORREGIDO: Obtener proceso por ID con logging detallado
 */
public function getProcesoById($id): ?array
{
    try {
        $id = (int) $id; // Asegurar que sea integer
        
        Log::info('🔍 getProcesoById: Iniciando búsqueda', [
            'proceso_id' => $id,
            'sqlite_available' => $this->isSQLiteAvailable()
        ]);
        
        if ($this->isSQLiteAvailable()) {
            // ✅ VERIFICAR SI LA TABLA EXISTE
            $tableExists = DB::connection('offline')
                ->select("SELECT name FROM sqlite_master WHERE type='table' AND name='procesos'");
            
            Log::info('🔍 Verificando tabla procesos', [
                'table_exists' => !empty($tableExists)
            ]);
            
            if (empty($tableExists)) {
                Log::error('❌ Tabla procesos NO EXISTE en SQLite');
                return null;
            }
            
            // ✅ CONTAR REGISTROS
            $count = DB::connection('offline')->table('procesos')->count();
            Log::info('📊 Total de procesos en SQLite', ['count' => $count]);
            
            // ✅ BUSCAR EL PROCESO
            $result = DB::connection('offline')->table('procesos')
                ->where('id', $id)
                ->first();
            
            Log::info('🔍 Resultado de búsqueda por ID', [
                'proceso_id' => $id,
                'found' => $result !== null,
                'result' => $result ? (array) $result : null
            ]);
            
            if ($result) {
                $proceso = [
                    'id' => $result->id,
                    'uuid' => $result->uuid,
                    'nombre' => $result->nombre,
                    'n_cups' => $result->n_cups,
                    'created_at' => $result->created_at,
                    'updated_at' => $result->updated_at,
                    'deleted_at' => $result->deleted_at ?? null
                ];
                
                Log::info('✅ Proceso encontrado en SQLite', [
                    'proceso_id' => $proceso['id'],
                    'proceso_nombre' => $proceso['nombre'],
                    'proceso_n_cups' => $proceso['n_cups']
                ]);
                
                return $proceso;
            } else {
                Log::warning('⚠️ Proceso no encontrado en SQLite', [
                    'proceso_id' => $id
                ]);
            }
        }
        
        // ✅ FALLBACK A JSON
        Log::info('🔍 Buscando proceso en archivos JSON');
        
        $procesosPath = $this->storagePath . '/procesos';
        
        if (!is_dir($procesosPath)) {
            Log::warning('⚠️ Directorio de procesos no existe', [
                'path' => $procesosPath
            ]);
            return null;
        }
        
        $files = glob($procesosPath . '/*.json');
        Log::info('📁 Archivos JSON encontrados', [
            'count' => count($files),
            'path' => $procesosPath
        ]);
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && ($data['id'] ?? null) == $id) {
                Log::info('✅ Proceso encontrado en JSON', [
                    'file' => basename($file),
                    'proceso_id' => $data['id'],
                    'proceso_nombre' => $data['nombre']
                ]);
                return $data;
            }
        }
        
        Log::error('❌ Proceso no encontrado en ningún lado', [
            'proceso_id' => $id,
            'sqlite_checked' => $this->isSQLiteAvailable(),
            'json_files_checked' => count($files ?? [])
        ]);
        
        return null;
        
    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo proceso por ID', [
            'id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return null;
    }
}


/**
 * ✅ NUEVO: Obtener brigada por UUID
 */
public function getBrigadaByUuid(string $uuid): ?array
{
    try {
        if ($this->isSQLiteAvailable()) {
            $result = DB::connection('offline')->table('brigadas')
                ->where('uuid', $uuid)
                ->first();
            
            if ($result) {
                return (array) $result;
            }
        }
        
        return null;
        
    } catch (\Exception $e) {
        return null;
    }
}
/**
 * ✅ NUEVO: Obtener proceso offline por ID
 */
public function getProcesoOffline($procesoId): ?array
{
    try {
        Log::info('🔍 Buscando proceso offline', [
            'proceso_id' => $procesoId
        ]);

        $proceso = null;

        // ✅ BUSCAR EN SQLite PRIMERO
        if ($this->isSQLiteAvailable()) {
            $result = DB::connection('offline')->table('procesos')
                ->where('id', $procesoId)
                ->first();

            if ($result) {
                $proceso = (array) $result;
                
                Log::info('✅ Proceso encontrado en SQLite', [
                    'proceso_id' => $procesoId,
                    'proceso_nombre' => $proceso['nombre'] ?? 'NO_NOMBRE'
                ]);
                
                return $proceso;
            }
        }

        // ✅ FALLBACK A JSON
        $procesosPath = $this->storagePath . '/procesos';
        if (is_dir($procesosPath)) {
            $files = glob($procesosPath . '/*.json');
            
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                
                if ($data && ($data['id'] ?? null) == $procesoId) {
                    Log::info('✅ Proceso encontrado en JSON', [
                        'proceso_id' => $procesoId,
                        'proceso_nombre' => $data['nombre'] ?? 'NO_NOMBRE'
                    ]);
                    
                    return $data;
                }
            }
        }

        Log::warning('⚠️ Proceso no encontrado offline', [
            'proceso_id' => $procesoId
        ]);

        return null;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo proceso offline', [
            'error' => $e->getMessage(),
            'proceso_id' => $procesoId
        ]);
        
        return null;
    }
}

public function getCitaOffline(string $uuid): ?array
{
    try {
        Log::info('🔍 Obteniendo cita offline con datos relacionados', [
            'uuid' => $uuid
        ]);

        $cita = null;

        // ✅ BUSCAR EN SQLite PRIMERO
        if ($this->isSQLiteAvailable()) {
            $citaRaw = DB::connection('offline')->table('citas')
                ->where('uuid', $uuid)
                ->whereNull('deleted_at')
                ->first();
            
            if ($citaRaw) {
                $cita = (array) $citaRaw;
                Log::info('✅ Cita encontrada en SQLite', [
                    'uuid' => $cita['uuid'],
                    'paciente_uuid' => $cita['paciente_uuid'] ?? 'null',
                    'agenda_uuid' => $cita['agenda_uuid'] ?? 'null'
                ]);
            }
        }

        // ✅ FALLBACK A JSON SI NO SE ENCUENTRA EN SQLite
        if (!$cita) {
            $cita = $this->getData('citas/' . $uuid . '.json');
            
            if ($cita) {
                Log::info('✅ Cita encontrada en JSON', [
                    'uuid' => $cita['uuid']
                ]);
            }
        }

        if (!$cita) {
            Log::warning('⚠️ Cita no encontrada offline', ['uuid' => $uuid]);
            return null;
        }

        // ✅ ENRIQUECER CON DATOS DEL PACIENTE
        if (!empty($cita['paciente_uuid'])) {
            $paciente = $this->getPacienteOffline($cita['paciente_uuid']);
            if ($paciente) {
                $cita['paciente'] = $paciente;
                Log::info('✅ Datos del paciente agregados', [
                    'paciente_uuid' => $paciente['uuid'],
                    'paciente_nombre' => $paciente['nombre_completo'] ?? 'N/A'
                ]);
            } else {
                // ✅ PACIENTE POR DEFECTO SI NO SE ENCUENTRA
                $cita['paciente'] = [
                    'uuid' => $cita['paciente_uuid'],
                    'nombre_completo' => 'Paciente no encontrado',
                    'documento' => 'N/A',
                    'telefono' => 'N/A'
                ];
                Log::warning('⚠️ Paciente no encontrado, usando datos por defecto', [
                    'paciente_uuid' => $cita['paciente_uuid']
                ]);
            }
        }

        // ✅ ENRIQUECER CON DATOS DE LA AGENDA
        if (!empty($cita['agenda_uuid'])) {
            $agenda = $this->getAgendaOffline($cita['agenda_uuid']);
            if ($agenda) {
                $cita['agenda'] = $agenda;
                Log::info('✅ Datos de la agenda agregados', [
                    'agenda_uuid' => $agenda['uuid'],
                    'consultorio' => $agenda['consultorio'] ?? 'N/A',
                    'modalidad' => $agenda['modalidad'] ?? 'N/A'
                ]);
            } else {
                // ✅ AGENDA POR DEFECTO SI NO SE ENCUENTRA
                $cita['agenda'] = [
                    'uuid' => $cita['agenda_uuid'],
                    'consultorio' => 'Consultorio no disponible',
                    'modalidad' => 'No disponible',
                    'etiqueta' => 'No disponible'
                ];
                Log::warning('⚠️ Agenda no encontrada, usando datos por defecto', [
                    'agenda_uuid' => $cita['agenda_uuid']
                ]);
            }
        }

      if (!empty($cita['usuario_creo_cita_id'])) {
    Log::info('🔍 Buscando usuario creador', [
        'usuario_creo_cita_id' => $cita['usuario_creo_cita_id']
    ]);
    
    // Buscar en usuarios offline
    $usuario = $this->getUsuarioOffline($cita['usuario_creo_cita_id']);
    if ($usuario) {
        $cita['usuario_creador'] = [
            'id' => $usuario['id'] ?? null,
            'uuid' => $usuario['uuid'] ?? null,
            'nombre_completo' => $usuario['nombre_completo'] ?? 'Usuario del sistema',
            'documento' => $usuario['documento'] ?? null,
            'especialidad' => [
                'nombre' => $usuario['especialidad_nombre'] ?? 'Sin especialidad'
            ]
        ];
        
        Log::info('✅ Usuario creador encontrado', [
            'nombre_completo' => $usuario['nombre_completo']
        ]);
    } else {
        $cita['usuario_creador'] = [
            'id' => $cita['usuario_creo_cita_id'],
            'nombre_completo' => 'Usuario del sistema'
        ];
        
        Log::warning('⚠️ Usuario creador no encontrado, usando por defecto');
    }
} else {
    Log::info('ℹ️ No hay usuario_creo_cita_id en la cita');
}

        // ✅ ENRIQUECER CON DATOS DE LA SEDE
        if (!empty($cita['sede_id'])) {
            $cita['sede'] = [
                'id' => $cita['sede_id'],
                'nombre' => 'Cajibio' // Por defecto
            ];
        }

        // ✅ AGREGAR ESTADO DE SINCRONIZACIÓN
        $cita['offline'] = ($cita['sync_status'] ?? 'synced') === 'pending';

        Log::info('✅ Cita offline enriquecida completamente', [
            'uuid' => $cita['uuid'],
            'has_paciente' => isset($cita['paciente']),
            'has_agenda' => isset($cita['agenda']),
            'has_usuario_creador' => isset($cita['usuario_creador']),
            'sync_status' => $cita['sync_status'] ?? 'synced'
        ]);

        return $cita;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo cita offline', [
            'uuid' => $uuid,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return null;
    }
}

/**
 * Obtener usuario desde almacenamiento offline (SQLite o JSON)
 */
public function getUsuarioOffline(string $uuid): ?array
{
    try {
        $usuario = null;
        
        // ✅ BUSCAR EN SQLite PRIMERO
        if ($this->isSQLiteAvailable()) {
            $usuarioRaw = DB::connection('offline')->table('usuarios')
                ->where('uuid', $uuid)
                ->first();
            
            if ($usuarioRaw) {
                $usuario = (array) $usuarioRaw;
                Log::info('✅ Usuario encontrado en SQLite offline', [
                    'uuid' => $usuario['uuid'],
                    'nombre' => $usuario['nombre_completo']
                ]);
            }
        }
        
        // ✅ FALLBACK A JSON
        if (!$usuario) {
            $path = $this->storagePath . "/usuarios/{$uuid}.json";
            
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $usuario = json_decode($content, true);
                
                Log::info('✅ Usuario encontrado en JSON offline', [
                    'uuid' => $usuario['uuid'] ?? 'NO_UUID',
                    'nombre' => $usuario['nombre_completo'] ?? 'NO_NOMBRE'
                ]);
            }
        }

        if (!$usuario) {
            Log::info('⚠️ Usuario no encontrado offline', ['uuid' => $uuid]);
            return null;
        }

        // ✅ ENRIQUECER CON OBJETOS DE RELACIONES PARA COMPATIBILIDAD
        $usuario = $this->enrichUsuarioRelations($usuario);

        return $usuario;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo usuario offline', [
            'uuid' => $uuid,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

/**
 * Enriquecer relaciones del usuario para compatibilidad con vistas
 */
private function enrichUsuarioRelations(array $usuario): array
{
    // Convertir campos planos a objetos anidados
    if (isset($usuario['especialidad_id'])) {
        $usuario['especialidad'] = [
            'id' => $usuario['especialidad_id'],
            'uuid' => $usuario['especialidad_uuid'] ?? null,
            'nombre' => $usuario['especialidad_nombre'] ?? null,
        ];
    }

    if (isset($usuario['sede_id'])) {
        $usuario['sede'] = [
            'id' => $usuario['sede_id'],
            'nombre' => $usuario['sede_nombre'] ?? null,
        ];
    }

    // Convertir tiene_firma a booleano
    if (isset($usuario['tiene_firma'])) {
        $usuario['tiene_firma'] = (bool) $usuario['tiene_firma'];
    }

    return $usuario;
}




public function syncPendingAgendas(): array
{
    try {
        Log::info('🔄 Iniciando sincronización de agendas pendientes');
        
        $results = [
            'success' => 0,
            'errors' => 0,
            'details' => []
        ];

        if (!$this->isSQLiteAvailable()) {
            Log::warning('⚠️ SQLite no disponible para sincronización');
            return $results;
        }

        // ✅ VERIFICAR CONEXIÓN PRIMERO
        $apiService = app(ApiService::class);
        if (!$apiService->isOnline()) {
            Log::warning('⚠️ Sin conexión para sincronizar');
            return [
                'success' => false,
                'error' => 'Sin conexión al servidor',
                'synced_count' => 0,
                'failed_count' => 0
            ];
        }

        // Obtener agendas pendientes
        $pendingAgendas = DB::connection('offline')
            ->table('agendas')
            ->where('sync_status', 'pending')
            ->orWhere('sync_status', 'error')
            ->get();

        Log::info('📊 Agendas pendientes encontradas', [
            'count' => $pendingAgendas->count()
        ]);

        if ($pendingAgendas->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No hay agendas pendientes',
                'synced_count' => 0,
                'failed_count' => 0
            ];
        }

        foreach ($pendingAgendas as $agenda) {
            try {
                $agendaArray = (array) $agenda;
                $oldUuid = $agenda->uuid; // ✅ GUARDAR UUID ORIGINAL
                
                Log::info('📡 Procesando agenda para sincronización', [
                    'uuid' => $agenda->uuid,
                    'fecha' => $agenda->fecha,
                    'consultorio' => $agenda->consultorio,
                    'operation_type' => $agenda->operation_type ?? 'create'
                ]);

                // ✅ PREPARAR DATOS LIMPIOS PARA LA API
                $syncData = $this->prepareAgendaDataForSync($agendaArray);
                
                Log::info('📤 Datos preparados para API', [
                    'uuid' => $agenda->uuid,
                    'sync_data' => $syncData
                ]);

                // ✅ ENVIAR A LA API CON LOGGING DETALLADO
                $response = $apiService->post('/agendas', $syncData);
                
                // ✅ LOG COMPLETO DE LA RESPUESTA
                Log::info('📥 Respuesta completa de API', [
                    'uuid' => $agenda->uuid,
                    'response' => $response, // ← ESTO ES CLAVE
                    'success' => $response['success'] ?? false,
                    'error' => $response['error'] ?? null,
                    'status' => $response['status'] ?? null
                ]);

                if (isset($response['success']) && $response['success'] === true) {
                    // ✅ ÉXITO - Actualizar con datos de la API
                    $serverData = $response['data'] ?? [];
                    $newUuid = $serverData['uuid'] ?? $oldUuid;
                    
                    // ✅ ACTUALIZAR AGENDA EN SQLite
                    $updateData = [
                        'sync_status' => 'synced',
                        'synced_at' => now(),
                        'error_message' => null
                    ];
                    
                    // Si el servidor devolvió un ID, guardarlo
                    if (isset($serverData['id'])) {
                        $updateData['id'] = $serverData['id'];
                    }
                    
                    // Si el servidor devolvió un UUID diferente, actualizarlo
                    if (isset($serverData['uuid'])) {
                        $updateData['uuid'] = $serverData['uuid'];
                    }
                    
                    DB::connection('offline')
                        ->table('agendas')
                        ->where('uuid', $oldUuid)
                        ->update($updateData);
                    
                    // ✅ NUEVO: ACTUALIZAR CITAS QUE USAN EL UUID VIEJO
                    if ($oldUuid !== $newUuid) {
                        Log::info('🔄 UUID de agenda cambió, actualizando citas relacionadas', [
                            'old_uuid' => $oldUuid,
                            'new_uuid' => $newUuid
                        ]);
                        
                        $this->updateCitasAgendaUuid($oldUuid, $newUuid);
                    }
                    
                    $results['success']++;
                    $results['details'][] = [
                        'uuid' => $oldUuid,
                        'new_uuid' => $newUuid,
                        'status' => 'success',
                        'action' => 'created',
                        'uuid_changed' => $oldUuid !== $newUuid
                    ];
                    
                    Log::info('✅ Agenda sincronizada exitosamente', [
                        'old_uuid' => $oldUuid,
                        'new_uuid' => $newUuid,
                        'uuid_changed' => $oldUuid !== $newUuid
                    ]);
                    
                } else {
                    // ✅ ERROR - CAPTURAR DETALLES REALES
                    $errorMessage = 'Error desconocido';
                    
                    if (isset($response['error'])) {
                        $errorMessage = $response['error'];
                    } elseif (isset($response['message'])) {
                        $errorMessage = $response['message'];
                    } elseif (isset($response['errors'])) {
                        $errorMessage = is_array($response['errors']) 
                            ? json_encode($response['errors']) 
                            : $response['errors'];
                    }
                    
                    Log::error('❌ Error real de la API', [
                        'uuid' => $agenda->uuid,
                        'error_message' => $errorMessage,
                        'full_response' => $response
                    ]);
                    
                    // ✅ VERIFICAR SI ES ERROR DE DUPLICADO
                    $errorLower = strtolower($errorMessage);
                    if (str_contains($errorLower, 'ya existe') || 
                        str_contains($errorLower, 'duplicate') ||
                        str_contains($errorLower, 'already exists') ||
                        str_contains($errorLower, 'conflicto')) {
                        
                        // Marcar como sincronizado si ya existe
                        DB::connection('offline')
                            ->table('agendas')
                            ->where('uuid', $agenda->uuid)
                            ->update([
                                'sync_status' => 'synced',
                                'synced_at' => now(),
                                'error_message' => null
                            ]);
                        
                        $results['success']++;
                        $results['details'][] = [
                            'uuid' => $agenda->uuid,
                            'status' => 'success',
                            'action' => 'already_exists'
                        ];
                        
                        Log::info('✅ Agenda ya existía en servidor', [
                            'uuid' => $agenda->uuid
                        ]);
                    } else {
                        // Error real
                        DB::connection('offline')
                            ->table('agendas')
                            ->where('uuid', $agenda->uuid)
                            ->update([
                                'sync_status' => 'error',
                                'error_message' => $errorMessage
                            ]);
                        
                        $results['errors']++;
                        $results['details'][] = [
                            'uuid' => $agenda->uuid,
                            'status' => 'error',
                            'error' => $errorMessage
                        ];
                        
                        Log::error('❌ Error sincronizando agenda', [
                            'uuid' => $agenda->uuid,
                            'error' => $errorMessage
                        ]);
                    }
                }

            } catch (\Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'uuid' => $agenda->uuid ?? 'unknown',
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
                
                Log::error('❌ Excepción sincronizando agenda', [
                    'uuid' => $agenda->uuid ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        Log::info('🏁 Sincronización completada', [
            'success' => $results['success'],
            'errors' => $results['errors'],
            'total' => $pendingAgendas->count()
        ]);

        return [
            'success' => true,
            'message' => "Sincronización completada: {$results['success']} exitosas, {$results['errors']} errores",
            'synced_count' => $results['success'],
            'failed_count' => $results['errors'],
            'details' => $results['details']
        ];

    } catch (\Exception $e) {
        Log::error('💥 Error crítico en sincronización', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'error' => 'Error crítico: ' . $e->getMessage(),
            'synced_count' => 0,
            'failed_count' => 0
        ];
    }
}

/**
 * ✅ NUEVO: Actualizar UUID de agenda en citas relacionadas
 */
private function updateCitasAgendaUuid(string $oldUuid, string $newUuid): void
{
    try {
        Log::info('🔄 Actualizando citas con nuevo UUID de agenda', [
            'old_uuid' => $oldUuid,
            'new_uuid' => $newUuid
        ]);
        
        $updatedCount = 0;
        
        // ✅ ACTUALIZAR EN SQLite
        if ($this->isSQLiteAvailable()) {
            $updated = DB::connection('offline')
                ->table('citas')
                ->where('agenda_uuid', $oldUuid)
                ->update(['agenda_uuid' => $newUuid]);
            
            $updatedCount += $updated;
            
            Log::info('✅ Citas actualizadas en SQLite', [
                'updated_count' => $updated,
                'old_uuid' => $oldUuid,
                'new_uuid' => $newUuid
            ]);
        }
        
        // ✅ ACTUALIZAR ARCHIVOS JSON
        $citasPath = $this->storagePath . '/citas';
        if (is_dir($citasPath)) {
            $files = glob($citasPath . '/*.json');
            
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                
                if ($data && 
                    isset($data['agenda_uuid']) && 
                    $data['agenda_uuid'] === $oldUuid) {
                    
                    $data['agenda_uuid'] = $newUuid;
                    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
                    $updatedCount++;
                    
                    Log::info('✅ Archivo JSON de cita actualizado', [
                        'file' => basename($file),
                        'cita_uuid' => $data['uuid'],
                        'old_agenda_uuid' => $oldUuid,
                        'new_agenda_uuid' => $newUuid
                    ]);
                }
            }
        }
        
        Log::info('✅ Actualización de UUIDs completada', [
            'total_updated' => $updatedCount,
            'old_uuid' => $oldUuid,
            'new_uuid' => $newUuid
        ]);
        
    } catch (\Exception $e) {
        Log::error('❌ Error actualizando UUIDs de citas', [
            'old_uuid' => $oldUuid,
            'new_uuid' => $newUuid,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * ✅ NUEVO: Preparar datos para enviar a la API
 */
private function prepareAgendaDataForSync(array $agenda): array
{
    // ✅ USAR DATOS ORIGINALES SI ESTÁN DISPONIBLES
    if (!empty($agenda['original_data'])) {
        $originalData = json_decode($agenda['original_data'], true);
        if ($originalData) {
            Log::info('📋 Usando datos originales para sincronización', [
                'uuid' => $agenda['uuid'],
                'original_keys' => array_keys($originalData)
            ]);
            return $this->cleanDataForApi($originalData);
        }
    }

    return $this->cleanDataForApi($agenda);
}

/**
 * ✅ NUEVO: Limpiar datos para la API
 */
private function cleanDataForApi(array $data): array
{
    Log::info('🧹 Limpiando datos para API', [
        'original_data_keys' => array_keys($data),
        'proceso_id_original' => $data['proceso_id'] ?? 'no-set',
        'brigada_id_original' => $data['brigada_id'] ?? 'no-set',
        'usuario_medico_id_original' => $data['usuario_medico_id'] ?? 'no-set',
        'usuario_medico_uuid_original' => $data['usuario_medico_uuid'] ?? 'no-set',
        'intervalo_original' => $data['intervalo'] ?? 'no-set'
    ]);

    $cleanData = [
        'modalidad' => $data['modalidad'] ?? 'Ambulatoria',
        'fecha' => $data['fecha'],
        'consultorio' => (string) ($data['consultorio'] ?? ''),
        'hora_inicio' => $data['hora_inicio'],
        'hora_fin' => $data['hora_fin'],
        'intervalo' => (string) ($data['intervalo'] ?? '15'),
        'etiqueta' => $data['etiqueta'] ?? '',
        'estado' => $data['estado'] ?? 'ACTIVO',
        'sede_id' => (int) ($data['sede_id'] ?? 1),
        'usuario_id' => (int) ($data['usuario_id'] ?? 1)
    ];

    // ✅ MANEJAR proceso_id - PRIORIDAD: proceso_id > proceso_uuid
    if (isset($data['proceso_id']) && !empty($data['proceso_id']) && $data['proceso_id'] !== 'null') {
        if (is_numeric($data['proceso_id'])) {
            $cleanData['proceso_id'] = (int) $data['proceso_id'];
            Log::info('✅ proceso_id incluido como entero', [
                'original' => $data['proceso_id'],
                'clean' => $cleanData['proceso_id']
            ]);
        } elseif (is_string($data['proceso_id']) && $this->isValidUuid($data['proceso_id'])) {
            $cleanData['proceso_id'] = $data['proceso_id'];
            Log::info('✅ proceso_id incluido como UUID', [
                'original' => $data['proceso_id'],
                'clean' => $cleanData['proceso_id']
            ]);
        }
    } elseif (isset($data['proceso_uuid']) && !empty($data['proceso_uuid']) && $data['proceso_uuid'] !== 'null') {
        // Si no hay proceso_id pero sí proceso_uuid, usarlo
        $cleanData['proceso_id'] = $data['proceso_uuid'];
        Log::info('✅ proceso_id tomado de proceso_uuid', [
            'proceso_uuid' => $data['proceso_uuid'],
            'clean' => $cleanData['proceso_id']
        ]);
    } else {
        Log::warning('⚠️ No se encontró proceso_id ni proceso_uuid válido', [
            'proceso_id' => $data['proceso_id'] ?? 'no-existe',
            'proceso_uuid' => $data['proceso_uuid'] ?? 'no-existe'
        ]);
    }

    // ✅ MANEJAR brigada_id - PRIORIDAD: brigada_id > brigada_uuid
    if (isset($data['brigada_id']) && !empty($data['brigada_id']) && $data['brigada_id'] !== 'null' && $data['brigada_id'] !== 0) {
        if (is_numeric($data['brigada_id'])) {
            $cleanData['brigada_id'] = (int) $data['brigada_id'];
            Log::info('✅ brigada_id incluido como entero', [
                'original' => $data['brigada_id'],
                'clean' => $cleanData['brigada_id']
            ]);
        } elseif (is_string($data['brigada_id']) && $this->isValidUuid($data['brigada_id'])) {
            $cleanData['brigada_id'] = $data['brigada_id'];
            Log::info('✅ brigada_id incluido como UUID', [
                'original' => $data['brigada_id'],
                'clean' => $cleanData['brigada_id']
            ]);
        }
    } elseif (isset($data['brigada_uuid']) && !empty($data['brigada_uuid']) && $data['brigada_uuid'] !== 'null') {
        // Si no hay brigada_id pero sí brigada_uuid, usarlo (opcional)
        $cleanData['brigada_id'] = $data['brigada_uuid'];
        Log::info('✅ brigada_id tomado de brigada_uuid', [
            'brigada_uuid' => $data['brigada_uuid'],
            'clean' => $cleanData['brigada_id']
        ]);
    } else {
        // ✅ NO ENVIAR brigada_id si es null - el backend usará su valor por defecto
        Log::info('ℹ️ brigada_id no enviado (null o vacío), backend usará valor por defecto', [
            'brigada_id_original' => $data['brigada_id'] ?? 'no-existe'
        ]);
    }

    // ✅ MANEJAR usuario_medico - BUSCAR EN MÚLTIPLES CAMPOS Y CONVERTIR ID A UUID
    $usuarioMedicoValue = null;
    $foundInField = 'ninguno';
    
    // Prioridad: usuario_medico_uuid > usuario_medico_id > medico_uuid
    if (!empty($data['usuario_medico_uuid']) && $data['usuario_medico_uuid'] !== 'null') {
        $usuarioMedicoValue = $data['usuario_medico_uuid'];
        $foundInField = 'usuario_medico_uuid';
    } elseif (!empty($data['usuario_medico_id']) && $data['usuario_medico_id'] !== 'null') {
        // Si es un ID numérico, buscar el UUID correspondiente
        if (is_numeric($data['usuario_medico_id']) || (!$this->isValidUuid($data['usuario_medico_id']))) {
            $usuarioMedicoValue = $this->getUserUuidFromId($data['usuario_medico_id']);
            $foundInField = 'usuario_medico_id (convertido a UUID)';
            
            if (!$usuarioMedicoValue) {
                Log::warning('⚠️ No se pudo encontrar UUID para usuario_medico_id', [
                    'usuario_medico_id' => $data['usuario_medico_id']
                ]);
            }
        } else {
            // Ya es UUID
            $usuarioMedicoValue = $data['usuario_medico_id'];
            $foundInField = 'usuario_medico_id';
        }
    } elseif (!empty($data['medico_uuid']) && $data['medico_uuid'] !== 'null') {
        $usuarioMedicoValue = $data['medico_uuid'];
        $foundInField = 'medico_uuid';
    }
    
    if ($usuarioMedicoValue) {
        // ✅ ENVIAR AMBOS CAMPOS PARA COMPATIBILIDAD CON BACKEND
        $cleanData['usuario_medico_uuid'] = $usuarioMedicoValue;
        $cleanData['medico_uuid'] = $usuarioMedicoValue;
        
        Log::info('✅ usuario_medico_uuid agregado a datos de API', [
            'value' => $usuarioMedicoValue,
            'found_in_field' => $foundInField,
            'is_uuid' => $this->isValidUuid($usuarioMedicoValue),
            'campos_disponibles' => [
                'usuario_medico_uuid' => $data['usuario_medico_uuid'] ?? 'no-set',
                'usuario_medico_id' => $data['usuario_medico_id'] ?? 'no-set',
                'medico_uuid' => $data['medico_uuid'] ?? 'no-set'
            ]
        ]);
    } else {
        Log::warning('⚠️ No se encontró usuario médico en ningún campo', [
            'campos_revisados' => [
                'usuario_medico_uuid' => $data['usuario_medico_uuid'] ?? 'no-existe',
                'usuario_medico_id' => $data['usuario_medico_id'] ?? 'no-existe',
                'medico_uuid' => $data['medico_uuid'] ?? 'no-existe'
            ]
        ]);
    }

    Log::info('🧹 Datos finales limpiados para API', [
        'clean_data_keys' => array_keys($cleanData),
        'has_proceso_id' => isset($cleanData['proceso_id']),
        'has_brigada_id' => isset($cleanData['brigada_id']),
        'has_usuario_medico_uuid' => isset($cleanData['usuario_medico_uuid']),
        'usuario_medico_uuid_value' => $cleanData['usuario_medico_uuid'] ?? 'no-enviado',
        'intervalo_type' => gettype($cleanData['intervalo'])
    ]);

    return $cleanData;
}


private function getProcesoIdFromUuid(string $uuid): ?int
{
    try {
        if ($this->isSQLiteAvailable()) {
            $proceso = DB::connection('offline')
                ->table('procesos')
                ->where('uuid', $uuid)
                ->first();
            
            return $proceso ? $proceso->id : null;
        }
        
        // Fallback a JSON
        $masterData = $this->getData('master_data.json', []);
        if (isset($masterData['procesos'])) {
            foreach ($masterData['procesos'] as $proceso) {
                if ($proceso['uuid'] === $uuid) {
                    return isset($proceso['id']) ? (int) $proceso['id'] : null;
                }
            }
        }
        
        return null;
        
    } catch (\Exception $e) {
        Log::error('Error obteniendo proceso ID desde UUID', [
            'uuid' => $uuid,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}
public function storeCupsOffline(array $cupsData): void
{
    try {
        if (empty($cupsData['uuid']) || empty($cupsData['codigo'])) {
            Log::warning('⚠️ Intentando guardar CUPS sin UUID o código');
            return;
        }

        $offlineData = [
            'uuid' => $cupsData['uuid'],
            'origen' => $cupsData['origen'] ?? null,
            'nombre' => $cupsData['nombre'],
            'codigo' => $cupsData['codigo'],
            'estado' => $cupsData['estado'] ?? 'ACTIVO',
            'categoria' => $cupsData['categoria'] ?? null,
            'created_at' => $cupsData['created_at'] ?? now()->toISOString(),
            'updated_at' => now()->toISOString()
        ];

        if ($this->isSQLiteAvailable()) {
            DB::connection('offline')->table('cups')->updateOrInsert(
                ['uuid' => $cupsData['uuid']],
                $offlineData
            );
        }

        // También guardar en JSON como backup
        $this->storeData('cups/' . $cupsData['uuid'] . '.json', $offlineData);

        Log::debug('✅ CUPS almacenado offline', [
            'uuid' => $cupsData['uuid'],
            'codigo' => $cupsData['codigo'],
            'nombre' => substr($cupsData['nombre'], 0, 50) . '...'
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error almacenando CUPS offline', [
            'error' => $e->getMessage(),
            'uuid' => $cupsData['uuid'] ?? 'sin-uuid'
        ]);
    }
}

public function clearCups(): void
{
    try {
        $cupsPath = $this->storagePath . '/cups';
        
        if (is_dir($cupsPath)) {
            $files = glob($cupsPath . '/*.json');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            Log::info('🗑️ CUPS antiguos eliminados', ['count' => count($files)]);
        }
    } catch (\Exception $e) {
        Log::error('Error limpiando CUPS', ['error' => $e->getMessage()]);
    }
}

/**
 * Contar CUPS almacenados
 */
public function countCups(): int
{
    try {
        $cupsPath = $this->storagePath . '/cups';
        
        if (!is_dir($cupsPath)) {
            return 0;
        }
        
        $files = glob($cupsPath . '/*.json');
        return count($files);
    } catch (\Exception $e) {
        Log::error('Error contando CUPS', ['error' => $e->getMessage()]);
        return 0;
    }
}

/**
 * ⚡ Contar medicamentos almacenados
 */
public function countMedicamentos(): int
{
    try {
        if ($this->isSQLiteAvailable()) {
            return DB::connection('offline')->table('medicamentos')->count();
        }
        
        $medicamentosPath = $this->storagePath . '/medicamentos';
        if (!is_dir($medicamentosPath)) {
            return 0;
        }
        
        $files = glob($medicamentosPath . '/*.json');
        return count($files);
    } catch (\Exception $e) {
        Log::error('Error contando medicamentos', ['error' => $e->getMessage()]);
        return 0;
    }
}

/**
 * ⚡ Contar diagnósticos almacenados
 */
public function countDiagnosticos(): int
{
    try {
        if ($this->isSQLiteAvailable()) {
            return DB::connection('offline')->table('diagnosticos')->count();
        }
        
        $diagnosticosPath = $this->storagePath . '/diagnosticos';
        if (!is_dir($diagnosticosPath)) {
            return 0;
        }
        
        $files = glob($diagnosticosPath . '/*.json');
        return count($files);
    } catch (\Exception $e) {
        Log::error('Error contando diagnósticos', ['error' => $e->getMessage()]);
        return 0;
    }
}

/**
 * ⚡ Contar remisiones almacenadas
 */
public function countRemisiones(): int
{
    try {
        if ($this->isSQLiteAvailable()) {
            return DB::connection('offline')->table('remisiones')->count();
        }
        
        $remisionesPath = $this->storagePath . '/remisiones';
        if (!is_dir($remisionesPath)) {
            return 0;
        }
        
        $files = glob($remisionesPath . '/*.json');
        return count($files);
    } catch (\Exception $e) {
        Log::error('Error contando remisiones', ['error' => $e->getMessage()]);
        return 0;
    }
}

/**
 * ✅ BUSCAR CUPS OFFLINE
 */
public function buscarCupsOffline(string $termino, int $limit = 20): array
{
    try {
        Log::info('🔍 Búsqueda CUPS offline iniciada', [
            'termino' => $termino,
            'limit' => $limit
        ]);

        $cups = [];
        
        if ($this->isSQLiteAvailable()) {
            Log::info('💾 Usando SQLite para búsqueda CUPS');
            
            // Asegurar que la tabla existe
            $this->createCupsTable();
            
            // Verificar si hay datos
            $totalCups = DB::connection('offline')->table('cups')->count();
            Log::info('📊 Total CUPS en SQLite', ['count' => $totalCups]);
            
            if ($totalCups === 0) {
                Log::warning('⚠️ No hay CUPS en SQLite');
                return [];
            }

            $query = DB::connection('offline')->table('cups')
                ->where('estado', 'ACTIVO');
            
            // ✅ MEJORAR LA LÓGICA DE BÚSQUEDA
            if (is_numeric($termino)) {
                // Búsqueda por código numérico
                $query->where(function($q) use ($termino) {
                    $q->where('codigo', 'LIKE', $termino . '%')
                      ->orWhere('codigo', '=', $termino)
                      ->orWhere('codigo', 'LIKE', '%' . $termino . '%');
                });
                
                Log::info('🔢 Búsqueda por código numérico', ['termino' => $termino]);
            } else {
                // Búsqueda por nombre (texto)
                $termino = trim($termino);
                $query->where(function($q) use ($termino) {
                    $q->where('nombre', 'LIKE', '%' . $termino . '%')
                      ->orWhere('codigo', 'LIKE', '%' . $termino . '%');
                });
                
                Log::info('📝 Búsqueda por nombre/texto', ['termino' => $termino]);
            }

            $results = $query->limit($limit)
                ->orderBy('codigo')
                ->get();

            Log::info('📋 Resultados SQLite', [
                'encontrados' => $results->count(),
                'query_termino' => $termino
            ]);

            $cups = $results->map(function($cup) {
                return [
                    'uuid' => $cup->uuid,
                    'codigo' => $cup->codigo,
                    'nombre' => $cup->nombre,
                    'origen' => $cup->origen,
                    'estado' => $cup->estado,
                    'categoria' => $cup->categoria,
                    'created_at' => $cup->created_at,
                    'updated_at' => $cup->updated_at
                ];
            })->toArray();

        } else {
            Log::info('📁 Usando archivos JSON para búsqueda CUPS');
            
            // Fallback a JSON
            $cupsPath = $this->getStoragePath() . '/cups';
            if (is_dir($cupsPath)) {
                $files = glob($cupsPath . '/*.json');
                Log::info('📂 Archivos JSON encontrados', ['count' => count($files)]);
                
                foreach ($files as $file) {
                    $data = json_decode(file_get_contents($file), true);
                    if ($data && 
                        isset($data['estado']) && $data['estado'] === 'ACTIVO' &&
                        (stripos($data['codigo'] ?? '', $termino) !== false || 
                         stripos($data['nombre'] ?? '', $termino) !== false)) {
                        $cups[] = $data;
                        if (count($cups) >= $limit) break;
                    }
                }
            } else {
                Log::warning('⚠️ Directorio CUPS no existe', ['path' => $cupsPath]);
            }
        }

        Log::info('✅ Búsqueda CUPS offline completada', [
            'termino' => $termino,
            'resultados' => count($cups)
        ]);

        return $cups;

    } catch (\Exception $e) {
        Log::error('❌ Error buscando CUPS offline', [
            'error' => $e->getMessage(),
            'termino' => $termino,
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        return [];
    }
}

/**
 * ✅ OBTENER CUPS POR CÓDIGO EXACTO OFFLINE
 */
public function obtenerCupsPorCodigoOffline(string $codigo): ?array
{
    try {
        if ($this->isSQLiteAvailable()) {
            $cups = DB::connection('offline')->table('cups')
                ->where('codigo', $codigo)
                ->where('estado', 'ACTIVO')
                ->first();
            
            return $cups ? (array) $cups : null;
        } else {
            // Fallback a JSON
            $cupsPath = $this->getStoragePath() . '/cups';
            if (is_dir($cupsPath)) {
                $files = glob($cupsPath . '/*.json');
                foreach ($files as $file) {
                    $data = json_decode(file_get_contents($file), true);
                    if ($data && 
                        $data['codigo'] === $codigo && 
                        $data['estado'] === 'ACTIVO') {
                        return $data;
                    }
                }
            }
        }

        return null;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo CUPS por código offline', [
            'error' => $e->getMessage(),
            'codigo' => $codigo
        ]);
        return null;
    }
}

/**
 * ✅ OBTENER CUPS ACTIVOS OFFLINE
 */
public function obtenerCupsActivosOffline(): array
{
    try {
        $cups = [];

        if ($this->isSQLiteAvailable()) {
            $cups = DB::connection('offline')->table('cups')
                ->where('estado', 'ACTIVO')
                ->orderBy('codigo')
                ->get()
                ->map(function ($item) {
                    return (array) $item;
                })
                ->toArray();
        } else {
            // Fallback a JSON
            $cupsPath = $this->getStoragePath() . '/cups';
            if (is_dir($cupsPath)) {
                $files = glob($cupsPath . '/*.json');
                foreach ($files as $file) {
                    $data = json_decode(file_get_contents($file), true);
                    if ($data && $data['estado'] === 'ACTIVO') {
                        $cups[] = $data;
                    }
                }
                
                // Ordenar por código
                usort($cups, function ($a, $b) {
                    return strcmp($a['codigo'], $b['codigo']);
                });
            }
        }

        return $cups;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo CUPS activos offline', [
            'error' => $e->getMessage()
        ]);
        return [];
    }
}
/**
 * ✅ SINCRONIZAR CUPS DESDE API - CORREGIDO
 */
public function syncCupsFromApi(array $cupsList): bool
{
    try {
        Log::info('🔄 Sincronizando CUPS offline', [
            'count' => count($cupsList),
            'tipo_datos' => gettype($cupsList),
            'primer_elemento' => !empty($cupsList) ? gettype($cupsList[0] ?? 'vacio') : 'array_vacio'
        ]);

        // ✅ VALIDAR QUE SEA UN ARRAY DE ARRAYS
        if (empty($cupsList) || !is_array($cupsList)) {
            Log::warning('⚠️ cupsList vacío o no es array');
            return false;
        }

        // Asegurar que la tabla existe
        if ($this->isSQLiteAvailable()) {
            $this->createCupsTable();
            
            // Limpiar datos existentes
            DB::connection('offline')->table('cups')->delete();
            Log::info('🗑️ Tabla CUPS limpiada');
        }

        $syncCount = 0;
        $errors = [];
        
        foreach ($cupsList as $index => $cups) {
            try {
                // ✅ VALIDAR QUE CADA ELEMENTO SEA UN ARRAY
                if (!is_array($cups)) {
                    Log::warning('⚠️ Elemento no es array', [
                        'index' => $index,
                        'tipo' => gettype($cups),
                        'valor' => $cups
                    ]);
                    continue;
                }

                // ✅ VALIDAR CAMPOS REQUERIDOS
                if (empty($cups['uuid']) || empty($cups['codigo'])) {
                    Log::warning('⚠️ CUPS sin UUID o código', [
                        'index' => $index,
                        'uuid' => $cups['uuid'] ?? 'null',
                        'codigo' => $cups['codigo'] ?? 'null'
                    ]);
                    continue;
                }

                $this->storeCupsOffline($cups);
                $syncCount++;
                
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'cups_uuid' => $cups['uuid'] ?? 'N/A',
                    'error' => $e->getMessage()
                ];
                
                Log::error('❌ Error guardando CUPS individual', [
                    'index' => $index,
                    'cups_uuid' => $cups['uuid'] ?? 'N/A',
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('✅ CUPS sincronizados offline', [
            'total' => count($cupsList),
            'sincronizados' => $syncCount,
            'errores' => count($errors)
        ]);

        return $syncCount > 0;

    } catch (\Exception $e) {
        Log::error('❌ Error sincronizando CUPS offline', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return false;
    }
}


/**
 * ✅ OBTENER ESTADÍSTICAS DE CUPS
 */
public function getCupsStatsOffline(): array
{
    try {
        $stats = [
            'total' => 0,
            'activos' => 0,
            'inactivos' => 0,
            'last_sync' => null
        ];

        if ($this->isSQLiteAvailable()) {
            $stats['total'] = DB::connection('offline')->table('cups')->count();
            $stats['activos'] = DB::connection('offline')->table('cups')
                ->where('estado', 'ACTIVO')->count();
            $stats['inactivos'] = DB::connection('offline')->table('cups')
                ->where('estado', 'INACTIVO')->count();
        } else {
            // Fallback a JSON
            $cupsPath = $this->getStoragePath() . '/cups';
            if (is_dir($cupsPath)) {
                $files = glob($cupsPath . '/*.json');
                $stats['total'] = count($files);
                
                foreach ($files as $file) {
                    $data = json_decode(file_get_contents($file), true);
                    if ($data) {
                        if ($data['estado'] === 'ACTIVO') {
                            $stats['activos']++;
                        } else {
                            $stats['inactivos']++;
                        }
                    }
                }
            }
        }

        return $stats;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo estadísticas CUPS', [
            'error' => $e->getMessage()
        ]);
        return [
            'total' => 0,
            'activos' => 0,
            'inactivos' => 0,
            'error' => $e->getMessage()
        ];
    }
}


private function getBrigadaIdFromUuid(string $uuid): ?int
{
    try {
        if ($this->isSQLiteAvailable()) {
            $brigada = DB::connection('offline')
                ->table('brigadas')
                ->where('uuid', $uuid)
                ->first();
            
            return $brigada ? $brigada->id : null;
        }
        
        // Fallback a JSON
        $masterData = $this->getData('master_data.json', []);
        if (isset($masterData['brigadas'])) {
            foreach ($masterData['brigadas'] as $brigada) {
                if ($brigada['uuid'] === $uuid) {
                    return isset($brigada['id']) ? (int) $brigada['id'] : null;
                }
            }
        }
        
        return null;
        
    } catch (\Exception $e) {
        Log::error('Error obteniendo brigada ID desde UUID', [
            'uuid' => $uuid,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

private function isValidUuid(string $uuid): bool
{
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
}

/**
 * ✅ NUEVO: Manejar errores de sincronización
 */
private function handleSyncError(string $uuid, array $response, array &$results): void
{
    $errorMessage = $response['error'] ?? 'Error desconocido';
    
    // ✅ SI EL ERROR ES "YA EXISTE", MARCAR COMO SINCRONIZADO
    if (str_contains($errorMessage, 'Ya existe una agenda') || 
        str_contains($errorMessage, 'already exists')) {
        
        DB::connection('offline')
            ->table('agendas')
            ->where('uuid', $uuid)
            ->update(['sync_status' => 'synced']);
        
        $results['success']++;
        $results['details'][] = [
            'uuid' => $uuid,
            'status' => 'success',
            'action' => 'conflict_resolved'
        ];
        
        Log::info('✅ Conflicto resuelto - agenda marcada como sincronizada', [
            'uuid' => $uuid
        ]);
    } else {
        // ✅ ERROR REAL
        $results['errors']++;
        $results['details'][] = [
            'uuid' => $uuid,
            'status' => 'error',
            'error' => $errorMessage
        ];
        
        Log::error('❌ Error sincronizando agenda', [
            'uuid' => $uuid,
            'error' => $errorMessage
        ]);
    }
}

/**
 * ✅ NUEVO: Limpiar datos de agenda para sincronización
 */
private function cleanAgendaDataForSync(array $agendaData): array
{
    $cleanData = [
        'modalidad' => $agendaData['modalidad'],
        'fecha' => $agendaData['fecha'],
        'consultorio' => $agendaData['consultorio'],
        'hora_inicio' => $agendaData['hora_inicio'],
        'hora_fin' => $agendaData['hora_fin'],
        'intervalo' => $agendaData['intervalo'],
        'etiqueta' => $agendaData['etiqueta'],
        'estado' => $agendaData['estado'] ?? 'ACTIVO',
        'sede_id' => $agendaData['sede_id'],
        'usuario_id' => $agendaData['usuario_id']
    ];

    // ✅ SOLO AGREGAR SI NO SON NULOS
    if (!empty($agendaData['proceso_id']) && $agendaData['proceso_id'] !== 'null') {
        $cleanData['proceso_id'] = null; // ✅ Enviar null explícitamente
    }
    
    if (!empty($agendaData['brigada_id']) && $agendaData['brigada_id'] !== 'null') {
        $cleanData['brigada_id'] = null; // ✅ Enviar null explícitamente
    }

    return $cleanData;
}

/**
 * ✅ MEJORADO: Sincronizar citas pendientes CON MANEJO ESPECÍFICO DE CUPS
 */
public function syncPendingCitas(): array
{
    try {
        Log::info('🔄 Iniciando sincronización de citas pendientes');
        
        $results = [
            'success' => 0,
            'errors' => 0,
            'details' => []
        ];

        if (!$this->isSQLiteAvailable()) {
            Log::warning('⚠️ SQLite no disponible para sincronización de citas');
            return $results;
        }

        // ✅ VERIFICAR CONEXIÓN PRIMERO
        $apiService = app(ApiService::class);
        if (!$apiService->isOnline()) {
            Log::warning('⚠️ Sin conexión para sincronizar citas');
            return [
                'success' => false,
                'error' => 'Sin conexión al servidor',
                'synced_count' => 0,
                'failed_count' => 0
            ];
        }

        // Obtener citas pendientes
        $pendingCitas = DB::connection('offline')
            ->table('citas')
            ->where('sync_status', 'pending')
            ->orWhere('sync_status', 'error')
            ->get();

        Log::info('📊 Citas pendientes encontradas', [
            'count' => $pendingCitas->count()
        ]);

        if ($pendingCitas->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No hay citas pendientes',
                'synced_count' => 0,
                'failed_count' => 0
            ];
        }

        foreach ($pendingCitas as $cita) {
            try {
                $citaArray = (array) $cita;
                
                Log::info('📡 Procesando cita para sincronización', [
                    'uuid' => $cita->uuid,
                    'fecha' => $cita->fecha,
                    'paciente_uuid' => $cita->paciente_uuid,
                    'cups_contratado_id' => $cita->cups_contratado_id ?? 'null'
                ]);

                // ✅ PREPARAR DATOS LIMPIOS PARA LA API CON CUPS
                $syncData = $this->prepareCitaDataForSync($citaArray);
                
                Log::info('📤 Datos preparados para API', [
                    'uuid' => $cita->uuid,
                    'sync_data_keys' => array_keys($syncData),
                    'has_cups_contratado' => isset($syncData['cups_contratado_uuid'])
                ]);

                // ✅ ENVIAR A LA API
                if ($citaArray['deleted_at']) {
                    // Cita eliminada - enviar DELETE
                    $response = $apiService->delete("/citas/{$cita->uuid}");
                } else {
                    // Cita nueva/actualizada - enviar POST
                    $response = $apiService->post('/citas', $syncData);
                }
                
                Log::info('📥 Respuesta de API para cita', [
                    'uuid' => $cita->uuid,
                    'success' => $response['success'] ?? false,
                    'error' => $response['error'] ?? null
                ]);

                if (isset($response['success']) && $response['success'] === true) {
                       // ✅ VERIFICAR SI EL UUID CAMBIÓ
                    $oldUuid = $cita->uuid;
                    $newUuid = $response['data']['uuid'] ?? $oldUuid;
                    
                    if ($oldUuid !== $newUuid) {
                        Log::info('🔄 UUID de cita cambió después de sincronizar', [
                            'old_uuid' => $oldUuid,
                            'new_uuid' => $newUuid
                        ]);
                        
                        // ✅ ACTUALIZAR HISTORIA CLÍNICA
                        $this->updateHistoriaClinicaCitaUuid($oldUuid, $newUuid);
                    }
                    
                    // ✅ ÉXITO
                    DB::connection('offline')
                        ->table('citas')
                        ->where('uuid', $cita->uuid)
                        ->update([
                            'sync_status' => 'synced',
                            'uuid' => $newUuid,
                            'updated_at' => now()
                        ]);
                    
                    $results['success']++;
                    $results['details'][] = [
                        'uuid' => $cita->uuid,
                        'status' => 'success',
                        'action' => $citaArray['deleted_at'] ? 'deleted' : 'created'
                    ];
                    
                    Log::info('✅ Cita sincronizada exitosamente', [
                        'uuid' => $cita->uuid
                    ]);
                    
                } else {
                    // ✅ ERROR
                    $errorMessage = $response['error'] ?? 'Error desconocido';
                    
                    // ✅ VERIFICAR SI ES ERROR DE DUPLICADO
                    $errorLower = strtolower($errorMessage);
                    if (str_contains($errorLower, 'ya existe') || 
                        str_contains($errorLower, 'duplicate') ||
                        str_contains($errorLower, 'already exists')) {
                        
                        // Marcar como sincronizado si ya existe
                        DB::connection('offline')
                            ->table('citas')
                            ->where('uuid', $cita->uuid)
                            ->update([
                                'sync_status' => 'synced',
                                'updated_at' => now()
                            ]);
                        
                        $results['success']++;
                        $results['details'][] = [
                            'uuid' => $cita->uuid,
                            'status' => 'success',
                            'action' => 'already_exists'
                        ];
                        
                        Log::info('✅ Cita ya existía en servidor', [
                            'uuid' => $cita->uuid
                        ]);
                    } else {
                        // Error real
                        DB::connection('offline')
                            ->table('citas')
                            ->where('uuid', $cita->uuid)
                            ->update(['sync_status' => 'error']);
                        
                        $results['errors']++;
                        $results['details'][] = [
                            'uuid' => $cita->uuid,
                            'status' => 'error',
                            'error' => $errorMessage
                        ];
                        
                        Log::error('❌ Error sincronizando cita', [
                            'uuid' => $cita->uuid,
                            'error' => $errorMessage
                        ]);
                    }
                }

            } catch (\Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'uuid' => $cita->uuid ?? 'unknown',
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
                
                Log::error('❌ Excepción sincronizando cita', [
                    'uuid' => $cita->uuid ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('🏁 Sincronización de citas completada', [
            'success' => $results['success'],
            'errors' => $results['errors'],
            'total' => $pendingCitas->count()
        ]);

        return [
            'success' => true,
            'message' => "Sincronización completada: {$results['success']} exitosas, {$results['errors']} errores",
            'synced_count' => $results['success'],
            'failed_count' => $results['errors'],
            'details' => $results['details']
        ];

    } catch (\Exception $e) {
        Log::error('💥 Error crítico en sincronización de citas', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'error' => 'Error crítico: ' . $e->getMessage(),
            'synced_count' => 0,
            'failed_count' => 0
        ];
    }
}
/**
 * ✅ NUEVO: Actualizar UUID de cita en historia clínica
 */
private function updateHistoriaClinicaCitaUuid(string $oldUuid, string $newUuid): void
{
    try {
        // Actualizar en SQLite
        if ($this->isSQLiteAvailable()) {
            $updated = DB::connection('offline')
                ->table('historias_clinicas')
                ->where('cita_uuid', $oldUuid)
                ->update([
                    'cita_uuid' => $newUuid,
                    'sync_status' => 'pending', // ✅ Marcar para re-sincronizar
                    'updated_at' => now()->toISOString()
                ]);
            
            if ($updated > 0) {
                Log::info('✅ Historia clínica actualizada en SQLite', [
                    'old_cita_uuid' => $oldUuid,
                    'new_cita_uuid' => $newUuid,
                    'historias_actualizadas' => $updated
                ]);
            }
        }
        
        // Actualizar en JSON
        $historiasPath = storage_path('app/offline/historias_clinicas');
        if (is_dir($historiasPath)) {
            $files = glob($historiasPath . '/*.json');
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($data && ($data['cita_uuid'] ?? '') === $oldUuid) {
                    $data['cita_uuid'] = $newUuid;
                    $data['sync_status'] = 'pending';
                    $data['updated_at'] = now()->toISOString();
                    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
                    
                    Log::info('✅ Historia clínica actualizada en JSON', [
                        'file' => basename($file),
                        'old_cita_uuid' => $oldUuid,
                        'new_cita_uuid' => $newUuid
                    ]);
                    break;
                }
            }
        }
        
    } catch (\Exception $e) {
        Log::error('❌ Error actualizando UUID de cita en historia', [
            'error' => $e->getMessage(),
            'old_uuid' => $oldUuid,
            'new_uuid' => $newUuid
        ]);
    }
}
/**
 * ✅ NUEVO: Preparar datos de cita para sincronización CON MANEJO DE CUPS
 */
private function prepareCitaDataForSync(array $cita): array
{
    Log::info('🧹 Preparando datos de cita para API', [
        'uuid' => $cita['uuid'],
        'cups_contratado_uuid_original' => $cita['cups_contratado_uuid'] ?? 'null'
    ]);

    $cleanData = [
        'fecha' => $cita['fecha'],
        'fecha_inicio' => $cita['fecha_inicio'],
        'fecha_final' => $cita['fecha_final'],
        'fecha_deseada' => $cita['fecha_deseada'] ?? null,
        'motivo' => $cita['motivo'] ?? null,
        'nota' => $cita['nota'] ?? '',
        'estado' => $cita['estado'] ?? 'PROGRAMADA',
        'patologia' => $cita['patologia'] ?? null,
        'paciente_uuid' => $cita['paciente_uuid'],
        'agenda_uuid' => $cita['agenda_uuid'],
        'sede_id' => (int) ($cita['sede_id'] ?? 1),
        'usuario_creo_cita_id' => (int) ($cita['usuario_creo_cita_id'] ?? 1)
    ];

    // ✅ MANEJO SIMPLE DE CUPS CONTRATADO
    if (!empty($cita['cups_contratado_uuid']) && $cita['cups_contratado_uuid'] !== 'null') {
        $cleanData['cups_contratado_uuid'] = $cita['cups_contratado_uuid'];
        
        Log::info('✅ CUPS contratado incluido en datos de sincronización', [
            'cups_contratado_uuid' => $cita['cups_contratado_uuid']
        ]);
    } else {
        Log::info('ℹ️ Cita sin CUPS contratado para sincronización');
    }

    Log::info('🧹 Datos de cita limpiados para API', [
        'uuid' => $cita['uuid'],
        'clean_data_keys' => array_keys($cleanData),
        'has_cups_contratado' => isset($cleanData['cups_contratado_uuid']),
        'cups_contratado_uuid' => $cleanData['cups_contratado_uuid'] ?? 'no-enviado'
    ]);

    return $cleanData;
}
/**
 * ✅ NUEVO: Resolver CUPS contratado ID a UUID
 */
private function resolveCupsContratadoIdToUuid($cupsContratadoId): ?string
{
    try {
        if ($this->isSQLiteAvailable()) {
            $cupsContratado = DB::connection('offline')
                ->table('cups_contratados')
                ->where('id', $cupsContratadoId)
                ->orWhere('uuid', $cupsContratadoId)
                ->first();
            
            if ($cupsContratado) {
                return $cupsContratado->uuid;
            }
        }
        
        // Fallback a archivos JSON
        $cupsContratadosPath = $this->storagePath . '/cups_contratados';
        if (is_dir($cupsContratadosPath)) {
            $files = glob($cupsContratadosPath . '/*.json');
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($data && 
                    (($data['id'] ?? null) == $cupsContratadoId || 
                     ($data['uuid'] ?? null) === $cupsContratadoId)) {
                    return $data['uuid'];
                }
            }
        }
        
        return null;
        
    } catch (\Exception $e) {
        Log::error('❌ Error resolviendo CUPS contratado ID a UUID', [
            'cups_contratado_id' => $cupsContratadoId,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}


/**
 * ✅ OBTENER CONTEO DE REGISTROS PENDIENTES
 */
public function getPendingSyncCount(): array
{
    try {
        $counts = [
            'agendas' => 0,
            'citas' => 0,
            'total' => 0
        ];

        if ($this->isSQLiteAvailable()) {
            $counts['agendas'] = DB::connection('offline')
                ->table('agendas')
                ->where('sync_status', 'pending')
                ->count();
                
            $counts['citas'] = DB::connection('offline')
                ->table('citas')
                ->where('sync_status', 'pending')
                ->count();
        }

        $counts['total'] = $counts['agendas'] + $counts['citas'];
        
        return $counts;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo conteo pendiente', [
            'error' => $e->getMessage()
        ]);
        
        return [
            'agendas' => 0,
            'citas' => 0,
            'total' => 0
        ];
    }
}
/**
 * ✅ OBTENER DATOS DE TEST PARA SINCRONIZACIÓN
 */
public function getTestSyncData($limit = 10): array
{
    try {
        Log::info('🧪 Test manual de sincronización de agendas iniciado');
        
        $this->ensureSQLiteExists();
        
        $pendingAgendas = DB::connection('offline')->table('agendas')
            ->where('sync_status', 'pending')
            ->orWhere('sync_status', 'error')
            ->limit($limit)
            ->get();
        
        Log::info('📊 Agendas pendientes encontradas', [
            'total' => $pendingAgendas->count(),
            'limit' => $limit
        ]);
        
        if ($pendingAgendas->isEmpty()) {
            return [
                'success' => true,
                'pending_count' => 0,
                'total_count' => 0,
                'error_count' => 0,
                'data' => [],
                'message' => 'No hay agendas pendientes de sincronización'
            ];
        }
        
        // ✅ CONVERTIR OBJETOS stdClass A ARRAYS
        $agendasArray = $pendingAgendas->map(function ($agenda) {
            $agendaArray = (array) $agenda;
            
            if (isset($agendaArray['original_data']) && is_string($agendaArray['original_data'])) {
                $originalData = json_decode($agendaArray['original_data'], true);
                if ($originalData) {
                    $agendaArray['original_data'] = $originalData;
                }
            }
            
            return $agendaArray;
        })->toArray();
        
        // Filtrar agendas válidas
        $validAgendas = array_filter($agendasArray, function ($agenda) {
            return isset($agenda['uuid']) && 
                   !empty($agenda['uuid']) && 
                   isset($agenda['fecha']) && 
                   !empty($agenda['fecha']);
        });
        
        // ✅ OBTENER TOTALES
        $totalCount = DB::connection('offline')->table('agendas')->count();
        $errorCount = DB::connection('offline')->table('agendas')
            ->where('sync_status', 'error')
            ->count();
        
        Log::info('✅ Agendas válidas para sincronización', [
            'total_pendientes' => count($agendasArray),
            'validas' => count($validAgendas),
            'total_count' => $totalCount,
            'error_count' => $errorCount
        ]);
        
        return [
            'success' => true,
            'pending_count' => count($validAgendas),
            'total_count' => $totalCount,
            'error_count' => $errorCount,
            'data' => array_values($validAgendas),
            'pending_details' => array_values($validAgendas),
            'message' => count($validAgendas) . ' agendas pendientes de sincronización'
        ];
        
    } catch (\Exception $e) {
        Log::error('❌ Error en getTestSyncData', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ]);
        
        return [
            'success' => false,
            'pending_count' => 0,
            'total_count' => 0,
            'error_count' => 0,
            'data' => [],
            'error' => 'Error obteniendo datos de prueba: ' . $e->getMessage()
        ];
    }
}
// app/Services/OfflineService.php - AGREGAR MÉTODO TEMPORAL
public function diagnosticSync(): array
{
    try {
        Log::info('🔍 Diagnóstico de sincronización iniciado');
        
        // ✅ VERIFICAR SQLITE
        if (!$this->isSQLiteAvailable()) {
            return [
                'success' => false,
                'error' => 'SQLite no disponible',
                'sqlite_available' => false
            ];
        }
        
        // ✅ CONTAR REGISTROS
        $totalAgendas = DB::connection('offline')->table('agendas')->count();
        $pendingAgendas = DB::connection('offline')->table('agendas')
            ->whereIn('sync_status', ['pending', 'error'])
            ->count();
        
        // ✅ OBTENER MUESTRA DE DATOS
        $sampleAgendas = DB::connection('offline')->table('agendas')
            ->whereIn('sync_status', ['pending', 'error'])
            ->limit(3)
            ->get();
        
        Log::info('📊 Diagnóstico completado', [
            'total_agendas' => $totalAgendas,
            'pending_agendas' => $pendingAgendas,
            'sample_count' => $sampleAgendas->count()
        ]);
        
        return [
            'success' => true,
            'sqlite_available' => true,
            'total_agendas' => $totalAgendas,
            'pending_agendas' => $pendingAgendas,
            'sample_data' => $sampleAgendas->toArray()
        ];
        
    } catch (\Exception $e) {
        Log::error('❌ Error en diagnóstico', [
            'error' => $e->getMessage()
        ]);
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'sqlite_available' => false
        ];
    }
}
public function recreateAgendasTable(): bool
{
    try {
        Log::info('🔧 Recreando tabla agendas con nueva estructura...');
        
        if (!$this->isSQLiteAvailable()) {
            Log::warning('⚠️ SQLite no disponible');
            return false;
        }
        
        // 1. Respaldar datos existentes
        $existingAgendas = [];
        try {
            $existingAgendas = DB::connection('offline')
                ->table('agendas')
                ->get()
                ->toArray();
            Log::info('💾 Respaldadas ' . count($existingAgendas) . ' agendas existentes');
        } catch (\Exception $e) {
            Log::info('ℹ️ No hay datos existentes para respaldar');
        }
        
        // 2. Eliminar tabla existente
        DB::connection('offline')->statement('DROP TABLE IF EXISTS agendas');
        Log::info('🗑️ Tabla agendas eliminada');
        
        // 3. Crear nueva tabla con estructura actualizada
        $this->createAgendasTable();
        Log::info('✅ Nueva tabla agendas creada');
        
        // 4. Restaurar datos existentes (si los hay)
        if (!empty($existingAgendas)) {
            foreach ($existingAgendas as $agenda) {
                $agendaArray = (array) $agenda;
                
                // ✅ AGREGAR CAMPO FALTANTE CON VALOR POR DEFECTO
                if (!isset($agendaArray['usuario_medico_id'])) {
                    $agendaArray['usuario_medico_id'] = null;
                }
                
                try {
                    DB::connection('offline')->table('agendas')->insert($agendaArray);
                } catch (\Exception $e) {
                    Log::warning('⚠️ Error restaurando agenda', [
                        'uuid' => $agendaArray['uuid'] ?? 'sin-uuid',
                        'error' => $e->getMessage()
                    ]);
                }
            }
            Log::info('♻️ Datos restaurados: ' . count($existingAgendas) . ' agendas');
        }
        
        Log::info('🎉 Tabla agendas recreada exitosamente');
        return true;
        
    } catch (\Exception $e) {
        Log::error('❌ Error recreando tabla agendas', [
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ]);
        return false;
    }
}

// En OfflineService.php - REEMPLAZAR getPacienteOffline
public function getPacienteOffline(string $uuid): ?array
{
    try {
        $paciente = null;
        
        // ✅ BUSCAR EN SQLite PRIMERO
        if ($this->isSQLiteAvailable()) {
            $pacienteRaw = DB::connection('offline')->table('pacientes')
                ->where('uuid', $uuid)
                ->whereNull('deleted_at')
                ->first();
            
            if ($pacienteRaw) {
                $paciente = (array) $pacienteRaw;
                Log::info('✅ Paciente encontrado en SQLite offline', [
                    'uuid' => $paciente['uuid'],
                    'nombre' => $paciente['nombre_completo']
                ]);
            }
        }
        
        // ✅ FALLBACK A JSON
        if (!$paciente) {
            $path = $this->storagePath . "/pacientes/{$uuid}.json";
            
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $paciente = json_decode($content, true);
                
                Log::info('✅ Paciente encontrado en JSON offline', [
                    'uuid' => $paciente['uuid'] ?? 'NO_UUID',
                    'nombre' => $paciente['nombre_completo'] ?? 'NO_NOMBRE'
                ]);
            }
        }

        if (!$paciente) {
            Log::info('⚠️ Paciente no encontrado offline', ['uuid' => $uuid]);
            return null;
        }

        // ✅ ENRIQUECER CON OBJETOS DE RELACIONES PARA COMPATIBILIDAD CON LA VISTA
        $paciente = $this->enrichPacienteRelations($paciente);

        return $paciente;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo paciente offline', [
            'uuid' => $uuid,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

/**
 * ✅ NUEVO: Enriquecer paciente con objetos de relaciones
 */
private function enrichPacienteRelations(array $paciente): array
{
    try {
        // ✅ CONSTRUIR OBJETOS DE RELACIONES PARA COMPATIBILIDAD
        
        // Tipo de documento
        if (!empty($paciente['tipo_documento_nombre'])) {
            $paciente['tipo_documento'] = [
                'nombre' => $paciente['tipo_documento_nombre'],
                'abreviacion' => $paciente['tipo_documento_abreviacion'] ?? null
            ];
        }
        
        // Empresa
        if (!empty($paciente['empresa_nombre'])) {
            $paciente['empresa'] = [
                'nombre' => $paciente['empresa_nombre'],
                'codigo_eapb' => $paciente['empresa_codigo_eapb'] ?? null
            ];
        }
        
        // Régimen
        if (!empty($paciente['regimen_nombre'])) {
            $paciente['regimen'] = [
                'nombre' => $paciente['regimen_nombre']
            ];
        }
        
        // Tipo de afiliación
        if (!empty($paciente['tipo_afiliacion_nombre'])) {
            $paciente['tipo_afiliacion'] = [
                'nombre' => $paciente['tipo_afiliacion_nombre']
            ];
        }
        
        // Zona de residencia
        if (!empty($paciente['zona_residencia_nombre'])) {
            $paciente['zona_residencia'] = [
                'nombre' => $paciente['zona_residencia_nombre'],
                'abreviacion' => $paciente['zona_residencia_abreviacion'] ?? null
            ];
        }
        
        // Departamentos
        if (!empty($paciente['depto_nacimiento_nombre'])) {
            $paciente['departamento_nacimiento'] = [
                'nombre' => $paciente['depto_nacimiento_nombre']
            ];
        }
        
        if (!empty($paciente['depto_residencia_nombre'])) {
            $paciente['departamento_residencia'] = [
                'nombre' => $paciente['depto_residencia_nombre']
            ];
        }
        
        // Municipios
        if (!empty($paciente['municipio_nacimiento_nombre'])) {
            $paciente['municipio_nacimiento'] = [
                'nombre' => $paciente['municipio_nacimiento_nombre']
            ];
        }
        
        if (!empty($paciente['municipio_residencia_nombre'])) {
            $paciente['municipio_residencia'] = [
                'nombre' => $paciente['municipio_residencia_nombre']
            ];
        }
        
        // Raza
        if (!empty($paciente['raza_nombre'])) {
            $paciente['raza'] = [
                'nombre' => $paciente['raza_nombre']
            ];
        }
        
        // Escolaridad
        if (!empty($paciente['escolaridad_nombre'])) {
            $paciente['escolaridad'] = [
                'nombre' => $paciente['escolaridad_nombre']
            ];
        }
        
        // Parentesco
        if (!empty($paciente['parentesco_nombre'])) {
            $paciente['parentesco'] = [
                'nombre' => $paciente['parentesco_nombre']
            ];
        }
        
        // Ocupación
        if (!empty($paciente['ocupacion_nombre'])) {
            $paciente['ocupacion'] = [
                'nombre' => $paciente['ocupacion_nombre'],
                'codigo' => $paciente['ocupacion_codigo'] ?? null
            ];
        }
        
        // ✅ NOVEDAD (IMPORTANTE)
        if (!empty($paciente['novedad_tipo'])) {
            $paciente['novedad'] = [
                'tipo_novedad' => $paciente['novedad_tipo']
            ];
        }
        
        // ✅ AUXILIAR (IMPORTANTE)
        if (!empty($paciente['auxiliar_nombre'])) {
            $paciente['auxiliar'] = [
                'nombre' => $paciente['auxiliar_nombre']
            ];
        }
        
        // ✅ BRIGADA (IMPORTANTE)
        if (!empty($paciente['brigada_nombre'])) {
            $paciente['brigada'] = [
                'nombre' => $paciente['brigada_nombre']
            ];
        }
        
        // ✅ ACUDIENTE
        if (!empty($paciente['nombre_acudiente'])) {
            $paciente['acudiente'] = [
                'nombre' => $paciente['nombre_acudiente'],
                'parentesco' => $paciente['parentesco_acudiente'] ?? null,
                'telefono' => $paciente['telefono_acudiente'] ?? null,
                'direccion' => $paciente['direccion_acudiente'] ?? null
            ];
        }
        
        // ✅ ACOMPAÑANTE
        if (!empty($paciente['acompanante_nombre'])) {
            $paciente['acompanante'] = [
                'nombre' => $paciente['acompanante_nombre'],
                'telefono' => $paciente['acompanante_telefono'] ?? null
            ];
        }
        
        Log::debug('✅ Paciente enriquecido con relaciones', [
            'uuid' => $paciente['uuid'],
            'has_empresa' => isset($paciente['empresa']),
            'has_novedad' => isset($paciente['novedad']),
            'has_auxiliar' => isset($paciente['auxiliar']),
            'has_brigada' => isset($paciente['brigada']),
            'has_acudiente' => isset($paciente['acudiente']),
            'has_acompanante' => isset($paciente['acompanante'])
        ]);
        
        return $paciente;
        
    } catch (\Exception $e) {
        Log::error('❌ Error enriqueciendo relaciones del paciente', [
            'uuid' => $paciente['uuid'] ?? 'sin-uuid',
            'error' => $e->getMessage()
        ]);
        return $paciente;
    }
}



/**
 * ✅ NUEVO: Buscar paciente por documento offline
 */
public function buscarPacientePorDocumentoOffline(string $documento): ?array
{
    try {
        Log::info('🔍 Buscando paciente por documento offline', [
            'documento' => $documento
        ]);

        // ✅ BUSCAR EN SQLite PRIMERO
        if ($this->isSQLiteAvailable()) {
            $paciente = DB::connection('offline')->table('pacientes')
                ->where('documento', $documento)
                ->where('estado', 'ACTIVO')
                ->whereNull('deleted_at')
                ->first();
            
            if ($paciente) {
                $pacienteArray = (array) $paciente;
                Log::info('✅ Paciente encontrado en SQLite', [
                    'uuid' => $pacienteArray['uuid'],
                    'nombre' => $pacienteArray['nombre_completo']
                ]);
                return $pacienteArray;
            }
        }

        // ✅ BUSCAR EN ÍNDICE JSON
        $indexPath = $this->storagePath . "/pacientes_by_document/{$documento}.json";
        if (file_exists($indexPath)) {
            $indexData = json_decode(file_get_contents($indexPath), true);
            if ($indexData && isset($indexData['uuid'])) {
                return $this->getPacienteOffline($indexData['uuid']);
            }
        }

        // ✅ BÚSQUEDA EXHAUSTIVA EN JSONs
        $pacientesPath = $this->storagePath . '/pacientes';
        if (is_dir($pacientesPath)) {
            $files = glob($pacientesPath . '/*.json');
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($data && 
                    isset($data['documento']) && 
                    $data['documento'] === $documento &&
                    $data['estado'] === 'ACTIVO' &&
                    empty($data['deleted_at'])) {
                    
                    Log::info('✅ Paciente encontrado en JSON', [
                        'uuid' => $data['uuid'],
                        'nombre' => $data['nombre_completo']
                    ]);
                    return $data;
                }
            }
        }

        Log::info('⚠️ Paciente no encontrado offline', ['documento' => $documento]);
        return null;

    } catch (\Exception $e) {
        Log::error('❌ Error buscando paciente por documento offline', [
            'error' => $e->getMessage(),
            'documento' => $documento
        ]);
        return null;
    }
}

/**
 * ✅ OBTENER CUPS OFFLINE POR UUID
 */
public function getCupsOffline(string $uuid): ?array
{
    try {
        // Buscar en archivo JSON directo
        $cups = $this->getData('cups/' . $uuid . '.json');
        
        if ($cups) {
            return $cups;
        }
        
        // Si no está en JSON, buscar en SQLite
        if ($this->isSQLiteAvailable()) {
            $cups = DB::connection('offline')->table('cups')
                ->where('uuid', $uuid)
                ->where('estado', 'ACTIVO')
                ->first();
            
            return $cups ? (array) $cups : null;
        }

        return null;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo CUPS offline', [
            'error' => $e->getMessage(),
            'uuid' => $uuid
        ]);
        return null;
    }
}

/**
 * ✅ MÉTODO GENÉRICO PARA OBTENER DATOS DE SQLITE
 */
public function getFromSQLite(string $table): array
{
    try {
        if (!$this->isSQLiteAvailable()) {
            return [];
        }
        
        $results = DB::connection('offline')->table($table)->get();
        
        return $results->map(function ($item) {
            return (array) $item;
        })->toArray();
        
    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo datos de SQLite', [
            'table' => $table,
            'error' => $e->getMessage()
        ]);
        return [];
    }
}
public function storePacienteOffline(array $pacienteData, bool $needsSync = false): void
{
    try {
        if (empty($pacienteData['uuid'])) {
            Log::warning('⚠️ Intentando guardar paciente sin UUID');
            return;
        }

        // Asegurar sede_id
        if (empty($pacienteData['sede_id'])) {
            $user = auth()->user() ?? session('usuario');
            $pacienteData['sede_id'] = $user['sede_id'] ?? 1;
        }

        // ✅ EXTRAER Y ALMACENAR TODAS LAS RELACIONES
        $offlineData = [
            'id' => $pacienteData['id'] ?? null,
            'uuid' => $pacienteData['uuid'],
            'sede_id' => $pacienteData['sede_id'],
            
            // ✅ DATOS BÁSICOS
            'primer_nombre' => $pacienteData['primer_nombre'] ?? '',
            'segundo_nombre' => $pacienteData['segundo_nombre'] ?? null,
            'primer_apellido' => $pacienteData['primer_apellido'] ?? '',
            'segundo_apellido' => $pacienteData['segundo_apellido'] ?? null,
            'nombre_completo' => $pacienteData['nombre_completo'] ?? 
                                (($pacienteData['primer_nombre'] ?? '') . ' ' . 
                                 ($pacienteData['primer_apellido'] ?? '')),
            'documento' => $pacienteData['documento'] ?? '',
            'fecha_nacimiento' => $pacienteData['fecha_nacimiento'] ?? null,
            'sexo' => $pacienteData['sexo'] ?? 'M',
            'telefono' => $pacienteData['telefono'] ?? null,
            'direccion' => $pacienteData['direccion'] ?? null,
            'correo' => $pacienteData['correo'] ?? null,
            'estado_civil' => $pacienteData['estado_civil'] ?? null,
            'observacion' => $pacienteData['observacion'] ?? null,
            'registro' => $pacienteData['registro'] ?? null,
            'estado' => $pacienteData['estado'] ?? 'ACTIVO',
            
            // ✅ IDs DE RELACIONES (pueden ser UUIDs o números)
            'tipo_documento_id' => $pacienteData['tipo_documento_id'] ?? null,
            'empresa_id' => $pacienteData['empresa_id'] ?? null,
            'regimen_id' => $pacienteData['regimen_id'] ?? null,
            'tipo_afiliacion_id' => $pacienteData['tipo_afiliacion_id'] ?? null,
            'zona_residencia_id' => $pacienteData['zona_residencia_id'] ?? null,
            'depto_nacimiento_id' => $pacienteData['depto_nacimiento_id'] ?? null,
            'depto_residencia_id' => $pacienteData['depto_residencia_id'] ?? null,
            'municipio_nacimiento_id' => $pacienteData['municipio_nacimiento_id'] ?? null,
            'municipio_residencia_id' => $pacienteData['municipio_residencia_id'] ?? null,
            'raza_id' => $pacienteData['raza_id'] ?? null,
            'escolaridad_id' => $pacienteData['escolaridad_id'] ?? null,
            'parentesco_id' => $pacienteData['parentesco_id'] ?? null,
            'ocupacion_id' => $pacienteData['ocupacion_id'] ?? null,
            'novedad_id' => $pacienteData['novedad_id'] ?? null,
            'auxiliar_id' => $pacienteData['auxiliar_id'] ?? null,
            'brigada_id' => $pacienteData['brigada_id'] ?? null,
            
            // ✅ NOMBRES DE RELACIONES PARA MOSTRAR (extraer de objetos anidados)
            'tipo_documento_nombre' => $this->extractRelationName($pacienteData, 'tipo_documento', 'nombre'),
            'tipo_documento_abreviacion' => $this->extractRelationName($pacienteData, 'tipo_documento', 'abreviacion'),
            'empresa_nombre' => $this->extractRelationName($pacienteData, 'empresa', 'nombre'),
            'empresa_codigo_eapb' => $this->extractRelationName($pacienteData, 'empresa', 'codigo_eapb'),
            'regimen_nombre' => $this->extractRelationName($pacienteData, 'regimen', 'nombre'),
            'tipo_afiliacion_nombre' => $this->extractRelationName($pacienteData, 'tipo_afiliacion', 'nombre'),
            'zona_residencia_nombre' => $this->extractRelationName($pacienteData, 'zona_residencia', 'nombre'),
            'zona_residencia_abreviacion' => $this->extractRelationName($pacienteData, 'zona_residencia', 'abreviacion'),
            'depto_nacimiento_nombre' => $this->extractRelationName($pacienteData, 'departamento_nacimiento', 'nombre'),
            'depto_residencia_nombre' => $this->extractRelationName($pacienteData, 'departamento_residencia', 'nombre'),
            'municipio_nacimiento_nombre' => $this->extractRelationName($pacienteData, 'municipio_nacimiento', 'nombre'),
            'municipio_residencia_nombre' => $this->extractRelationName($pacienteData, 'municipio_residencia', 'nombre'),
            'raza_nombre' => $this->extractRelationName($pacienteData, 'raza', 'nombre'),
            'escolaridad_nombre' => $this->extractRelationName($pacienteData, 'escolaridad', 'nombre'),
            'parentesco_nombre' => $this->extractRelationName($pacienteData, 'parentesco', 'nombre'),
            'ocupacion_nombre' => $this->extractRelationName($pacienteData, 'ocupacion', 'nombre'),
            'ocupacion_codigo' => $this->extractRelationName($pacienteData, 'ocupacion', 'codigo'),
            'novedad_tipo' => $this->extractRelationName($pacienteData, 'novedad', 'tipo_novedad'),
            'auxiliar_nombre' => $this->extractRelationName($pacienteData, 'auxiliar', 'nombre'),
            'brigada_nombre' => $this->extractRelationName($pacienteData, 'brigada', 'nombre'),
            
            // ✅ DATOS DE ACUDIENTE
            'nombre_acudiente' => $pacienteData['nombre_acudiente'] ?? 
                                 $this->extractRelationName($pacienteData, 'acudiente', 'nombre'),
            'parentesco_acudiente' => $pacienteData['parentesco_acudiente'] ?? 
                                     $this->extractRelationName($pacienteData, 'acudiente', 'parentesco'),
            'telefono_acudiente' => $pacienteData['telefono_acudiente'] ?? 
                                   $this->extractRelationName($pacienteData, 'acudiente', 'telefono'),
            'direccion_acudiente' => $pacienteData['direccion_acudiente'] ?? 
                                    $this->extractRelationName($pacienteData, 'acudiente', 'direccion'),
            
            // ✅ DATOS DE ACOMPAÑANTE
            'acompanante_nombre' => $pacienteData['acompanante_nombre'] ?? 
                                   $this->extractRelationName($pacienteData, 'acompanante', 'nombre'),
            'acompanante_telefono' => $pacienteData['acompanante_telefono'] ?? 
                                     $this->extractRelationName($pacienteData, 'acompanante', 'telefono'),
            
            // ✅ FECHAS Y CONTROL
            'fecha_registro' => $pacienteData['fecha_registro'] ?? now()->format('Y-m-d'),
            'fecha_actualizacion' => $pacienteData['fecha_actualizacion'] ?? null,
            'sync_status' => $needsSync ? 'pending' : 'synced',
            'stored_at' => now()->toISOString(),
            'deleted_at' => $pacienteData['deleted_at'] ?? null
        ];

        // ✅ GUARDAR EN SQLite SI ESTÁ DISPONIBLE
        if ($this->isSQLiteAvailable()) {
            try {
                // Asegurar que la tabla pacientes existe
                $this->createPacientesTable();
                
                DB::connection('offline')->table('pacientes')->updateOrInsert(
                    ['uuid' => $pacienteData['uuid']],
                    array_merge($offlineData, [
                        'created_at' => $offlineData['fecha_registro'] ?? now()->toISOString(),
                        'updated_at' => now()->toISOString()
                    ])
                );
                
                Log::debug('✅ Paciente guardado en SQLite', [
                    'uuid' => $pacienteData['uuid']
                ]);
            } catch (\Exception $e) {
                Log::warning('⚠️ Error guardando en SQLite, usando JSON', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ✅ SIEMPRE GUARDAR EN JSON COMO BACKUP
        $this->storeData('pacientes/' . $pacienteData['uuid'] . '.json', $offlineData);
        
        // También indexar por documento
        if (!empty($pacienteData['documento'])) {
            $this->storeData('pacientes_by_document/' . $pacienteData['documento'] . '.json', [
                'uuid' => $pacienteData['uuid'],
                'sede_id' => $pacienteData['sede_id']
            ]);
        }

        Log::debug('✅ Paciente almacenado offline completo', [
            'uuid' => $pacienteData['uuid'],
            'documento' => $pacienteData['documento'] ?? 'sin-documento',
            'sync_status' => $offlineData['sync_status'],
            'has_empresa' => !empty($offlineData['empresa_nombre']),
            'has_novedad' => !empty($offlineData['novedad_tipo']),
            'has_auxiliar' => !empty($offlineData['auxiliar_nombre']),
            'has_brigada' => !empty($offlineData['brigada_nombre'])
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error almacenando paciente offline', [
            'error' => $e->getMessage(),
            'uuid' => $pacienteData['uuid'] ?? 'sin-uuid',
            'trace' => $e->getTraceAsString()
        ]);
    }
}

/**
 * ✅ NUEVO: Método auxiliar para extraer nombres de relaciones
 */
private function extractRelationName(array $data, string $relationKey, string $field): ?string
{
    try {
        // Si ya existe el campo directo, usarlo
        $directField = $relationKey . '_' . $field;
        if (isset($data[$directField])) {
            return $data[$directField];
        }
        
        // Si existe la relación anidada, extraer el campo
        if (isset($data[$relationKey]) && is_array($data[$relationKey])) {
            return $data[$relationKey][$field] ?? null;
        }
        
        return null;
        
    } catch (\Exception $e) {
        Log::debug('⚠️ Error extrayendo relación', [
            'relation' => $relationKey,
            'field' => $field,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}
public function getCupsContratadoPorCupsUuid(string $cupsUuid): ?array
{
    try {
        // Buscar en archivos JSON primero
        $cupsContratadosPath = $this->storagePath . '/cups_contratados';
        if (is_dir($cupsContratadosPath)) {
            $files = glob($cupsContratadosPath . '/*.json');
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($data && 
                    isset($data['cups_uuid']) && 
                    $data['cups_uuid'] === $cupsUuid &&
                    $data['estado'] === 'ACTIVO') {
                    return $data;
                }
            }
        }

        return null;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo CUPS contratado por CUPS UUID offline', [
            'error' => $e->getMessage(),
            'cups_uuid' => $cupsUuid
        ]);
        return null;
    }
}

public function storeCupsContratadoOffline(array $cupsContratadoData): void
{
    try {
        if (empty($cupsContratadoData['uuid'])) {
            Log::warning('⚠️ Intentando guardar CUPS contratado sin UUID');
            return;
        }

        // ✅ EXTRAER INFORMACIÓN
        $cups = $cupsContratadoData['cups'] ?? [];
        $contrato = $cupsContratadoData['contrato'] ?? [];
        $categoriaCups = $cupsContratadoData['categoria_cups'] ?? [];
        $empresa = $contrato['empresa'] ?? [];

        // ✅ 1. GUARDAR CATEGORÍA CUPS SI NO EXISTE
        if (!empty($categoriaCups['id'])) {
            $this->storeCategoriasCupsOffline($categoriaCups);
        }

        // ✅ 2. GUARDAR CONTRATO SI NO EXISTE
        if (!empty($contrato['uuid'])) {
            $this->storeContratoOffline($contrato);
        }

        // ✅ 3. GUARDAR CUPS CONTRATADO
        $offlineData = [
            'uuid' => $cupsContratadoData['uuid'],
            'contrato_id' => $cupsContratadoData['contrato_id'] ?? null,
            'contrato_uuid' => $contrato['uuid'] ?? null,
            'categoria_cups_id' => $cupsContratadoData['categoria_cups_id'] ?? null,
            'cups_id' => $cupsContratadoData['cups_id'] ?? null,
            'cups_uuid' => $cups['uuid'] ?? null,
            'cups_codigo' => $cups['codigo'] ?? null,
            'cups_nombre' => $cups['nombre'] ?? null,
            'tarifa' => $cupsContratadoData['tarifa'] ?? '0',
            'estado' => $cupsContratadoData['estado'] ?? 'ACTIVO',
            'contrato_fecha_inicio' => $contrato['fecha_inicio'] ?? null,
            'contrato_fecha_fin' => $contrato['fecha_fin'] ?? null,
            'contrato_estado' => $contrato['estado'] ?? null,
            'empresa_nombre' => $empresa['nombre'] ?? null,
            'created_at' => $cupsContratadoData['created_at'] ?? now()->toISOString(),
            'updated_at' => now()->toISOString()
        ];

        // ✅ GUARDAR EN SQLite
        if ($this->isSQLiteAvailable()) {
            DB::connection('offline')->table('cups_contratados')->updateOrInsert(
                ['uuid' => $cupsContratadoData['uuid']],
                $offlineData
            );
        }

        // ✅ TAMBIÉN GUARDAR EN JSON CON ESTRUCTURA COMPLETA
        $jsonData = array_merge($offlineData, [
            'categoria_cups' => $categoriaCups,
            'contrato' => $contrato,
            'cups' => $cups
        ]);
        
        $this->storeData('cups_contratados/' . $cupsContratadoData['uuid'] . '.json', $jsonData);

        Log::debug('✅ CUPS contratado almacenado offline con relaciones', [
            'uuid' => $cupsContratadoData['uuid'],
            'cups_codigo' => $offlineData['cups_codigo'],
            'categoria' => $categoriaCups['nombre'] ?? 'N/A',
            'contrato_uuid' => $contrato['uuid'] ?? 'N/A'
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error almacenando CUPS contratado offline', [
            'error' => $e->getMessage(),
            'uuid' => $cupsContratadoData['uuid'] ?? 'sin-uuid',
            'trace' => $e->getTraceAsString()
        ]);
    }
}

/**
 * ✅ NUEVO: Guardar categoría CUPS
 */
private function storeCategoriasCupsOffline(array $categoriaData): void
{
    try {
        if (empty($categoriaData['id'])) {
            return;
        }

        if ($this->isSQLiteAvailable()) {
            DB::connection('offline')->table('categorias_cups')->updateOrInsert(
                ['id' => $categoriaData['id']],
                [
                    'uuid' => $categoriaData['uuid'] ?? null,
                    'nombre' => $categoriaData['nombre'] ?? 'SIN_NOMBRE',
                    'updated_at' => now()->toISOString()
                ]
            );
        }

        Log::debug('✅ Categoría CUPS guardada', [
            'id' => $categoriaData['id'],
            'nombre' => $categoriaData['nombre'] ?? 'N/A'
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error guardando categoría CUPS', [
            'error' => $e->getMessage()
        ]);
    }
}
/**
 * ✅ GUARDAR CONTRATO COMPLETO
 */
private function storeContratoOffline(array $contratoData): void
{
    try {
        if (empty($contratoData['uuid'])) {
            Log::warning('⚠️ Intentando guardar contrato sin UUID');
            return;
        }

        $empresa = $contratoData['empresa'] ?? [];

        $offlineData = [
            'id' => $contratoData['id'] ?? null,
            'uuid' => $contratoData['uuid'],
            'empresa_id' => $contratoData['empresa_id'] ?? null,
            'empresa_uuid' => $empresa['uuid'] ?? null,
            'empresa_nombre' => $empresa['nombre'] ?? null,
            'numero' => $contratoData['numero'] ?? null,
            'descripcion' => $contratoData['descripcion'] ?? null,
            'plan_beneficio' => $contratoData['plan_beneficio'] ?? null,
            'poliza' => $contratoData['poliza'] ?? null,
            'por_descuento' => $contratoData['por_descuento'] ?? null,
            'fecha_inicio' => $contratoData['fecha_inicio'] ?? null,
            'fecha_fin' => $contratoData['fecha_fin'] ?? null,
            'valor' => $contratoData['valor'] ?? null,
            'fecha_registro' => $contratoData['fecha_registro'] ?? null,
            'tipo' => $contratoData['tipo'] ?? null,
            'copago' => $contratoData['copago'] ?? null,
            'estado' => $contratoData['estado'] ?? 'ACTIVO',
            'created_at' => $contratoData['created_at'] ?? null,
            'updated_at' => $contratoData['updated_at'] ?? now()->toISOString(),
            'deleted_at' => $contratoData['deleted_at'] ?? null
        ];

        if ($this->isSQLiteAvailable()) {
            DB::connection('offline')->table('contratos')->updateOrInsert(
                ['uuid' => $contratoData['uuid']],
                $offlineData
            );
        }

        Log::debug('✅ Contrato guardado completo', [
            'uuid' => $contratoData['uuid'],
            'numero' => $offlineData['numero'],
            'empresa' => $empresa['nombre'] ?? 'N/A',
            'fecha_inicio' => $offlineData['fecha_inicio'],
            'fecha_fin' => $offlineData['fecha_fin']
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error guardando contrato', [
            'error' => $e->getMessage(),
            'uuid' => $contratoData['uuid'] ?? 'sin-uuid',
            'trace' => $e->getTraceAsString()
        ]);
    }
}

public function getCupsContratadoPorCupsUuidOffline(string $cupsUuid): ?array
{
    // ✅ USAR EL NUEVO MÉTODO MEJORADO
    return $this->getCupsContratadoVigenteOffline($cupsUuid);
}

/**
 * ✅ NUEVO: Sincronizar CUPS contratados desde API
 */
public function syncCupsContratadosFromApi(): bool
{
    try {
        Log::info('🔄 Sincronizando CUPS contratados desde API');

        $apiService = app(ApiService::class);
        
        if (!$apiService->isOnline()) {
            Log::warning('⚠️ Sin conexión para sincronizar CUPS contratados');
            return false;
        }

        $response = $apiService->get('/cups-contratados/disponibles');
        
        if (!$response['success']) {
            Log::error('❌ Error obteniendo CUPS contratados de API', [
                'error' => $response['error'] ?? 'Error desconocido'
            ]);
            return false;
        }

        $cupsContratados = $response['data'];
        
        if (empty($cupsContratados)) {
            Log::info('ℹ️ No hay CUPS contratados para sincronizar');
            return true;
        }

        $syncCount = 0;
        foreach ($cupsContratados as $cupsContratado) {
            $this->storeCupsContratadoOffline($cupsContratado);
            $syncCount++;
        }

        Log::info('✅ CUPS contratados sincronizados', [
            'total' => count($cupsContratados),
            'sincronizados' => $syncCount
        ]);

        return true;

    } catch (\Exception $e) {
        Log::error('❌ Error sincronizando CUPS contratados', [
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * ✅ REPARAR AGENDAS EXISTENTES - EXTRAER UUID DEL ORIGINAL_DATA
 */
public function repararUUIDsAgendas()
{
    try {
        Log::info('🔧 Iniciando reparación de UUIDs en agendas');
        
        // Obtener todas las agendas que tienen original_data pero no tienen usuario_medico_uuid
        $agendasSinUUID = DB::connection('offline')
            ->table('agendas')
            ->whereNull('usuario_medico_uuid')
            ->whereNotNull('original_data')
            ->get();
        
        Log::info('📊 Agendas a reparar', ['total' => $agendasSinUUID->count()]);
        
        $reparadas = 0;
        $errores = 0;
        
        foreach ($agendasSinUUID as $agenda) {
            try {
                // Decodificar los datos originales
                $originalData = json_decode($agenda->original_data, true);
                
                if ($originalData && isset($originalData['usuario_medico']) && is_array($originalData['usuario_medico'])) {
                    $usuarioMedicoUuid = $originalData['usuario_medico']['uuid'] ?? null;
                    
                    if ($usuarioMedicoUuid) {
                        // Actualizar la agenda con el UUID correcto
                        DB::connection('offline')
                            ->table('agendas')
                            ->where('uuid', $agenda->uuid)
                            ->update([
                                'usuario_medico_uuid' => $usuarioMedicoUuid,
                                'medico_uuid' => $usuarioMedicoUuid,
                                'updated_at' => now()
                            ]);
                        
                        $reparadas++;
                        
                        Log::info('✅ Agenda reparada', [
                            'agenda_uuid' => $agenda->uuid,
                            'medico_uuid' => $usuarioMedicoUuid
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $errores++;
                Log::error('❌ Error reparando agenda', [
                    'agenda_uuid' => $agenda->uuid,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        Log::info('🏁 Reparación completada', [
            'reparadas' => $reparadas,
            'errores' => $errores,
            'total' => $agendasSinUUID->count()
        ]);
        
        return [
            'success' => true,
            'reparadas' => $reparadas,
            'errores' => $errores,
            'total' => $agendasSinUUID->count()
        ];
        
    } catch (\Exception $e) {
        Log::error('❌ Error en reparación de UUIDs', ['error' => $e->getMessage()]);
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// app/Services/OfflineService.php - MÉTODO CORREGIDO
/**
 * ✅ OBTENER AGENDAS DEL DÍA - VERSIÓN CORREGIDA SIN DEPENDENCIA
 */
public function getAgendasDelDia($usuarioUuid, $fecha)
{
    try {
        Log::info('📅 getAgendasDelDia iniciado', [
            'usuario_uuid' => $usuarioUuid,
            'fecha' => $fecha,
            'sqlite_available' => $this->isSQLiteAvailable()
        ]);

        $agendas = [];

        if ($this->isSQLiteAvailable()) {
            // ✅ LIMPIAR FECHA
            $fechaLimpia = $fecha;
            if (strpos($fechaLimpia, 'T') !== false) {
                $fechaLimpia = explode('T', $fechaLimpia)[0];
            }

            Log::info('🔍 Preparando consulta SQLite', [
                'fecha_original' => $fecha,
                'fecha_limpia' => $fechaLimpia,
                'usuario_uuid' => $usuarioUuid
            ]);

            // ✅ VERIFICAR DATOS DISPONIBLES
            $totalAgendas = DB::connection('offline')->table('agendas')->count();
            Log::info('📊 Total agendas en SQLite', ['total' => $totalAgendas]);

            if ($totalAgendas > 0) {
                // ✅ VERIFICAR ESTRUCTURA DE DATOS
                $muestra = DB::connection('offline')
                    ->table('agendas')
                    ->select('uuid', 'fecha', 'usuario_medico_uuid', 'usuario_medico_id', 'etiqueta', 'medico_uuid')
                    ->whereNull('deleted_at')
                    ->limit(3)
                    ->get();
                
                Log::info('🔍 Muestra de agendas disponibles', [
                    'total_muestra' => $muestra->count(),
                    'campos_disponibles' => $muestra->first() ? array_keys((array)$muestra->first()) : [],
                    'primera_agenda' => $muestra->first() ? [
                        'uuid' => $muestra->first()->uuid,
                        'fecha' => $muestra->first()->fecha,
                        'usuario_medico_uuid' => $muestra->first()->usuario_medico_uuid ?? 'NULL',
                        'usuario_medico_id' => $muestra->first()->usuario_medico_id ?? 'NULL',
                        'medico_uuid' => $muestra->first()->medico_uuid ?? 'NULL',
                        'etiqueta' => $muestra->first()->etiqueta ?? 'NULL'
                    ] : 'N/A'
                ]);

                // ✅ REPARAR UUIDs NULL AUTOMÁTICAMENTE
                if ($muestra->first() && empty($muestra->first()->usuario_medico_uuid)) {
                    Log::warning('⚠️ UUIDs vacíos detectados, ejecutando reparación automática');
                    try {
                        $this->repararUUIDsAgendas();
                        Log::info('✅ Reparación automática completada');
                    } catch (\Exception $e) {
                        Log::error('❌ Error en reparación automática', ['error' => $e->getMessage()]);
                    }
                }

                // ✅ ESTRATEGIA MÚLTIPLE DE BÚSQUEDA
                $results = collect();

                // ESTRATEGIA 1: Por usuario_medico_uuid
                if (!$results->count()) {
                    $results = DB::connection('offline')
                        ->table('agendas')
                        ->whereNull('deleted_at')
                        ->where('usuario_medico_uuid', $usuarioUuid)
                        ->where(function($q) use ($fechaLimpia) {
                            $q->whereDate('fecha', $fechaLimpia)
                              ->orWhere('fecha', $fechaLimpia)
                              ->orWhere('fecha', 'LIKE', $fechaLimpia . '%');
                        })
                        ->get();
                    
                    Log::info('🔍 Estrategia 1 (usuario_medico_uuid)', [
                        'resultados' => $results->count(),
                        'usuario_uuid' => $usuarioUuid
                    ]);
                }

                // ESTRATEGIA 2: Por medico_uuid (campo alternativo)
                if (!$results->count()) {
                    $results = DB::connection('offline')
                        ->table('agendas')
                        ->whereNull('deleted_at')
                        ->where('medico_uuid', $usuarioUuid)
                        ->where(function($q) use ($fechaLimpia) {
                            $q->whereDate('fecha', $fechaLimpia)
                              ->orWhere('fecha', $fechaLimpia)
                              ->orWhere('fecha', 'LIKE', $fechaLimpia . '%');
                        })
                        ->get();
                    
                    Log::info('🔍 Estrategia 2 (medico_uuid)', [
                        'resultados' => $results->count()
                    ]);
                }

                // ESTRATEGIA 3: Solo por fecha (SIEMPRE usar como fallback)
                if (!$results->count()) {
                    $agendasDelDia = DB::connection('offline')
                        ->table('agendas')
                        ->whereNull('deleted_at')
                        ->where(function($q) use ($fechaLimpia) {
                            $q->whereDate('fecha', $fechaLimpia)
                              ->orWhere('fecha', $fechaLimpia)
                              ->orWhere('fecha', 'LIKE', $fechaLimpia . '%');
                        })
                        ->get();
                    
                    Log::info('🔍 Estrategia 3 (solo fecha)', [
                        'resultados' => $agendasDelDia->count(),
                        'fecha' => $fechaLimpia
                    ]);

                    // ✅ USAR TODAS LAS AGENDAS DEL DÍA (sin límite de 5)
                    if ($agendasDelDia->count() > 0) {
                        $results = $agendasDelDia;
                        Log::info('✅ Usando todas las agendas del día como fallback', [
                            'total' => $agendasDelDia->count()
                        ]);
                    }
                }

                // ESTRATEGIA 4: Búsqueda flexible por UUID parcial
                if (!$results->count() && strlen($usuarioUuid) > 8) {
                    $uuidParcial = substr($usuarioUuid, -12); // Últimos 12 caracteres
                    
                    $results = DB::connection('offline')
                        ->table('agendas')
                        ->whereNull('deleted_at')
                        ->where(function($q) use ($uuidParcial) {
                            $q->where('usuario_medico_uuid', 'LIKE', '%' . $uuidParcial)
                              ->orWhere('medico_uuid', 'LIKE', '%' . $uuidParcial);
                        })
                        ->where(function($q) use ($fechaLimpia) {
                            $q->whereDate('fecha', $fechaLimpia)
                              ->orWhere('fecha', $fechaLimpia)
                              ->orWhere('fecha', 'LIKE', $fechaLimpia . '%');
                        })
                        ->get();
                    
                    Log::info('🔍 Estrategia 4 (UUID parcial)', [
                        'resultados' => $results->count(),
                        'uuid_parcial' => $uuidParcial
                    ]);
                }

                Log::info('📊 Resultado final de búsqueda', [
                    'total_encontradas' => $results->count(),
                    'estrategia_exitosa' => $results->count() > 0 ? 'SÍ' : 'NO'
                ]);

                $agendas = $results->map(function ($agenda) {
                    return (array) $agenda;
                })->toArray();

            } else {
                Log::warning('⚠️ No hay agendas en SQLite');
            }

        } else {
            // ✅ FALLBACK A JSON
            Log::info('📱 Usando fallback JSON para agendas');
            
            $agendasPath = $this->getStoragePath() . '/agendas';
            if (is_dir($agendasPath)) {
                $files = glob($agendasPath . '/*.json');
                Log::info('📁 Archivos JSON encontrados', ['total' => count($files)]);
                
                foreach ($files as $file) {
                    $data = json_decode(file_get_contents($file), true);
                    if ($data && empty($data['deleted_at'])) {
                        // ✅ FILTROS JSON FLEXIBLES
                        $fechaAgenda = $data['fecha'] ?? '';
                        if (strpos($fechaAgenda, 'T') !== false) {
                            $fechaAgenda = explode('T', $fechaAgenda)[0];
                        }
                        
                        $cumpleFecha = ($fechaAgenda === $fecha || 
                                      strpos($fechaAgenda, $fecha) !== false);
                        
                        $cumpleUsuario = (
                            ($data['usuario_medico_uuid'] ?? '') === $usuarioUuid ||
                            ($data['medico_uuid'] ?? '') === $usuarioUuid ||
                            strpos($data['usuario_medico_uuid'] ?? '', substr($usuarioUuid, -12)) !== false
                        );
                        
                        if ($cumpleFecha && $cumpleUsuario) {
                            $agendas[] = $data;
                        }
                    }
                }

                Log::info('✅ Agendas JSON procesadas', [
                    'total_final' => count($agendas)
                ]);
            }
        }

        Log::info('✅ getAgendasDelDia completado', [
            'usuario_uuid' => $usuarioUuid,
            'fecha' => $fecha,
            'total_agendas_retornadas' => count($agendas),
            'primera_agenda_uuid' => $agendas[0]['uuid'] ?? 'N/A'
        ]);

        return $agendas;

    } catch (\Exception $e) {
        Log::error('❌ Error en getAgendasDelDia', [
            'error' => $e->getMessage(),
            'usuario_uuid' => $usuarioUuid,
            'fecha' => $fecha,
            'trace' => $e->getTraceAsString()
        ]);
        return [];
    }
}

/**
 * ✅ OBTENER CITAS POR AGENDA Y FECHA
 */
public function getCitasPorAgenda($agendaUuid, $fecha)
{
    try {
        Log::info('📋 Obteniendo citas por agenda offline', [
            'agenda_uuid' => $agendaUuid,
            'fecha' => $fecha
        ]);

        $citas = [];

        if ($this->isSQLiteAvailable()) {
            $query = DB::connection('offline')
                ->table('citas')
                ->leftJoin('pacientes', 'citas.paciente_uuid', '=', 'pacientes.uuid')
                ->where('citas.agenda_uuid', $agendaUuid)
                ->whereDate('citas.fecha_inicio', $fecha)
                ->whereNull('citas.deleted_at')
                ->select(
                    'citas.*',
                    'pacientes.nombre_completo',
                    'pacientes.documento',
                    'pacientes.telefono',
                    'pacientes.fecha_nacimiento',
                    'pacientes.sexo'
                )
                ->orderBy('citas.fecha_inicio');

            $results = $query->get();

            $citas = $results->map(function ($cita) {
                $citaArray = (array) $cita;
                
                // Construir objeto paciente
                if ($citaArray['nombre_completo']) {
                    $citaArray['paciente'] = [
                        'uuid' => $citaArray['paciente_uuid'],
                        'nombre_completo' => $citaArray['nombre_completo'],
                        'documento' => $citaArray['documento'],
                        'telefono' => $citaArray['telefono'],
                        'fecha_nacimiento' => $citaArray['fecha_nacimiento'],
                        'sexo' => $citaArray['sexo']
                    ];
                }

                // Limpiar campos duplicados
                unset($citaArray['nombre_completo'], $citaArray['documento'], 
                      $citaArray['telefono'], $citaArray['fecha_nacimiento'], $citaArray['sexo']);

                return $citaArray;
            })->toArray();

            Log::info('✅ Citas obtenidas desde SQLite con JOIN', [
                'total' => count($citas)
            ]);
        } else {
            // Fallback a JSON
            $citasPath = $this->getStoragePath() . '/citas';
            if (is_dir($citasPath)) {
                $files = glob($citasPath . '/*.json');
                foreach ($files as $file) {
                    $data = json_decode(file_get_contents($file), true);
                    if ($data && 
                        $data['agenda_uuid'] === $agendaUuid &&
                        date('Y-m-d', strtotime($data['fecha_inicio'])) === $fecha &&
                        empty($data['deleted_at'])) {
                        
                        // Enriquecer con datos del paciente si no están presentes
                        if (!isset($data['paciente']) && !empty($data['paciente_uuid'])) {
                            $paciente = $this->getPacienteOffline($data['paciente_uuid']);
                            if ($paciente) {
                                $data['paciente'] = $paciente;
                            }
                        }
                        
                        $citas[] = $data;
                    }
                }

                // Ordenar por hora
                usort($citas, function ($a, $b) {
                    return strcmp($a['fecha_inicio'] ?? '', $b['fecha_inicio'] ?? '');
                });
            }

            Log::info('✅ Citas obtenidas desde JSON', [
                'total' => count($citas)
            ]);
        }

        return $citas;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo citas por agenda offline', [
            'error' => $e->getMessage(),
            'agenda_uuid' => $agendaUuid,
            'fecha' => $fecha
        ]);
        return [];
    }
}

/**
 * ✅ ACTUALIZAR ESTADO DE CITA OFFLINE
 */
public function actualizarEstadoCita($uuid, $estado)
{
    try {
        Log::info('🔄 Actualizando estado de cita offline', [
            'cita_uuid' => $uuid,
            'nuevo_estado' => $estado
        ]);

        $updated = false;

        if ($this->isSQLiteAvailable()) {
            $affected = DB::connection('offline')
                ->table('citas')
                ->where('uuid', $uuid)
                ->update([
                    'estado' => $estado,
                    'updated_at' => now()->toISOString()
                ]);
                
            $updated = $affected > 0;
            
            if ($updated) {
                Log::info('✅ Estado actualizado en SQLite', [
                    'cita_uuid' => $uuid,
                    'nuevo_estado' => $estado
                ]);
            }
        }

        // También actualizar en JSON si existe
        $jsonPath = $this->getStoragePath() . "/citas/{$uuid}.json";
        if (file_exists($jsonPath)) {
            $citaData = json_decode(file_get_contents($jsonPath), true);
            if ($citaData) {
                $citaData['estado'] = $estado;
                $citaData['updated_at'] = now()->toISOString();
                file_put_contents($jsonPath, json_encode($citaData, JSON_PRETTY_PRINT));
                $updated = true;
                
                Log::info('✅ Estado actualizado en JSON', [
                    'cita_uuid' => $uuid,
                    'nuevo_estado' => $estado
                ]);
            }
        }

        return $updated;

    } catch (\Exception $e) {
        Log::error('❌ Error actualizando estado de cita offline', [
            'error' => $e->getMessage(),
            'cita_uuid' => $uuid,
            'estado' => $estado
        ]);
        return false;
    }
}
public function getCupsContratadoVigenteOffline(string $cupsUuid): ?array
{
    try {
        Log::info('🔍 Buscando CUPS contratado vigente offline', [
            'cups_uuid' => $cupsUuid,
            'fecha_actual' => now()->format('Y-m-d')
        ]);

        $fechaActual = now()->format('Y-m-d');

        if ($this->isSQLiteAvailable()) {
            $cupsContratado = DB::connection('offline')->table('cups_contratados')
                ->where('cups_uuid', $cupsUuid)
                ->where('estado', 'ACTIVO')
                ->where('contrato_estado', 'ACTIVO')
                ->where('contrato_fecha_inicio', '<=', $fechaActual)
                ->where('contrato_fecha_fin', '>=', $fechaActual)
                ->orderBy('contrato_fecha_fin', 'desc')
                ->first();
            
            if ($cupsContratado) {
                $result = (array) $cupsContratado;
                
                // ✅ VALIDACIÓN ADICIONAL DE FECHAS
                if ($this->isContractExpired($result)) {
                    Log::warning('⚠️ Contrato expirado encontrado en cache, eliminando', [
                        'cups_uuid' => $cupsUuid,
                        'fecha_fin' => $result['contrato_fecha_fin']
                    ]);
                    
                    $this->invalidateCupsContratadoCache($cupsUuid);
                    return null;
                }
                
                return $result;
            }
        }

        // ✅ FALLBACK A JSON CON VALIDACIÓN ESTRICTA
        return $this->getValidContractFromJson($cupsUuid, $fechaActual);

    } catch (\Exception $e) {
        Log::error('❌ Error buscando CUPS contratado vigente offline', [
            'error' => $e->getMessage(),
            'cups_uuid' => $cupsUuid
        ]);
        return null;
    }
}

private function isContractExpired(array $contract): bool
{
    $fechaFin = $contract['contrato_fecha_fin'] ?? null;
    if (!$fechaFin) return true;
    
    $fechaFinCarbon = \Carbon\Carbon::parse($fechaFin);
    return $fechaFinCarbon->isPast();
}
/**
 * ✅ NUEVO: Validar vigencia de contrato con logging detallado
 */
private function validarVigenciaContrato(array $contrato, string $fechaActual): bool
{
    try {
        // ✅ VALIDAR CAMPOS REQUERIDOS
        if (empty($contrato['estado']) || 
            empty($contrato['contrato_estado']) ||
            empty($contrato['contrato_fecha_inicio']) ||
            empty($contrato['contrato_fecha_fin'])) {
            
            Log::warning('⚠️ Contrato con campos faltantes', [
                'contrato_uuid' => $contrato['uuid'] ?? 'N/A',
                'estado' => $contrato['estado'] ?? 'null',
                'contrato_estado' => $contrato['contrato_estado'] ?? 'null',
                'fecha_inicio' => $contrato['contrato_fecha_inicio'] ?? 'null',
                'fecha_fin' => $contrato['contrato_fecha_fin'] ?? 'null'
            ]);
            return false;
        }

        // ✅ VALIDAR ESTADOS
        if ($contrato['estado'] !== 'ACTIVO' || $contrato['contrato_estado'] !== 'ACTIVO') {
            Log::info('ℹ️ Contrato con estado inactivo', [
                'contrato_uuid' => $contrato['uuid'],
                'estado' => $contrato['estado'],
                'contrato_estado' => $contrato['contrato_estado']
            ]);
            return false;
        }

        // ✅ VALIDAR FECHAS CON LOGGING DETALLADO
        $fechaInicio = $contrato['contrato_fecha_inicio'];
        $fechaFin = $contrato['contrato_fecha_fin'];
        
        $esVigente = ($fechaInicio <= $fechaActual) && ($fechaFin >= $fechaActual);
        
        Log::info($esVigente ? '✅ Contrato vigente' : '⚠️ Contrato fuera de vigencia', [
            'contrato_uuid' => $contrato['uuid'],
            'fecha_actual' => $fechaActual,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'inicio_ok' => $fechaInicio <= $fechaActual,
            'fin_ok' => $fechaFin >= $fechaActual,
            'es_vigente' => $esVigente
        ]);

        return $esVigente;

    } catch (\Exception $e) {
        Log::error('❌ Error validando vigencia de contrato', [
            'error' => $e->getMessage(),
            'contrato_uuid' => $contrato['uuid'] ?? 'N/A'
        ]);
        return false;
    }
}
/**
 * ✅ NUEVO: Limpiar CUPS contratados existentes
 */
public function clearCupsContratados(): bool
{
    try {
        Log::info('🗑️ Limpiando CUPS contratados offline');

        // ✅ LIMPIAR SQLite
        if ($this->isSQLiteAvailable()) {
            DB::connection('offline')->table('cups_contratados')->delete();
            Log::info('✅ CUPS contratados eliminados de SQLite');
        }

        // ✅ LIMPIAR ARCHIVOS JSON
        $cupsContratadosPath = $this->storagePath . '/cups_contratados';
        if (is_dir($cupsContratadosPath)) {
            $files = glob($cupsContratadosPath . '/*.json');
            foreach ($files as $file) {
                unlink($file);
            }
            Log::info('✅ Archivos JSON de CUPS contratados eliminados', [
                'files_deleted' => count($files)
            ]);
        }

        return true;

    } catch (\Exception $e) {
        Log::error('❌ Error limpiando CUPS contratados', [
            'error' => $e->getMessage()
        ]);
        return false;
    }
}
/**
 * ✅ NUEVO: Invalidar cache de CUPS contratado específico
 */
public function invalidateCupsContratadoCache(string $cupsUuid): bool
{
    try {
        Log::info('🗑️ Invalidando cache de CUPS contratado', [
            'cups_uuid' => $cupsUuid
        ]);

        $invalidated = false;

        // ✅ LIMPIAR SQLite
        if ($this->isSQLiteAvailable()) {
            $deleted = DB::connection('offline')->table('cups_contratados')
                ->where('cups_uuid', $cupsUuid)
                ->delete();
            
            if ($deleted > 0) {
                Log::info('✅ Cache SQLite invalidado', [
                    'cups_uuid' => $cupsUuid,
                    'records_deleted' => $deleted
                ]);
                $invalidated = true;
            }
        }

        // ✅ LIMPIAR ARCHIVOS JSON
        $cupsContratadosPath = $this->storagePath . '/cups_contratados';
        if (is_dir($cupsContratadosPath)) {
            $files = glob($cupsContratadosPath . '/*.json');
            
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($data && isset($data['cups_uuid']) && $data['cups_uuid'] === $cupsUuid) {
                    unlink($file);
                    Log::info('✅ Archivo JSON de cache eliminado', [
                        'file' => basename($file),
                        'cups_uuid' => $cupsUuid
                    ]);
                    $invalidated = true;
                }
            }
        }

        return $invalidated;

    } catch (\Exception $e) {
        Log::error('❌ Error invalidando cache de CUPS contratado', [
            'cups_uuid' => $cupsUuid,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * ✅ NUEVO: Forzar recarga de CUPS contratado desde API
 */
public function forceReloadCupsContratado(string $cupsUuid): ?array
{
    try {
        Log::info('🔄 Forzando recarga de CUPS contratado', [
            'cups_uuid' => $cupsUuid
        ]);

        // ✅ INVALIDAR CACHE PRIMERO
        $this->invalidateCupsContratadoCache($cupsUuid);

        // ✅ INTENTAR RECARGAR DESDE API
        $apiService = app(ApiService::class);
        $authService = app(AuthService::class);

        if ($authService->hasValidToken() && $apiService->isOnline()) {
            try {
                $response = $apiService->get("/cups-contratados/por-cups/{$cupsUuid}");
                
                if ($response['success']) {
                    $this->storeCupsContratadoOffline($response['data']);
                    
                    Log::info('✅ CUPS contratado recargado desde API', [
                        'cups_uuid' => $cupsUuid,
                        'contrato_uuid' => $response['data']['uuid']
                    ]);
                    
                    return $response['data'];
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Error recargando desde API', [
                    'cups_uuid' => $cupsUuid,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return null;

    } catch (\Exception $e) {
        Log::error('❌ Error forzando recarga de CUPS contratado', [
            'cups_uuid' => $cupsUuid,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}
private function getValidContractFromJson(string $cupsUuid, string $fechaActual): ?array
{
    try {
        Log::info('📁 Buscando contrato válido en archivos JSON', [
            'cups_uuid' => $cupsUuid,
            'fecha_actual' => $fechaActual
        ]);

        $cupsContratadosPath = $this->storagePath . '/cups_contratados';
        
        if (!is_dir($cupsContratadosPath)) {
            Log::info('📂 Directorio de CUPS contratados no existe');
            return null;
        }

        $files = glob($cupsContratadosPath . '/*.json');
        $validContracts = [];

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            
            if (!$data || !isset($data['cups_uuid']) || $data['cups_uuid'] !== $cupsUuid) {
                continue;
            }

            // ✅ VALIDAR VIGENCIA CON EL MÉTODO EXISTENTE
            if ($this->validarVigenciaContrato($data, $fechaActual)) {
                $validContracts[] = $data;
                
                Log::info('✅ Contrato válido encontrado en JSON', [
                    'contrato_uuid' => $data['uuid'],
                    'cups_codigo' => $data['cups_codigo'] ?? 'N/A',
                    'fecha_inicio' => $data['contrato_fecha_inicio'],
                    'fecha_fin' => $data['contrato_fecha_fin']
                ]);
            } else {
                Log::info('⚠️ Contrato no vigente encontrado en JSON', [
                    'contrato_uuid' => $data['uuid'],
                    'fecha_inicio' => $data['contrato_fecha_inicio'] ?? 'null',
                    'fecha_fin' => $data['contrato_fecha_fin'] ?? 'null',
                    'estado' => $data['estado'] ?? 'null'
                ]);
            }
        }

        if (empty($validContracts)) {
            Log::info('⚠️ No se encontraron contratos válidos en JSON', [
                'cups_uuid' => $cupsUuid,
                'archivos_revisados' => count($files)
            ]);
            return null;
        }

        // ✅ DEVOLVER EL CONTRATO MÁS RECIENTE (POR FECHA DE FIN)
        usort($validContracts, function($a, $b) {
            $fechaFinA = $a['contrato_fecha_fin'] ?? '1900-01-01';
            $fechaFinB = $b['contrato_fecha_fin'] ?? '1900-01-01';
            return strcmp($fechaFinB, $fechaFinA); // Orden descendente
        });

        $selectedContract = $validContracts[0];
        
        Log::info('✅ Contrato seleccionado de JSON', [
            'contrato_uuid' => $selectedContract['uuid'],
            'cups_codigo' => $selectedContract['cups_codigo'] ?? 'N/A',
            'fecha_fin' => $selectedContract['contrato_fecha_fin'],
            'total_contratos_validos' => count($validContracts)
        ]);

        return $selectedContract;

    } catch (\Exception $e) {
        Log::error('❌ Error buscando contrato válido en JSON', [
            'cups_uuid' => $cupsUuid,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return null;
    }
}
/**
 * ✅ GUARDAR HISTORIA CLÍNICA OFFLINE - VERSIÓN CORREGIDA
 * SQLite: Solo campos escalares (búsqueda rápida)
 * JSON: Estructura completa con arrays (datos completos)
 */
public function storeHistoriaClinicaOffline(array $historiaData, bool $needsSync = false): void
{
    try {
        if (!is_array($historiaData)) {
            throw new \InvalidArgumentException('historiaData debe ser un array');
        }

        if (empty($historiaData['uuid'])) {
            Log::warning('⚠️ Intentando guardar historia sin UUID');
            return;
        }

        // ✅ ASEGURAR QUE EL UUID SEA STRING (NO OBJETO LazyUuidFromString)
        $uuid = $historiaData['uuid'];
        if (is_object($uuid)) {
            $uuid = (string) $uuid;
        }
        $historiaData['uuid'] = $uuid;

        // ✅ EXTRAER Y PRESERVAR ARRAYS DE RELACIONES ANTES DE PROCESAR
        $diagnosticos = $historiaData['diagnosticos'] ?? [];
        $medicamentos = $historiaData['medicamentos'] ?? [];
        $remisiones = $historiaData['remisiones'] ?? [];
        $cups = $historiaData['cups'] ?? [];
        $complementaria = $historiaData['complementaria'] ?? null;
        $cita = $historiaData['cita'] ?? null;
        $sede = $historiaData['sede'] ?? null;

        Log::info('📦 Arrays extraídos para almacenamiento', [
            'uuid' => $uuid,
            'uuid_type' => gettype($uuid),
            'diagnosticos_count' => count($diagnosticos),
            'medicamentos_count' => count($medicamentos),
            'remisiones_count' => count($remisiones),
            'cups_count' => count($cups),
            'tiene_complementaria' => !is_null($complementaria),
            'diagnostico_sample' => !empty($diagnosticos) ? array_keys($diagnosticos[0]) : [],
            'medicamento_sample' => !empty($medicamentos) ? array_keys($medicamentos[0]) : [],
            'tiene_cita' => !is_null($cita),
            'tiene_sede' => !is_null($sede)
        ]);

        // ✅✅✅ PREPARAR DATOS PARA SQLITE (SOLO CAMPOS ESCALARES) ✅✅✅
        $offlineData = [
            // ═══════════════════════════════════════════
            // 🔹 CAMPOS BÁSICOS
            // ═══════════════════════════════════════════
            'uuid' => $uuid, // ✅ USAR EL UUID YA CONVERTIDO A STRING
            'cita_uuid' => is_object($historiaData['cita_uuid'] ?? null) ? (string) $historiaData['cita_uuid'] : ($historiaData['cita_uuid'] ?? null),
            'cita_id' => $historiaData['cita_id'] ?? null,
            'sede_id' => $historiaData['sede_id'] ?? null,
            'usuario_id' => $historiaData['usuario_id'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 DATOS DE CONSULTA
            // ═══════════════════════════════════════════
            'especialidad' => $historiaData['especialidad'] ?? null,
            'tipo_consulta' => $historiaData['tipo_consulta'] ?? null,
            'finalidad' => $historiaData['finalidad'] ?? null,
            'causa_externa' => $historiaData['causa_externa'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 MOTIVO Y ENFERMEDAD
            // ═══════════════════════════════════════════
            'motivo_consulta' => $historiaData['motivo_consulta'] ?? '',
            'enfermedad_actual' => $historiaData['enfermedad_actual'] ?? '',
            'diagnostico_principal' => $historiaData['diagnostico_principal'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 ACOMPAÑANTE
            // ═══════════════════════════════════════════
            'acompanante' => $historiaData['acompanante'] ?? null,
            'acu_telefono' => $historiaData['acu_telefono'] ?? null,
            'acu_parentesco' => $historiaData['acu_parentesco'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 DISCAPACIDADES
            // ═══════════════════════════════════════════
            'discapacidad_fisica' => $historiaData['discapacidad_fisica'] ?? null,
            'discapacidad_visual' => $historiaData['discapacidad_visual'] ?? null,
            'discapacidad_mental' => $historiaData['discapacidad_mental'] ?? null,
            'discapacidad_auditiva' => $historiaData['discapacidad_auditiva'] ?? null,
            'discapacidad_intelectual' => $historiaData['discapacidad_intelectual'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 DROGODEPENDENCIA
            // ═══════════════════════════════════════════
            'drogo_dependiente' => $historiaData['drogo_dependiente'] ?? null,
            'drogo_dependiente_cual' => $historiaData['drogo_dependiente_cual'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 MEDIDAS ANTROPOMÉTRICAS
            // ═══════════════════════════════════════════
            'peso' => $historiaData['peso'] ?? null,
            'talla' => $historiaData['talla'] ?? null,
            'imc' => $historiaData['imc'] ?? null,
            'clasificacion' => $historiaData['clasificacion'] ?? null,
            'perimetro_abdominal' => $historiaData['perimetro_abdominal'] ?? null,
            'obs_perimetro_abdominal' => $historiaData['obs_perimetro_abdominal'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 ANTECEDENTES FAMILIARES
            // ═══════════════════════════════════════════
            'hipertension_arterial' => $historiaData['hipertension_arterial'] ?? null,
            'parentesco_hipertension' => $historiaData['parentesco_hipertension'] ?? null,
            'diabetes_mellitus' => $historiaData['diabetes_mellitus'] ?? null,
            'parentesco_mellitus' => $historiaData['parentesco_mellitus'] ?? null,
            'artritis' => $historiaData['artritis'] ?? null,
            'parentesco_artritis' => $historiaData['parentesco_artritis'] ?? null,
            'enfermedad_cardiovascular' => $historiaData['enfermedad_cardiovascular'] ?? null,
            'parentesco_cardiovascular' => $historiaData['parentesco_cardiovascular'] ?? null,
            'antecedente_metabolico' => $historiaData['antecedente_metabolico'] ?? null,
            'parentesco_metabolico' => $historiaData['parentesco_metabolico'] ?? null,
            'cancer_mama_estomago_prostata_colon' => $historiaData['cancer_mama_estomago_prostata_colon'] ?? null,
            'parentesco_cancer' => $historiaData['parentesco_cancer'] ?? null,
            'leucemia' => $historiaData['leucemia'] ?? null,
            'parentesco_leucemia' => $historiaData['parentesco_leucemia'] ?? null,
            'vih' => $historiaData['vih'] ?? null,
            'parentesco_vih' => $historiaData['parentesco_vih'] ?? null,
            'otro' => $historiaData['otro'] ?? null,
            'parentesco_otro' => $historiaData['parentesco_otro'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 ANTECEDENTES PERSONALES (TODOS)
            // ═══════════════════════════════════════════
            'hipertension_arterial_personal' => $historiaData['hipertension_arterial_personal'] ?? 'NO',
            'obs_personal_hipertension_arterial' => $historiaData['obs_personal_hipertension_arterial'] ?? null,
            'diabetes_mellitus_personal' => $historiaData['diabetes_mellitus_personal'] ?? 'NO',
            'obs_personal_mellitus' => $historiaData['obs_personal_mellitus'] ?? null,
            'enfermedad_cardiovascular_personal' => $historiaData['enfermedad_cardiovascular_personal'] ?? null,
            'obs_personal_enfermedad_cardiovascular' => $historiaData['obs_personal_enfermedad_cardiovascular'] ?? null,
            'arterial_periferica_personal' => $historiaData['arterial_periferica_personal'] ?? null,
            'obs_personal_arterial_periferica' => $historiaData['obs_personal_arterial_periferica'] ?? null,
            'carotidea_personal' => $historiaData['carotidea_personal'] ?? null,
            'obs_personal_carotidea' => $historiaData['obs_personal_carotidea'] ?? null,
            'aneurisma_aorta_personal' => $historiaData['aneurisma_aorta_personal'] ?? null,
            'obs_personal_aneurisma_aorta' => $historiaData['obs_personal_aneurisma_aorta'] ?? null,
            'sindrome_coronario_agudo_angina_personal' => $historiaData['sindrome_coronario_agudo_angina_personal'] ?? null,
            'obs_personal_sindrome_coronario' => $historiaData['obs_personal_sindrome_coronario'] ?? null,
            'artritis_personal' => $historiaData['artritis_personal'] ?? null,
            'obs_personal_artritis' => $historiaData['obs_personal_artritis'] ?? null,
            'iam_personal' => $historiaData['iam_personal'] ?? null,
            'obs_personal_iam' => $historiaData['obs_personal_iam'] ?? null,
            'revascul_coronaria_personal' => $historiaData['revascul_coronaria_personal'] ?? null,
            'obs_personal_revascul_coronaria' => $historiaData['obs_personal_revascul_coronaria'] ?? null,
            'insuficiencia_cardiaca_personal' => $historiaData['insuficiencia_cardiaca_personal'] ?? null,
            'obs_personal_insuficiencia_cardiaca' => $historiaData['obs_personal_insuficiencia_cardiaca'] ?? null,
            'amputacion_pie_diabetico_personal' => $historiaData['amputacion_pie_diabetico_personal'] ?? null,
            'obs_personal_amputacion_pie_diabetico' => $historiaData['obs_personal_amputacion_pie_diabetico'] ?? null,
            'enfermedad_pulmonar_personal' => $historiaData['enfermedad_pulmonar_personal'] ?? null,
            'obs_personal_enfermedad_pulmonar' => $historiaData['obs_personal_enfermedad_pulmonar'] ?? null,
            'victima_maltrato_personal' => $historiaData['victima_maltrato_personal'] ?? null,
            'obs_personal_maltrato_personal' => $historiaData['obs_personal_maltrato_personal'] ?? null,
            'antecedentes_quirurgicos' => $historiaData['antecedentes_quirurgicos'] ?? null,
            'obs_personal_antecedentes_quirurgicos' => $historiaData['obs_personal_antecedentes_quirurgicos'] ?? null,
            'acontosis_personal' => $historiaData['acontosis_personal'] ?? null,
            'obs_personal_acontosis' => $historiaData['obs_personal_acontosis'] ?? null,
            'otro_personal' => $historiaData['otro_personal'] ?? null,
            'obs_personal_otro' => $historiaData['obs_personal_otro'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 TEST DE MORISKY
            // ═══════════════════════════════════════════
            'olvida_tomar_medicamentos' => $historiaData['olvida_tomar_medicamentos'] ?? 'NO',
            'toma_medicamentos_hora_indicada' => $historiaData['toma_medicamentos_hora_indicada'] ?? 'SI',
            'cuando_esta_bien_deja_tomar_medicamentos' => $historiaData['cuando_esta_bien_deja_tomar_medicamentos'] ?? 'NO',
            'siente_mal_deja_tomarlos' => $historiaData['siente_mal_deja_tomarlos'] ?? 'NO',
            'valoracion_psicologia' => $historiaData['valoracion_psicologia'] ?? 'NO',
            'adherente' => $historiaData['adherente'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 REVISIÓN POR SISTEMAS
            // ═══════════════════════════════════════════
            'general' => $historiaData['general'] ?? null,
            'cabeza' => $historiaData['cabeza'] ?? null,
            'orl' => $historiaData['orl'] ?? null,
            'respiratorio' => $historiaData['respiratorio'] ?? null,
            'cardiovascular' => $historiaData['cardiovascular'] ?? null,
            'gastrointestinal' => $historiaData['gastrointestinal'] ?? null,
            'osteoatromuscular' => $historiaData['osteoatromuscular'] ?? null,
            'snc' => $historiaData['snc'] ?? null,
            'revision_sistemas' => $historiaData['revision_sistemas'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 SIGNOS VITALES
            // ═══════════════════════════════════════════
            'presion_arterial_sistolica_sentado_pie' => $historiaData['presion_arterial_sistolica_sentado_pie'] ?? null,
            'presion_arterial_distolica_sentado_pie' => $historiaData['presion_arterial_distolica_sentado_pie'] ?? null,
            'presion_arterial_sistolica_acostado' => $historiaData['presion_arterial_sistolica_acostado'] ?? null,
            'presion_arterial_distolica_acostado' => $historiaData['presion_arterial_distolica_acostado'] ?? null,
            'frecuencia_cardiaca' => $historiaData['frecuencia_cardiaca'] ?? null,
            'frecuencia_respiratoria' => $historiaData['frecuencia_respiratoria'] ?? null,
            'temperatura' => $historiaData['temperatura'] ?? null,
            'saturacion_oxigeno' => $historiaData['saturacion_oxigeno'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 EXAMEN FÍSICO
            // ═══════════════════════════════════════════
            'ef_cabeza' => $historiaData['ef_cabeza'] ?? null,
            'obs_cabeza' => $historiaData['obs_cabeza'] ?? null,
            'agudeza_visual' => $historiaData['agudeza_visual'] ?? null,
            'obs_agudeza_visual' => $historiaData['obs_agudeza_visual'] ?? null,
            'fundoscopia' => $historiaData['fundoscopia'] ?? null,
            'obs_fundoscopia' => $historiaData['obs_fundoscopia'] ?? null,
            'oidos' => $historiaData['oidos'] ?? null,
            'nariz_senos_paranasales' => $historiaData['nariz_senos_paranasales'] ?? null,
            'cavidad_oral' => $historiaData['cavidad_oral'] ?? null,
            'cuello' => $historiaData['cuello'] ?? null,
            'obs_cuello' => $historiaData['obs_cuello'] ?? null,
            'cardio_respiratorio' => $historiaData['cardio_respiratorio'] ?? null,
            'torax' => $historiaData['torax'] ?? null,
            'obs_torax' => $historiaData['obs_torax'] ?? null,
            'mamas' => $historiaData['mamas'] ?? null,
            'obs_mamas' => $historiaData['obs_mamas'] ?? null,
            'abdomen' => $historiaData['abdomen'] ?? null,
            'obs_abdomen' => $historiaData['obs_abdomen'] ?? null,
            'genito_urinario' => $historiaData['genito_urinario'] ?? null,
            'obs_genito_urinario' => $historiaData['obs_genito_urinario'] ?? null,
            'musculo_esqueletico' => $historiaData['musculo_esqueletico'] ?? null,
            'extremidades' => $historiaData['extremidades'] ?? null,
            'obs_extremidades' => $historiaData['obs_extremidades'] ?? null,
            'piel_anexos_pulsos' => $historiaData['piel_anexos_pulsos'] ?? null,
            'obs_piel_anexos_pulsos' => $historiaData['obs_piel_anexos_pulsos'] ?? null,
            'inspeccion_sensibilidad_pies' => $historiaData['inspeccion_sensibilidad_pies'] ?? null,
            'sistema_nervioso' => $historiaData['sistema_nervioso'] ?? null,
            'obs_sistema_nervioso' => $historiaData['obs_sistema_nervioso'] ?? null,
            'capacidad_cognitiva' => $historiaData['capacidad_cognitiva'] ?? null,
            'obs_capacidad_cognitiva' => $historiaData['obs_capacidad_cognitiva'] ?? null,
            'capacidad_cognitiva_orientacion' => $historiaData['capacidad_cognitiva_orientacion'] ?? null,
            'orientacion' => $historiaData['orientacion'] ?? null,
            'obs_orientacion' => $historiaData['obs_orientacion'] ?? null,
            'reflejo_aquiliar' => $historiaData['reflejo_aquiliar'] ?? null,
            'obs_reflejo_aquiliar' => $historiaData['obs_reflejo_aquiliar'] ?? null,
            'reflejo_patelar' => $historiaData['reflejo_patelar'] ?? null,
            'obs_reflejo_patelar' => $historiaData['obs_reflejo_patelar'] ?? null,
            'hallazgo_positivo_examen_fisico' => $historiaData['hallazgo_positivo_examen_fisico'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 FACTORES DE RIESGO
            // ═══════════════════════════════════════════
            'tabaquismo' => $historiaData['tabaquismo'] ?? null,
            'obs_tabaquismo' => $historiaData['obs_tabaquismo'] ?? null,
            'dislipidemia' => $historiaData['dislipidemia'] ?? null,
            'obs_dislipidemia' => $historiaData['obs_dislipidemia'] ?? null,
            'menor_cierta_edad' => $historiaData['menor_cierta_edad'] ?? null,
            'obs_menor_cierta_edad' => $historiaData['obs_menor_cierta_edad'] ?? null,
            'condicion_clinica_asociada' => $historiaData['condicion_clinica_asociada'] ?? null,
            'obs_condicion_clinica_asociada' => $historiaData['obs_condicion_clinica_asociada'] ?? null,
            'lesion_organo_blanco' => $historiaData['lesion_organo_blanco'] ?? null,
            'obs_lesion_organo_blanco' => $historiaData['obs_lesion_organo_blanco'] ?? null,
            'descripcion_lesion_organo_blanco' => $historiaData['descripcion_lesion_organo_blanco'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 EXÁMENES COMPLEMENTARIOS
            // ═══════════════════════════════════════════
            'fex_es' => $historiaData['fex_es'] ?? null,
            'electrocardiograma' => $historiaData['electrocardiograma'] ?? null,
            'fex_es1' => $historiaData['fex_es1'] ?? null,
            'ecocardiograma' => $historiaData['ecocardiograma'] ?? null,
            'fex_es2' => $historiaData['fex_es2'] ?? null,
            'ecografia_renal' => $historiaData['ecografia_renal'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 CLASIFICACIONES
            // ═══════════════════════════════════════════
            'clasificacion_estado_metabolico' => $historiaData['clasificacion_estado_metabolico'] ?? null,
            'clasificacion_hta' => $historiaData['clasificacion_hta'] ?? null,
            'clasificacion_dm' => $historiaData['clasificacion_dm'] ?? null,
            'clasificacion_rcv' => $historiaData['clasificacion_rcv'] ?? null,
            'clasificacion_erc_estado' => $historiaData['clasificacion_erc_estado'] ?? null,
            'clasificacion_erc_estadodos' => $historiaData['clasificacion_erc_estadodos'] ?? null,
            'clasificacion_erc_categoria_ambulatoria_persistente' => $historiaData['clasificacion_erc_categoria_ambulatoria_persistente'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 TASAS DE FILTRACIÓN
            // ═══════════════════════════════════════════
            'tasa_filtracion_glomerular_ckd_epi' => $historiaData['tasa_filtracion_glomerular_ckd_epi'] ?? null,
            'tasa_filtracion_glomerular_gockcroft_gault' => $historiaData['tasa_filtracion_glomerular_gockcroft_gault'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 EDUCACIÓN EN SALUD
            // ═══════════════════════════════════════════
            'alimentacion' => $historiaData['alimentacion'] ?? null,
            'disminucion_consumo_sal_azucar' => $historiaData['disminucion_consumo_sal_azucar'] ?? null,
            'fomento_actividad_fisica' => $historiaData['fomento_actividad_fisica'] ?? null,
            'importancia_adherencia_tratamiento' => $historiaData['importancia_adherencia_tratamiento'] ?? null,
            'consumo_frutas_verduras' => $historiaData['consumo_frutas_verduras'] ?? null,
            'manejo_estres' => $historiaData['manejo_estres'] ?? null,
            'disminucion_consumo_cigarrillo' => $historiaData['disminucion_consumo_cigarrillo'] ?? null,
            'disminucion_peso' => $historiaData['disminucion_peso'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 OTROS CAMPOS
            // ═══════════════════════════════════════════
            'insulina_requiriente' => $historiaData['insulina_requiriente'] ?? null,
            'recibe_tratamiento_alternativo' => $historiaData['recibe_tratamiento_alternativo'] ?? null,
            'recibe_tratamiento_con_plantas_medicinales' => $historiaData['recibe_tratamiento_con_plantas_medicinales'] ?? null,
            'recibe_ritual_medicina_tradicional' => $historiaData['recibe_ritual_medicina_tradicional'] ?? null,
            'numero_frutas_diarias' => $historiaData['numero_frutas_diarias'] ?? null,
            'elevado_consumo_grasa_saturada' => $historiaData['elevado_consumo_grasa_saturada'] ?? null,
            'adiciona_sal_despues_preparar_comida' => $historiaData['adiciona_sal_despues_preparar_comida'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 REFORMULACIÓN
            // ═══════════════════════════════════════════
            'razon_reformulacion' => $historiaData['razon_reformulacion'] ?? null,
            'motivo_reformulacion' => $historiaData['motivo_reformulacion'] ?? null,
            'reformulacion_quien_reclama' => $historiaData['reformulacion_quien_reclama'] ?? null,
            'reformulacion_nombre_reclama' => $historiaData['reformulacion_nombre_reclama'] ?? null,
            'adicional' => $historiaData['adicional'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 OBSERVACIONES
            // ═══════════════════════════════════════════
            'observaciones_generales' => $historiaData['observaciones_generales'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 CAMPOS DE ESPECIALIDADES COMPLEMENTARIAS
            // ═══════════════════════════════════════════
            
            // ✅ PSICOLOGÍA
            'estructura_familiar' => $historiaData['estructura_familiar'] ?? null,
            'psicologia_red_apoyo' => $historiaData['psicologia_red_apoyo'] ?? null,
            'psicologia_comportamiento_consulta' => $historiaData['psicologia_comportamiento_consulta'] ?? null,
            'psicologia_tratamiento_actual_adherencia' => $historiaData['psicologia_tratamiento_actual_adherencia'] ?? null,
            'psicologia_descripcion_problema' => $historiaData['psicologia_descripcion_problema'] ?? null,
            'analisis_conclusiones' => $historiaData['analisis_conclusiones'] ?? null,
            'psicologia_plan_intervencion_recomendacion' => $historiaData['psicologia_plan_intervencion_recomendacion'] ?? null,
            'avance_paciente' => $historiaData['avance_paciente'] ?? null,
            
            // ✅ FISIOTERAPIA
            'actitud' => $historiaData['actitud'] ?? null,
            'evaluacion_d' => $historiaData['evaluacion_d'] ?? null,
            'evaluacion_p' => $historiaData['evaluacion_p'] ?? null,
            'estado' => $historiaData['estado'] ?? null,
            'evaluacion_dolor' => $historiaData['evaluacion_dolor'] ?? null,
            'evaluacion_os' => $historiaData['evaluacion_os'] ?? null,
            'evaluacion_neu' => $historiaData['evaluacion_neu'] ?? null,
            'comitante' => $historiaData['comitante'] ?? null,
            'plan_seguir' => $historiaData['plan_seguir'] ?? null,
            
            // ✅ NUTRICIONISTA - PRIMERA VEZ
            'enfermedad_diagnostica' => $historiaData['enfermedad_diagnostica'] ?? null,
            'habito_intestinal' => $historiaData['habito_intestinal'] ?? null,
            'quirurgicos' => $historiaData['quirurgicos'] ?? null,
            'quirurgicos_observaciones' => $historiaData['quirurgicos_observaciones'] ?? null,
            'alergicos' => $historiaData['alergicos'] ?? null,
            'alergicos_observaciones' => $historiaData['alergicos_observaciones'] ?? null,
            'familiares' => $historiaData['familiares'] ?? null,
            'familiares_observaciones' => $historiaData['familiares_observaciones'] ?? null,
            'psa' => $historiaData['psa'] ?? null,
            'psa_observaciones' => $historiaData['psa_observaciones'] ?? null,
            'farmacologicos' => $historiaData['farmacologicos'] ?? null,
            'farmacologicos_observaciones' => $historiaData['farmacologicos_observaciones'] ?? null,
            'sueno' => $historiaData['sueno'] ?? null,
            'sueno_observaciones' => $historiaData['sueno_observaciones'] ?? null,
            'ejercicio' => $historiaData['ejercicio'] ?? null,
            'ejercicio_observaciones' => $historiaData['ejercicio_observaciones'] ?? null,
            'metodo_conceptivo' => $historiaData['metodo_conceptivo'] ?? null,
            'metodo_conceptivo_cual' => $historiaData['metodo_conceptivo_cual'] ?? null,
            'embarazo_actual' => $historiaData['embarazo_actual'] ?? null,
            'semanas_gestacion' => $historiaData['semanas_gestacion'] ?? null,
            'climatero' => $historiaData['climatero'] ?? null,
            'tolerancia_via_oral' => $historiaData['tolerancia_via_oral'] ?? null,
            'percepcion_apetito' => $historiaData['percepcion_apetito'] ?? null,
            'percepcion_apetito_observacion' => $historiaData['percepcion_apetito_observacion'] ?? null,
            'alimentos_preferidos' => $historiaData['alimentos_preferidos'] ?? null,
            'alimentos_rechazados' => $historiaData['alimentos_rechazados'] ?? null,
            'suplemento_nutricionales' => $historiaData['suplemento_nutricionales'] ?? null,
            'dieta_especial' => $historiaData['dieta_especial'] ?? null,
            'dieta_especial_cual' => $historiaData['dieta_especial_cual'] ?? null,
            'desayuno_hora' => $historiaData['desayuno_hora'] ?? null,
            'desayuno_hora_observacion' => $historiaData['desayuno_hora_observacion'] ?? null,
            'media_manana_hora' => $historiaData['media_manana_hora'] ?? null,
            'media_manana_hora_observacion' => $historiaData['media_manana_hora_observacion'] ?? null,
            'almuerzo_hora' => $historiaData['almuerzo_hora'] ?? null,
            'almuerzo_hora_observacion' => $historiaData['almuerzo_hora_observacion'] ?? null,
            'media_tarde_hora' => $historiaData['media_tarde_hora'] ?? null,
            'media_tarde_hora_observacion' => $historiaData['media_tarde_hora_observacion'] ?? null,
            'cena_hora' => $historiaData['cena_hora'] ?? null,
            'cena_hora_observacion' => $historiaData['cena_hora_observacion'] ?? null,
            'refrigerio_nocturno_hora' => $historiaData['refrigerio_nocturno_hora'] ?? null,
            'refrigerio_nocturno_hora_observacion' => $historiaData['refrigerio_nocturno_hora_observacion'] ?? null,
            'peso_ideal' => $historiaData['peso_ideal'] ?? null,
            'interpretacion' => $historiaData['interpretacion'] ?? null,
            'meta_meses' => $historiaData['meta_meses'] ?? null,
            'analisis_nutricional' => $historiaData['analisis_nutricional'] ?? null,
            'plan_seguir_nutri' => $historiaData['plan_seguir_nutri'] ?? null,
            'diagnostico_nutri' => $historiaData['diagnostico_nutri'] ?? null,
            
            // ✅ NUTRICIONISTA - CONTROL (RECORDATORIO 24H Y FRECUENCIA)
            'comida_desayuno' => $historiaData['comida_desayuno'] ?? null,
            'comida_medio_desayuno' => $historiaData['comida_medio_desayuno'] ?? null,
            'comida_almuerzo' => $historiaData['comida_almuerzo'] ?? null,
            'comida_medio_almuerzo' => $historiaData['comida_medio_almuerzo'] ?? null,
            'comida_cena' => $historiaData['comida_cena'] ?? null,
            'lacteo' => $historiaData['lacteo'] ?? null,
            'lacteo_observacion' => $historiaData['lacteo_observacion'] ?? null,
            'huevo' => $historiaData['huevo'] ?? null,
            'huevo_observacion' => $historiaData['huevo_observacion'] ?? null,
            'embutido' => $historiaData['embutido'] ?? null,
            'embutido_observacion' => $historiaData['embutido_observacion'] ?? null,
            'carne_roja' => $historiaData['carne_roja'] ?? null,
            'carne_blanca' => $historiaData['carne_blanca'] ?? null,
            'carne_vicera' => $historiaData['carne_vicera'] ?? null,
            'carne_observacion' => $historiaData['carne_observacion'] ?? null,
            'leguminosas' => $historiaData['leguminosas'] ?? null,
            'leguminosas_observacion' => $historiaData['leguminosas_observacion'] ?? null,
            'frutas_jugo' => $historiaData['frutas_jugo'] ?? null,
            'frutas_porcion' => $historiaData['frutas_porcion'] ?? null,
            'frutas_observacion' => $historiaData['frutas_observacion'] ?? null,
            'verduras_hortalizas' => $historiaData['verduras_hortalizas'] ?? null,
            'vh_observacion' => $historiaData['vh_observacion'] ?? null,
            'cereales' => $historiaData['cereales'] ?? null,
            'cereales_observacion' => $historiaData['cereales_observacion'] ?? null,
            'rtp' => $historiaData['rtp'] ?? null,
            'rtp_observacion' => $historiaData['rtp_observacion'] ?? null,
            'azucar_dulce' => $historiaData['azucar_dulce'] ?? null,
            'ad_observacion' => $historiaData['ad_observacion'] ?? null,
           
            
            // ═══════════════════════════════════════════
            // 🔹 DATOS ADICIONALES PARA BÚSQUEDA (ESCALARES)
            // ═══════════════════════════════════════════
            'paciente_uuid' => $historiaData['paciente_uuid'] ?? null,
            'paciente_nombre' => $historiaData['paciente_nombre'] ?? null,
            'paciente_documento' => $historiaData['paciente_documento'] ?? null,
            'agenda_uuid' => $historiaData['agenda_uuid'] ?? null,
            'proceso_nombre' => $historiaData['proceso_nombre'] ?? null,
            'medico_nombre' => $historiaData['medico_nombre'] ?? null,
            'sede_nombre' => $historiaData['sede_nombre'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 CONTROL
            // ═══════════════════════════════════════════
            'sync_status' => $needsSync ? 'pending' : 'synced',
            'created_at' => $historiaData['created_at'] ?? now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'deleted_at' => null
        ];

        Log::info('📊 Datos preparados para SQLite (SOLO ESCALARES)', [
            'uuid' => $historiaData['uuid'],
            'total_campos_escalares' => count($offlineData),
            'excluidos' => ['diagnosticos', 'medicamentos', 'remisiones', 'cups', 'complementaria', 'cita', 'sede']
        ]);

        // ═══════════════════════════════════════════
        // 🔹 PASO 1: GUARDAR EN SQLITE (SOLO ESCALARES)
        // ═══════════════════════════════════════════
        if ($this->isSQLiteAvailable()) {
            try {
                $this->createHistoriasClinicasTable();
                
                DB::connection('offline')->table('historias_clinicas')->updateOrInsert(
                    ['uuid' => $historiaData['uuid']],
                    $offlineData
                );

                Log::debug('✅ Historia guardada en SQLite', [
                    'uuid' => $historiaData['uuid'],
                    'campos_guardados' => count($offlineData)
                ]);
            } catch (\Exception $e) {
                Log::error('❌ Error guardando en SQLite', [
                    'uuid' => $historiaData['uuid'],
                    'error' => $e->getMessage(),
                    'line' => $e->getLine()
                ]);
                // ✅ NO LANZAR EXCEPCIÓN - CONTINUAR CON JSON
            }
        }

        // ═══════════════════════════════════════════
        // 🔹 PASO 2: GUARDAR JSON COMPLETO (CON ARRAYS)
        // ═══════════════════════════════════════════
        try {
            // ✅ CONSTRUIR OBJETO COMPLETO PRESERVANDO LOS ARRAYS EXTRAÍDOS
           $historiaCompleta = array_merge($historiaData, [
            'diagnosticos' => $diagnosticos,      // ✅ Nombre correcto
            'medicamentos' => $medicamentos,      // ✅ Nombre correcto
            'remisiones' => $remisiones,          // ✅ Nombre correcto
            'cups' => $cups,                      // ✅ Nombre correcto
            'sync_status' => $needsSync ? 'pending' : 'synced',
            'updated_at' => now()->toISOString(),
        ]);

            // ✅ GUARDAR EN JSON CON ESTRUCTURA COMPLETA
            $this->storeData('historias_clinicas/' . $historiaData['uuid'] . '.json', $historiaCompleta);
            
            Log::info('✅ Historia guardada en JSON con TODAS las relaciones PRESERVADAS', [
                'uuid' => $historiaData['uuid'],
                'diagnosticos_count' => count($historiaCompleta['diagnosticos']),
                'medicamentos_count' => count($historiaCompleta['medicamentos']),
                'remisiones_count' => count($historiaCompleta['remisiones']),
                'cups_count' => count($historiaCompleta['cups']),
                'tiene_complementaria' => !is_null($historiaCompleta['complementaria']),
                'tiene_cita' => !is_null($historiaCompleta['cita']),
                'tiene_sede' => !is_null($historiaCompleta['sede'])
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error guardando JSON', [
                'uuid' => $historiaData['uuid'],
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
        }

        Log::info('✅ Historia clínica almacenada offline COMPLETAMENTE', [
            'uuid' => $historiaData['uuid'],
            'cita_uuid' => $historiaData['cita_uuid'] ?? 'N/A',
            'sync_status' => $offlineData['sync_status'],
            'sqlite_campos' => count($offlineData),
            'json_diagnosticos' => count($diagnosticos),
            'json_medicamentos' => count($medicamentos),
            'json_remisiones' => count($remisiones),
            'json_cups' => count($cups)
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error crítico almacenando historia', [
            'error' => $e->getMessage(),
            'uuid' => $historiaData['uuid'] ?? 'sin-uuid',
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}


/**
 * ✅ VERIFICAR SI UNA HISTORIA YA EXISTE
 */
private function historiaClinicaExiste(string $uuid): bool
{
    try {
        // ✅ VERIFICAR EN SQLite
        if ($this->isSQLiteAvailable()) {
            $existe = DB::connection('offline')
                ->table('historias_clinicas')
                ->where('uuid', $uuid)
                ->whereNull('deleted_at')
                ->exists();
            
            if ($existe) {
                return true;
            }
        }

        // ✅ VERIFICAR EN ARCHIVOS JSON
        $jsonPath = storage_path('app/offline/historias_clinicas/' . $uuid . '.json');
        return file_exists($jsonPath);

    } catch (\Exception $e) {
        Log::warning('⚠️ Error verificando existencia de historia', [
            'uuid' => $uuid,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * ✅ CREAR TABLA DE HISTORIAS CLÍNICAS - VERSIÓN COMPLETA CON TODOS LOS CAMPOS
 */
private function createHistoriasClinicasTable(): void
{
    try {
        // ✅ ELIMINAR TABLA EXISTENTE PARA RECREARLA
        DB::connection('offline')->statement('DROP TABLE IF EXISTS historias_clinicas');
        
        DB::connection('offline')->statement('
            CREATE TABLE IF NOT EXISTS historias_clinicas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                cita_uuid TEXT,
                cita_id INTEGER,
                sede_id INTEGER,
                usuario_id INTEGER NULL,
                
                -- ═══════════════════════════════════════════
                -- 🔹 DATOS DE CONSULTA
                -- ═══════════════════════════════════════════
                especialidad TEXT,
                tipo_consulta TEXT,
                finalidad TEXT,
                causa_externa TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 MOTIVO Y ENFERMEDAD
                -- ═══════════════════════════════════════════
                motivo_consulta TEXT,
                enfermedad_actual TEXT,
                diagnostico_principal TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 ACOMPAÑANTE
                -- ═══════════════════════════════════════════
                acompanante TEXT,
                acu_telefono TEXT,
                acu_parentesco TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 DISCAPACIDADES
                -- ═══════════════════════════════════════════
                discapacidad_fisica TEXT,
                discapacidad_visual TEXT,
                discapacidad_mental TEXT,
                discapacidad_auditiva TEXT,
                discapacidad_intelectual TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 DROGODEPENDENCIA
                -- ═══════════════════════════════════════════
                drogo_dependiente TEXT,
                drogo_dependiente_cual TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 MEDIDAS ANTROPOMÉTRICAS
                -- ═══════════════════════════════════════════
                peso REAL,
                talla REAL,
                imc REAL,
                clasificacion TEXT,
                perimetro_abdominal REAL,
                obs_perimetro_abdominal TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 ANTECEDENTES FAMILIARES
                -- ═══════════════════════════════════════════
                hipertension_arterial TEXT,
                parentesco_hipertension TEXT,
                diabetes_mellitus TEXT,
                parentesco_mellitus TEXT,
                artritis TEXT,
                parentesco_artritis TEXT,
                enfermedad_cardiovascular TEXT,
                parentesco_cardiovascular TEXT,
                antecedente_metabolico TEXT,
                parentesco_metabolico TEXT,
                cancer_mama_estomago_prostata_colon TEXT,
                parentesco_cancer TEXT,
                leucemia TEXT,
                parentesco_leucemia TEXT,
                vih TEXT,
                parentesco_vih TEXT,
                otro TEXT,
                parentesco_otro TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 ANTECEDENTES PERSONALES
                -- ═══════════════════════════════════════════
                hipertension_arterial_personal TEXT,
                obs_personal_hipertension_arterial TEXT,
                diabetes_mellitus_personal TEXT,
                obs_personal_mellitus TEXT,
                enfermedad_cardiovascular_personal TEXT,
                obs_personal_enfermedad_cardiovascular TEXT,
                arterial_periferica_personal TEXT,
                obs_personal_arterial_periferica TEXT,
                carotidea_personal TEXT,
                obs_personal_carotidea TEXT,
                aneurisma_aorta_personal TEXT,
                obs_personal_aneurisma_aorta TEXT,
                sindrome_coronario_agudo_angina_personal TEXT,
                obs_personal_sindrome_coronario TEXT,
                artritis_personal TEXT,
                obs_personal_artritis TEXT,
                iam_personal TEXT,
                obs_personal_iam TEXT,
                revascul_coronaria_personal TEXT,
                obs_personal_revascul_coronaria TEXT,
                insuficiencia_cardiaca_personal TEXT,
                obs_personal_insuficiencia_cardiaca TEXT,
                amputacion_pie_diabetico_personal TEXT,
                obs_personal_amputacion_pie_diabetico TEXT,
                enfermedad_pulmonar_personal TEXT,
                obs_personal_enfermedad_pulmonar TEXT,
                victima_maltrato_personal TEXT,
                obs_personal_maltrato_personal TEXT,
                antecedentes_quirurgicos TEXT,
                obs_personal_antecedentes_quirurgicos TEXT,
                acontosis_personal TEXT,
                obs_personal_acontosis TEXT,
                otro_personal TEXT,
                obs_personal_otro TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 TEST DE MORISKY
                -- ═══════════════════════════════════════════
                olvida_tomar_medicamentos TEXT,
                toma_medicamentos_hora_indicada TEXT,
                cuando_esta_bien_deja_tomar_medicamentos TEXT,
                siente_mal_deja_tomarlos TEXT,
                valoracion_psicologia TEXT,
                adherente TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 REVISIÓN POR SISTEMAS
                -- ═══════════════════════════════════════════
                general TEXT,
                cabeza TEXT,
                orl TEXT,
                respiratorio TEXT,
                cardiovascular TEXT,
                gastrointestinal TEXT,
                osteoatromuscular TEXT,
                snc TEXT,
                revision_sistemas TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 SIGNOS VITALES
                -- ═══════════════════════════════════════════
                presion_arterial_sistolica_sentado_pie REAL,
                presion_arterial_distolica_sentado_pie REAL,
                presion_arterial_sistolica_acostado REAL,
                presion_arterial_distolica_acostado REAL,
                frecuencia_cardiaca REAL,
                frecuencia_respiratoria REAL,
                temperatura REAL,
                saturacion_oxigeno REAL,
                
                -- ═══════════════════════════════════════════
                -- 🔹 EXAMEN FÍSICO
                -- ═══════════════════════════════════════════
                ef_cabeza TEXT,
                obs_cabeza TEXT,
                agudeza_visual TEXT,
                obs_agudeza_visual TEXT,
                fundoscopia TEXT,
                obs_fundoscopia TEXT,
                oidos TEXT,
                nariz_senos_paranasales TEXT,
                cavidad_oral TEXT,
                cuello TEXT,
                obs_cuello TEXT,
                cardio_respiratorio TEXT,
                torax TEXT,
                obs_torax TEXT,
                mamas TEXT,
                obs_mamas TEXT,
                abdomen TEXT,
                obs_abdomen TEXT,
                genito_urinario TEXT,
                obs_genito_urinario TEXT,
                musculo_esqueletico TEXT,
                extremidades TEXT,
                obs_extremidades TEXT,
                piel_anexos_pulsos TEXT,
                obs_piel_anexos_pulsos TEXT,
                inspeccion_sensibilidad_pies TEXT,
                sistema_nervioso TEXT,
                obs_sistema_nervioso TEXT,
                capacidad_cognitiva TEXT,
                obs_capacidad_cognitiva TEXT,
                capacidad_cognitiva_orientacion TEXT,
                orientacion TEXT,
                obs_orientacion TEXT,
                reflejo_aquiliar TEXT,
                obs_reflejo_aquiliar TEXT,
                reflejo_patelar TEXT,
                obs_reflejo_patelar TEXT,
                hallazgo_positivo_examen_fisico TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 FACTORES DE RIESGO
                -- ═══════════════════════════════════════════
                tabaquismo TEXT,
                obs_tabaquismo TEXT,
                dislipidemia TEXT,
                obs_dislipidemia TEXT,
                menor_cierta_edad TEXT,
                obs_menor_cierta_edad TEXT,
                condicion_clinica_asociada TEXT,
                obs_condicion_clinica_asociada TEXT,
                lesion_organo_blanco TEXT,
                obs_lesion_organo_blanco TEXT,
                descripcion_lesion_organo_blanco TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 EXÁMENES COMPLEMENTARIOS
                -- ═══════════════════════════════════════════
                fex_es TEXT,
                electrocardiograma TEXT,
                fex_es1 TEXT,
                ecocardiograma TEXT,
                fex_es2 TEXT,
                ecografia_renal TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 CLASIFICACIONES
                -- ═══════════════════════════════════════════
                clasificacion_estado_metabolico TEXT,
                clasificacion_hta TEXT,
                clasificacion_dm TEXT,
                clasificacion_rcv TEXT,
                clasificacion_erc_estado TEXT,
                clasificacion_erc_estadodos TEXT,
                clasificacion_erc_categoria_ambulatoria_persistente TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 TASAS DE FILTRACIÓN
                -- ═══════════════════════════════════════════
                tasa_filtracion_glomerular_ckd_epi REAL,
                tasa_filtracion_glomerular_gockcroft_gault REAL,
                
                -- ═══════════════════════════════════════════
                -- 🔹 EDUCACIÓN EN SALUD
                -- ═══════════════════════════════════════════
                alimentacion TEXT,
                disminucion_consumo_sal_azucar TEXT,
                fomento_actividad_fisica TEXT,
                importancia_adherencia_tratamiento TEXT,
                consumo_frutas_verduras TEXT,
                manejo_estres TEXT,
                disminucion_consumo_cigarrillo TEXT,
                disminucion_peso TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 OTROS CAMPOS
                -- ═══════════════════════════════════════════
                insulina_requiriente TEXT,
                recibe_tratamiento_alternativo TEXT,
                recibe_tratamiento_con_plantas_medicinales TEXT,
                recibe_ritual_medicina_tradicional TEXT,
                numero_frutas_diarias INTEGER,
                elevado_consumo_grasa_saturada TEXT,
                adiciona_sal_despues_preparar_comida TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 REFORMULACIÓN
                -- ═══════════════════════════════════════════
                razon_reformulacion TEXT,
                motivo_reformulacion TEXT,
                reformulacion_quien_reclama TEXT,
                reformulacion_nombre_reclama TEXT,
                adicional TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 OBSERVACIONES
                -- ═══════════════════════════════════════════
                observaciones_generales TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 CAMPOS DE ESPECIALIDADES COMPLEMENTARIAS
                -- ═══════════════════════════════════════════
                
                -- PSICOLOGÍA
                estructura_familiar TEXT,
                psicologia_red_apoyo TEXT,
                psicologia_comportamiento_consulta TEXT,
                psicologia_tratamiento_actual_adherencia TEXT,
                psicologia_descripcion_problema TEXT,
                analisis_conclusiones TEXT,
                psicologia_plan_intervencion_recomendacion TEXT,
                avance_paciente TEXT,
                
                -- FISIOTERAPIA
                actitud TEXT,
                evaluacion_d TEXT,
                evaluacion_p TEXT,
                estado TEXT,
                evaluacion_dolor TEXT,
                evaluacion_os TEXT,
                evaluacion_neu TEXT,
                comitante TEXT,
                plan_seguir TEXT,
                
                -- NUTRICIONISTA PRIMERA VEZ
                enfermedad_diagnostica TEXT,
                habito_intestinal TEXT,
                quirurgicos TEXT,
                quirurgicos_observaciones TEXT,
                alergicos TEXT,
                alergicos_observaciones TEXT,
                familiares TEXT,
                familiares_observaciones TEXT,
                psa TEXT,
                psa_observaciones TEXT,
                farmacologicos TEXT,
                farmacologicos_observaciones TEXT,
                sueno TEXT,
                sueno_observaciones TEXT,
                ejercicio TEXT,
                ejercicio_observaciones TEXT,
                metodo_conceptivo TEXT,
                metodo_conceptivo_cual TEXT,
                embarazo_actual TEXT,
                semanas_gestacion INTEGER,
                climatero TEXT,
                tolerancia_via_oral TEXT,
                percepcion_apetito TEXT,
                percepcion_apetito_observacion TEXT,
                alimentos_preferidos TEXT,
                alimentos_rechazados TEXT,
                suplemento_nutricionales TEXT,
                dieta_especial TEXT,
                dieta_especial_cual TEXT,
                desayuno_hora TEXT,
                desayuno_hora_observacion TEXT,
                media_manana_hora TEXT,
                media_manana_hora_observacion TEXT,
                almuerzo_hora TEXT,
                almuerzo_hora_observacion TEXT,
                media_tarde_hora TEXT,
                media_tarde_hora_observacion TEXT,
                cena_hora TEXT,
                cena_hora_observacion TEXT,
                refrigerio_nocturno_hora TEXT,
                refrigerio_nocturno_hora_observacion TEXT,
                peso_ideal REAL,
                interpretacion TEXT,
                meta_meses INTEGER,
                analisis_nutricional TEXT,
                plan_seguir_nutri TEXT,
                diagnostico_nutri TEXT,
                
                -- NUTRICIONISTA CONTROL (RECORDATORIO 24H Y FRECUENCIA)
                comida_desayuno TEXT,
                comida_medio_desayuno TEXT,
                comida_almuerzo TEXT,
                comida_medio_almuerzo TEXT,
                comida_cena TEXT,
                lacteo TEXT,
                lacteo_observacion TEXT,
                huevo TEXT,
                huevo_observacion TEXT,
                embutido TEXT,
                embutido_observacion TEXT,
                carne_roja TEXT,
                carne_blanca TEXT,
                carne_vicera TEXT,
                carne_observacion TEXT,
                leguminosas TEXT,
                leguminosas_observacion TEXT,
                frutas_jugo TEXT,
                frutas_porcion TEXT,
                frutas_observacion TEXT,
                verduras_hortalizas TEXT,
                vh_observacion TEXT,
                cereales TEXT,
                cereales_observacion TEXT,
                rtp TEXT,
                rtp_observacion TEXT,
                azucar_dulce TEXT,
                ad_observacion TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 DATOS ADICIONALES PARA BÚSQUEDA
                -- ═══════════════════════════════════════════
                paciente_uuid TEXT,
                paciente_nombre TEXT,
                paciente_documento TEXT,
                agenda_uuid TEXT,
                proceso_nombre TEXT,
                medico_nombre TEXT,
                sede_nombre TEXT,
                
                -- ═══════════════════════════════════════════
                -- 🔹 CONTROL
                -- ═══════════════════════════════════════════
                sync_status TEXT DEFAULT "synced",
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL
            )
        ');

        // ✅ CREAR ÍNDICES PARA BÚSQUEDAS RÁPIDAS
        DB::connection('offline')->statement('
            CREATE INDEX IF NOT EXISTS idx_historias_paciente ON historias_clinicas(paciente_uuid)
        ');

        DB::connection('offline')->statement('
            CREATE INDEX IF NOT EXISTS idx_historias_cita ON historias_clinicas(cita_uuid)
        ');

        DB::connection('offline')->statement('
            CREATE INDEX IF NOT EXISTS idx_historias_fecha ON historias_clinicas(created_at)
        ');

        DB::connection('offline')->statement('
            CREATE INDEX IF NOT EXISTS idx_historias_sync ON historias_clinicas(sync_status)
        ');

        DB::connection('offline')->statement('
            CREATE INDEX IF NOT EXISTS idx_historias_documento ON historias_clinicas(paciente_documento)
        ');
        
        Log::info('✅ Tabla historias_clinicas recreada con TODOS los campos', [
            'total_columnas' => 'más de 150 campos',
            'indices_creados' => 5
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error creando tabla historias_clinicas', [
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ]);
        throw $e;
    }
}



/**
 * ✅ BUSCAR MEDICAMENTOS OFFLINE
 */
public function buscarMedicamentosOffline(string $termino, int $limit = 20): array
{
    try {
        $medicamentos = [];
        
        if ($this->isSQLiteAvailable()) {
            $results = DB::connection('offline')->table('medicamentos')
                ->where('nombre', 'LIKE', '%' . $termino . '%')
                ->limit($limit)
                ->get();
                            $medicamentos = $results->map(function($medicamento) {
                return [
                    'id' => $medicamento->id,
                    'uuid' => $medicamento->uuid,
                    'nombre' => $medicamento->nombre,
                    'principio_activo' => $medicamento->principio_activo ?? null,
                    'concentracion' => $medicamento->concentracion ?? null,
                    'forma_farmaceutica' => $medicamento->forma_farmaceutica ?? null
                ];
            })->toArray();
        } else {
            // ✅ FALLBACK A JSON
            $medicamentosPath = $this->getStoragePath() . '/medicamentos';
            if (is_dir($medicamentosPath)) {
                $files = glob($medicamentosPath . '/*.json');
                foreach ($files as $file) {
                    $data = json_decode(file_get_contents($file), true);
                    if ($data && stripos($data['nombre'] ?? '', $termino) !== false) {
                        $medicamentos[] = $data;
                        if (count($medicamentos) >= $limit) break;
                    }
                }
            }
        }

        return $medicamentos;

    } catch (\Exception $e) {
        Log::error('❌ Error buscando medicamentos offline', [
            'error' => $e->getMessage(),
            'termino' => $termino
        ]);
        return [];
    }
}

/**
 * ✅ BUSCAR DIAGNÓSTICOS OFFLINE
 */
public function buscarDiagnosticosOffline(string $termino, int $limit = 20): array
{
    try {
        $diagnosticos = [];
        
        if ($this->isSQLiteAvailable()) {
            $results = DB::connection('offline')->table('diagnosticos')
                ->where(function($q) use ($termino) {
                    $q->where('codigo', 'LIKE', '%' . $termino . '%')
                      ->orWhere('nombre', 'LIKE', '%' . $termino . '%');
                })
                ->limit($limit)
                ->get();
                
            $diagnosticos = $results->map(function($diagnostico) {
                return [
                    'id' => $diagnostico->id,
                    'uuid' => $diagnostico->uuid,
                    'codigo' => $diagnostico->codigo,
                    'nombre' => $diagnostico->nombre,
                    'categoria' => $diagnostico->categoria ?? null
                ];
            })->toArray();
        } else {
            // ✅ FALLBACK A JSON
            $diagnosticosPath = $this->getStoragePath() . '/diagnosticos';
            if (is_dir($diagnosticosPath)) {
                $files = glob($diagnosticosPath . '/*.json');
                foreach ($files as $file) {
                    $data = json_decode(file_get_contents($file), true);
                    if ($data && (
                        stripos($data['codigo'] ?? '', $termino) !== false ||
                        stripos($data['nombre'] ?? '', $termino) !== false
                    )) {
                        $diagnosticos[] = $data;
                        if (count($diagnosticos) >= $limit) break;
                    }
                }
            }
        }

        return $diagnosticos;

    } catch (\Exception $e) {
        Log::error('❌ Error buscando diagnósticos offline', [
            'error' => $e->getMessage(),
            'termino' => $termino
        ]);
        return [];
    }
}

/**
 * ✅ BUSCAR REMISIONES OFFLINE
 */
public function buscarRemisionesOffline(string $termino, int $limit = 20): array
{
    try {
        $remisiones = [];
        
        if ($this->isSQLiteAvailable()) {
            $results = DB::connection('offline')->table('remisiones')
                ->where('nombre', 'LIKE', '%' . $termino . '%')
                ->where('activo', true)
                ->limit($limit)
                ->get();
                
            $remisiones = $results->map(function($remision) {
                return [
                    'id' => $remision->id,
                    'uuid' => $remision->uuid,
                    'codigo' => $remision->codigo,
                    'nombre' => $remision->nombre,
                    'tipo' => $remision->tipo ?? null
                ];
            })->toArray();
        } else {
            // ✅ FALLBACK A JSON
            $remisionesPath = $this->getStoragePath() . '/remisiones';
            if (is_dir($remisionesPath)) {
                $files = glob($remisionesPath . '/*.json');
                foreach ($files as $file) {
                    $data = json_decode(file_get_contents($file), true);
                    if ($data && 
                        stripos($data['nombre'] ?? '', $termino) !== false &&
                        ($data['activo'] ?? true)) {
                        $remisiones[] = $data;
                        if (count($remisiones) >= $limit) break;
                    }
                }
            }
        }

        return $remisiones;

    } catch (\Exception $e) {
        Log::error('❌ Error buscando remisiones offline', [
            'error' => $e->getMessage(),
            'termino' => $termino
        ]);
        return [];
    }
}
/**
 * ✅ CREAR TABLA DE MEDICAMENTOS
 */
private function createMedicamentosTable(): void
{
    DB::connection('offline')->statement('
        CREATE TABLE IF NOT EXISTS medicamentos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            codigo TEXT,
            nombre TEXT NOT NULL,
            principio_activo TEXT,
            concentracion TEXT,
            forma_farmaceutica TEXT,
            via_administracion TEXT,
            unidad_medida TEXT,
            pos BOOLEAN DEFAULT 1,
            activo BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');
    
    // Crear índices
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_medicamentos_nombre ON medicamentos(nombre)
    ');
    
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_medicamentos_activo ON medicamentos(activo)
    ');
}

/**
 * ✅ CREAR TABLA DE DIAGNÓSTICOS
 */
private function createDiagnosticosTable(): void
{
    DB::connection('offline')->statement('
        CREATE TABLE IF NOT EXISTS diagnosticos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            codigo TEXT NOT NULL,
            nombre TEXT NOT NULL,
            cod_categoria TEXT,
            categoria TEXT,
            activo BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');
    
    // Crear índices
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_diagnosticos_codigo ON diagnosticos(codigo)
    ');
    
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_diagnosticos_nombre ON diagnosticos(nombre)
    ');
    
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_diagnosticos_activo ON diagnosticos(activo)
    ');
}

/**
 * ✅ CREAR TABLA DE REMISIONES
 */
private function createRemisionesTable(): void
{
    DB::connection('offline')->statement('
        CREATE TABLE IF NOT EXISTS remisiones (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            codigo TEXT,
            nombre TEXT NOT NULL,
            tipo TEXT,
            especialidad_id INTEGER,
            descripcion TEXT,
            activo BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');
    
    // Crear índices
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_remisiones_nombre ON remisiones(nombre)
    ');
    
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_remisiones_activo ON remisiones(activo)
    ');
}

/**
 * ✅ SINCRONIZAR MEDICAMENTOS DESDE API
 */
public function syncMedicamentosFromApi(array $medicamentos): bool
{
    try {
        Log::info('🔄 Sincronizando medicamentos offline', [
            'count' => count($medicamentos)
        ]);

        if ($this->isSQLiteAvailable()) {
            $this->createMedicamentosTable();
            
            // Limpiar datos existentes
            DB::connection('offline')->table('medicamentos')->delete();
            
            foreach ($medicamentos as $medicamento) {
                DB::connection('offline')->table('medicamentos')->insert([
                    'uuid' => $medicamento['uuid'],
                    'codigo' => $medicamento['codigo'] ?? null,
                    'nombre' => $medicamento['nombre'],
                    'principio_activo' => $medicamento['principio_activo'] ?? null,
                    'concentracion' => $medicamento['concentracion'] ?? null,
                    'forma_farmaceutica' => $medicamento['forma_farmaceutica'] ?? null,
                    'via_administracion' => $medicamento['via_administracion'] ?? null,
                    'unidad_medida' => $medicamento['unidad_medida'] ?? null,
                    'pos' => $medicamento['pos'] ?? true,
                    'activo' => $medicamento['activo'] ?? true,
                    'created_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString()
                ]);
            }
        }

        // También guardar en JSON como backup
        foreach ($medicamentos as $medicamento) {
            $this->storeData('medicamentos/' . $medicamento['uuid'] . '.json', $medicamento);
        }

        Log::info('✅ Medicamentos sincronizados offline', [
            'synced' => count($medicamentos)
        ]);

        return true;

    } catch (\Exception $e) {
        Log::error('❌ Error sincronizando medicamentos offline', [
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * ✅ SINCRONIZAR DIAGNÓSTICOS DESDE API
 */
public function syncDiagnosticosFromApi(array $diagnosticos): bool
{
    try {
        Log::info('🔄 Sincronizando diagnósticos offline', [
            'count' => count($diagnosticos)
        ]);

        if ($this->isSQLiteAvailable()) {
            $this->createDiagnosticosTable();
            
            // Limpiar datos existentes
            DB::connection('offline')->table('diagnosticos')->delete();
            
            foreach ($diagnosticos as $diagnostico) {
                DB::connection('offline')->table('diagnosticos')->insert([
                    'uuid' => $diagnostico['uuid'],
                    'codigo' => $diagnostico['codigo'],
                    'nombre' => $diagnostico['nombre'],
                    'cod_categoria' => $diagnostico['cod_categoria'] ?? null,
                    'categoria' => $diagnostico['categoria'] ?? null,
                    'activo' => true,
                    'created_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString()
                ]);
            }
        }

        // También guardar en JSON como backup
        foreach ($diagnosticos as $diagnostico) {
            $this->storeData('diagnosticos/' . $diagnostico['uuid'] . '.json', $diagnostico);
        }

        Log::info('✅ Diagnósticos sincronizados offline', [
            'synced' => count($diagnosticos)
        ]);

        return true;

    } catch (\Exception $e) {
        Log::error('❌ Error sincronizando diagnósticos offline', [
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * ✅ SINCRONIZAR REMISIONES DESDE API
 */
public function syncRemisionesFromApi(array $remisiones): bool
{
    try {
        Log::info('🔄 Sincronizando remisiones offline', [
            'count' => count($remisiones)
        ]);

        if ($this->isSQLiteAvailable()) {
            $this->createRemisionesTable();
            
            // Limpiar datos existentes
            DB::connection('offline')->table('remisiones')->delete();
            
            foreach ($remisiones as $remision) {
                DB::connection('offline')->table('remisiones')->insert([
                    'uuid' => $remision['uuid'],
                    'codigo' => $remision['codigo'] ?? null,
                    'nombre' => $remision['nombre'],
                    'tipo' => $remision['tipo'] ?? null,
                    'especialidad_id' => $remision['especialidad_id'] ?? null,
                    'descripcion' => $remision['descripcion'] ?? null,
                    'activo' => $remision['activo'] ?? true,
                    'created_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString()
                ]);
            }
        }

        // También guardar en JSON como backup
        foreach ($remisiones as $remision) {
            $this->storeData('remisiones/' . $remision['uuid'] . '.json', $remision);
        }

        Log::info('✅ Remisiones sincronizadas offline', [
            'synced' => count($remisiones)
        ]);

        return true;

    } catch (\Exception $e) {
        Log::error('❌ Error sincronizando remisiones offline', [
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * ✅ OBTENER HISTORIAS CLÍNICAS POR PACIENTE
 */
public function getHistoriasClinicasByPaciente(string $pacienteUuid): array
{
    try {
        $historiasPath = storage_path('app/offline/historias_clinicas');
        
        if (!is_dir($historiasPath)) {
            return [];
        }
        
        $historias = [];
        $files = glob($historiasPath . '/*.json');
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            
            if (isset($data['paciente_uuid']) && $data['paciente_uuid'] === $pacienteUuid) {
                $historias[] = $data;
            }
        }
        
        Log::info('✅ Historias offline encontradas', [
            'paciente_uuid' => $pacienteUuid,
            'count' => count($historias)
        ]);
        
        return $historias;
        
    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo historias offline', [
            'error' => $e->getMessage(),
            'paciente_uuid' => $pacienteUuid
        ]);
        
        return [];
    }
}

/**
 * ✅ BUSCAR HISTORIAS EN SQLITE (OPCIONAL)
 */
public function buscarHistoriasEnSQLite(string $pacienteUuid): array
{
    try {
        if (!$this->isSQLiteAvailable()) {
            return [];
        }
        
        $results = DB::connection('offline')->table('historias_clinicas')
            ->where('paciente_uuid', $pacienteUuid)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return $results->toArray();
        
    } catch (\Exception $e) {
        Log::debug('ℹ️ No se pudo buscar en SQLite', [
            'error' => $e->getMessage()
        ]);
        
        return [];
    }
}

/**
 * ✅ OBTENER CUPS CONTRATADOS CON RELACIONES COMPLETAS
 */
public function getCupsContratadosOffline(): array
{
    try {
        if (!$this->isSQLiteAvailable()) {
            Log::warning('⚠️ SQLite no disponible para CUPS contratados');
            return [];
        }

        Log::info('🔍 Obteniendo CUPS contratados desde offline');

        // ✅ OBTENER CUPS CONTRATADOS BASE
        $cupsContratados = DB::connection('offline')
            ->table('cups_contratados')
            ->where('estado', 'ACTIVO')
            ->get()
            ->toArray();

        if (empty($cupsContratados)) {
            Log::info('ℹ️ No hay CUPS contratados en SQLite');
            return [];
        }

        // ✅ ENRIQUECER CON RELACIONES
        $resultado = [];
        
        foreach ($cupsContratados as $cupsContratado) {
            $cupsContratadoArray = (array) $cupsContratado;
            
            // ✅ OBTENER CATEGORÍA CUPS
            $categoriaCups = null;
            if (!empty($cupsContratadoArray['categoria_cups_id'])) {
                $categoriaData = DB::connection('offline')
                    ->table('categorias_cups')
                    ->where('id', $cupsContratadoArray['categoria_cups_id'])
                    ->first();
                
                if ($categoriaData) {
                    $categoriaCups = [
                        'id' => $categoriaData->id,
                        'uuid' => $categoriaData->uuid ?? null,
                        'nombre' => $categoriaData->nombre,
                        'created_at' => $categoriaData->created_at ?? null,
                        'updated_at' => $categoriaData->updated_at ?? null
                    ];
                }
            }

            // ✅ OBTENER CONTRATO COMPLETO
            $contrato = null;
            if (!empty($cupsContratadoArray['contrato_uuid'])) {
                $contratoData = DB::connection('offline')
                    ->table('contratos')
                    ->where('uuid', $cupsContratadoArray['contrato_uuid'])
                    ->first();
                
                if ($contratoData) {
                    $contrato = [
                        'id' => $contratoData->id,
                        'uuid' => $contratoData->uuid,
                        'empresa_id' => $contratoData->empresa_id ?? null,
                        'empresa_uuid' => $contratoData->empresa_uuid ?? null,
                        'numero' => $contratoData->numero ?? null,
                        'descripcion' => $contratoData->descripcion ?? null,
                        'plan_beneficio' => $contratoData->plan_beneficio ?? null,
                        'poliza' => $contratoData->poliza ?? null,
                        'por_descuento' => $contratoData->por_descuento ?? null,
                        'fecha_inicio' => $contratoData->fecha_inicio,
                        'fecha_fin' => $contratoData->fecha_fin,
                        'valor' => $contratoData->valor ?? null,
                        'fecha_registro' => $contratoData->fecha_registro ?? null,
                        'tipo' => $contratoData->tipo ?? null,
                        'copago' => $contratoData->copago ?? null,
                        'estado' => $contratoData->estado,
                        'created_at' => $contratoData->created_at ?? null,
                        'updated_at' => $contratoData->updated_at ?? null,
                        'deleted_at' => $contratoData->deleted_at ?? null,
                        // ✅ EMPRESA ANIDADA
                        'empresa' => [
                            'id' => $contratoData->empresa_id ?? null,
                            'uuid' => $contratoData->empresa_uuid ?? null,
                            'nombre' => $contratoData->empresa_nombre ?? null
                        ]
                    ];
                }
            }

            // ✅ CONSTRUIR OBJETO CUPS
            $cups = [
                'id' => $cupsContratadoArray['cups_id'] ?? null,
                'uuid' => $cupsContratadoArray['cups_uuid'] ?? null,
                'codigo' => $cupsContratadoArray['cups_codigo'] ?? null,
                'nombre' => $cupsContratadoArray['cups_nombre'] ?? null
            ];

            // ✅ CONSTRUIR ESTRUCTURA FINAL ANIDADA
            $resultado[] = [
                'uuid' => $cupsContratadoArray['uuid'],
                'contrato_id' => $cupsContratadoArray['contrato_id'] ?? null,
                'categoria_cups_id' => $cupsContratadoArray['categoria_cups_id'] ?? null,
                'cups_id' => $cupsContratadoArray['cups_id'] ?? null,
                'tarifa' => $cupsContratadoArray['tarifa'] ?? 0,
                'estado' => $cupsContratadoArray['estado'] ?? 'ACTIVO',
                'created_at' => $cupsContratadoArray['created_at'] ?? null,
                'updated_at' => $cupsContratadoArray['updated_at'] ?? null,
                
                // ✅ OBJETOS ANIDADOS
                'categoria_cups' => $categoriaCups ?? [
                    'id' => null,
                    'nombre' => 'SIN_CATEGORIA'
                ],
                'contrato' => $contrato ?? [
                    'uuid' => null,
                    'estado' => 'INACTIVO'
                ],
                'cups' => $cups
            ];
        }

        Log::info('✅ CUPS contratados obtenidos con relaciones completas', [
            'total' => count($resultado),
            'ejemplo_estructura' => isset($resultado[0]) ? [
                'tiene_categoria' => isset($resultado[0]['categoria_cups']),
                'categoria_nombre' => $resultado[0]['categoria_cups']['nombre'] ?? 'N/A',
                'tiene_contrato' => isset($resultado[0]['contrato']),
                'contrato_numero' => $resultado[0]['contrato']['numero'] ?? 'N/A',
                'tiene_cups' => isset($resultado[0]['cups']),
                'cups_codigo' => $resultado[0]['cups']['codigo'] ?? 'N/A'
            ] : null
        ]);

        return $resultado;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo CUPS contratados offline', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [];
    }
}
/**
 * ✅ OBTENER CATEGORÍA CUPS OFFLINE
 */
public function getCategoriaCupsOffline($categoriaId): ?array
{
    try {
        // ✅ BUSCAR EN SQLite
        if ($this->isSQLiteAvailable()) {
            $categoria = DB::connection('offline')->table('categorias_cups')
                ->where('id', $categoriaId)
                ->first();
            
            if ($categoria) {
                return (array) $categoria;
            }
        }
        
        // ✅ FALLBACK A JSON
        $categoriasPath = $this->storagePath . '/categorias_cups';
        if (is_dir($categoriasPath)) {
            $files = glob($categoriasPath . '/*.json');
            
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($data && ($data['id'] ?? null) == $categoriaId) {
                    return $data;
                }
            }
        }
        
        return null;
        
    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo categoría CUPS offline', [
            'error' => $e->getMessage(),
            'categoria_id' => $categoriaId
        ]);
        
        return null;
    }
}
/**
 * ✅ SINCRONIZAR CATEGORÍAS CUPS DESDE API
 */
public function syncCategoriasCupsFromApi(array $categorias): bool
{
    try {
        Log::info('🔄 Sincronizando categorías CUPS offline', [
            'count' => count($categorias)
        ]);

        // ✅ CREAR TABLA SI NO EXISTE
        if ($this->isSQLiteAvailable()) {
            $this->createCategoriasCupsTable();
            
            // Limpiar datos existentes
            DB::connection('offline')->table('categorias_cups')->delete();
            
            foreach ($categorias as $categoria) {
                DB::connection('offline')->table('categorias_cups')->insert([
                    'id' => $categoria['id'],
                    'uuid' => $categoria['uuid'] ?? null,
                    'nombre' => $categoria['nombre'],
                    'descripcion' => $categoria['descripcion'] ?? null,
                    'created_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString()
                ]);
            }
        }

        // ✅ TAMBIÉN GUARDAR EN JSON
        foreach ($categorias as $categoria) {
            $this->storeData('categorias_cups/' . $categoria['id'] . '.json', $categoria);
        }

        Log::info('✅ Categorías CUPS sincronizadas offline', [
            'synced' => count($categorias)
        ]);

        return true;

    } catch (\Exception $e) {
        Log::error('❌ Error sincronizando categorías CUPS offline', [
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * ✅ CREAR TABLA DE CATEGORÍAS CUPS
 */
private function createCategoriasCupsTable(): void
{
    DB::connection('offline')->statement('
        CREATE TABLE IF NOT EXISTS categorias_cups (
            id INTEGER PRIMARY KEY,
            uuid TEXT UNIQUE,
            nombre TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');
    
    // Crear índices
    DB::connection('offline')->statement('
        CREATE INDEX IF NOT EXISTS idx_categorias_cups_nombre ON categorias_cups(nombre)
    ');
}

/**
 * ✅ VERIFICAR SI UN CUPS EXISTE
 */
public function cupsExists(string $uuid): bool
{
    try {
        if (!$this->isSQLiteAvailable()) {
            return false;
        }
        
        $count = DB::connection('offline')->table('cups')
            ->where('uuid', $uuid)
            ->count();
        
        return $count > 0;
        
    } catch (\Exception $e) {
        Log::error('Error verificando existencia de CUPS', [
            'uuid' => $uuid,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * ✅ VERIFICAR SI UN CUPS CONTRATADO EXISTE
 */
public function cupsContratadoExists(string $uuid): bool
{
    try {
        if (!$this->isSQLiteAvailable()) {
            return false;
        }
        
        $count = DB::connection('offline')->table('cups_contratados')
            ->where('uuid', $uuid)
            ->count();
        
        return $count > 0;
        
    } catch (\Exception $e) {
        Log::error('Error verificando existencia de CUPS contratado', [
            'uuid' => $uuid,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * ✅ CONTAR CUPS CONTRATADOS
 */
public function countCupsContratados(): int
{
    try {
        if (!$this->isSQLiteAvailable()) {
            // Fallback a contar archivos JSON
            $cupsContratadosPath = $this->storagePath . '/cups_contratados';
            if (is_dir($cupsContratadosPath)) {
                $files = glob($cupsContratadosPath . '/*.json');
                return count($files);
            }
            return 0;
        }
        
        $count = DB::connection('offline')->table('cups_contratados')->count();
        
        return (int) $count;
        
    } catch (\Exception $e) {
        Log::error('Error contando CUPS contratados', [
            'error' => $e->getMessage()
        ]);
        return 0;
    }
}

/**
 * ✅ OBTENER CUPS ACTIVOS DESDE OFFLINE
 */
public function getCupsActivosOffline(): array
{
    try {
        Log::info('🔍 Obteniendo CUPS activos desde offline');

        $cups = [];

        // ✅ 1. INTENTAR DESDE SQLITE
        if ($this->isSQLiteAvailable()) {
            try {
                $results = DB::connection('offline')
                    ->table('cups')
                    ->where('estado', 'ACTIVO')
                    ->orderBy('nombre', 'asc')
                    ->get();

                if ($results->isNotEmpty()) {
                    $cups = $results->map(function($item) {
                        return [
                            'id' => $item->id,
                            'uuid' => $item->uuid,
                            'codigo' => $item->codigo ?? null,
                            'nombre' => $item->nombre,
                            'descripcion' => $item->descripcion ?? null,
                            'estado' => $item->estado ?? 'ACTIVO',
                            'created_at' => $item->created_at ?? null,
                            'updated_at' => $item->updated_at ?? null
                        ];
                    })->toArray();

                    Log::info('✅ CUPS obtenidos desde SQLite', [
                        'count' => count($cups)
                    ]);

                    return $cups;
                }
            } catch (\Exception $sqliteError) {
                Log::debug('ℹ️ SQLite no disponible para CUPS, intentando JSON', [
                    'error' => $sqliteError->getMessage()
                ]);
            }
        }

        // ✅ 2. FALLBACK A JSON
        $cupsPath = $this->getStoragePath() . '/cups';
        
        if (!is_dir($cupsPath)) {
            Log::warning('⚠️ Directorio de CUPS no existe', [
                'path' => $cupsPath
            ]);
            return [];
        }

        $files = glob($cupsPath . '/*.json');
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            
            if ($data && json_last_error() === JSON_ERROR_NONE) {
                // ✅ FILTRAR SOLO ACTIVOS
                if (($data['estado'] ?? 'ACTIVO') === 'ACTIVO') {
                    $cups[] = [
                        'id' => $data['id'] ?? null,
                        'uuid' => $data['uuid'],
                        'codigo' => $data['codigo'] ?? null,
                        'nombre' => $data['nombre'],
                        'descripcion' => $data['descripcion'] ?? null,
                        'estado' => $data['estado'] ?? 'ACTIVO',
                        'created_at' => $data['created_at'] ?? null,
                        'updated_at' => $data['updated_at'] ?? null
                    ];
                }
            }
        }

        // ✅ ORDENAR POR NOMBRE
        usort($cups, function($a, $b) {
            return strcmp($a['nombre'] ?? '', $b['nombre'] ?? '');
        });

        Log::info('✅ CUPS obtenidos desde JSON', [
            'count' => count($cups)
        ]);

        return $cups;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo CUPS activos offline', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [];
    }
}

/**
 * ✅ OBTENER CONEXIÓN PDO A SQLITE
 */
private function getSQLiteConnection(): ?\PDO
{
    try {
        $dbPath = storage_path('app/offline/offline_data.sqlite');
        
        // Verificar si existe el archivo
        if (!file_exists($dbPath)) {
            Log::warning('⚠️ Base de datos SQLite no encontrada', [
                'path' => $dbPath
            ]);
            return null;
        }

        // Crear conexión PDO
        $pdo = new \PDO("sqlite:{$dbPath}");
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        
        Log::debug('✅ Conexión SQLite PDO establecida');
        
        return $pdo;
        
    } catch (\Exception $e) {
        Log::error('❌ Error conectando a SQLite con PDO', [
            'error' => $e->getMessage(),
            'path' => $dbPath ?? 'N/A'
        ]);
        return null;
    }
}
public function getHistoriasClinicasByPacienteYEspecialidad(
    string $pacienteUuid, 
    string $especialidad
): array {
    try {
        $db = $this->getSQLiteConnection();
        
        if (!$db) {
            return [];
        }

        // ✅ USAR LA NUEVA FUNCIÓN DE NORMALIZACIÓN
        $especialidadNormalizada = $this->normalizarEspecialidad($especialidad);

        Log::info('🔍 Buscando historias en SQLite', [
            'paciente_uuid' => $pacienteUuid,
            'especialidad_original' => $especialidad,
            'especialidad_normalizada' => $especialidadNormalizada
        ]);

        // ✅ BUSCAR EN SQLITE (sin normalizar en SQL, ya está normalizado)
        $stmt = $db->prepare("
            SELECT * FROM historias_clinicas 
            WHERE paciente_uuid = :paciente_uuid 
            ORDER BY created_at DESC
        ");
        
        $stmt->execute(['paciente_uuid' => $pacienteUuid]);
        $todasHistorias = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // ✅ FILTRAR POR ESPECIALIDAD EN PHP (más confiable)
        $historias = array_filter($todasHistorias, function($historia) use ($especialidadNormalizada) {
            $especialidadHistoria = $this->extraerEspecialidadDeHistoria($historia);
            
            if (!$especialidadHistoria) {
                return false;
            }
            
            return $this->normalizarEspecialidad($especialidadHistoria) === $especialidadNormalizada;
        });

        Log::info('🗄️ Historias encontradas en SQLite', [
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad,
            'total' => count($historias)
        ]);

        return array_values($historias); // Reindexar array

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo historias por especialidad (SQLite)', [
            'error' => $e->getMessage()
        ]);
        
        return [];
    }
}

public function esPrimeraConsultaOffline(
    string $pacienteUuid, 
    string $especialidad, 
    ?int $citaActualId = null
): bool {
    try {
        Log::info('🔍 OFFLINE: Verificando si es PRIMERA CONSULTA de la especialidad', [
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad,
            'cita_actual_id' => $citaActualId
        ]);

        // ✅ PASO 1: Verificar que el paciente existe
        $paciente = $this->getPacienteOffline($pacienteUuid);
        
        if (!$paciente) {
            Log::warning('⚠️ Paciente no encontrado offline, asumiendo PRIMERA VEZ');
            return true;
        }

        // ✅ PASO 2: Normalizar especialidad (NUEVA FUNCIÓN MEJORADA)
        $especialidadNormalizada = $this->normalizarEspecialidad($especialidad);
        
        Log::info('🔤 Especialidad normalizada', [
            'original' => $especialidad,
            'normalizada' => $especialidadNormalizada
        ]);

        // ✅ PASO 3: Obtener TODAS las historias del paciente
        $historias = $this->getTodasLasHistoriasDelPacienteOffline($pacienteUuid, $citaActualId);

        Log::info('📋 Total de historias encontradas (excluyendo cita actual)', [
            'total' => count($historias),
            'paciente_uuid' => $pacienteUuid,
            'cita_actual_excluida' => $citaActualId
        ]);

        if (empty($historias)) {
            Log::info('✅ No hay historias previas → PRIMERA VEZ');
            return true;
        }

        // ✅ PASO 4: Filtrar por especialidad (USAR NUEVA FUNCIÓN)
        $historiasDeEspecialidad = array_filter($historias, function($historia) use ($especialidadNormalizada, $especialidad) {
            $especialidadHistoria = $this->extraerEspecialidadDeHistoria($historia);
            
            if (empty($especialidadHistoria)) {
                Log::debug('⚠️ Historia sin especialidad', [
                    'historia_uuid' => $historia['uuid'] ?? 'N/A'
                ]);
                return false;
            }
            
            // ✅ NORMALIZAR CON LA NUEVA FUNCIÓN
            $especialidadHistoriaNormalizada = $this->normalizarEspecialidad($especialidadHistoria);
            
            $coincide = $especialidadHistoriaNormalizada === $especialidadNormalizada;
            
            Log::info('🔍 Comparando especialidades', [
                'historia_uuid' => $historia['uuid'] ?? 'N/A',
                'especialidad_historia_original' => $especialidadHistoria,
                'especialidad_historia_normalizada' => $especialidadHistoriaNormalizada,
                'especialidad_buscada_original' => $especialidad,
                'especialidad_buscada_normalizada' => $especialidadNormalizada,
                'coincide' => $coincide ? '✅ SÍ' : '❌ NO'
            ]);
            
            return $coincide;
        });

        $totalHistorias = count($historiasDeEspecialidad);
        $esPrimeraVez = $totalHistorias === 0;

        Log::info('✅ Resultado: Verificación de primera consulta offline', [
            'paciente_uuid' => $pacienteUuid,
            'especialidad_original' => $especialidad,
            'especialidad_normalizada' => $especialidadNormalizada,
            'total_historias_de_especialidad' => $totalHistorias,
            'es_primera_vez' => $esPrimeraVez,
            'tipo_consulta' => $esPrimeraVez ? '🆕 PRIMERA VEZ' : '🔄 CONTROL'
        ]);

        return $esPrimeraVez;

    } catch (\Exception $e) {
        Log::error('❌ Error verificando primera consulta offline', [
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ]);
        return true;
    }
}

/**
 * ✅ OBTENER TODAS LAS HISTORIAS DEL PACIENTE (EXCLUYENDO CITA ACTUAL) - VERSIÓN FINAL CORREGIDA
 */
private function getTodasLasHistoriasDelPacienteOffline(
    string $pacienteUuid, 
    ?int $citaActualId = null
): array {
    try {
        Log::info('🗄️ Obteniendo todas las historias del paciente (offline)', [
            'paciente_uuid' => $pacienteUuid,
            'cita_actual_id' => $citaActualId
        ]);

        $pdo = $this->getSQLiteConnection();
        
        if (!$pdo) {
            Log::warning('⚠️ No se pudo conectar a SQLite, buscando en JSON');
            return $this->getHistoriasDesdeJSON($pacienteUuid, $citaActualId);
        }

        // ✅ VERIFICAR ESTRUCTURA DE LA TABLA
        $columns = $pdo->query("PRAGMA table_info(historias_clinicas)")->fetchAll(\PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'name');
        
        Log::debug('📊 Columnas disponibles en historias_clinicas', [
            'columns' => $columnNames
        ]);

        // ✅ VERIFICAR QUÉ COLUMNAS TIENE
        $tienePacienteUuid = in_array('paciente_uuid', $columnNames);
        $tieneCitaId = in_array('cita_id', $columnNames);
        $tieneCitaUuid = in_array('cita_uuid', $columnNames);

        Log::info('🔍 Estructura de tabla detectada', [
            'tiene_paciente_uuid' => $tienePacienteUuid,
            'tiene_cita_id' => $tieneCitaId,
            'tiene_cita_uuid' => $tieneCitaUuid
        ]);

        // ✅ CONSTRUIR QUERY SEGÚN ESTRUCTURA REAL
        if ($tienePacienteUuid) {
            // ✅ CASO 1: Tabla tiene paciente_uuid directamente (IDEAL)
            $query = "
                SELECT hc.*
                FROM historias_clinicas hc
                WHERE hc.paciente_uuid = :paciente_uuid
            ";
            $params = ['paciente_uuid' => $pacienteUuid];
            
            // ✅ EXCLUIR CITA ACTUAL (usando cita_uuid si existe, sino cita_id)
            if ($citaActualId) {
                if ($tieneCitaUuid) {
                    $query .= " AND hc.cita_uuid != :cita_actual_uuid";
                    $params['cita_actual_uuid'] = $citaActualId;
                } elseif ($tieneCitaId) {
                    $query .= " AND hc.cita_id != :cita_actual_id";
                    $params['cita_actual_id'] = $citaActualId;
                }
            }
            
        } else {
            // ✅ CASO 2: Necesita JOIN con tabla citas
            if ($tieneCitaUuid) {
                $query = "
                    SELECT hc.*
                    FROM historias_clinicas hc
                    INNER JOIN citas c ON c.uuid = hc.cita_uuid
                    WHERE c.paciente_uuid = :paciente_uuid
                    AND c.estado IN ('ATENDIDA', 'CONFIRMADA')
                ";
            } elseif ($tieneCitaId) {
                $query = "
                    SELECT hc.*
                    FROM historias_clinicas hc
                    INNER JOIN citas c ON c.id = hc.cita_id
                    WHERE c.paciente_uuid = :paciente_uuid
                    AND c.estado IN ('ATENDIDA', 'CONFIRMADA')
                ";
            } else {
                Log::error('❌ No se encontró columna de relación con citas');
                return $this->getHistoriasDesdeJSON($pacienteUuid, $citaActualId);
            }
            
            $params = ['paciente_uuid' => $pacienteUuid];
            
            // ✅ EXCLUIR CITA ACTUAL
            if ($citaActualId) {
                if ($tieneCitaUuid) {
                    $query .= " AND hc.cita_uuid != :cita_actual_uuid";
                    $params['cita_actual_uuid'] = $citaActualId;
                } elseif ($tieneCitaId) {
                    $query .= " AND hc.cita_id != :cita_actual_id";
                    $params['cita_actual_id'] = $citaActualId;
                }
            }
        }

        $query .= " ORDER BY hc.id DESC";

        Log::debug('🔍 Query construida', [
            'query' => $query,
            'params' => $params
        ]);

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $historias = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        Log::info('✅ Historias obtenidas desde SQLite', [
            'total' => count($historias),
            'paciente_uuid' => $pacienteUuid
        ]);

        // ✅ SI NO HAY HISTORIAS EN SQLITE, BUSCAR EN JSON
        if (empty($historias)) {
            Log::info('📁 No hay historias en SQLite, buscando en JSON');
            return $this->getHistoriasDesdeJSON($pacienteUuid, $citaActualId);
        }

        return $historias;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo historias por especialidad (SQLite)', [
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ]);
        
        // ✅ FALLBACK: Buscar en JSON
        return $this->getHistoriasDesdeJSON($pacienteUuid, $citaActualId);
    }
}

/**
 * ✅ OBTENER HISTORIAS DESDE ARCHIVOS JSON (FALLBACK) - VERSIÓN CORREGIDA CON RUTA CORRECTA
 */
private function getHistoriasDesdeJSON(string $pacienteUuid, $citaActualId = null): array
{
    try {
        // ✅ PROBAR AMBAS RUTAS (con guion bajo Y con guion medio)
        $posiblesRutas = [
            storage_path('app/offline/historias_clinicas'),  // ← Guion bajo
            storage_path('app/offline/historias-clinicas'),  // ← Guion medio
        ];

        $historiasPath = null;
        foreach ($posiblesRutas as $ruta) {
            if (is_dir($ruta)) {
                $historiasPath = $ruta;
                Log::info('📁 Carpeta de historias encontrada', [
                    'path' => $ruta
                ]);
                break;
            }
        }

        if (!$historiasPath) {
            Log::warning('📁 Ninguna carpeta de historias existe', [
                'rutas_probadas' => $posiblesRutas
            ]);
            return [];
        }

        $files = glob($historiasPath . '/*.json');
        $historias = [];

        Log::info('📂 Buscando historias en archivos JSON', [
            'total_archivos' => count($files),
            'paciente_uuid' => $pacienteUuid,
            'path' => $historiasPath
        ]);

        foreach ($files as $file) {
            $historia = json_decode(file_get_contents($file), true);
            
            if (!$historia || json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('⚠️ Archivo JSON corrupto', [
                    'file' => basename($file),
                    'error' => json_last_error_msg()
                ]);
                continue;
            }

            // ✅ Verificar si pertenece al paciente
            $historiaPatienteUuid = $historia['paciente_uuid'] ?? 
                                   $historia['cita']['paciente_uuid'] ?? 
                                   $historia['cita']['paciente']['uuid'] ?? 
                                   null;
            
            if ($historiaPatienteUuid !== $pacienteUuid) {
                Log::debug('⏭️ Historia de otro paciente', [
                    'file' => basename($file),
                    'paciente_historia' => $historiaPatienteUuid,
                    'paciente_buscado' => $pacienteUuid
                ]);
                continue;
            }

            Log::info('✅ Historia del paciente encontrada', [
                'file' => basename($file),
                'historia_uuid' => $historia['uuid'] ?? 'N/A',
                'paciente_uuid' => $historiaPatienteUuid
            ]);

            // ✅ Excluir cita actual (puede ser UUID o ID)
            if ($citaActualId) {
                $historiaCitaId = $historia['cita_id'] ?? 
                                 $historia['cita_uuid'] ?? 
                                 $historia['cita']['id'] ?? 
                                 $historia['cita']['uuid'] ?? 
                                 null;
                
                // ✅ Comparar tanto UUID como ID
                if ($historiaCitaId === $citaActualId || 
                    (string)$historiaCitaId === (string)$citaActualId) {
                    Log::debug('⏭️ Excluyendo cita actual del JSON', [
                        'historia_cita_id' => $historiaCitaId,
                        'cita_actual_id' => $citaActualId
                    ]);
                    continue;
                }
            }

            $historias[] = $historia;
        }

        Log::info('✅ Historias obtenidas desde JSON', [
            'total' => count($historias),
            'paciente_uuid' => $pacienteUuid,
            'path' => $historiasPath
        ]);

        return $historias;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo historias desde JSON', [
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ]);
        return [];
    }
}


/**
 * ✅ EXTRAER ESPECIALIDAD DE UNA HISTORIA (SIN DEPENDER DE original_data)
 */
private function extraerEspecialidadDeHistoria(array $historia): ?string
{
    try {
        // ✅ INTENTO 1: Desde campo directo (si existe)
        if (!empty($historia['especialidad'])) {
            Log::debug('✅ Especialidad desde campo directo', [
                'especialidad' => $historia['especialidad']
            ]);
            return $historia['especialidad'];
        }

        // ✅ INTENTO 2: Desde original_data (si existe)
        if (!empty($historia['original_data'])) {
            $originalData = is_string($historia['original_data']) 
                ? json_decode($historia['original_data'], true) 
                : $historia['original_data'];
            
            if ($originalData) {
                // Desde cita.agenda.usuarioMedico.especialidad.nombre
                if (isset($originalData['cita']['agenda']['usuarioMedico']['especialidad']['nombre'])) {
                    Log::debug('✅ Especialidad desde original_data (usuarioMedico)', [
                        'especialidad' => $originalData['cita']['agenda']['usuarioMedico']['especialidad']['nombre']
                    ]);
                    return $originalData['cita']['agenda']['usuarioMedico']['especialidad']['nombre'];
                }

                // Desde cita.agenda.proceso.nombre
                if (isset($originalData['cita']['agenda']['proceso']['nombre'])) {
                    Log::debug('✅ Especialidad desde original_data (proceso)', [
                        'especialidad' => $originalData['cita']['agenda']['proceso']['nombre']
                    ]);
                    return $originalData['cita']['agenda']['proceso']['nombre'];
                }
            }
        }

        // ✅ INTENTO 3: Buscar en la CITA relacionada (desde SQLite o JSON)
        $citaUuid = $historia['cita_uuid'] ?? null;
        
        if ($citaUuid) {
            Log::debug('🔍 Buscando especialidad en cita relacionada', [
                'cita_uuid' => $citaUuid
            ]);
            
            // Buscar cita en SQLite
            $cita = $this->getCitaOffline($citaUuid);
            
            if ($cita) {
                // Buscar en agenda de la cita
                $especialidad = $cita['agenda']['proceso']['nombre'] ?? 
                               $cita['proceso']['nombre'] ?? 
                               $cita['agenda']['usuario_medico']['especialidad']['nombre'] ?? 
                               null;
                
                if ($especialidad) {
                    Log::debug('✅ Especialidad desde cita relacionada', [
                        'especialidad' => $especialidad
                    ]);
                    return $especialidad;
                }
            }
        }

        Log::warning('⚠️ No se pudo extraer especialidad de la historia', [
            'historia_uuid' => $historia['uuid'] ?? 'N/A',
            'tiene_especialidad_campo' => isset($historia['especialidad']),
            'tiene_original_data' => isset($historia['original_data']),
            'tiene_cita_uuid' => isset($historia['cita_uuid'])
        ]);

        return null;

    } catch (\Exception $e) {
        Log::error('❌ Error extrayendo especialidad de historia', [
            'error' => $e->getMessage()
        ]);
        return null;
    }
}
/**
 * COMIENZO HISTORIAS CLINICASSSSS
 * 
 */
public function obtenerUltimaHistoriaOffline(
    string $pacienteUuid, 
    string $especialidad = null  // ✅ HACER OPCIONAL
): ?array {
    Log::info('🔥🔥🔥 [OFFLINESERVICE] Inicio de obtenerUltimaHistoriaOffline', [
        'paciente_uuid' => $pacienteUuid,
        'especialidad_solicitada' => $especialidad,
        'buscar_cualquier_especialidad' => empty($especialidad),
        'archivo' => __FILE__,
        'linea' => __LINE__
    ]);

    try {
        // 🔥 OBTENER TODAS LAS HISTORIAS DEL PACIENTE (SIN FILTRAR POR ESPECIALIDAD)
        $todasLasHistorias = $this->obtenerTodasLasHistoriasOffline($pacienteUuid, null);
        
        Log::info('📊 [OFFLINESERVICE] Historias obtenidas', [
            'total' => count($todasLasHistorias),
            'paciente_uuid' => $pacienteUuid
        ]);

        if (empty($todasLasHistorias)) {
            Log::info('ℹ️ [OFFLINESERVICE] No se encontraron historias del paciente');
            return null;
        }

        // ✅ SI SE ESPECIFICÓ ESPECIALIDAD, INTENTAR BUSCAR DE ESA ESPECIALIDAD PRIMERO
        if (!empty($especialidad)) {
            $historiasDeLaEspecialidad = array_filter($todasLasHistorias, function($historia) use ($especialidad) {
                $historiaEspecialidad = $historia['especialidad'] ?? 
                                       $historia['cita']['agenda']['proceso']['nombre'] ?? 
                                       null;
                
                return $this->normalizarEspecialidad($historiaEspecialidad) === 
                       $this->normalizarEspecialidad($especialidad);
            });

            if (!empty($historiasDeLaEspecialidad)) {
                Log::info('✅ [OFFLINESERVICE] Encontradas historias de la especialidad solicitada', [
                    'especialidad' => $especialidad,
                    'total' => count($historiasDeLaEspecialidad)
                ]);
                
                // ✅ ORDENAR POR FECHA
                usort($historiasDeLaEspecialidad, function($a, $b) {
                    $fechaA = $a['created_at'] ?? '1970-01-01 00:00:00';
                    $fechaB = $b['created_at'] ?? '1970-01-01 00:00:00';
                    return strtotime($fechaB) - strtotime($fechaA);
                });

                $ultimaHistoria = $historiasDeLaEspecialidad[0];
                
                Log::info('✅ [OFFLINESERVICE] Última historia de la especialidad', [
                    'historia_uuid' => $ultimaHistoria['uuid'] ?? null,
                    'especialidad' => $ultimaHistoria['especialidad'] ?? null,
                    'created_at' => $ultimaHistoria['created_at'] ?? null
                ]);

                return $ultimaHistoria;
            }

            Log::warning('⚠️ [OFFLINESERVICE] No hay historias de la especialidad solicitada, buscando de cualquier especialidad', [
                'especialidad_solicitada' => $especialidad
            ]);
        }

        // ✅ SI NO HAY DE LA ESPECIALIDAD O NO SE ESPECIFICÓ, TOMAR LA MÁS RECIENTE DE CUALQUIER ESPECIALIDAD
        usort($todasLasHistorias, function($a, $b) {
            $fechaA = $a['created_at'] ?? '1970-01-01 00:00:00';
            $fechaB = $b['created_at'] ?? '1970-01-01 00:00:00';
            return strtotime($fechaB) - strtotime($fechaA); // DESC: más reciente primero
        });

        $ultimaHistoria = $todasLasHistorias[0];

        Log::info('✅ [OFFLINESERVICE] Última historia encontrada (cualquier especialidad)', [
            'historia_uuid' => $ultimaHistoria['uuid'] ?? null,
            'created_at' => $ultimaHistoria['created_at'] ?? null,
            'especialidad_historia' => $ultimaHistoria['especialidad'] ?? null,
            'especialidad_solicitada' => $especialidad,
            'tiene_medicamentos' => !empty($ultimaHistoria['medicamentos']),
            'medicamentos_count' => count($ultimaHistoria['medicamentos'] ?? []),
            'tiene_diagnosticos' => !empty($ultimaHistoria['diagnosticos']),
            'diagnosticos_count' => count($ultimaHistoria['diagnosticos'] ?? [])
        ]);

        return $ultimaHistoria;

    } catch (\Exception $e) {
        Log::error('❌ [OFFLINESERVICE] Error obteniendo última historia offline', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => basename($e->getFile()),
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad
        ]);
        
        return null;
    }
}




/**
 * ✅ OBTENER ÚLTIMA HISTORIA DESDE JSON (FALLBACK)
 */
private function obtenerUltimaHistoriaDesdeJSON(string $pacienteUuid): ?array
{
    Log::info('🔥🔥🔥 [JSON CORRECTO] Inicio de obtenerUltimaHistoriaDesdeJSON', [
        'paciente_uuid' => $pacienteUuid,
        'archivo' => __FILE__,
        'linea' => __LINE__
    ]);

    try {
        $historiasPath = storage_path('app/offline/historias_clinicas');
        
        if (!is_dir($historiasPath)) {
            Log::warning('⚠️ [JSON] Carpeta no existe');
            return null;
        }

        $files = glob($historiasPath . '/*.json');
        
        Log::info('📂 [JSON] Archivos encontrados', [
            'total' => count($files)
        ]);

        if (empty($files)) {
            Log::warning('⚠️ [JSON] No hay archivos JSON');
            return null;
        }

        $historiasDelPaciente = [];

        foreach ($files as $file) {
            $contenido = file_get_contents($file);
            
            if (!$contenido) {
                continue;
            }

            $historia = json_decode($contenido, true);
            
            if (!$historia) {
                continue;
            }

            $historiaPatienteUuid = $historia['paciente_uuid'] ?? 
                                   $historia['cita']['paciente']['uuid'] ?? 
                                   $historia['cita']['paciente_uuid'] ?? 
                                   null;

            if ($historiaPatienteUuid === $pacienteUuid) {
                $historiasDelPaciente[] = $historia;
            }
        }

        Log::info('✅ [JSON] Historias del paciente encontradas', [
            'total' => count($historiasDelPaciente)
        ]);

        if (empty($historiasDelPaciente)) {
            Log::info('ℹ️ [JSON] No se encontraron historias del paciente');
            return null;
        }

        // 🔥 ORDENAR POR FECHA DE CREACIÓN (created_at) DESC
        usort($historiasDelPaciente, function($a, $b) {
            $fechaA = $a['created_at'] ?? '1970-01-01 00:00:00';
            $fechaB = $b['created_at'] ?? '1970-01-01 00:00:00';
            
            // Convertir a timestamp para comparar
            $timestampA = strtotime($fechaA);
            $timestampB = strtotime($fechaB);
            
            return $timestampB - $timestampA; // DESC: más reciente primero
        });

        $ultimaHistoria = $historiasDelPaciente[0];

        Log::info('✅ [JSON] Última historia obtenida (ordenada por created_at)', [
            'historia_uuid' => $ultimaHistoria['uuid'] ?? null,
            'historia_id' => $ultimaHistoria['id'] ?? null,
            'created_at' => $ultimaHistoria['created_at'] ?? null,
            'especialidad' => $ultimaHistoria['especialidad'] ?? null
        ]);

        $resultado = $this->procesarHistoriaJSONParaFrontend($ultimaHistoria);
        
        Log::info('✅ [JSON] Historia procesada para frontend', [
            'tiene_medicamentos' => !empty($resultado['medicamentos']),
            'medicamentos_count' => count($resultado['medicamentos'] ?? []),
            'tiene_diagnosticos' => !empty($resultado['diagnosticos']),
            'diagnosticos_count' => count($resultado['diagnosticos'] ?? [])
        ]);

        return $resultado;

    } catch (\Exception $e) {
        Log::error('❌ [JSON] Error obteniendo historia desde JSON', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ]);
        return null;
    }
}


/**
 * ✅ PROCESAR HISTORIA OFFLINE PARA FRONTEND (DESDE SQLITE)
 */
private function procesarHistoriaOfflineParaFrontend(array $historia): array
{
    try {
        Log::info('🔧 Procesando historia SQLITE para frontend', [
            'historia_uuid' => $historia['uuid'] ?? null,
            'historia_id' => $historia['id'] ?? null
        ]);

        // ✅ FUNCIÓN HELPER PARA LIMPIAR VALORES
        $limpiarValor = function($valor) {
            if (is_string($valor) && trim($valor) === '') {
                return null;
            }
            return $valor;
        };

        // ✅ EXTRAER original_data
        $originalData = [];
        if (isset($historia['original_data'])) {
            if (is_string($historia['original_data'])) {
                $originalData = json_decode($historia['original_data'], true) ?? [];
            } elseif (is_array($historia['original_data'])) {
                $originalData = $historia['original_data'];
            }
        }

        // ✅ MEDICAMENTOS
        $medicamentos = $originalData['historia_medicamentos'] ?? 
                       $originalData['medicamentos'] ?? 
                       $historia['historia_medicamentos'] ?? 
                       [];

        // ✅ DIAGNÓSTICOS
        $diagnosticos = $originalData['historia_diagnosticos'] ?? 
                       $originalData['diagnosticos'] ?? 
                       $historia['historia_diagnosticos'] ?? 
                       [];

        // ✅ REMISIONES
        $remisiones = $originalData['historia_remisiones'] ?? 
                     $originalData['remisiones'] ?? 
                     $historia['historia_remisiones'] ?? 
                     [];

        // ✅ CUPS
        $cups = $originalData['historia_cups'] ?? 
               $originalData['cups'] ?? 
               $historia['historia_cups'] ?? 
               [];

        Log::info('✅ Historia SQLITE procesada', [
            'historia_uuid' => $historia['uuid'] ?? null,
            'medicamentos_count' => count($medicamentos),
            'diagnosticos_count' => count($diagnosticos),
            'remisiones_count' => count($remisiones),
            'cups_count' => count($cups)
        ]);

        return [
            // ✅ ARRAYS DE RELACIONES
            'medicamentos' => $medicamentos,
            'diagnosticos' => $diagnosticos,
            'remisiones' => $remisiones,
            'cups' => $cups,

            // ✅ CLASIFICACIONES (CON LIMPIEZA)
            'clasificacion_estado_metabolico' => $limpiarValor($historia['clasificacion_estado_metabolico'] ?? null),
            'clasificacion_hta' => $limpiarValor($historia['clasificacion_hta'] ?? null),
            'clasificacion_dm' => $limpiarValor($historia['clasificacion_dm'] ?? null),
            'clasificacion_rcv' => $limpiarValor($historia['clasificacion_rcv'] ?? null),
            'clasificacion_erc_estado' => $limpiarValor($historia['clasificacion_erc_estado'] ?? null),
            'clasificacion_erc_estadodos' => $limpiarValor($historia['clasificacion_erc_estadodos'] ?? null),
            'clasificacion_erc_categoria_ambulatoria_persistente' => $limpiarValor($historia['clasificacion_erc_categoria_ambulatoria_persistente'] ?? null),

            // ✅ TASAS DE FILTRACIÓN
            'tasa_filtracion_glomerular_ckd_epi' => $limpiarValor($historia['tasa_filtracion_glomerular_ckd_epi'] ?? null),
            'tasa_filtracion_glomerular_gockcroft_gault' => $limpiarValor($historia['tasa_filtracion_glomerular_gockcroft_gault'] ?? null),

            // ✅ ANTECEDENTES PERSONALES
            'hipertension_arterial_personal' => $limpiarValor($historia['hipertension_arterial_personal'] ?? 'NO'),
            'obs_hipertension_arterial_personal' => $limpiarValor($historia['obs_personal_hipertension_arterial'] ?? null),
            'diabetes_mellitus_personal' => $limpiarValor($historia['diabetes_mellitus_personal'] ?? 'NO'),
            'obs_diabetes_mellitus_personal' => $limpiarValor($historia['obs_personal_mellitus'] ?? null),

            // ✅ TALLA
            'talla' => $limpiarValor($historia['talla'] ?? null),

            // ✅ TEST DE MORISKY
            'olvida_tomar_medicamentos' => $limpiarValor($historia['olvida_tomar_medicamentos'] ?? null),
            'toma_medicamentos_hora_indicada' => $limpiarValor($historia['toma_medicamentos_hora_indicada'] ?? null),
            'cuando_esta_bien_deja_tomar_medicamentos' => $limpiarValor($historia['cuando_esta_bien_deja_tomar_medicamentos'] ?? null),
            'siente_mal_deja_tomarlos' => $limpiarValor($historia['siente_mal_deja_tomarlos'] ?? null),
            'valoracion_psicologia' => $limpiarValor($historia['valoracion_psicologia'] ?? null),
            'adherente' => $limpiarValor($historia['adherente'] ?? null),

            // ✅ EDUCACIÓN EN SALUD
            'alimentacion' => $limpiarValor($historia['alimentacion'] ?? null),
            'disminucion_consumo_sal_azucar' => $limpiarValor($historia['disminucion_consumo_sal_azucar'] ?? null),
            'fomento_actividad_fisica' => $limpiarValor($historia['fomento_actividad_fisica'] ?? null),
            'importancia_adherencia_tratamiento' => $limpiarValor($historia['importancia_adherencia_tratamiento'] ?? null),
            'consumo_frutas_verduras' => $limpiarValor($historia['consumo_frutas_verduras'] ?? null),
            'manejo_estres' => $limpiarValor($historia['manejo_estres'] ?? null),
            'disminucion_consumo_cigarrillo' => $limpiarValor($historia['disminucion_consumo_cigarrillo'] ?? null),
            'disminucion_peso' => $limpiarValor($historia['disminucion_peso'] ?? null),

            // ✅ METADATOS
            'historia_uuid' => $historia['uuid'] ?? null,
            'historia_id' => $historia['id'] ?? null,
            'created_at' => $historia['created_at'] ?? null,
        ];

    } catch (\Exception $e) {
        Log::error('❌ Error procesando historia SQLITE', [
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ]);
        
        return [
            'medicamentos' => [],
            'diagnosticos' => [],
            'remisiones' => [],
            'cups' => [],
        ];
    }
}
/**
 * 🔥 PROCESAR HISTORIA JSON PARA FRONTEND (DESDE ARCHIVOS JSON)
 */
private function procesarHistoriaJSONParaFrontend(array $historia): array
{
    try {
        Log::info('🔧 Procesando historia JSON para frontend', [
            'historia_uuid' => $historia['uuid'] ?? null,
            'historia_id' => $historia['id'] ?? null
        ]);

        // ✅ FUNCIÓN HELPER PARA LIMPIAR VALORES
        $limpiarValor = function($valor) {
            if (is_string($valor) && trim($valor) === '') {
                return null;
            }
            return $valor;
        };

        // ✅ MEDICAMENTOS
        $medicamentos = $historia['historia_medicamentos'] ?? 
                       $historia['medicamentos'] ?? 
                       [];

        // ✅ DIAGNÓSTICOS
        $diagnosticos = $historia['historia_diagnosticos'] ?? 
                       $historia['diagnosticos'] ?? 
                       [];

        // ✅ REMISIONES
        $remisiones = $historia['historia_remisiones'] ?? 
                     $historia['remisiones'] ?? 
                     [];

        // ✅ CUPS
        $cups = $historia['historia_cups'] ?? 
               $historia['cups'] ?? 
               [];

        Log::info('✅ Historia JSON procesada', [
            'historia_uuid' => $historia['uuid'] ?? null,
            'medicamentos_count' => count($medicamentos),
            'diagnosticos_count' => count($diagnosticos),
            'remisiones_count' => count($remisiones),
            'cups_count' => count($cups),
            'tiene_clasificaciones' => !empty($limpiarValor($historia['clasificacion_estado_metabolico'] ?? null))
        ]);

        return [
            // ✅ ARRAYS DE RELACIONES
            'medicamentos' => $medicamentos,
            'diagnosticos' => $diagnosticos,
            'remisiones' => $remisiones,
            'cups' => $cups,

            // ✅ CLASIFICACIONES (CON LIMPIEZA Y NOMBRE CORRECTO)
            'clasificacion_estado_metabolico' => $limpiarValor($historia['clasificacion_estado_metabolico'] ?? null),
            'clasificacion_hta' => $limpiarValor($historia['clasificacion_hta'] ?? null),
            'clasificacion_dm' => $limpiarValor($historia['clasificacion_dm'] ?? null),
            'clasificacion_rcv' => $limpiarValor($historia['clasificacion_rcv'] ?? null),
            'clasificacion_erc_estado' => $limpiarValor($historia['clasificacion_erc_estado'] ?? null),
            'clasificacion_erc_estadodos' => $limpiarValor($historia['clasificacion_erc_estadodos'] ?? null),
            'clasificacion_erc_categoria_ambulatoria_persistente' => $limpiarValor($historia['clasificacion_erc_categoria_ambulatoria_persistente'] ?? null),

            // ✅ TASAS DE FILTRACIÓN
            'tasa_filtracion_glomerular_ckd_epi' => $limpiarValor($historia['tasa_filtracion_glomerular_ckd_epi'] ?? null),
            'tasa_filtracion_glomerular_gockcroft_gault' => $limpiarValor($historia['tasa_filtracion_glomerular_gockcroft_gault'] ?? null),

            // ✅ ANTECEDENTES PERSONALES
            'hipertension_arterial_personal' => $limpiarValor($historia['hipertension_arterial_personal'] ?? 'NO'),
            'obs_hipertension_arterial_personal' => $limpiarValor($historia['obs_personal_hipertension_arterial'] ?? null),
            'diabetes_mellitus_personal' => $limpiarValor($historia['diabetes_mellitus_personal'] ?? 'NO'),
            'obs_diabetes_mellitus_personal' => $limpiarValor($historia['obs_personal_mellitus'] ?? null),

            // ✅ TALLA
            'talla' => $limpiarValor($historia['talla'] ?? null),

            // ✅ TEST DE MORISKY
            'olvida_tomar_medicamentos' => $limpiarValor($historia['olvida_tomar_medicamentos'] ?? null),
            'toma_medicamentos_hora_indicada' => $limpiarValor($historia['toma_medicamentos_hora_indicada'] ?? null),
            'cuando_esta_bien_deja_tomar_medicamentos' => $limpiarValor($historia['cuando_esta_bien_deja_tomar_medicamentos'] ?? null),
            'siente_mal_deja_tomarlos' => $limpiarValor($historia['siente_mal_deja_tomarlos'] ?? null),
            'valoracion_psicologia' => $limpiarValor($historia['valoracion_psicologia'] ?? null),
            'adherente' => $limpiarValor($historia['adherente'] ?? null),

            // ✅ EDUCACIÓN EN SALUD
            'alimentacion' => $limpiarValor($historia['alimentacion'] ?? null),
            'disminucion_consumo_sal_azucar' => $limpiarValor($historia['disminucion_consumo_sal_azucar'] ?? null),
            'fomento_actividad_fisica' => $limpiarValor($historia['fomento_actividad_fisica'] ?? null),
            'importancia_adherencia_tratamiento' => $limpiarValor($historia['importancia_adherencia_tratamiento'] ?? null),
            'consumo_frutas_verduras' => $limpiarValor($historia['consumo_frutas_verduras'] ?? null),
            'manejo_estres' => $limpiarValor($historia['manejo_estres'] ?? null),
            'disminucion_consumo_cigarrillo' => $limpiarValor($historia['disminucion_consumo_cigarrillo'] ?? null),
            'disminucion_peso' => $limpiarValor($historia['disminucion_peso'] ?? null),

            // ✅ METADATOS
            'historia_uuid' => $historia['uuid'] ?? null,
            'historia_id' => $historia['id'] ?? null,
            'created_at' => $historia['created_at'] ?? null,
        ];

    } catch (\Exception $e) {
        Log::error('❌ Error procesando historia JSON', [
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ]);
        
        return [
            'medicamentos' => [],
            'diagnosticos' => [],
            'remisiones' => [],
            'cups' => [],
        ];
    }
}

public function obtenerTodasLasHistoriasOffline(string $pacienteUuid, ?string $especialidad = null): array
{
    Log::info('🔥🔥🔥 [OFFLINESERVICE] obtenerTodasLasHistoriasOffline', [
        'paciente_uuid' => $pacienteUuid,
        'especialidad' => $especialidad,
        'archivo' => __FILE__,
        'linea' => __LINE__
    ]);

    try {
        // ✅ USAR EL MÉTODO PRIVADO EXISTENTE
        $historias = $this->getHistoriasDesdeJSON($pacienteUuid, null);
        
        Log::info('📊 [OFFLINESERVICE] Historias obtenidas desde JSON', [
            'total' => count($historias),
            'paciente_uuid' => $pacienteUuid
        ]);

        // ✅ FILTRAR POR ESPECIALIDAD (si se especificó)
        if ($especialidad !== null && !empty($historias)) {
            $historiasFiltradas = [];
            
            foreach ($historias as $historia) {
                $historiaEspecialidad = $historia['especialidad'] ?? 
                                       $historia['cita']['agenda']['proceso']['nombre'] ?? 
                                       null;
                
                if ($historiaEspecialidad === $especialidad) {
                    $historiasFiltradas[] = $historia;
                }
            }
            
            Log::info('🔍 [OFFLINESERVICE] Historias filtradas por especialidad', [
                'total_original' => count($historias),
                'total_filtradas' => count($historiasFiltradas),
                'especialidad' => $especialidad
            ]);
            
            return $historiasFiltradas;
        }

        // ✅ PROCESAR CADA HISTORIA PARA EL FRONTEND
        $historiasFormateadas = [];
        foreach ($historias as $historia) {
            $historiaFormateada = $this->procesarHistoriaJSONParaFrontend($historia);
            if ($historiaFormateada) {
                $historiasFormateadas[] = $historiaFormateada;
            }
        }

        Log::info('✅ [OFFLINESERVICE] Historias procesadas', [
            'total' => count($historiasFormateadas),
            'paciente_uuid' => $pacienteUuid
        ]);

        return $historiasFormateadas;

    } catch (\Exception $e) {
        Log::error('❌ [OFFLINESERVICE] Error obteniendo historias offline', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => basename($e->getFile()),
            'paciente_uuid' => $pacienteUuid
        ]);
        
        return [];
    }
}
/**
 * ✅ COMPLETAR DATOS FALTANTES DE HISTORIAS ANTERIORES (OFFLINE)
 * Replica: completarDatosFaltantesDeCualquierEspecialidad del backend
 */
public function completarDatosFaltantesOffline(string $pacienteUuid, array $historiaBase): array
{
    Log::info('🔍 [OFFLINESERVICE] Buscando datos faltantes en historias anteriores', [
        'paciente_uuid' => $pacienteUuid,
        'medicamentos_iniciales' => count($historiaBase['medicamentos'] ?? []),
        'diagnosticos_iniciales' => count($historiaBase['diagnosticos'] ?? []),
        'remisiones_iniciales' => count($historiaBase['remisiones'] ?? []),
        'cups_iniciales' => count($historiaBase['cups'] ?? []),
    ]);

    try {
        // ✅ VERIFICAR SI LOS ARRAYS YA TIENEN DATOS (NO SOBRESCRIBIR)
        $necesitaMedicamentos = empty($historiaBase['medicamentos']);
        $necesitaDiagnosticos = empty($historiaBase['diagnosticos']);
        $necesitaRemisiones = empty($historiaBase['remisiones']);
        $necesitaCups = empty($historiaBase['cups']);

        // ✅ IDENTIFICAR SOLO CAMPOS ESCALARES VACÍOS
        $camposPorCompletar = [
            // CLASIFICACIONES
            'clasificacion_estado_metabolico' => empty($historiaBase['clasificacion_estado_metabolico']),
            'clasificacion_hta' => empty($historiaBase['clasificacion_hta']),
            'clasificacion_dm' => empty($historiaBase['clasificacion_dm']),
            'clasificacion_rcv' => empty($historiaBase['clasificacion_rcv']),
            'clasificacion_erc_estado' => empty($historiaBase['clasificacion_erc_estado']),
            'clasificacion_erc_estadodos' => empty($historiaBase['clasificacion_erc_estadodos']),
            'clasificacion_erc_categoria_ambulatoria_persistente' => empty($historiaBase['clasificacion_erc_categoria_ambulatoria_persistente']),
            
            // TASAS DE FILTRACIÓN
            'tasa_filtracion_glomerular_ckd_epi' => empty($historiaBase['tasa_filtracion_glomerular_ckd_epi']),
            'tasa_filtracion_glomerular_gockcroft_gault' => empty($historiaBase['tasa_filtracion_glomerular_gockcroft_gault']),
            
            // ANTECEDENTES PERSONALES
            'hipertension_arterial_personal' => ($historiaBase['hipertension_arterial_personal'] ?? 'NO') === 'NO',
            'diabetes_mellitus_personal' => ($historiaBase['diabetes_mellitus_personal'] ?? 'NO') === 'NO',
            
            // TALLA
            'talla' => empty($historiaBase['talla']),
            
            // TEST DE MORISKY
            'olvida_tomar_medicamentos' => empty($historiaBase['olvida_tomar_medicamentos']),
            'toma_medicamentos_hora_indicada' => empty($historiaBase['toma_medicamentos_hora_indicada']),
            'cuando_esta_bien_deja_tomar_medicamentos' => empty($historiaBase['cuando_esta_bien_deja_tomar_medicamentos']),
            'siente_mal_deja_tomarlos' => empty($historiaBase['siente_mal_deja_tomarlos']),
            'valoracion_psicologia' => empty($historiaBase['valoracion_psicologia']),
            'adherente' => empty($historiaBase['adherente']),
            
            // EDUCACIÓN EN SALUD
            'alimentacion' => empty($historiaBase['alimentacion']),
            'disminucion_consumo_sal_azucar' => empty($historiaBase['disminucion_consumo_sal_azucar']),
            'fomento_actividad_fisica' => empty($historiaBase['fomento_actividad_fisica']),
            'importancia_adherencia_tratamiento' => empty($historiaBase['importancia_adherencia_tratamiento']),
            'consumo_frutas_verduras' => empty($historiaBase['consumo_frutas_verduras']),
            'manejo_estres' => empty($historiaBase['manejo_estres']),
            'disminucion_consumo_cigarrillo' => empty($historiaBase['disminucion_consumo_cigarrillo']),
            'disminucion_peso' => empty($historiaBase['disminucion_peso']),
        ];

        Log::info('📋 [OFFLINESERVICE] Estado inicial de campos', [
            'campos_escalares_vacios' => count(array_filter($camposPorCompletar)),
            'necesita_medicamentos' => $necesitaMedicamentos,
            'necesita_diagnosticos' => $necesitaDiagnosticos,
            'necesita_remisiones' => $necesitaRemisiones,
            'necesita_cups' => $necesitaCups,
        ]);

        // ✅ SI TODO ESTÁ LLENO, RETORNAR SIN MODIFICAR
        if (!in_array(true, $camposPorCompletar) && 
            !$necesitaMedicamentos && 
            !$necesitaDiagnosticos && 
            !$necesitaRemisiones && 
            !$necesitaCups) {
            Log::info('✅ [OFFLINESERVICE] Todos los campos están completos, no es necesario buscar');
            return $historiaBase;
        }

        // 🔥 BUSCAR EN HISTORIAS ANTERIORES (ÚLTIMAS 20)
        $todasLasHistorias = $this->obtenerTodasLasHistoriasOffline($pacienteUuid, null);
        
        // ✅ ORDENAR POR FECHA DESC
        usort($todasLasHistorias, function($a, $b) {
            $fechaA = $a['created_at'] ?? '1970-01-01 00:00:00';
            $fechaB = $b['created_at'] ?? '1970-01-01 00:00:00';
            return strtotime($fechaB) - strtotime($fechaA);
        });

        // ✅ SALTAR LA PRIMERA (YA LA TENEMOS) Y TOMAR HASTA 20
        $historiasAnteriores = array_slice($todasLasHistorias, 1, 20);

        Log::info('🔍 [OFFLINESERVICE] Historias anteriores encontradas', [
            'count' => count($historiasAnteriores)
        ]);

        // ✅ RECORRER HISTORIAS Y COMPLETAR SOLO DATOS FALTANTES
        foreach ($historiasAnteriores as $historia) {
            
            $especialidadHistoria = $historia['especialidad'] ?? 
                                   $historia['cita']['agenda']['proceso']['nombre'] ?? 
                                   'DESCONOCIDA';
            
            Log::info('🔍 [OFFLINESERVICE] Revisando historia', [
                'historia_uuid' => $historia['uuid'] ?? null,
                'especialidad' => $especialidadHistoria,
                'tiene_medicamentos' => !empty($historia['medicamentos']),
                'tiene_diagnosticos' => !empty($historia['diagnosticos']),
            ]);

            // ═══════════════════════════════════════════
            // 🔹 COMPLETAR MEDICAMENTOS (SOLO SI ESTÁ VACÍO)
            // ═══════════════════════════════════════════
            if ($necesitaMedicamentos && !empty($historia['medicamentos'])) {
                $historiaBase['medicamentos'] = $historia['medicamentos'];
                $necesitaMedicamentos = false;
                Log::info('✅ [OFFLINESERVICE] Medicamentos completados', [
                    'especialidad_origen' => $especialidadHistoria,
                    'count' => count($historia['medicamentos'])
                ]);
            }

            // ═══════════════════════════════════════════
            // 🔹 COMPLETAR DIAGNÓSTICOS (SOLO SI ESTÁ VACÍO)
            // ═══════════════════════════════════════════
            if ($necesitaDiagnosticos && !empty($historia['diagnosticos'])) {
                $historiaBase['diagnosticos'] = $historia['diagnosticos'];
                $necesitaDiagnosticos = false;
                Log::info('✅ [OFFLINESERVICE] Diagnósticos completados', [
                    'especialidad_origen' => $especialidadHistoria,
                    'count' => count($historia['diagnosticos'])
                ]);
            }

            // ═══════════════════════════════════════════
            // 🔹 COMPLETAR REMISIONES (SOLO SI ESTÁ VACÍO)
            // ═══════════════════════════════════════════
            if ($necesitaRemisiones && !empty($historia['remisiones'])) {
                $historiaBase['remisiones'] = $historia['remisiones'];
                $necesitaRemisiones = false;
                Log::info('✅ [OFFLINESERVICE] Remisiones completadas', [
                    'especialidad_origen' => $especialidadHistoria,
                    'count' => count($historia['remisiones'])
                ]);
            }

            // ═══════════════════════════════════════════
            // 🔹 COMPLETAR CUPS (SOLO SI ESTÁ VACÍO)
            // ═══════════════════════════════════════════
            if ($necesitaCups && !empty($historia['cups'])) {
                $historiaBase['cups'] = $historia['cups'];
                $necesitaCups = false;
                Log::info('✅ [OFFLINESERVICE] CUPS completados', [
                    'especialidad_origen' => $especialidadHistoria,
                    'count' => count($historia['cups'])
                ]);
            }

            // ═══════════════════════════════════════════
            // 🔹 COMPLETAR CLASIFICACIONES
            // ═══════════════════════════════════════════
            if ($camposPorCompletar['clasificacion_estado_metabolico'] && !empty($historia['clasificacion_estado_metabolico'])) {
                $historiaBase['clasificacion_estado_metabolico'] = $historia['clasificacion_estado_metabolico'];
                $camposPorCompletar['clasificacion_estado_metabolico'] = false;
            }

            if ($camposPorCompletar['clasificacion_hta'] && !empty($historia['clasificacion_hta'])) {
                $historiaBase['clasificacion_hta'] = $historia['clasificacion_hta'];
                $camposPorCompletar['clasificacion_hta'] = false;
            }

            if ($camposPorCompletar['clasificacion_dm'] && !empty($historia['clasificacion_dm'])) {
                $historiaBase['clasificacion_dm'] = $historia['clasificacion_dm'];
                $camposPorCompletar['clasificacion_dm'] = false;
            }

            if ($camposPorCompletar['clasificacion_rcv'] && !empty($historia['clasificacion_rcv'])) {
                $historiaBase['clasificacion_rcv'] = $historia['clasificacion_rcv'];
                $camposPorCompletar['clasificacion_rcv'] = false;
            }

            if ($camposPorCompletar['clasificacion_erc_estado'] && !empty($historia['clasificacion_erc_estado'])) {
                $historiaBase['clasificacion_erc_estado'] = $historia['clasificacion_erc_estado'];
                $camposPorCompletar['clasificacion_erc_estado'] = false;
            }

            if ($camposPorCompletar['clasificacion_erc_estadodos'] && !empty($historia['clasificacion_erc_estadodos'])) {
                $historiaBase['clasificacion_erc_estadodos'] = $historia['clasificacion_erc_estadodos'];
                $camposPorCompletar['clasificacion_erc_estadodos'] = false;
            }

            if ($camposPorCompletar['clasificacion_erc_categoria_ambulatoria_persistente'] && !empty($historia['clasificacion_erc_categoria_ambulatoria_persistente'])) {
                $historiaBase['clasificacion_erc_categoria_ambulatoria_persistente'] = $historia['clasificacion_erc_categoria_ambulatoria_persistente'];
                $camposPorCompletar['clasificacion_erc_categoria_ambulatoria_persistente'] = false;
            }

            // ═══════════════════════════════════════════
            // 🔹 COMPLETAR TASAS DE FILTRACIÓN
            // ═══════════════════════════════════════════
            if ($camposPorCompletar['tasa_filtracion_glomerular_ckd_epi'] && !empty($historia['tasa_filtracion_glomerular_ckd_epi'])) {
                $historiaBase['tasa_filtracion_glomerular_ckd_epi'] = $historia['tasa_filtracion_glomerular_ckd_epi'];
                $camposPorCompletar['tasa_filtracion_glomerular_ckd_epi'] = false;
            }

            if ($camposPorCompletar['tasa_filtracion_glomerular_gockcroft_gault'] && !empty($historia['tasa_filtracion_glomerular_gockcroft_gault'])) {
                $historiaBase['tasa_filtracion_glomerular_gockcroft_gault'] = $historia['tasa_filtracion_glomerular_gockcroft_gault'];
                $camposPorCompletar['tasa_filtracion_glomerular_gockcroft_gault'] = false;
            }

            // ═══════════════════════════════════════════
            // 🔹 COMPLETAR ANTECEDENTES PERSONALES
            // ═══════════════════════════════════════════
            if ($camposPorCompletar['hipertension_arterial_personal'] && !empty($historia['hipertension_arterial_personal']) && $historia['hipertension_arterial_personal'] !== 'NO') {
                $historiaBase['hipertension_arterial_personal'] = $historia['hipertension_arterial_personal'];
                $historiaBase['obs_hipertension_arterial_personal'] = $historia['obs_hipertension_arterial_personal'] ?? null;
                $camposPorCompletar['hipertension_arterial_personal'] = false;
            }

            if ($camposPorCompletar['diabetes_mellitus_personal'] && !empty($historia['diabetes_mellitus_personal']) && $historia['diabetes_mellitus_personal'] !== 'NO') {
                $historiaBase['diabetes_mellitus_personal'] = $historia['diabetes_mellitus_personal'];
                $historiaBase['obs_diabetes_mellitus_personal'] = $historia['obs_diabetes_mellitus_personal'] ?? null;
                $camposPorCompletar['diabetes_mellitus_personal'] = false;
            }

            // ═══════════════════════════════════════════
            // 🔹 COMPLETAR TALLA
            // ═══════════════════════════════════════════
            if ($camposPorCompletar['talla'] && !empty($historia['talla'])) {
                $historiaBase['talla'] = $historia['talla'];
                $camposPorCompletar['talla'] = false;
            }

            // ═══════════════════════════════════════════
            // 🔹 COMPLETAR TEST DE MORISKY
            // ═══════════════════════════════════════════
            if ($camposPorCompletar['olvida_tomar_medicamentos'] && !empty($historia['olvida_tomar_medicamentos'])) {
                $historiaBase['olvida_tomar_medicamentos'] = $historia['olvida_tomar_medicamentos'];
                $camposPorCompletar['olvida_tomar_medicamentos'] = false;
            }

            if ($camposPorCompletar['toma_medicamentos_hora_indicada'] && !empty($historia['toma_medicamentos_hora_indicada'])) {
                $historiaBase['toma_medicamentos_hora_indicada'] = $historia['toma_medicamentos_hora_indicada'];
                $camposPorCompletar['toma_medicamentos_hora_indicada'] = false;
            }

            if ($camposPorCompletar['cuando_esta_bien_deja_tomar_medicamentos'] && !empty($historia['cuando_esta_bien_deja_tomar_medicamentos'])) {
                $historiaBase['cuando_esta_bien_deja_tomar_medicamentos'] = $historia['cuando_esta_bien_deja_tomar_medicamentos'];
                $camposPorCompletar['cuando_esta_bien_deja_tomar_medicamentos'] = false;
            }

            if ($camposPorCompletar['siente_mal_deja_tomarlos'] && !empty($historia['siente_mal_deja_tomarlos'])) {
                $historiaBase['siente_mal_deja_tomarlos'] = $historia['siente_mal_deja_tomarlos'];
                $camposPorCompletar['siente_mal_deja_tomarlos'] = false;
            }

            if ($camposPorCompletar['valoracion_psicologia'] && !empty($historia['valoracion_psicologia'])) {
                $historiaBase['valoracion_psicologia'] = $historia['valoracion_psicologia'];
                $camposPorCompletar['valoracion_psicologia'] = false;
            }

            if ($camposPorCompletar['adherente'] && !empty($historia['adherente'])) {
                $historiaBase['adherente'] = $historia['adherente'];
                $camposPorCompletar['adherente'] = false;
            }

            // ═══════════════════════════════════════════
            // 🔹 COMPLETAR EDUCACIÓN EN SALUD
            // ═══════════════════════════════════════════
            if ($camposPorCompletar['alimentacion'] && !empty($historia['alimentacion'])) {
                $historiaBase['alimentacion'] = $historia['alimentacion'];
                $camposPorCompletar['alimentacion'] = false;
            }

            if ($camposPorCompletar['disminucion_consumo_sal_azucar'] && !empty($historia['disminucion_consumo_sal_azucar'])) {
                $historiaBase['disminucion_consumo_sal_azucar'] = $historia['disminucion_consumo_sal_azucar'];
                $camposPorCompletar['disminucion_consumo_sal_azucar'] = false;
            }

            if ($camposPorCompletar['fomento_actividad_fisica'] && !empty($historia['fomento_actividad_fisica'])) {
                $historiaBase['fomento_actividad_fisica'] = $historia['fomento_actividad_fisica'];
                $camposPorCompletar['fomento_actividad_fisica'] = false;
            }

            if ($camposPorCompletar['importancia_adherencia_tratamiento'] && !empty($historia['importancia_adherencia_tratamiento'])) {
                $historiaBase['importancia_adherencia_tratamiento'] = $historia['importancia_adherencia_tratamiento'];
                $camposPorCompletar['importancia_adherencia_tratamiento'] = false;
            }

            if ($camposPorCompletar['consumo_frutas_verduras'] && !empty($historia['consumo_frutas_verduras'])) {
                $historiaBase['consumo_frutas_verduras'] = $historia['consumo_frutas_verduras'];
                $camposPorCompletar['consumo_frutas_verduras'] = false;
            }

            if ($camposPorCompletar['manejo_estres'] && !empty($historia['manejo_estres'])) {
                $historiaBase['manejo_estres'] = $historia['manejo_estres'];
                $camposPorCompletar['manejo_estres'] = false;
            }

            if ($camposPorCompletar['disminucion_consumo_cigarrillo'] && !empty($historia['disminucion_consumo_cigarrillo'])) {
                $historiaBase['disminucion_consumo_cigarrillo'] = $historia['disminucion_consumo_cigarrillo'];
                $camposPorCompletar['disminucion_consumo_cigarrillo'] = false;
            }

            if ($camposPorCompletar['disminucion_peso'] && !empty($historia['disminucion_peso'])) {
                $historiaBase['disminucion_peso'] = $historia['disminucion_peso'];
                $camposPorCompletar['disminucion_peso'] = false;
            }

            // ═══════════════════════════════════════════
            // 🔹 VERIFICAR SI YA COMPLETAMOS TODO
            // ═══════════════════════════════════════════
            if (!in_array(true, $camposPorCompletar) && 
                !$necesitaMedicamentos && 
                !$necesitaDiagnosticos && 
                !$necesitaRemisiones && 
                !$necesitaCups) {
                Log::info('✅ [OFFLINESERVICE] Todos los campos completados, deteniendo búsqueda');
                break;
            }
        }

        Log::info('📊 [OFFLINESERVICE] Resultado final de completar datos', [
            'medicamentos_final' => count($historiaBase['medicamentos'] ?? []),
            'diagnosticos_final' => count($historiaBase['diagnosticos'] ?? []),
            'remisiones_final' => count($historiaBase['remisiones'] ?? []),
            'cups_final' => count($historiaBase['cups'] ?? []),
            'tiene_clasificacion' => !empty($historiaBase['clasificacion_estado_metabolico']),
            'tiene_talla' => !empty($historiaBase['talla']),
        ]);

        return $historiaBase;

    } catch (\Exception $e) {
        Log::error('❌ [OFFLINESERVICE] Error completando datos faltantes', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ]);
        
        return $historiaBase;
    }
}
/**
 * FIN HISTORIAS CLINICASSSSS
 * 
 */
/**
 * ✅ NORMALIZAR ESPECIALIDAD (FUNCIÓN HELPER REUTILIZABLE)
 * Elimina espacios, acentos y convierte a mayúsculas
 */
private function normalizarEspecialidad(string $especialidad): string
{
    // ✅ PASO 1: Trim y convertir a mayúsculas
    $especialidad = strtoupper(trim($especialidad));
    
    // ✅ PASO 2: Reemplazar caracteres acentuados (TODAS las variantes)
    $acentos = [
        'Á' => 'A', 'á' => 'A', 'À' => 'A', 'à' => 'A', 'Ä' => 'A', 'ä' => 'A',
        'É' => 'E', 'é' => 'E', 'È' => 'E', 'è' => 'E', 'Ë' => 'E', 'ë' => 'E',
        'Í' => 'I', 'í' => 'I', 'Ì' => 'I', 'ì' => 'I', 'Ï' => 'I', 'ï' => 'I',
        'Ó' => 'O', 'ó' => 'O', 'Ò' => 'O', 'ò' => 'O', 'Ö' => 'O', 'ö' => 'O',
        'Ú' => 'U', 'ú' => 'U', 'Ù' => 'U', 'ù' => 'U', 'Ü' => 'U', 'ü' => 'U',
        'Ñ' => 'N', 'ñ' => 'N'
    ];
    
    $especialidad = str_replace(array_keys($acentos), array_values($acentos), $especialidad);
    
    // ✅ PASO 3: Eliminar espacios
    $especialidad = str_replace(' ', '', $especialidad);
    
    return $especialidad;
}
/**
 * ✅ SINCRONIZAR HISTORIAS CLÍNICAS DESDE EL BACKEND - VERSIÓN INCREMENTAL
 */
public function syncHistoriasClinicas(int $sedeId, ?string $pacienteUuid = null, bool $forceAll = false): array
{
    try {
        Log::info('🔄 Iniciando sincronización INCREMENTAL de historias clínicas', [
            'sede_id' => $sedeId,
            'paciente_uuid' => $pacienteUuid,
            'force_all' => $forceAll
        ]);

        // ✅ PASO 1: OBTENER UUIDs DE HISTORIAS YA EXISTENTES
        $uuidsExistentes = $this->getUuidsHistoriasExistentes();
        
        Log::info('📊 Historias existentes en local', [
            'total_existentes' => count($uuidsExistentes),
            'muestra_uuids' => array_slice($uuidsExistentes, 0, 5)
        ]);

        // ✅ CONSTRUIR PARÁMETROS DE CONSULTA
        $params = ['sede_id' => $sedeId];
        if ($pacienteUuid) {
            $params['paciente_uuid'] = $pacienteUuid;
        }

        // ✅ OBTENER ÚLTIMA FECHA DE SINCRONIZACIÓN (solo si no es forzado)
        if (!$forceAll) {
            $ultimaSync = $this->getUltimaSincronizacion('historias_clinicas');
            if ($ultimaSync) {
                $params['updated_after'] = $ultimaSync;
                Log::info('📅 Sincronizando desde última fecha', [
                    'ultima_sync' => $ultimaSync
                ]);
            }
        }

        $allHistorias = [];
        $currentPage = 1;
        $totalPages = 1;
        $errors = [];
        $historiasOmitidas = 0;

        // ✅ ITERAR SOBRE TODAS LAS PÁGINAS
        do {
            try {
                $params['page'] = $currentPage;

                Log::info('📄 Consultando página de historias', [
                    'page' => $currentPage,
                    'total_pages' => $totalPages
                ]);

                $response = app(ApiService::class)->get('/historias-clinicas', $params);

                if (!isset($response['success']) || !$response['success']) {
                    Log::warning('⚠️ API no devolvió datos válidos', [
                        'page' => $currentPage
                    ]);
                    break;
                }

                // ✅ DETECTAR RESPUESTA PAGINADA
                if (isset($response['data']['data']) && is_array($response['data']['data'])) {
                    $historiasPagina = $response['data']['data'];
                    $totalPages = $response['data']['last_page'] ?? 1;
                    $currentPage = $response['data']['current_page'] ?? 1;
                } elseif (isset($response['data']) && is_array($response['data'])) {
                    $historiasPagina = $response['data'];
                    $totalPages = 1;
                } else {
                    break;
                }

                // ✅ FILTRAR HISTORIAS NUEVAS (que no existen localmente)
                foreach ($historiasPagina as $historia) {
                    if (is_array($historia) && isset($historia['uuid'])) {
                        // ✅ VERIFICAR SI YA EXISTE
                        if (in_array($historia['uuid'], $uuidsExistentes)) {
                            $historiasOmitidas++;
                            Log::debug('⏭️ Historia ya existe, omitiendo', [
                                'uuid' => $historia['uuid']
                            ]);
                            continue;
                        }
                        
                        // ✅ AGREGAR SOLO SI ES NUEVA
                        $allHistorias[] = $historia;
                    }
                }

                $currentPage++;

            } catch (\Exception $e) {
                Log::error('❌ Error consultando página', [
                    'page' => $currentPage,
                    'error' => $e->getMessage()
                ]);
                break;
            }

        } while ($currentPage <= $totalPages);

        Log::info('📥 Análisis de sincronización', [
            'total_en_backend' => count($allHistorias) + $historiasOmitidas,
            'historias_nuevas' => count($allHistorias),
            'historias_existentes_omitidas' => $historiasOmitidas,
            'paginas_procesadas' => $currentPage - 1
        ]);

        // ✅ SI NO HAY HISTORIAS NUEVAS, TERMINAR
        if (empty($allHistorias)) {
            Log::info('✅ No hay historias nuevas para sincronizar');
            
            return [
                'success' => true,
                'synced' => 0,
                'total' => 0,
                'omitidas' => $historiasOmitidas,
                'message' => 'Todas las historias ya están sincronizadas',
                'errors' => []
            ];
        }

        // ✅ GUARDAR SOLO LAS HISTORIAS NUEVAS
        $syncedCount = 0;

          foreach ($allHistorias as $index => $historia) {
            try {
                // ✅ NORMALIZAR
                $historiaNormalizada = $this->normalizarHistoriaClinica($historia);

                // ✅ VERIFICAR ESTRUCTURA ANTES DE GUARDAR
                if (!$this->verificarEstructuraHistoria($historiaNormalizada)) {
                    Log::error('❌ Estructura de historia inválida', [
                        'uuid' => $historiaNormalizada['uuid'] ?? 'sin-uuid',
                        'diagnosticos' => isset($historiaNormalizada['diagnosticos']),
                        'medicamentos' => isset($historiaNormalizada['medicamentos']),
                    ]);
                    $errors[] = [
                        'uuid' => $historiaNormalizada['uuid'] ?? 'sin-uuid',
                        'error' => 'Estructura de historia inválida'
                    ];
                    continue;
                }

                // ✅ GUARDAR
                $this->storeHistoriaClinicaOffline($historiaNormalizada, false);
                $syncedCount++;

            } catch (\Exception $e) {
                Log::error('❌ Error guardando historia', [
                    'uuid' => $historia['uuid'] ?? 'sin-uuid',
                    'error' => $e->getMessage(),
                    'line' => $e->getLine()
                ]);
                $errors[] = [
                    'uuid' => $historia['uuid'] ?? 'sin-uuid',
                    'error' => $e->getMessage()
                ];
            }
        }

        // ✅ ACTUALIZAR TIMESTAMP DE SINCRONIZACIÓN
        if ($syncedCount > 0) {
            $this->updateSincronizacionTimestamp('historias_clinicas');
        }

        Log::info('✅ Sincronización incremental completada', [
            'total_en_backend' => count($allHistorias) + $historiasOmitidas,
            'historias_nuevas_sincronizadas' => $syncedCount,
            'historias_existentes_omitidas' => $historiasOmitidas,
            'errores' => count($errors)
        ]);

        return [
            'success' => true,
            'synced' => $syncedCount,
            'total' => count($allHistorias),
            'omitidas' => $historiasOmitidas,
            'errors' => $errors
        ];

    } catch (\Exception $e) {
        Log::error('❌ Error crítico en sincronización incremental', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);

        return [
            'success' => false,
            'error' => $e->getMessage(),
            'synced' => 0
        ];
    }
}

/**
 * ✅ OBTENER UUIDs DE HISTORIAS YA EXISTENTES
 */
private function getUuidsHistoriasExistentes(): array
{
    try {
        $uuids = [];

        // ✅ MÉTODO 1: Desde SQLite
        if ($this->isSQLiteAvailable()) {
            try {
                $resultados = DB::connection('offline')
                    ->table('historias_clinicas')
                    ->whereNull('deleted_at')
                    ->pluck('uuid')
                    ->toArray();
                
                $uuids = array_merge($uuids, $resultados);
                
                Log::debug('📊 UUIDs desde SQLite', [
                    'count' => count($resultados)
                ]);
            } catch (\Exception $e) {
                Log::warning('⚠️ Error obteniendo UUIDs desde SQLite', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ✅ MÉTODO 2: Desde archivos JSON
        try {
            $historiasPath = storage_path('app/offline/historias_clinicas');
            
            if (is_dir($historiasPath)) {
                $files = glob($historiasPath . '/*.json');
                
                foreach ($files as $file) {
                    $uuid = basename($file, '.json');
                    if (!in_array($uuid, $uuids)) {
                        $uuids[] = $uuid;
                    }
                }
                
                Log::debug('📊 UUIDs desde archivos JSON', [
                    'count' => count($files)
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('⚠️ Error obteniendo UUIDs desde archivos', [
                'error' => $e->getMessage()
            ]);
        }

        // ✅ ELIMINAR DUPLICADOS Y RETORNAR
        $uuids = array_unique($uuids);
        
        Log::info('✅ Total UUIDs existentes', [
            'total' => count($uuids)
        ]);

        return $uuids;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo UUIDs existentes', [
            'error' => $e->getMessage()
        ]);
        return [];
    }
}
private function normalizarHistoriaClinica(array $historia): array
{
    try {
        // ✅ EXTRAER DATOS DE RELACIONES
        $cita = $historia['cita'] ?? [];
        $paciente = $cita['paciente'] ?? [];
        $agenda = $cita['agenda'] ?? [];
        $proceso = $agenda['proceso'] ?? [];
        $medico = $agenda['usuario_medico'] ?? $agenda['usuario'] ?? [];
        $sede = $historia['sede'] ?? [];

        // ✅ EXTRAER USUARIO_ID
        $usuarioId = null;
        if (isset($agenda['usuario_medico']['id'])) {
            $usuarioId = $agenda['usuario_medico']['id'];
        } elseif (isset($agenda['usuario']['id'])) {
            $usuarioId = $agenda['usuario']['id'];
        }

         // ✅ ESTRUCTURA CORRECTA DE DIAGNÓSTICOS
    $diagnosticos = [];
    if (!empty($historia['historia_diagnosticos']) && is_array($historia['historia_diagnosticos'])) {
        foreach ($historia['historia_diagnosticos'] as $diag) {
            $diagnosticos[] = [
                'uuid' => $diag['uuid'] ?? null,
                'diagnostico_id' => $diag['diagnostico_id'] ?? null,
                'tipo' => $diag['tipo'] ?? 'PRINCIPAL',
                'tipo_diagnostico' => $diag['tipo_diagnostico'] ?? 'IMPRESION_DIAGNOSTICA',
                'diagnostico' => [
                    'uuid' => $diag['diagnostico']['uuid'] ?? null,
                    'codigo' => $diag['diagnostico']['codigo'] ?? 'N/A',
                    'nombre' => $diag['diagnostico']['nombre'] ?? 'N/A',
                ]
            ];
        }
    }

    // ✅ ESTRUCTURA CORRECTA DE MEDICAMENTOS
    $medicamentos = [];
    if (!empty($historia['historia_medicamentos']) && is_array($historia['historia_medicamentos'])) {
        foreach ($historia['historia_medicamentos'] as $med) {
            $medicamentos[] = [
                'uuid' => $med['uuid'] ?? null,
                'medicamento_id' => $med['medicamento_id'] ?? null,
                'cantidad' => $med['cantidad'] ?? '1',
                'dosis' => $med['dosis'] ?? 'Según indicación',
                'medicamento' => [
                    'uuid' => $med['medicamento']['uuid'] ?? null,
                    'nombre' => $med['medicamento']['nombre'] ?? 'Sin nombre',
                    'principio_activo' => $med['medicamento']['principio_activo'] ?? '',
                ]
            ];
        }
    }

    // ✅ ESTRUCTURA CORRECTA DE REMISIONES
    $remisiones = [];
    if (!empty($historia['historia_remisiones']) && is_array($historia['historia_remisiones'])) {
        foreach ($historia['historia_remisiones'] as $rem) {
            $remisiones[] = [
                'uuid' => $rem['uuid'] ?? null,
                'remision_id' => $rem['remision_id'] ?? null,
                'observacion' => $rem['observacion'] ?? '',
                'remision' => [
                    'uuid' => $rem['remision']['uuid'] ?? null,
                    'nombre' => $rem['remision']['nombre'] ?? 'Sin nombre',
                    'tipo' => $rem['remision']['tipo'] ?? '',
                ]
            ];
        }
    }

    // ✅ ESTRUCTURA CORRECTA DE CUPS
    $cups = [];
    if (!empty($historia['historia_cups']) && is_array($historia['historia_cups'])) {
        foreach ($historia['historia_cups'] as $cup) {
            $cups[] = [
                'uuid' => $cup['uuid'] ?? null,
                'cups_id' => $cup['cups_id'] ?? null,
                'observacion' => $cup['observacion'] ?? '',
                'cups' => [
                    'uuid' => $cup['cups']['uuid'] ?? null,
                    'codigo' => $cup['cups']['codigo'] ?? 'N/A',
                    'nombre' => $cup['cups']['nombre'] ?? 'Sin nombre',
                ]
            ];
        }
    }

        $complementaria = $historia['complementaria'] ?? null;

        Log::info('🔍 Datos extraídos de historia CON ARRAYS ESTRUCTURADOS', [
            'historia_uuid' => $historia['uuid'],
            'usuario_id' => $usuarioId,
            'medico_nombre' => $medico['nombre_completo'] ?? 'N/A',
            'diagnosticos_count' => count($diagnosticos),
            'medicamentos_count' => count($medicamentos),
            'remisiones_count' => count($remisiones),
            'cups_count' => count($cups),
            'tiene_complementaria' => !is_null($complementaria)
        ]);

        // ✅ CONSTRUIR HISTORIA NORMALIZADA CON **TODOS LOS CAMPOS**
        $historiaNormalizada = [
            // ═══════════════════════════════════════════
            // 🔹 CAMPOS BÁSICOS
            // ═══════════════════════════════════════════
            'uuid' => $historia['uuid'],
            'cita_uuid' => $cita['uuid'] ?? null,
            'cita_id' => $historia['cita_id'] ?? null,
            'sede_id' => $historia['sede_id'] ?? null,
            'usuario_id' => $usuarioId,
            
            // ═══════════════════════════════════════════
            // 🔹 DATOS DE CONSULTA
            // ═══════════════════════════════════════════
            'especialidad' => $historia['especialidad'] ?? null,
            'tipo_consulta' => $historia['tipo_consulta'] ?? null,
            'finalidad' => $historia['finalidad'] ?? null,
            'causa_externa' => $historia['causa_externa'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 MOTIVO Y ENFERMEDAD
            // ═══════════════════════════════════════════
            'motivo_consulta' => $historia['motivo_consulta'] ?? '',
            'enfermedad_actual' => $historia['enfermedad_actual'] ?? '',
            'diagnostico_principal' => $historia['diagnostico_principal'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 ACOMPAÑANTE
            // ═══════════════════════════════════════════
            'acompanante' => $historia['acompanante'] ?? null,
            'acu_telefono' => $historia['acu_telefono'] ?? null,
            'acu_parentesco' => $historia['acu_parentesco'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 DISCAPACIDADES
            // ═══════════════════════════════════════════
            'discapacidad_fisica' => $historia['discapacidad_fisica'] ?? null,
            'discapacidad_visual' => $historia['discapacidad_visual'] ?? null,
            'discapacidad_mental' => $historia['discapacidad_mental'] ?? null,
            'discapacidad_auditiva' => $historia['discapacidad_auditiva'] ?? null,
            'discapacidad_intelectual' => $historia['discapacidad_intelectual'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 DROGODEPENDENCIA
            // ═══════════════════════════════════════════
            'drogo_dependiente' => $historia['drogo_dependiente'] ?? null,
            'drogo_dependiente_cual' => $historia['drogo_dependiente_cual'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 MEDIDAS ANTROPOMÉTRICAS
            // ═══════════════════════════════════════════
            'peso' => $historia['peso'] ?? null,
            'talla' => $historia['talla'] ?? null,
            'imc' => $historia['imc'] ?? null,
            'clasificacion' => $historia['clasificacion'] ?? null,
            'perimetro_abdominal' => $historia['perimetro_abdominal'] ?? null,
            'obs_perimetro_abdominal' => $historia['obs_perimetro_abdominal'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 ANTECEDENTES FAMILIARES
            // ═══════════════════════════════════════════
            'hipertension_arterial' => $historia['hipertension_arterial'] ?? null,
            'parentesco_hipertension' => $historia['parentesco_hipertension'] ?? null,
            'diabetes_mellitus' => $historia['diabetes_mellitus'] ?? null,
            'parentesco_mellitus' => $historia['parentesco_mellitus'] ?? null,
            'artritis' => $historia['artritis'] ?? null,
            'parentesco_artritis' => $historia['parentesco_artritis'] ?? null,
            'enfermedad_cardiovascular' => $historia['enfermedad_cardiovascular'] ?? null,
            'parentesco_cardiovascular' => $historia['parentesco_cardiovascular'] ?? null,
            'antecedente_metabolico' => $historia['antecedente_metabolico'] ?? null,
            'parentesco_metabolico' => $historia['parentesco_metabolico'] ?? null,
            'cancer_mama_estomago_prostata_colon' => $historia['cancer_mama_estomago_prostata_colon'] ?? null,
            'parentesco_cancer' => $historia['parentesco_cancer'] ?? null,
            'leucemia' => $historia['leucemia'] ?? null,
            'parentesco_leucemia' => $historia['parentesco_leucemia'] ?? null,
            'vih' => $historia['vih'] ?? null,
            'parentesco_vih' => $historia['parentesco_vih'] ?? null,
            'otro' => $historia['otro'] ?? null,
            'parentesco_otro' => $historia['parentesco_otro'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 ANTECEDENTES PERSONALES
            // ═══════════════════════════════════════════
            'hipertension_arterial_personal' => $historia['hipertension_arterial_personal'] ?? 'NO',
            'obs_personal_hipertension_arterial' => $historia['obs_personal_hipertension_arterial'] ?? null,
            'diabetes_mellitus_personal' => $historia['diabetes_mellitus_personal'] ?? 'NO',
            'obs_personal_mellitus' => $historia['obs_personal_mellitus'] ?? null,
            'enfermedad_cardiovascular_personal' => $historia['enfermedad_cardiovascular_personal'] ?? null,
            'obs_personal_enfermedad_cardiovascular' => $historia['obs_personal_enfermedad_cardiovascular'] ?? null,
            'arterial_periferica_personal' => $historia['arterial_periferica_personal'] ?? null,
            'obs_personal_arterial_periferica' => $historia['obs_personal_arterial_periferica'] ?? null,
            'carotidea_personal' => $historia['carotidea_personal'] ?? null,
            'obs_personal_carotidea' => $historia['obs_personal_carotidea'] ?? null,
            'aneurisma_aorta_personal' => $historia['aneurisma_aorta_personal'] ?? null,
            'obs_personal_aneurisma_aorta' => $historia['obs_personal_aneurisma_aorta'] ?? null,
            'sindrome_coronario_agudo_angina_personal' => $historia['sindrome_coronario_agudo_angina_personal'] ?? null,
            'obs_personal_sindrome_coronario' => $historia['obs_personal_sindrome_coronario'] ?? null,
            'artritis_personal' => $historia['artritis_personal'] ?? null,
            'obs_personal_artritis' => $historia['obs_personal_artritis'] ?? null,
            'iam_personal' => $historia['iam_personal'] ?? null,
            'obs_personal_iam' => $historia['obs_personal_iam'] ?? null,
            'revascul_coronaria_personal' => $historia['revascul_coronaria_personal'] ?? null,
            'obs_personal_revascul_coronaria' => $historia['obs_personal_revascul_coronaria'] ?? null,
            'insuficiencia_cardiaca_personal' => $historia['insuficiencia_cardiaca_personal'] ?? null,
            'obs_personal_insuficiencia_cardiaca' => $historia['obs_personal_insuficiencia_cardiaca'] ?? null,
            'amputacion_pie_diabetico_personal' => $historia['amputacion_pie_diabetico_personal'] ?? null,
            'obs_personal_amputacion_pie_diabetico' => $historia['obs_personal_amputacion_pie_diabetico'] ?? null,
            'enfermedad_pulmonar_personal' => $historia['enfermedad_pulmonar_personal'] ?? null,
            'obs_personal_enfermedad_pulmonar' => $historia['obs_personal_enfermedad_pulmonar'] ?? null,
            'victima_maltrato_personal' => $historia['victima_maltrato_personal'] ?? null,
            'obs_personal_maltrato_personal' => $historia['obs_personal_maltrato_personal'] ?? null,
            'antecedentes_quirurgicos' => $historia['antecedentes_quirurgicos'] ?? null,
            'obs_personal_antecedentes_quirurgicos' => $historia['obs_personal_antecedentes_quirurgicos'] ?? null,
            'acontosis_personal' => $historia['acontosis_personal'] ?? null,
            'obs_personal_acontosis' => $historia['obs_personal_acontosis'] ?? null,
            'otro_personal' => $historia['otro_personal'] ?? null,
            'obs_personal_otro' => $historia['obs_personal_otro'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 TEST DE MORISKY
            // ═══════════════════════════════════════════
            'olvida_tomar_medicamentos' => $historia['olvida_tomar_medicamentos'] ?? 'NO',
            'toma_medicamentos_hora_indicada' => $historia['toma_medicamentos_hora_indicada'] ?? 'SI',
            'cuando_esta_bien_deja_tomar_medicamentos' => $historia['cuando_esta_bien_deja_tomar_medicamentos'] ?? 'NO',
            'siente_mal_deja_tomarlos' => $historia['siente_mal_deja_tomarlos'] ?? 'NO',
            'valoracion_psicologia' => $historia['valoracion_psicologia'] ?? 'NO',
            'adherente' => $historia['adherente'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 REVISIÓN POR SISTEMAS
            // ═══════════════════════════════════════════
            'general' => $historia['general'] ?? null,
            'cabeza' => $historia['cabeza'] ?? null,
            'orl' => $historia['orl'] ?? null,
            'respiratorio' => $historia['respiratorio'] ?? null,
            'cardiovascular' => $historia['cardiovascular'] ?? null,
            'gastrointestinal' => $historia['gastrointestinal'] ?? null,
            'osteoatromuscular' => $historia['osteoatromuscular'] ?? null,
            'snc' => $historia['snc'] ?? null,
            'revision_sistemas' => $historia['revision_sistemas'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 SIGNOS VITALES
            // ═══════════════════════════════════════════
            'presion_arterial_sistolica_sentado_pie' => $historia['presion_arterial_sistolica_sentado_pie'] ?? null,
            'presion_arterial_distolica_sentado_pie' => $historia['presion_arterial_distolica_sentado_pie'] ?? null,
            'presion_arterial_sistolica_acostado' => $historia['presion_arterial_sistolica_acostado'] ?? null,
            'presion_arterial_distolica_acostado' => $historia['presion_arterial_distolica_acostado'] ?? null,
            'frecuencia_cardiaca' => $historia['frecuencia_cardiaca'] ?? null,
            'frecuencia_respiratoria' => $historia['frecuencia_respiratoria'] ?? null,
            'temperatura' => $historia['temperatura'] ?? null,
            'saturacion_oxigeno' => $historia['saturacion_oxigeno'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 EXAMEN FÍSICO
            // ═══════════════════════════════════════════
            'ef_cabeza' => $historia['ef_cabeza'] ?? null,
            'obs_cabeza' => $historia['obs_cabeza'] ?? null,
            'agudeza_visual' => $historia['agudeza_visual'] ?? null,
            'obs_agudeza_visual' => $historia['obs_agudeza_visual'] ?? null,
            'fundoscopia' => $historia['fundoscopia'] ?? null,
            'obs_fundoscopia' => $historia['obs_fundoscopia'] ?? null,
            'oidos' => $historia['oidos'] ?? null,
            'nariz_senos_paranasales' => $historia['nariz_senos_paranasales'] ?? null,
            'cavidad_oral' => $historia['cavidad_oral'] ?? null,
            'cuello' => $historia['cuello'] ?? null,
            'obs_cuello' => $historia['obs_cuello'] ?? null,
            'cardio_respiratorio' => $historia['cardio_respiratorio'] ?? null,
            'torax' => $historia['torax'] ?? null,
            'obs_torax' => $historia['obs_torax'] ?? null,
            'mamas' => $historia['mamas'] ?? null,
            'obs_mamas' => $historia['obs_mamas'] ?? null,
            'abdomen' => $historia['abdomen'] ?? null,
            'obs_abdomen' => $historia['obs_abdomen'] ?? null,
            'genito_urinario' => $historia['genito_urinario'] ?? null,
            'obs_genito_urinario' => $historia['obs_genito_urinario'] ?? null,
            'musculo_esqueletico' => $historia['musculo_esqueletico'] ?? null,
            'extremidades' => $historia['extremidades'] ?? null,
            'obs_extremidades' => $historia['obs_extremidades'] ?? null,
            'piel_anexos_pulsos' => $historia['piel_anexos_pulsos'] ?? null,
            'obs_piel_anexos_pulsos' => $historia['obs_piel_anexos_pulsos'] ?? null,
            'inspeccion_sensibilidad_pies' => $historia['inspeccion_sensibilidad_pies'] ?? null,
            'sistema_nervioso' => $historia['sistema_nervioso'] ?? null,
            'obs_sistema_nervioso' => $historia['obs_sistema_nervioso'] ?? null,
            'capacidad_cognitiva' => $historia['capacidad_cognitiva'] ?? null,
            'obs_capacidad_cognitiva' => $historia['obs_capacidad_cognitiva'] ?? null,
            'capacidad_cognitiva_orientacion' => $historia['capacidad_cognitiva_orientacion'] ?? null,
            'orientacion' => $historia['orientacion'] ?? null,
            'obs_orientacion' => $historia['obs_orientacion'] ?? null,
            'reflejo_aquiliar' => $historia['reflejo_aquiliar'] ?? null,
            'obs_reflejo_aquiliar' => $historia['obs_reflejo_aquiliar'] ?? null,
            'reflejo_patelar' => $historia['reflejo_patelar'] ?? null,
            'obs_reflejo_patelar' => $historia['obs_reflejo_patelar'] ?? null,
            'hallazgo_positivo_examen_fisico' => $historia['hallazgo_positivo_examen_fisico'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 FACTORES DE RIESGO
            // ═══════════════════════════════════════════
            'tabaquismo' => $historia['tabaquismo'] ?? null,
            'obs_tabaquismo' => $historia['obs_tabaquismo'] ?? null,
            'dislipidemia' => $historia['dislipidemia'] ?? null,
            'obs_dislipidemia' => $historia['obs_dislipidemia'] ?? null,
            'menor_cierta_edad' => $historia['menor_cierta_edad'] ?? null,
            'obs_menor_cierta_edad' => $historia['obs_menor_cierta_edad'] ?? null,
            'condicion_clinica_asociada' => $historia['condicion_clinica_asociada'] ?? null,
            'obs_condicion_clinica_asociada' => $historia['obs_condicion_clinica_asociada'] ?? null,
            'lesion_organo_blanco' => $historia['lesion_organo_blanco'] ?? null,
            'obs_lesion_organo_blanco' => $historia['obs_lesion_organo_blanco'] ?? null,
            'descripcion_lesion_organo_blanco' => $historia['descripcion_lesion_organo_blanco'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 EXÁMENES COMPLEMENTARIOS
            // ═══════════════════════════════════════════
            'fex_es' => $historia['fex_es'] ?? null,
            'electrocardiograma' => $historia['electrocardiograma'] ?? null,
            'fex_es1' => $historia['fex_es1'] ?? null,
            'ecocardiograma' => $historia['ecocardiograma'] ?? null,
            'fex_es2' => $historia['fex_es2'] ?? null,
            'ecografia_renal' => $historia['ecografia_renal'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 CLASIFICACIONES
            // ═══════════════════════════════════════════
            'clasificacion_estado_metabolico' => $historia['clasificacion_estado_metabolico'] ?? null,
            'clasificacion_hta' => $historia['clasificacion_hta'] ?? null,
            'clasificacion_dm' => $historia['clasificacion_dm'] ?? null,
            'clasificacion_rcv' => $historia['clasificacion_rcv'] ?? null,
            'clasificacion_erc_estado' => $historia['clasificacion_erc_estado'] ?? null,
            'clasificacion_erc_estadodos' => $historia['clasificacion_erc_estadodos'] ?? null,
            'clasificacion_erc_categoria_ambulatoria_persistente' => $historia['clasificacion_erc_categoria_ambulatoria_persistente'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 TASAS DE FILTRACIÓN
            // ═══════════════════════════════════════════
            'tasa_filtracion_glomerular_ckd_epi' => $historia['tasa_filtracion_glomerular_ckd_epi'] ?? null,
            'tasa_filtracion_glomerular_gockcroft_gault' => $historia['tasa_filtracion_glomerular_gockcroft_gault'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 EDUCACIÓN EN SALUD
            // ═══════════════════════════════════════════
            'alimentacion' => $historia['alimentacion'] ?? null,
            'disminucion_consumo_sal_azucar' => $historia['disminucion_consumo_sal_azucar'] ?? null,
            'fomento_actividad_fisica' => $historia['fomento_actividad_fisica'] ?? null,
            'importancia_adherencia_tratamiento' => $historia['importancia_adherencia_tratamiento'] ?? null,
            'consumo_frutas_verduras' => $historia['consumo_frutas_verduras'] ?? null,
            'manejo_estres' => $historia['manejo_estres'] ?? null,
            'disminucion_consumo_cigarrillo' => $historia['disminucion_consumo_cigarrillo'] ?? null,
            'disminucion_peso' => $historia['disminucion_peso'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 OTROS CAMPOS
            // ═══════════════════════════════════════════
            'insulina_requiriente' => $historia['insulina_requiriente'] ?? null,
            'recibe_tratamiento_alternativo' => $historia['recibe_tratamiento_alternativo'] ?? null,
            'recibe_tratamiento_con_plantas_medicinales' => $historia['recibe_tratamiento_con_plantas_medicinales'] ?? null,
            'recibe_ritual_medicina_tradicional' => $historia['recibe_ritual_medicina_tradicional'] ?? null,
            'numero_frutas_diarias' => $historia['numero_frutas_diarias'] ?? null,
            'elevado_consumo_grasa_saturada' => $historia['elevado_consumo_grasa_saturada'] ?? null,
            'adiciona_sal_despues_preparar_comida' => $historia['adiciona_sal_despues_preparar_comida'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 REFORMULACIÓN
            // ═══════════════════════════════════════════
            'razon_reformulacion' => $historia['razon_reformulacion'] ?? null,
            'motivo_reformulacion' => $historia['motivo_reformulacion'] ?? null,
            'reformulacion_quien_reclama' => $historia['reformulacion_quien_reclama'] ?? null,
            'reformulacion_nombre_reclama' => $historia['reformulacion_nombre_reclama'] ?? null,
            'adicional' => $historia['adicional'] ?? null,
            
            // ═══════════════════════════════════════════
            // 🔹 OBSERVACIONES
            // ═══════════════════════════════════════════
            'observaciones_generales' => $historia['observaciones_generales'] ?? null,
            
            // ✅✅✅ RELACIONES ANIDADAS (ARRAYS ESTRUCTURADOS) ✅✅✅
            'diagnosticos' => $diagnosticos,
            'medicamentos' => $medicamentos,
            'remisiones' => $remisiones,
            'cups' => $cups,
            'complementaria' => $complementaria,
            
            // ✅✅✅ RELACIONES PRINCIPALES ✅✅✅
            'cita' => $cita,
            'sede' => $sede,
            
            // Datos adicionales para búsqueda
            'paciente_uuid' => $paciente['uuid'] ?? null,
            'paciente_nombre' => $paciente['nombre_completo'] ?? null,
            'paciente_documento' => $paciente['documento'] ?? null,
            'agenda_uuid' => $agenda['uuid'] ?? null,
            'proceso_nombre' => $proceso['nombre'] ?? null,
            'medico_nombre' => $medico['nombre_completo'] ?? null,
            'sede_nombre' => $sede['nombre'] ?? null,
            
            // Fechas
            'created_at' => $historia['created_at'] ?? now()->toISOString(),
            'updated_at' => $historia['updated_at'] ?? now()->toISOString()
        ];

        // ✅ LOG DE VERIFICACIÓN FINAL
        Log::info('✅ Historia normalizada COMPLETAMENTE CON ARRAYS', [
            'uuid' => $historiaNormalizada['uuid'],
            'diagnosticos_final' => count($historiaNormalizada['diagnosticos']),
            'medicamentos_final' => count($historiaNormalizada['medicamentos']),
            'remisiones_final' => count($historiaNormalizada['remisiones']),
            'cups_final' => count($historiaNormalizada['cups']),
            'tiene_cita' => !empty($historiaNormalizada['cita']),
            'tiene_sede' => !empty($historiaNormalizada['sede'])
        ]);

        return $historiaNormalizada;

    } catch (\Exception $e) {
        Log::error('❌ Error normalizando historia', [
            'uuid' => $historia['uuid'] ?? 'sin-uuid',
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ]);

        // ✅ DEVOLVER HISTORIA ORIGINAL SI FALLA
        return $historia;
    }
}



/**
 * ✅ OBTENER HISTORIAS CLÍNICAS OFFLINE
 */
public function getHistoriasClinicasOffline(array $filters = []): array
{
    try {
        $historias = [];

        // ✅ INTENTAR DESDE SQLite PRIMERO
        if ($this->isSQLiteAvailable()) {
            $query = DB::connection('offline')
                ->table('historias_clinicas')
                ->whereNull('deleted_at');

            // ✅ APLICAR FILTROS
            if (!empty($filters['paciente_uuid'])) {
                // Buscar en JSON files por paciente
                return $this->getHistoriasClinicasByPacienteFromFiles($filters['paciente_uuid']);
            }

            if (!empty($filters['cita_uuid'])) {
                $query->where('cita_uuid', $filters['cita_uuid']);
            }

            if (!empty($filters['fecha_desde'])) {
                $query->where('created_at', '>=', $filters['fecha_desde']);
            }

            if (!empty($filters['fecha_hasta'])) {
                $query->where('created_at', '<=', $filters['fecha_hasta']);
            }

            $historias = $query->orderBy('created_at', 'desc')->get()->toArray();
            $historias = array_map(function($h) { return (array)$h; }, $historias);

            Log::info('✅ Historias obtenidas desde SQLite', [
                'total' => count($historias)
            ]);
        }

        // ✅ COMPLEMENTAR CON ARCHIVOS JSON
        $historiasFromFiles = $this->getHistoriasFromFiles($filters);
        
        // ✅ MERGE Y ELIMINAR DUPLICADOS
        $allHistorias = $this->mergeHistorias($historias, $historiasFromFiles);

        Log::info('✅ Total historias offline', [
            'sqlite' => count($historias),
            'files' => count($historiasFromFiles),
            'total' => count($allHistorias)
        ]);

        return $allHistorias;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo historias offline', [
            'error' => $e->getMessage()
        ]);
        return [];
    }
}

private function getHistoriasFromFiles(array $filters = []): array
{
    try {
        $historiasPath = storage_path('app/offline/historias_clinicas');
        
        if (!is_dir($historiasPath)) {
            return [];
        }

        $historias = [];
        $files = glob($historiasPath . '/*.json');

        foreach ($files as $file) {
            try {
                $data = json_decode(file_get_contents($file), true);
                
                if (!$data || !isset($data['uuid'])) {
                    continue;
                }

                // ✅ VERIFICAR ESTRUCTURA AL LEER
                Log::debug('📄 Historia leída desde JSON', [
                    'uuid' => $data['uuid'],
                    'tiene_diagnosticos' => isset($data['diagnosticos']),
                    'tiene_medicamentos' => isset($data['medicamentos']),
                    'tiene_remisiones' => isset($data['remisiones']),
                    'tiene_cups' => isset($data['cups']),
                    'diagnosticos_count' => count($data['diagnosticos'] ?? []),
                    'medicamentos_count' => count($data['medicamentos'] ?? []),
                    // ✅ VERIFICAR ESTRUCTURA INTERNA
                    'diagnostico_keys' => !empty($data['diagnosticos']) ? array_keys($data['diagnosticos'][0]) : [],
                ]);

                // ✅ APLICAR FILTROS
                if (!empty($filters['paciente_uuid']) && 
                    ($data['paciente_uuid'] ?? '') !== $filters['paciente_uuid']) {
                    continue;
                }

                $historias[] = $data;

            } catch (\Exception $e) {
                Log::warning('⚠️ Error leyendo archivo', [
                    'file' => basename($file),
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $historias;

    } catch (\Exception $e) {
        Log::error('❌ Error en getHistoriasFromFiles', [
            'error' => $e->getMessage()
        ]);
        return [];
    }
}
private function verificarEstructuraHistoria(array $historia): bool
{
    $camposRequeridos = ['uuid', 'cita_uuid', 'paciente_uuid'];
    $arraysEsperados = ['diagnosticos', 'medicamentos', 'remisiones', 'cups'];
    
    // ✅ VERIFICAR CAMPOS REQUERIDOS
    foreach ($camposRequeridos as $campo) {
        if (empty($historia[$campo])) {
            Log::warning("⚠️ Campo requerido faltante: {$campo}");
            return false;
        }
    }
    
    // ✅ VERIFICAR ARRAYS (deben existir aunque estén vacíos)
    foreach ($arraysEsperados as $array) {
        if (!isset($historia[$array]) || !is_array($historia[$array])) {
            Log::warning("⚠️ Array faltante o inválido: {$array}", [
                'existe' => isset($historia[$array]),
                'es_array' => isset($historia[$array]) ? is_array($historia[$array]) : false
            ]);
            return false;
        }
    }
    
    // ✅ VERIFICAR ESTRUCTURA INTERNA DE DIAGNÓSTICOS
    if (!empty($historia['diagnosticos'])) {
        $primerDiag = $historia['diagnosticos'][0];
        $camposEsperados = ['uuid', 'diagnostico_id', 'tipo', 'diagnostico'];
        
        foreach ($camposEsperados as $campo) {
            if (!isset($primerDiag[$campo])) {
                Log::warning("⚠️ Campo faltante en diagnóstico: {$campo}");
                return false;
            }
        }
        
        if (!isset($primerDiag['diagnostico']['codigo']) || 
            !isset($primerDiag['diagnostico']['nombre'])) {
            Log::warning("⚠️ Estructura de diagnóstico anidado incorrecta");
            return false;
        }
    }
    
    return true;
}
/**
 * ✅ OBTENER HISTORIAS POR PACIENTE DESDE ARCHIVOS
 */
private function getHistoriasClinicasByPacienteFromFiles(string $pacienteUuid): array
{
    try {
        $historiasPath = storage_path('app/offline/historias_clinicas');
        
        if (!is_dir($historiasPath)) {
            return [];
        }

        $historias = [];
        $files = glob($historiasPath . '/*.json');

        foreach ($files as $file) {
            try {
                $data = json_decode(file_get_contents($file), true);
                
                if (isset($data['paciente_uuid']) && $data['paciente_uuid'] === $pacienteUuid) {
                    $historias[] = $data;
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Error leyendo archivo', [
                    'file' => basename($file)
                ]);
            }
        }

        // ✅ ORDENAR POR FECHA
        usort($historias, function($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });

        return $historias;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo historias por paciente', [
            'error' => $e->getMessage()
        ]);
        return [];
    }
}

/**
 * ✅ MERGE DE HISTORIAS ELIMINANDO DUPLICADOS
 */
private function mergeHistorias(array $historias1, array $historias2): array
{
    $merged = [];
    $uuids = [];

    foreach (array_merge($historias1, $historias2) as $historia) {
        $uuid = $historia['uuid'] ?? null;
        
        if (!$uuid || in_array($uuid, $uuids)) {
            continue;
        }

        $uuids[] = $uuid;
        $merged[] = $historia;
    }

    // ✅ ORDENAR POR FECHA
    usort($merged, function($a, $b) {
        return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
    });

    return $merged;
}

/**
 * ✅ OBTENER ÚLTIMA SINCRONIZACIÓN
 */
private function getUltimaSincronizacion(string $tipo): ?string
{
    try {
        if (!$this->isSQLiteAvailable()) {
            return null;
        }

        $result = DB::connection('offline')
            ->table('sync_status')
            ->where('sync_type', $tipo)
            ->orderBy('last_sync', 'desc')
            ->first();

        return $result ? $result->last_sync : null;

    } catch (\Exception $e) {
        Log::warning('⚠️ Error obteniendo última sincronización', [
            'tipo' => $tipo,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

/**
 * ✅ ACTUALIZAR TIMESTAMP DE SINCRONIZACIÓN
 */
private function updateSincronizacionTimestamp(string $tipo): void
{
    try {
        if (!$this->isSQLiteAvailable()) {
            return;
        }

        DB::connection('offline')->table('sync_status')->updateOrInsert(
            ['sync_type' => $tipo],
            [
                'last_sync' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ]
        );

    } catch (\Exception $e) {
        Log::error('❌ Error actualizando timestamp de sincronización', [
            'tipo' => $tipo,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * ✅ VERIFICAR SI HAY NUEVAS HISTORIAS EN EL BACKEND
 */
public function checkNuevasHistorias(int $sedeId): array
{
    try {
        $ultimaSync = $this->getUltimaSincronizacion('historias_clinicas');
        
        $params = [
            'sede_id' => $sedeId,
            'count_only' => true
        ];

        if ($ultimaSync) {
            $params['updated_after'] = $ultimaSync;
        }

        $response = app(ApiService::class)->get('/historias-clinicas/count', $params);

        if (!isset($response['success']) || !$response['success']) {
            return [
                'success' => false,
                'nuevas' => 0
            ];
        }

        $countNuevas = $response['data']['count'] ?? 0;

        return [
            'success' => true,
            'nuevas' => $countNuevas,
            'ultima_sync' => $ultimaSync
        ];

    } catch (\Exception $e) {
        Log::error('❌ Error verificando nuevas historias', [
            'error' => $e->getMessage()
        ]);

        return [
            'success' => false,
            'nuevas' => 0
        ];
    }
}

/**
 * ✅✅✅ ENVIAR HISTORIAS PENDIENTES A LA API (OFFLINE → API) ✅✅✅
 * Las historias se buscan SOLO en SQLite (JSON es para datos completos)
 */
public function enviarHistoriasPendientes(int $sedeId, ?string $pacienteUuid = null): array
{
    try {
        Log::info('📤 Iniciando envío de historias pendientes a la API', [
            'sede_id' => $sedeId,
            'paciente_uuid' => $pacienteUuid
        ]);

        // ✅ BUSCAR EN SQLite (FUENTE ÚNICA PARA SINCRONIZACIÓN)
        $query = DB::connection('offline')->table('historias_clinicas')
            ->where('sync_status', 'pending');

        if ($pacienteUuid) {
            $query->where('paciente_uuid', $pacienteUuid);
        }

        $historiasPendientes = $query->get();
        
        Log::info('📊 Historias pendientes encontradas', [
            'total' => $historiasPendientes->count()
        ]);

        if ($historiasPendientes->count() === 0) {
            return [
                'success' => true,
                'enviadas' => 0,
                'total' => 0,
                'errors' => []
            ];
        }

        $enviadas = 0;
        $errors = [];

        // ✅ ENVIAR CADA HISTORIA A LA API
        foreach ($historiasPendientes as $historia) {
            try {
                $uuid = $historia->uuid;
                
                Log::info('📤 Enviando historia a API', [
                    'uuid' => $uuid,
                    'paciente' => $historia->paciente_nombre ?? 'N/A'
                ]);

                // ✅ CARGAR ARCHIVO JSON CON DATOS COMPLETOS
                $jsonPath = storage_path("app/offline/historias_clinicas/{$uuid}.json");
                
                if (!file_exists($jsonPath)) {
                    Log::warning('⚠️ Archivo JSON no encontrado', [
                        'uuid' => $uuid,
                        'path' => $jsonPath
                    ]);
                    $errors[] = [
                        'uuid' => $uuid,
                        'error' => 'Archivo JSON no encontrado'
                    ];
                    continue;
                }

                $historiaData = json_decode(file_get_contents($jsonPath), true);
                
                if (!$historiaData) {
                    Log::warning('⚠️ No se pudo decodificar JSON', [
                        'uuid' => $uuid
                    ]);
                    $errors[] = [
                        'uuid' => $uuid,
                        'error' => 'Error decodificando JSON'
                    ];
                    continue;
                }

                // ✅ ENVIAR A LA API
                $response = app(ApiService::class)->post('/historias-clinicas', $historiaData);

                if (isset($response['success']) && $response['success']) {
                    Log::info('✅ Historia enviada exitosamente', [
                        'uuid' => $uuid
                    ]);

                    // ✅ ACTUALIZAR sync_status A 'synced' EN SQLite
                    DB::connection('offline')->table('historias_clinicas')
                        ->where('uuid', $uuid)
                        ->update([
                            'sync_status' => 'synced',
                            'updated_at' => now()
                        ]);

                    // ✅ TAMBIÉN ACTUALIZAR EN EL ARCHIVO JSON
                    $historiaData['sync_status'] = 'synced';
                    $historiaData['updated_at'] = now()->toISOString();
                    file_put_contents($jsonPath, json_encode($historiaData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    
                    Log::info('✅ sync_status actualizado en SQLite y JSON', [
                        'uuid' => $uuid
                    ]);

                    $enviadas++;
                } else {
                    Log::warning('⚠️ API rechazó la historia', [
                        'uuid' => $uuid,
                        'response' => $response
                    ]);
                    $errors[] = [
                        'uuid' => $uuid,
                        'error' => $response['error'] ?? 'Error desconocido'
                    ];
                }

            } catch (\Exception $e) {
                Log::error('❌ Error enviando historia individual', [
                    'uuid' => $historia->uuid ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                $errors[] = [
                    'uuid' => $historia->uuid ?? 'unknown',
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::info('✅ Envío de historias completado', [
            'total_pendientes' => $historiasPendientes->count(),
            'enviadas' => $enviadas,
            'errores' => count($errors)
        ]);

        return [
            'success' => true,
            'enviadas' => $enviadas,
            'total' => $historiasPendientes->count(),
            'errors' => $errors
        ];

    } catch (\Exception $e) {
        Log::error('❌ Error en envío de historias pendientes', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return [
            'success' => false,
            'enviadas' => 0,
            'total' => 0,
            'errors' => [['error' => $e->getMessage()]]
        ];
    }
}

/**
 * ✅✅✅ NUEVO: ALIAS PARA DESCARGAR HISTORIAS DESDE API (API → OFFLINE) ✅✅✅
 */
public function descargarHistoriasDesdeAPI(int $sedeId, ?string $pacienteUuid = null): array
{
    try {
        Log::info('📥 Descargando historias desde API', [
            'sede_id' => $sedeId,
            'paciente_uuid' => $pacienteUuid
        ]);

        // ✅ USAR EL MÉTODO EXISTENTE syncHistoriasClinicas QUE YA HACE ESTO
        $result = $this->syncHistoriasClinicas($sedeId, $pacienteUuid, false);

        return [
            'success' => $result['success'] ?? true,
            'descargadas' => $result['synced'] ?? 0,
            'total' => $result['total'] ?? 0,
            'errors' => $result['errors'] ?? []
        ];

    } catch (\Exception $e) {
        Log::error('❌ Error descargando historias desde API', [
            'error' => $e->getMessage()
        ]);

        return [
            'success' => false,
            'descargadas' => 0,
            'total' => 0,
            'errors' => [['error' => $e->getMessage()]]
        ];
    }
}

/**
 * ✅✅✅ NUEVO: SINCRONIZAR ESTADOS DE CITAS PENDIENTES (OFFLINE → API) ✅✅✅
 */
public function sincronizarEstadosCitas(int $sedeId): array
{
    try {
        Log::info('📤 Iniciando sincronización de estados de citas', [
            'sede_id' => $sedeId
        ]);

        // ✅ OBTENER CITAS CON CAMBIOS PENDIENTES EN LOCALSTORAGE
        // Primero obtenemos citas que tienen sync_status diferente entre SQLite y lo que debería estar en API
        $citasConEstadoModificado = DB::connection('offline')->table('citas')
            ->whereIn('estado', ['ATENDIDA', 'CANCELADA', 'NO_ASISTIO'])
            ->where('sync_status', 'pending')
            ->get();

        Log::info('📊 Citas con estados pendientes de sincronizar', [
            'total' => $citasConEstadoModificado->count()
        ]);

        if ($citasConEstadoModificado->count() === 0) {
            return [
                'success' => true,
                'actualizadas' => 0,
                'total' => 0,
                'errors' => []
            ];
        }

        $actualizadas = 0;
        $errors = [];

        // ✅ SINCRONIZAR CADA ESTADO DE CITA
        foreach ($citasConEstadoModificado as $cita) {
            try {
                Log::info('📤 Actualizando estado de cita en API', [
                    'uuid' => $cita->uuid,
                    'nuevo_estado' => $cita->estado
                ]);

                // ✅ ENVIAR CAMBIO DE ESTADO A LA API
                $response = app(ApiService::class)->put("/citas/{$cita->uuid}/estado", [
                    'estado' => $cita->estado
                ]);

                if (isset($response['success']) && $response['success']) {
                    Log::info('✅ Estado de cita actualizado en API', [
                        'uuid' => $cita->uuid,
                        'estado' => $cita->estado
                    ]);

                    // ✅ ACTUALIZAR sync_status A 'synced'
                    DB::connection('offline')->table('citas')
                        ->where('uuid', $cita->uuid)
                        ->update([
                            'sync_status' => 'synced',
                            'updated_at' => now()
                        ]);

                    $actualizadas++;
                } else {
                    Log::warning('⚠️ API rechazó el cambio de estado', [
                        'uuid' => $cita->uuid,
                        'estado' => $cita->estado,
                        'response' => $response
                    ]);
                    $errors[] = [
                        'uuid' => $cita->uuid,
                        'error' => $response['error'] ?? 'Error desconocido'
                    ];
                }

            } catch (\Exception $e) {
                Log::error('❌ Error actualizando estado de cita individual', [
                    'uuid' => $cita->uuid ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                $errors[] = [
                    'uuid' => $cita->uuid ?? 'unknown',
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::info('✅ Sincronización de estados de citas completada', [
            'total_pendientes' => $citasConEstadoModificado->count(),
            'actualizadas' => $actualizadas,
            'errores' => count($errors)
        ]);

        return [
            'success' => true,
            'actualizadas' => $actualizadas,
            'total' => $citasConEstadoModificado->count(),
            'errors' => $errors
        ];

    } catch (\Exception $e) {
        Log::error('❌ Error en sincronización de estados de citas', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return [
            'success' => false,
            'actualizadas' => 0,
            'total' => 0,
            'errors' => [['error' => $e->getMessage()]]
        ];
    }
}

/**
 * ✅ LIMPIAR AGENDAS PENDIENTES QUE NO ESTÁN EN LA API
 * Elimina agendas con sync_status='pending' que no existen en el servidor
 */
public function limpiarAgendasPendientesHuerfanas(array $agendasApiUuids = []): array
{
    try {
        Log::info('🧹 Iniciando limpieza de agendas pendientes huérfanas');
        
        $eliminadas = 0;
        $errores = [];
        
        // Obtener agendas pendientes de SQLite
        $agendasPendientes = [];
        
        if ($this->isSQLiteAvailable()) {
            $agendasPendientes = DB::connection('offline')
                ->table('agendas')
                ->where('sync_status', 'pending')
                ->get();
        }
        
        // También revisar archivos JSON
        $agendasPath = storage_path('app/offline/agendas');
        $jsonFiles = glob($agendasPath . '/*.json');
        
        foreach ($jsonFiles as $jsonFile) {
            try {
                $content = file_get_contents($jsonFile);
                $agenda = json_decode($content, true);
                
                // Si está pendiente y NO está en la API, eliminar
                if (isset($agenda['sync_status']) && $agenda['sync_status'] === 'pending') {
                    $uuid = $agenda['uuid'] ?? '';
                    
                    // Si no tenemos lista de UUIDs de API, o el UUID no está en la lista
                    if (empty($agendasApiUuids) || !in_array($uuid, $agendasApiUuids)) {
                        // Eliminar archivo JSON
                        if (unlink($jsonFile)) {
                            Log::info('🗑️ Archivo JSON de agenda eliminado', ['uuid' => $uuid]);
                            $eliminadas++;
                        }
                        
                        // Eliminar de SQLite si existe
                        if ($this->isSQLiteAvailable() && !empty($uuid)) {
                            DB::connection('offline')
                                ->table('agendas')
                                ->where('uuid', $uuid)
                                ->delete();
                        }
                    }
                }
            } catch (\Exception $e) {
                $errores[] = [
                    'file' => basename($jsonFile),
                    'error' => $e->getMessage()
                ];
            }
        }
        
        Log::info('✅ Limpieza de agendas pendientes completada', [
            'eliminadas' => $eliminadas,
            'errores' => count($errores)
        ]);
        
        return [
            'success' => true,
            'eliminadas' => $eliminadas,
            'errores' => $errores
        ];
        
    } catch (\Exception $e) {
        Log::error('❌ Error limpiando agendas pendientes', [
            'error' => $e->getMessage()
        ]);
        
        return [
            'success' => false,
            'eliminadas' => 0,
            'errores' => [['error' => $e->getMessage()]]
        ];
    }
}

/**
 * ✅ OBTENER ESTADÍSTICAS DE AGENDAS OFFLINE
 */
public function getAgendasOfflineStats(): array
{
    try {
        $stats = [
            'total_sqlite' => 0,
            'pendientes_sqlite' => 0,
            'sincronizadas_sqlite' => 0,
            'total_json' => 0,
            'pendientes_json' => 0,
            'sincronizadas_json' => 0
        ];
        
        // Stats de SQLite
        if ($this->isSQLiteAvailable()) {
            $stats['total_sqlite'] = DB::connection('offline')
                ->table('agendas')
                ->count();
            
            $stats['pendientes_sqlite'] = DB::connection('offline')
                ->table('agendas')
                ->where('sync_status', 'pending')
                ->count();
            
            $stats['sincronizadas_sqlite'] = DB::connection('offline')
                ->table('agendas')
                ->where('sync_status', 'synced')
                ->count();
        }
        
        // Stats de archivos JSON
        $agendasPath = storage_path('app/offline/agendas');
        $jsonFiles = glob($agendasPath . '/*.json');
        $stats['total_json'] = count($jsonFiles);
        
        foreach ($jsonFiles as $jsonFile) {
            try {
                $content = file_get_contents($jsonFile);
                $agenda = json_decode($content, true);
                
                if (isset($agenda['sync_status'])) {
                    if ($agenda['sync_status'] === 'pending') {
                        $stats['pendientes_json']++;
                    } else {
                        $stats['sincronizadas_json']++;
                    }
                }
            } catch (\Exception $e) {
                // Ignorar errores al leer archivos
            }
        }
        
        return $stats;
        
    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo stats de agendas', [
            'error' => $e->getMessage()
        ]);
        
        return [];
    }
}

}