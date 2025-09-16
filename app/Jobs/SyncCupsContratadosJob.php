<?php
// app/Jobs/SyncCupsContratadosJob.php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\{ApiService, OfflineService, AuthService};
use Illuminate\Support\Facades\Log;

class SyncCupsContratadosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $force;

    public function __construct(bool $force = false)
    {
        $this->force = $force;
    }

    public function handle()
    {
        try {
            Log::info('🔄 Iniciando sincronización automática de CUPS contratados', [
                'force' => $this->force
            ]);
            
            $apiService = app(ApiService::class);
            $offlineService = app(OfflineService::class);
            $authService = app(AuthService::class);
            
            if (!$authService->hasValidToken() || !$apiService->isOnline()) {
                Log::warning('⚠️ No se puede sincronizar: sin token o conexión');
                return;
            }
            
            // ✅ LIMPIAR CACHE SI ES FORZADO
            if ($this->force) {
                Log::info('🗑️ Limpiando cache completo (modo forzado)');
                $offlineService->clearCupsContratados();
            }
            
            // ✅ OBTENER CUPS CONTRATADOS VIGENTES DESDE API
            $response = $apiService->get('/cups-contratados/disponibles');
            
            if (!$response['success']) {
                Log::error('❌ Error obteniendo CUPS contratados vigentes', [
                    'error' => $response['error'] ?? 'Error desconocido'
                ]);
                return;
            }
            
            // ✅ ALMACENAR SOLO LOS CONTRATOS VIGENTES
            $syncCount = 0;
            $errorCount = 0;
            
            foreach ($response['data'] as $cupsContratado) {
                try {
                    $offlineService->storeCupsContratadoOffline($cupsContratado);
                    $syncCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error('❌ Error almacenando CUPS contratado', [
                        'cups_codigo' => $cupsContratado['cups']['codigo'] ?? 'N/A',
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            Log::info('✅ Sincronización automática completada', [
                'contratos_sincronizados' => $syncCount,
                'errores' => $errorCount,
                'force' => $this->force
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error en sincronización automática', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
