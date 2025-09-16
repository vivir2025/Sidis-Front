<?php
// Crear: app/Console/Commands/ForzarSyncCupsContratados.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\{ApiService, OfflineService, AuthService};
use Illuminate\Support\Facades\{DB, Log};

class ForzarSyncCupsContratados extends Command
{
    protected $signature = 'cups:force-sync {cups_uuid?} {--clear : Limpiar cache antes de sincronizar}';
    protected $description = 'Forzar sincronización inmediata de CUPS contratados';

    protected $apiService;
    protected $offlineService;
    protected $authService;

    public function __construct(ApiService $apiService, OfflineService $offlineService, AuthService $authService)
    {
        parent::__construct();
        $this->apiService = $apiService;
        $this->offlineService = $offlineService;
        $this->authService = $authService;
    }

    public function handle()
    {
        $cupsUuid = $this->argument('cups_uuid');
        
        if ($cupsUuid) {
            return $this->syncCupsEspecifico($cupsUuid);
        } else {
            return $this->syncTodosLosCups();
        }
    }

    private function syncCupsEspecifico(string $cupsUuid): int
    {
        $this->info("🔄 Sincronizando CUPS específico: {$cupsUuid}");

        try {
            // ✅ LIMPIAR CACHE SI SE SOLICITA
            if ($this->option('clear')) {
                $this->info('🗑️ Limpiando cache de CUPS contratado...');
                $this->limpiarCacheCupsContratado($cupsUuid);
            }

            // ✅ MOSTRAR ESTADO ACTUAL
            $this->mostrarEstadoActual($cupsUuid);

            // ✅ SINCRONIZAR DESDE API
            if ($this->authService->hasValidToken() && $this->apiService->isOnline()) {
                $this->info('📡 Obteniendo desde API...');
                
                try {
                    $response = $this->apiService->get("/cups-contratados/por-cups/{$cupsUuid}");
                    
                    if ($response['success']) {
                        $this->offlineService->storeCupsContratadoOffline($response['data']);
                        $this->info('✅ Sincronizado desde API');
                    } else {
                        $this->warn('⚠️ API no devolvió contrato vigente');
                    }
                } catch (\Exception $e) {
                    $this->error('❌ Error en API: ' . $e->getMessage());
                }
            }

            // ✅ OBTENER TODOS LOS CONTRATOS DESDE API
            $this->info('📡 Obteniendo todos los contratos para este CUPS...');
            $this->obtenerTodosLosContratosCups($cupsUuid);

            // ✅ MOSTRAR ESTADO FINAL
            $this->info('🔍 Estado después de sincronización:');
            $this->mostrarEstadoActual($cupsUuid);

            return 0;

        } catch (\Exception $e) {
            $this->error('💥 Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function limpiarCacheCupsContratado(string $cupsUuid): void
    {
        try {
            // ✅ LIMPIAR SQLite
            if ($this->offlineService->isSQLiteAvailable()) {
                DB::connection('offline')->table('cups_contratados')
                    ->where('cups_uuid', $cupsUuid)
                    ->delete();
                $this->info('✅ Cache SQLite limpiado');
            }

            // ✅ LIMPIAR ARCHIVOS JSON
            $storagePath = storage_path('app/offline/cups_contratados');
            $files = glob($storagePath . '/*.json');
            
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($data && ($data['cups_uuid'] === $cupsUuid)) {
                    unlink($file);
                    $this->info("✅ Archivo JSON eliminado: " . basename($file));
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ Error limpiando cache: ' . $e->getMessage());
        }
    }

    private function mostrarEstadoActual(string $cupsUuid): void
    {
        $this->info('📊 Estado actual en SQLite:');
        
        if ($this->offlineService->isSQLiteAvailable()) {
            $contratos = DB::connection('offline')->table('cups_contratados')
                ->where('cups_uuid', $cupsUuid)
                ->get();

            if ($contratos->isEmpty()) {
                $this->warn('⚠️ No hay contratos en SQLite');
            } else {
                $headers = ['UUID', 'Estado', 'Fecha Inicio', 'Fecha Fin', 'Tarifa', 'Vigente'];
                $rows = [];
                
                foreach ($contratos as $contrato) {
                    $fechaActual = now()->format('Y-m-d');
                    $fechaInicio = substr($contrato->contrato_fecha_inicio, 0, 10);
                    $fechaFin = substr($contrato->contrato_fecha_fin, 0, 10);
                    
                    $esVigente = ($fechaInicio <= $fechaActual) && 
                                ($fechaFin >= $fechaActual) &&
                                $contrato->estado === 'ACTIVO' &&
                                $contrato->contrato_estado === 'ACTIVO';
                    
                    $rows[] = [
                        substr($contrato->uuid, 0, 8) . '...',
                        $contrato->estado,
                        $fechaInicio,
                        $fechaFin,
                        $contrato->tarifa,
                        $esVigente ? '✅ SÍ' : '❌ NO'
                    ];
                }
                
                $this->table($headers, $rows);
            }
        }

        // ✅ VERIFICAR CON EL SERVICIO
        $contratoVigente = $this->offlineService->getCupsContratadoVigenteOffline($cupsUuid);
        if ($contratoVigente) {
            $this->info("✅ Servicio encuentra contrato vigente con tarifa: {$contratoVigente['tarifa']}");
        } else {
            $this->warn('⚠️ Servicio NO encuentra contrato vigente');
        }
    }

    private function obtenerTodosLosContratosCups(string $cupsUuid): void
    {
        try {
            if (!$this->authService->hasValidToken() || !$this->apiService->isOnline()) {
                $this->warn('⚠️ Sin conexión para obtener todos los contratos');
                return;
            }

            // ✅ OBTENER TODOS LOS CONTRATOS (INCLUYENDO VENCIDOS)
            $response = $this->apiService->get('/cups-contratados', [
                'cups_uuid' => $cupsUuid,
                'include_expired' => true,
                'per_page' => 100
            ]);

            if ($response['success']) {
                $contratos = $response['data']['data'] ?? $response['data'] ?? [];
                
                $this->info("📊 Encontrados " . count($contratos) . " contratos totales en API");
                
                foreach ($contratos as $contrato) {
                    if (isset($contrato['cups']) && $contrato['cups']['uuid'] === $cupsUuid) {
                        $this->offlineService->storeCupsContratadoOffline($contrato);
                        
                        $fechaFin = $contrato['contrato']['fecha_fin'] ?? 'N/A';
                        $estado = $contrato['estado'] ?? 'N/A';
                        $this->info("  ✅ Sincronizado contrato: {$estado} hasta {$fechaFin}");
                    }
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ Error obteniendo todos los contratos: ' . $e->getMessage());
        }
    }

    private function syncTodosLosCups(): int
    {
        $this->info('🔄 Sincronización completa de CUPS contratados...');
        
        if ($this->option('clear')) {
            $this->info('🗑️ Limpiando todo el cache...');
            $this->offlineService->clearCupsContratados();
        }

        // Usar el comando existente
        $this->call('cups:sync-contratados', ['--force' => true]);
        
        return 0;
    }
}
