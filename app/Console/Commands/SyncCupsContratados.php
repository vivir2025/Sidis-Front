<?php
// app/Console/Commands/SyncCupsContratados.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncCupsContratadosJob;

class SyncCupsContratados extends Command
{
    protected $signature = 'cups:sync-contratados {--force : Forzar sincronización completa}';
    protected $description = 'Sincronizar CUPS contratados desde la API';

    public function handle()
    {
        $this->info('🔄 Iniciando sincronización de CUPS contratados...');
        
        SyncCupsContratadosJob::dispatch();
        
        $this->info('✅ Trabajo de sincronización enviado a la cola');
        
        if ($this->option('force')) {
            $this->info('🔧 Sincronización forzada activada');
        }
    }
}
