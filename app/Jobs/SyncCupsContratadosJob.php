<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\ApiService;
use App\Services\AuthService;
use App\Services\OfflineService;

class SyncCupsContratadosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 2;

    public function handle(
        ApiService $apiService,
        AuthService $authService,
        OfflineService $offlineService
    ): void
    {
        try {
            Log::info('🚀 [JOB CUPS CONTRATADOS] Iniciando sincronización');

            if (!$apiService->isOnline()) {
                Log::info('📱 [JOB CUPS CONTRATADOS] Modo offline');
                return;
            }

            if (!$authService->hasValidToken()) {
                Log::info('🔐 [JOB CUPS CONTRATADOS] Sin token válido');
                return;
            }

            // Verificar si ya se sincronizó hoy
            $lastSync = cache()->get('cups_contratados_last_sync');
            $today = now()->format('Y-m-d');
            
            if ($lastSync === $today) {
                Log::info('✅ [JOB CUPS CONTRATADOS] Ya sincronizados hoy');
                return;
            }

            $response = $apiService->get('/cups-contratados/disponibles');
            
            if (!$response['success']) {
                Log::warning('⚠️ [JOB CUPS CONTRATADOS] Error en API', [
                    'error' => $response['error'] ?? 'Error desconocido'
                ]);
                return;
            }

            $cupsContratados = $response['data'] ?? [];
            
            if (empty($cupsContratados)) {
                Log::info('ℹ️ [JOB CUPS CONTRATADOS] No hay datos disponibles');
                return;
            }

            Log::info('📥 [JOB CUPS CONTRATADOS] Datos recibidos', [
                'total' => count($cupsContratados)
            ]);

            $offlineService->clearCupsContratados();

            $syncedCount = 0;
            
            foreach ($cupsContratados as $cupsContratado) {
                try {
                    $offlineService->storeCupsContratadoOffline($cupsContratado);
                    $syncedCount++;
                } catch (\Exception $e) {
                    Log::warning('⚠️ [JOB CUPS CONTRATADOS] Error guardando registro', [
                        'uuid' => $cupsContratado['uuid'] ?? 'N/A',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            cache()->put('cups_contratados_last_sync', $today, now()->addDay());

            Log::info('✅ [JOB CUPS CONTRATADOS] Sincronizados correctamente', [
                'total' => count($cupsContratados),
                'sincronizados' => $syncedCount
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [JOB CUPS CONTRATADOS] Excepción', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('❌ [JOB CUPS CONTRATADOS] Job falló', [
            'error' => $exception->getMessage()
        ]);
    }
}
