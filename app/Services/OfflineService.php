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
    private function ensureSQLiteExists(): void
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
    private function isSQLiteAvailable(): bool
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
                return; // Las tablas ya existen
            }
            
            Log::info('🔧 Creando tablas SQLite offline...');
            
            // Crear tablas una por una
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
            'ocupaciones', 'novedades', 'auxiliares', 'brigadas'
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
            'ocupaciones', 'novedades', 'auxiliares', 'brigadas'
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
}
