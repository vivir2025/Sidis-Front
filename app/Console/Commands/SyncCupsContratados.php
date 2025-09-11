<?php
// app/Console/Commands/SyncCupsContratados.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OfflineService;

class SyncCupsContratados extends Command
{
    protected $signature = 'cups:sync-contratados';
    protected $description = 'Sincronizar CUPS contratados desde la API';

    public function handle(OfflineService $offlineService)
    {
        $this->info('🔄 Iniciando sincronización de CUPS contratados...');
        
        $success = $offlineService->syncCupsContratadosFromApi();
        
        if ($success) {
            $this->info('✅ CUPS contratados sincronizados exitosamente');
        } else {
            $this->error('❌ Error sincronizando CUPS contratados');
        }
    }
}
