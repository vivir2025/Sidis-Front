<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\{ApiService, OfflineService};
use Illuminate\Support\Facades\Log;

class SincronizarUsuarios extends Command
{
    protected $signature = 'usuarios:sincronizar {--sede_id=}';
    protected $description = 'Sincronizar usuarios con firmas desde la API';

    protected $apiService;
    protected $offlineService;

    public function __construct(ApiService $apiService, OfflineService $offlineService)
    {
        parent::__construct();
        $this->apiService = $apiService;
        $this->offlineService = $offlineService;
    }

    public function handle()
    {
        $this->info('🔄 Iniciando sincronización de usuarios...');

        try {
            if (!$this->apiService->isOnline()) {
                $this->error('❌ No hay conexión con la API');
                return 1;
            }

            $sedeId = $this->option('sede_id');
            $filters = $sedeId ? ['sede_id' => $sedeId] : [];

            $this->info('📡 Obteniendo usuarios de la API...');
            $response = $this->apiService->get('/usuarios/all', $filters);

            if (!$response['success']) {
                $this->error('❌ Error obteniendo usuarios: ' . ($response['error'] ?? 'Error desconocido'));
                return 1;
            }

            $usuarios = $response['data'] ?? [];
            $total = count($usuarios);
            $conFirma = 0;
            $sinFirma = 0;

            $this->info("📊 Total de usuarios a sincronizar: {$total}");

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            foreach ($usuarios as $usuario) {
                try {
                    // ✅ Almacenar usuario completo con firma
                    $this->offlineService->storeUsuarioCompleto($usuario);
                    
                    if (!empty($usuario['firma'])) {
                        $conFirma++;
                    } else {
                        $sinFirma++;
                    }

                    $bar->advance();

                } catch (\Exception $e) {
                    Log::error('Error sincronizando usuario', [
                        'uuid' => $usuario['uuid'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("✅ Sincronización completada:");
            $this->table(
                ['Métrica', 'Cantidad'],
                [
                    ['Total sincronizados', $total],
                    ['Con firma', $conFirma],
                    ['Sin firma', $sinFirma],
                ]
            );

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error en sincronización: ' . $e->getMessage());
            Log::error('Error en comando sincronizar usuarios', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
