<?php
// app/Console/Commands/SyncMasterDataOffline.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\{ApiService, OfflineService};

class SyncMasterDataOffline extends Command
{
    protected $signature = 'offline:sync-master-data {--force : Forzar sincronización completa}';
    protected $description = 'Sincronizar datos maestros para uso offline';

    public function handle()
    {
        $this->info('🔄 Iniciando sincronización de datos maestros...');
        
        $apiService = app(ApiService::class);
        $offlineService = app(OfflineService::class);
        
        if (!$apiService->isOnline()) {
            $this->error('❌ No hay conexión a internet');
            return 1;
        
        }

        try {
            if ($this->option('force')) {
                $this->info('🧹 Limpiando datos offline existentes...');
                $offlineService->clearAllOfflineData();
            }

            $this->info('📡 Obteniendo datos desde la API...');
            $response = $apiService->get('/master-data/all');
            
            if ($response['success'] && isset($response['data'])) {
                $this->info('💾 Sincronizando datos offline...');
                $success = $offlineService->syncMasterDataFromApi($response['data']);
                
                if ($success) {
                    $stats = $offlineService->getOfflineStats();
                    
                    $this->info('✅ Sincronización completada exitosamente');
                    $this->table(
                        ['Tabla', 'Registros'],
                        collect($stats)->except('sync_info')->map(function($count, $table) {
                            return [$table, $count];
                        })->toArray()
                    );
                    
                    return 0;
                } else {
                    $this->error('❌ Error sincronizando datos offline');
                    return 1;
                }
            } else {
                $this->error('❌ Error obteniendo datos desde la API');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
