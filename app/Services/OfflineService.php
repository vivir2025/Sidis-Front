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
            if (!in_array('contratos', $existingTables)) {
                $this->createContratosTable();
                Log::info('✅ Tabla contratos creada');
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
        
        // ✅ AGREGAR ESTA LÍNEA QUE FALTA
        $this->createProcesosTable();
         $this->createUsuariosTable();
        // ✅ CREAR NUEVAS TABLAS
        $this->createAgendasTable();
        $this->createCitasTable();
        $this->createCupsTable();
        $this->createPacientesTable();
         $this->createCupsContratadosTable(); 
        $this->createContratosTable();      
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
        private function createAgendasTable(): void
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
            usuario_id INTEGER,
            brigada_id TEXT NULL,
            usuario_medico_id TEXT NULL,  
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
}
private function createPacientesTable(): void
{
    DB::connection('offline')->statement('
        CREATE TABLE IF NOT EXISTS pacientes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            sede_id INTEGER NOT NULL,
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
            estado TEXT DEFAULT "ACTIVO",
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

private function createContratosTable(): void
{
    DB::connection('offline')->statement('
        CREATE TABLE IF NOT EXISTS contratos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            empresa_id INTEGER,
            empresa_uuid TEXT,
            empresa_nombre TEXT,
            numero_contrato TEXT,
            fecha_inicio DATE,
            fecha_fin DATE,
            estado TEXT DEFAULT "ACTIVO",
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
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
    DB::connection('offline')->table('usuarios')->delete();
    foreach ($data as $item) {
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
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    $this->updateSyncStatus('usuarios', count($data));
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

          $usuarios = DB::connection('offline')->table('usuarios')->get();
    $masterData['usuarios_con_especialidad'] = $usuarios->map(function ($usuario) {
        return [
            'id' => $usuario->id,
            'uuid' => $usuario->uuid,
            'documento' => $usuario->documento,
            'nombre' => $usuario->nombre,
            'apellido' => $usuario->apellido,
            'nombre_completo' => $usuario->nombre_completo,
            'login' => $usuario->login,
            'especialidad_id' => $usuario->especialidad_id,
            'especialidad' => [
                'id' => $usuario->especialidad_id,
                'uuid' => $usuario->especialidad_uuid,
                'nombre' => $usuario->especialidad_nombre
            ],
            'sede_id' => $usuario->sede_id,
            'sede' => [
                'id' => $usuario->sede_id,
                'nombre' => $usuario->sede_nombre
            ]
        ];
    })->toArray();



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
            'stored_at' => now()->toISOString()
        ];

        $this->storeData('users/' . $userData['login'] . '.json', $offlineData);
        
        // Guardar hash de contraseña para validación offline
        $this->savePasswordHash($userData['login'], session('temp_password_for_offline'));
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
        $userFile = 'users/' . $login . '.json';
        
        if (!$this->hasData($userFile)) {
            return null;
        }

        return $this->getData($userFile);
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
            'proceso_id' => $agendaData['proceso_id'] ?? null,
            'usuario_id' => (int) ($agendaData['usuario_id'] ?? 1),
            'usuario_medico_id' => $agendaData['usuario_medico_id'] ?? null,
            'brigada_id' => $agendaData['brigada_id'] ?? null,
            'cupos_disponibles' => (int) ($agendaData['cupos_disponibles'] ?? 0),
            'sync_status' => $needsSync ? 'pending' : 'synced',
            'operation_type' => $needsSync ? 'create' : 'sync',
            'original_data' => $needsSync ? json_encode($agendaData) : null,
            'created_at' => $agendaData['created_at'] ?? now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'deleted_at' => $agendaData['deleted_at'] ?? null
        ];

        // ✅ GUARDAR EN SQLITE
        if ($this->isSQLiteAvailable()) {
            DB::connection('offline')->table('agendas')->updateOrInsert(
                ['uuid' => $agendaData['uuid']],
                $sqliteData
            );
        }

        // ✅ CORREGIR: Usar $agendaData en lugar de $offlineData
        $this->storeData('agendas/' . $agendaData['uuid'] . '.json', $agendaData);

        Log::debug('✅ Agenda almacenada offline', [
            'uuid' => $agendaData['uuid'],
            'fecha' => $agendaData['fecha'],
            'consultorio' => $agendaData['consultorio'],
            'usuario_medico_id' => $agendaData['usuario_medico_id'] ?? null,
            'sync_status' => $sqliteData['sync_status']
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error almacenando agenda offline', [
            'error' => $e->getMessage(),
            'uuid' => $agendaData['uuid'] ?? 'sin-uuid'
        ]);
    }
}

public function storeCitaOffline(array $citaData, bool $needsSync = false): void
{
    try {
        if (empty($citaData['uuid'])) {
            Log::warning('⚠️ Intentando guardar cita sin UUID');
            return;
        }

        if (empty($citaData['sede_id'])) {
            $user = auth()->user();
            $citaData['sede_id'] = $user['sede_id'] ?? 1;
        }

        // ✅ CORREGIR FECHA ANTES DE GUARDAR - IGUAL QUE EN AGENDAS
        if (isset($citaData['fecha'])) {
            $fechaOriginal = $citaData['fecha'];
            
            // Si la fecha viene con timestamp, extraer solo la fecha
            if (strpos($fechaOriginal, 'T') !== false) {
                $fechaLimpia = explode('T', $fechaOriginal)[0]; // "2025-09-12T00:00:00.000Z" -> "2025-09-12"
                $citaData['fecha'] = $fechaLimpia;
                
                Log::info('✅ Fecha de cita corregida al guardar offline', [
                    'cita_uuid' => $citaData['uuid'],
                    'fecha_original' => $fechaOriginal,
                    'fecha_corregida' => $fechaLimpia
                ]);
            }
        }

        // ✅ TAMBIÉN CORREGIR fecha_inicio Y fecha_final SI VIENEN CON ZONA HORARIA
        if (isset($citaData['fecha_inicio'])) {
            $fechaInicio = $citaData['fecha_inicio'];
            if (strpos($fechaInicio, 'T') !== false) {
                $fechaLimpia = explode('T', $fechaInicio)[0]; // "2025-09-12T09:00:00" -> "2025-09-12"
                $horaLimpia = explode('T', $fechaInicio)[1]; // "2025-09-12T09:00:00" -> "09:00:00"
                $horaLimpia = substr($horaLimpia, 0, 8); // "09:00:00.000Z" -> "09:00:00"
                
                // Reconstruir sin zona horaria
                $citaData['fecha_inicio'] = $fechaLimpia . 'T' . $horaLimpia;
            }
        }

        if (isset($citaData['fecha_final'])) {
            $fechaFinal = $citaData['fecha_final'];
            if (strpos($fechaFinal, 'T') !== false) {
                $fechaLimpia = explode('T', $fechaFinal)[0]; // "2025-09-12T09:15:00" -> "2025-09-12"
                $horaLimpia = explode('T', $fechaFinal)[1]; // "2025-09-12T09:15:00" -> "09:15:00"
                $horaLimpia = substr($horaLimpia, 0, 8); // "09:15:00.000Z" -> "09:15:00"
                
                // Reconstruir sin zona horaria
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
            'fecha' => $citaData['fecha'], // ✅ YA ESTÁ CORREGIDA
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
            'cups_contratado_id' => null, // ✅ SIEMPRE NULL
            'cups_contratado_uuid' => $citaData['cups_contratado_uuid'] ?? null, // ✅ ESTE ES EL IMPORTANTE
            'usuario_creo_cita_id' => (int) ($citaData['usuario_creo_cita_id'] ?? 1),
            'sync_status' => $needsSync ? 'pending' : 'synced',
            'created_at' => $citaData['created_at'] ?? now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'deleted_at' => $citaData['deleted_at'] ?? null
        ];

        if ($this->isSQLiteAvailable()) {
            DB::connection('offline')->table('citas')->updateOrInsert(
                ['uuid' => $citaData['uuid']],
                $offlineData
            );
        }

        // También guardar en JSON como backup (con fecha corregida)
        $this->storeData('citas/' . $citaData['uuid'] . '.json', $citaData);

        Log::debug('✅ Cita almacenada offline con fecha corregida', [
            'uuid' => $citaData['uuid'],
            'fecha_final' => $citaData['fecha'], // ✅ MOSTRAR FECHA CORREGIDA
            'paciente_uuid' => $citaData['paciente_uuid'],
            'cups_contratado_uuid' => $citaData['cups_contratado_uuid'] ?? 'null'
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error almacenando cita offline', [
            'error' => $e->getMessage(),
            'uuid' => $citaData['uuid'] ?? 'sin-uuid'
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

            $agendas = $query->orderBy('fecha', 'desc')
                ->orderBy('hora_inicio', 'asc')
                ->get()
                ->toArray();
        } else {
            // Fallback a JSON
            $agendasPath = $this->getStoragePath() . '/agendas';
            if (is_dir($agendasPath)) {
                $files = glob($agendasPath . '/*.json');
                foreach ($files as $file) {
                    $data = json_decode(file_get_contents($file), true);
                    if ($data && $data['sede_id'] == $sedeId && !$data['deleted_at']) {
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

public function getCitasOffline(int $sedeId, array $filters = []): array
{
    try {
        $citas = [];

        if ($this->isSQLiteAvailable()) {
            $query = DB::connection('offline')->table('citas')
                ->where('sede_id', $sedeId)
                ->whereNull('deleted_at');

            // ✅ APLICAR FILTROS CON FECHA LIMPIA - IGUAL QUE EN AGENDAS
            if (!empty($filters['fecha'])) {
                // Limpiar fecha del filtro igual que en agendas
                $fechaFiltro = $filters['fecha'];
                if (strpos($fechaFiltro, 'T') !== false) {
                    $fechaFiltro = explode('T', $fechaFiltro)[0]; // "2025-09-12T00:00:00.000Z" -> "2025-09-12"
                }
                $query->where('fecha', $fechaFiltro);
                
                Log::info('🔍 Filtro de fecha aplicado en citas', [
                    'fecha_original' => $filters['fecha'],
                    'fecha_limpia' => $fechaFiltro
                ]);
            }
            
            if (!empty($filters['estado'])) {
                $query->where('estado', $filters['estado']);
            }
            if (!empty($filters['paciente_uuid'])) {
                $query->where('paciente_uuid', $filters['paciente_uuid']);
            }
            if (!empty($filters['agenda_uuid'])) {
                $query->where('agenda_uuid', $filters['agenda_uuid']);
            }

            $results = $query->orderBy('fecha_inicio', 'desc')->get();
            
            // ✅ CONVERTIR OBJETOS stdClass A ARRAYS
            $citas = $results->map(function($cita) {
                $citaArray = (array) $cita;
                
                // ✅ AGREGAR INFORMACIÓN DEL PACIENTE SI ESTÁ DISPONIBLE
                if (!empty($citaArray['paciente_uuid'])) {
                    $paciente = $this->getPacienteOffline($citaArray['paciente_uuid']);
                    if ($paciente) {
                        $citaArray['paciente'] = $paciente;
                    }
                }
                
                // ✅ AGREGAR INFORMACIÓN DE LA AGENDA SI ESTÁ DISPONIBLE
                if (!empty($citaArray['agenda_uuid'])) {
                    $agenda = $this->getAgendaOffline($citaArray['agenda_uuid']);
                    if ($agenda) {
                        $citaArray['agenda'] = $agenda;
                    }
                }
                
                return $citaArray;
            })->toArray();
            
        } else {
            // Fallback a JSON con fecha limpia
            $citasPath = $this->getStoragePath() . '/citas';
            if (is_dir($citasPath)) {
                $files = glob($citasPath . '/*.json');
                foreach ($files as $file) {
                    $data = json_decode(file_get_contents($file), true);
                    if ($data && $data['sede_id'] == $sedeId && !$data['deleted_at']) {
                        
                        // ✅ APLICAR FILTRO DE FECHA LIMPIA
                        if (!empty($filters['fecha'])) {
                            $fechaFiltro = $filters['fecha'];
                            if (strpos($fechaFiltro, 'T') !== false) {
                                $fechaFiltro = explode('T', $fechaFiltro)[0];
                            }
                            
                            $fechaCita = $data['fecha'];
                            if (strpos($fechaCita, 'T') !== false) {
                                $fechaCita = explode('T', $fechaCita)[0];
                            }
                            
                            if ($fechaCita !== $fechaFiltro) {
                                continue; // Saltar esta cita si no coincide la fecha
                            }
                        }
                        
                        $citas[] = $data;
                    }
                }
            }
        }

        return $citas;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo citas offline', [
            'error' => $e->getMessage(),
            'sede_id' => $sedeId
        ]);
        return [];
    }
}
/**
 * ✅ CORREGIDO: Obtener agenda offline por UUID
 */
public function getAgendaOffline(string $uuid): ?array
{
    try {
        // ✅ USAR $this->storagePath en lugar de $this->offlinePath
        $path = $this->storagePath . "/agendas/{$uuid}.json";
        
        if (file_exists($path)) {
            $content = file_get_contents($path);
            $agenda = json_decode($content, true);
            
            Log::info('✅ OfflineService: Agenda encontrada en JSON', [
                'agenda_uuid' => $agenda['uuid'] ?? 'NO_UUID',
                'agenda_fecha' => $agenda['fecha'] ?? 'NO_FECHA'
            ]);
            
            return $agenda;
        }

        // ✅ TAMBIÉN BUSCAR EN SQLite
        if ($this->isSQLiteAvailable()) {
            $agenda = DB::connection('offline')->table('agendas')
                ->where('uuid', $uuid)
                ->whereNull('deleted_at')
                ->first();
            
            if ($agenda) {
                $agendaArray = (array) $agenda;
                Log::info('✅ OfflineService: Agenda encontrada en SQLite', [
                    'agenda_uuid' => $agendaArray['uuid'],
                    'agenda_fecha' => $agendaArray['fecha']
                ]);
                return $agendaArray;
            }
        }

        Log::info('⚠️ OfflineService: Agenda no encontrada', ['uuid' => $uuid]);
        return null;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo agenda offline', [
            'uuid' => $uuid,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}
public function getCitaOffline(string $uuid): ?array
{
    try {
        if ($this->isSQLiteAvailable()) {
            $cita = DB::connection('offline')->table('citas')
                ->where('uuid', $uuid)
                ->whereNull('deleted_at')
                ->first();
            
            return $cita ? (array) $cita : null;
        }

        return $this->getData('citas/' . $uuid . '.json');

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo cita offline', [
            'error' => $e->getMessage(),
            'uuid' => $uuid
        ]);
        return null;
    }
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
                    // ✅ ÉXITO
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
                        'action' => 'created'
                    ];
                    
                    Log::info('✅ Agenda sincronizada exitosamente', [
                        'uuid' => $agenda->uuid
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
        'original_data' => $data,
        'proceso_id_original' => $data['proceso_id'] ?? 'no-set',
        'brigada_id_original' => $data['brigada_id'] ?? 'no-set',
        'usuario_medico_id_original' => $data['usuario_medico_id'] ?? 'no-set',
        'intervalo_original' => $data['intervalo'] ?? 'no-set'
    ]);

    $cleanData = [
        'modalidad' => $data['modalidad'] ?? 'Ambulatoria',
        'fecha' => $data['fecha'],
        'consultorio' => (string) ($data['consultorio'] ?? ''),
        'hora_inicio' => $data['hora_inicio'],
        'hora_fin' => $data['hora_fin'],
        'intervalo' => (string) ($data['intervalo'] ?? '15'), // ✅ CAMBIAR A STRING
        'etiqueta' => $data['etiqueta'] ?? '',
        'estado' => $data['estado'] ?? 'ACTIVO',
        'sede_id' => (int) ($data['sede_id'] ?? 1),
        'usuario_id' => (int) ($data['usuario_id'] ?? 1)
    ];

    // ✅ MANEJAR proceso_id CORRECTAMENTE
   if (isset($data['proceso_id']) && !empty($data['proceso_id']) && $data['proceso_id'] !== 'null') {
    if (is_numeric($data['proceso_id'])) {
        // Es un ID numérico
        $cleanData['proceso_id'] = (int) $data['proceso_id'];
        Log::info('✅ proceso_id incluido como entero', [
            'original' => $data['proceso_id'],
            'clean' => $cleanData['proceso_id']
        ]);
    } elseif (is_string($data['proceso_id']) && $this->isValidUuid($data['proceso_id'])) {
        // Es un UUID válido - ENVIAR COMO STRING
        $cleanData['proceso_id'] = $data['proceso_id'];
        Log::info('✅ proceso_id incluido como UUID', [
            'original' => $data['proceso_id'],
            'clean' => $cleanData['proceso_id']
        ]);
    } else {
        Log::warning('⚠️ proceso_id inválido, omitiendo', [
            'proceso_id' => $data['proceso_id']
        ]);
    }
}

// ✅ MANEJAR brigada_id CORRECTAMENTE (ACEPTA UUIDs Y ENTEROS)
if (isset($data['brigada_id']) && !empty($data['brigada_id']) && $data['brigada_id'] !== 'null') {
    if (is_numeric($data['brigada_id'])) {
        // Es un ID numérico
        $cleanData['brigada_id'] = (int) $data['brigada_id'];
        Log::info('✅ brigada_id incluido como entero', [
            'original' => $data['brigada_id'],
            'clean' => $cleanData['brigada_id']
        ]);
    } elseif (is_string($data['brigada_id']) && $this->isValidUuid($data['brigada_id'])) {
        // Es un UUID válido - ENVIAR COMO STRING
        $cleanData['brigada_id'] = $data['brigada_id'];
        Log::info('✅ brigada_id incluido como UUID', [
            'original' => $data['brigada_id'],
            'clean' => $cleanData['brigada_id']
        ]);
    } else {
        Log::warning('⚠️ brigada_id inválido, omitiendo', [
            'brigada_id' => $data['brigada_id']
        ]);
    }
}
 // ✅ CORREGIDO: MANEJAR usuario_medico COMO UUID
    if (isset($data['usuario_medico_id']) && !empty($data['usuario_medico_id']) && $data['usuario_medico_id'] !== 'null') {
        // ✅ ENVIAR SIEMPRE COMO usuario_medico_uuid (EL BACKEND LO CONVIERTE)
        $cleanData['usuario_medico_uuid'] = $data['usuario_medico_id'];
        
        Log::info('✅ usuario_medico_uuid agregado a datos de API', [
            'original_field' => 'usuario_medico_id',
            'api_field' => 'usuario_medico_uuid',
            'value' => $data['usuario_medico_id']
        ]);
    }

    Log::info('🧹 Datos finales limpiados para API', [
    'clean_data' => $cleanData,
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
 * ✅ SINCRONIZAR CUPS DESDE API
 */
public function syncCupsFromApi(array $cupsList): bool
{
    try {
        Log::info('🔄 Sincronizando CUPS offline', [
            'count' => count($cupsList)
        ]);

        // Asegurar que la tabla existe
        if ($this->isSQLiteAvailable()) {
            $this->createCupsTable();
            
            // Limpiar datos existentes
            DB::connection('offline')->table('cups')->delete();
        }

        $syncCount = 0;
        foreach ($cupsList as $cups) {
            $this->storeCupsOffline($cups);
            $syncCount++;
        }

        Log::info('✅ CUPS sincronizados offline', [
            'synced' => $syncCount,
            'total' => count($cupsList)
        ]);

        return true;

    } catch (\Exception $e) {
        Log::error('❌ Error sincronizando CUPS offline', [
            'error' => $e->getMessage()
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
                    // ✅ ÉXITO
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

// En OfflineService.php - AGREGAR ESTOS MÉTODOS AL FINAL

/**
 * ✅ OBTENER PACIENTE OFFLINE POR UUID
 */
public function getPacienteOffline(string $uuid): ?array
{
    try {
        // ✅ USAR $this->storagePath en lugar de $this->offlinePath
        $path = $this->storagePath . "/pacientes/{$uuid}.json";
        
        if (file_exists($path)) {
            $content = file_get_contents($path);
            $paciente = json_decode($content, true);
            
            Log::info('✅ OfflineService: Paciente encontrado en JSON', [
                'paciente_uuid' => $paciente['uuid'] ?? 'NO_UUID',
                'paciente_nombre' => $paciente['nombre_completo'] ?? 'NO_NOMBRE'
            ]);
            
            return $paciente;
        }

        // ✅ TAMBIÉN BUSCAR EN SQLite
        if ($this->isSQLiteAvailable()) {
            $paciente = DB::connection('offline')->table('pacientes')
                ->where('uuid', $uuid)
                ->whereNull('deleted_at')
                ->first();
            
            if ($paciente) {
                $pacienteArray = (array) $paciente;
                Log::info('✅ OfflineService: Paciente encontrado en SQLite', [
                    'paciente_uuid' => $pacienteArray['uuid'],
                    'paciente_nombre' => $pacienteArray['nombre_completo']
                ]);
                return $pacienteArray;
            }
        }

        // Buscar en índice por documento
        $indexPath = $this->storagePath . "/pacientes_by_document";
        if (is_dir($indexPath)) {
            $files = glob($indexPath . "/*.json");
            foreach ($files as $file) {
                $content = json_decode(file_get_contents($file), true);
                if (isset($content['uuid']) && $content['uuid'] === $uuid) {
                    return $this->getPacienteOffline($content['uuid']);
                }
            }
        }

        Log::info('⚠️ OfflineService: Paciente no encontrado', ['uuid' => $uuid]);
        return null;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo paciente offline', [
            'uuid' => $uuid,
            'error' => $e->getMessage()
        ]);
        return null;
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

/**
 * ✅ ALMACENAR PACIENTE OFFLINE
 */
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

        $offlineData = [
            'id' => $pacienteData['id'] ?? null,
            'uuid' => $pacienteData['uuid'],
            'sede_id' => $pacienteData['sede_id'],
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
            'estado' => $pacienteData['estado'] ?? 'ACTIVO',
            'sync_status' => $needsSync ? 'pending' : 'synced',
            'stored_at' => now()->toISOString(),
            'deleted_at' => $pacienteData['deleted_at'] ?? null
        ];

        // Guardar en JSON
        $this->storeData('pacientes/' . $pacienteData['uuid'] . '.json', $offlineData);
        
        // También indexar por documento
        if (!empty($pacienteData['documento'])) {
            $this->storeData('pacientes_by_document/' . $pacienteData['documento'] . '.json', [
                'uuid' => $pacienteData['uuid'],
                'sede_id' => $pacienteData['sede_id']
            ]);
        }

        Log::debug('✅ Paciente almacenado offline', [
            'uuid' => $pacienteData['uuid'],
            'documento' => $pacienteData['documento'] ?? 'sin-documento',
            'sync_status' => $offlineData['sync_status']
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error almacenando paciente offline', [
            'error' => $e->getMessage(),
            'uuid' => $pacienteData['uuid'] ?? 'sin-uuid'
        ]);
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

/**
 * ✅ ALMACENAR CUPS CONTRATADO OFFLINE
 */
public function storeCupsContratadoOffline(array $cupsContratadoData): void
{
    try {
        if (empty($cupsContratadoData['uuid'])) {
            Log::warning('⚠️ Intentando guardar CUPS contratado sin UUID');
            return;
        }

        // ✅ EXTRAER INFORMACIÓN ANIDADA CORRECTAMENTE
        $offlineData = [
            'uuid' => $cupsContratadoData['uuid'],
            'contrato_id' => $cupsContratadoData['contrato_id'] ?? null,
            'contrato_uuid' => $cupsContratadoData['contrato']['uuid'] ?? null,
            'categoria_cups_id' => $cupsContratadoData['categoria_cups_id'] ?? null,
            'cups_id' => $cupsContratadoData['cups_id'] ?? null,
            'cups_uuid' => $cupsContratadoData['cups']['uuid'] ?? null,
            'cups_codigo' => $cupsContratadoData['cups']['codigo'] ?? null,
            'cups_nombre' => $cupsContratadoData['cups']['nombre'] ?? null,
            'tarifa' => $cupsContratadoData['tarifa'] ?? '0',
            'estado' => $cupsContratadoData['estado'] ?? 'ACTIVO',
            'contrato_fecha_inicio' => $cupsContratadoData['contrato']['fecha_inicio'] ?? null,
            'contrato_fecha_fin' => $cupsContratadoData['contrato']['fecha_fin'] ?? null,
            'contrato_estado' => $cupsContratadoData['contrato']['estado'] ?? null,
            'empresa_nombre' => $cupsContratadoData['contrato']['empresa']['nombre'] ?? null,
            'created_at' => $cupsContratadoData['created_at'] ?? now()->toISOString(),
            'updated_at' => now()->toISOString()
        ];

        if ($this->isSQLiteAvailable()) {
            DB::connection('offline')->table('cups_contratados')->updateOrInsert(
                ['uuid' => $cupsContratadoData['uuid']],
                $offlineData
            );
        }

        // También guardar en JSON
        $this->storeData('cups_contratados/' . $cupsContratadoData['uuid'] . '.json', $offlineData);

        Log::debug('✅ CUPS contratado almacenado offline', [
            'uuid' => $cupsContratadoData['uuid'],
            'cups_uuid' => $offlineData['cups_uuid'],
            'cups_codigo' => $offlineData['cups_codigo']
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error almacenando CUPS contratado offline', [
            'error' => $e->getMessage(),
            'uuid' => $cupsContratadoData['uuid'] ?? 'sin-uuid'
        ]);
    }
}


/**
 * ✅ BUSCAR CUPS CONTRATADO POR CUPS UUID OFFLINE
 */
public function getCupsContratadoPorCupsUuidOffline(string $cupsUuid): ?array
{
    try {
        Log::info('🔍 Buscando CUPS contratado offline', [
            'cups_uuid' => $cupsUuid
        ]);

        if ($this->isSQLiteAvailable()) {
            $cupsContratado = DB::connection('offline')->table('cups_contratados')
                ->where('cups_uuid', $cupsUuid)
                ->where('estado', 'ACTIVO')
                ->where('contrato_estado', 'ACTIVO')
                ->where('contrato_fecha_inicio', '<=', now()->format('Y-m-d'))
                ->where('contrato_fecha_fin', '>=', now()->format('Y-m-d'))
                ->first();
            
            if ($cupsContratado) {
                $result = (array) $cupsContratado;
                Log::info('✅ CUPS contratado encontrado en SQLite', [
                    'cups_contratado_uuid' => $result['uuid'],
                    'cups_codigo' => $result['cups_codigo'] ?? 'N/A'
                ]);
                return $result;
            }
        }

        // Fallback a JSON
        $cupsContratadosPath = $this->storagePath . '/cups_contratados';
        if (is_dir($cupsContratadosPath)) {
            $files = glob($cupsContratadosPath . '/*.json');
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($data && 
                    $data['cups_uuid'] === $cupsUuid &&
                    $data['estado'] === 'ACTIVO' &&
                    $data['contrato_estado'] === 'ACTIVO' &&
                    $data['contrato_fecha_inicio'] <= now()->format('Y-m-d') &&
                    $data['contrato_fecha_fin'] >= now()->format('Y-m-d')) {
                    
                    Log::info('✅ CUPS contratado encontrado en JSON', [
                        'cups_contratado_uuid' => $data['uuid']
                    ]);
                    return $data;
                }
            }
        }

        Log::info('⚠️ CUPS contratado no encontrado offline', ['cups_uuid' => $cupsUuid]);
        return null;

    } catch (\Exception $e) {
        Log::error('❌ Error buscando CUPS contratado offline', [
            'error' => $e->getMessage(),
            'cups_uuid' => $cupsUuid
        ]);
        return null;
    }
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

}
