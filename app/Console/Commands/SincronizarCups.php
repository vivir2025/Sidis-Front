<?php
// app/Console/Commands/SincronizarCups.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CupsService;

class SincronizarCups extends Command
{
    protected $signature = 'cups:sincronizar {--limit=1000 : Límite de registros a sincronizar}';
    protected $description = 'Sincronizar CUPS desde la API al almacenamiento offline';

    protected $cupsService;

    public function __construct(CupsService $cupsService)
    {
        parent::__construct();
        $this->cupsService = $cupsService;
    }

    public function handle()
    {
        $this->info('🔄 Iniciando sincronización de CUPS...');
        
        $result = $this->cupsService->sincronizarCups();
        
        if ($result['success']) {
            $this->info("✅ {$result['message']}");
            return Command::SUCCESS;
        } else {
            $this->error("❌ Error: {$result['error']}");
            return Command::FAILURE;
        }
    }
}
