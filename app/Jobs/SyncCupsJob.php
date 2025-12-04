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

class SyncCupsJob implements ShouldQueue
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
            Log::info('🚀 [JOB CUPS] Iniciando sincronización');

            if (!$apiService->isOnline()) {
                Log::info('📱 [JOB CUPS] Modo offline');
                return;
            }

            if (!$authService->hasValidToken()) {
                Log::info('🔐 [JOB CUPS] Sin token válido');
                return;
            }

            Log::info('📡 [JOB CUPS] Llamando a API: GET /cups?all=true');
            
            $response = $apiService->get('/cups', ['all' => 'true']);
            
            if (!$response['success']) {
                Log::warning('⚠️ [JOB CUPS] Error en API', [
                    'error' => $response['error'] ?? 'Error desconocido'
                ]);
                return;
            }

            $cupsList = $response['data'] ?? [];
            
            if (empty($cupsList) || !is_array($cupsList)) {
                Log::info('ℹ️ [JOB CUPS] No hay CUPS disponibles');
                return;
            }

            Log::info('✅ [JOB CUPS] Datos recibidos', [
                'total' => count($cupsList)
            ]);

            $syncResult = $offlineService->syncCupsFromApi($cupsList);

            if ($syncResult) {
                Log::info('✅ [JOB CUPS] Sincronizados correctamente', [
                    'total' => count($cupsList)
                ]);
                
                // Guardar fecha de última sincronización
                cache()->put('cups_last_sync', now()->format('Y-m-d H:i:s'), now()->addDay());
            } else {
                Log::error('❌ [JOB CUPS] Error en sincronización');
            }

        } catch (\Exception $e) {
            Log::error('❌ [JOB CUPS] Excepción', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e; // Para que se reintente
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('❌ [JOB CUPS] Job falló', [
            'error' => $exception->getMessage()
        ]);
    }
}
