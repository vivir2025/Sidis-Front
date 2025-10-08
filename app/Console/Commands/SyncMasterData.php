<?php

// php artisan make:command SyncMasterData

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\{ApiService, OfflineService};

class SyncMasterData extends Command
{
    protected $signature = 'sync:master-data';
    protected $description = 'Sincronizar datos maestros desde la API';

    public function handle(ApiService $apiService, OfflineService $offlineService)
    {
        $this->info('🔄 Sincronizando datos maestros...');

        // Sincronizar medicamentos
        if ($apiService->isOnline()) {
            $response = $apiService->get('/medicamentos');
            if ($response['success']) {
                $offlineService->syncMedicamentosFromApi($response['data']);
                $this->info('✅ Medicamentos sincronizados');
            }

            // Sincronizar diagnósticos
            $response = $apiService->get('/diagnosticos');
            if ($response['success']) {
                $offlineService->syncDiagnosticosFromApi($response['data']);
                $this->info('✅ Diagnósticos sincronizados');
            }

            // Sincronizar remisiones
            $response = $apiService->get('/remisiones');
            if ($response['success']) {
                $offlineService->syncRemisionesFromApi($response['data']);
                $this->info('✅ Remisiones sincronizadas');
            }
        } else {
            $this->error('❌ API no disponible');
        }
    }
}
