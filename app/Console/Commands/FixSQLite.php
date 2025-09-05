<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OfflineService;
use Illuminate\Support\Facades\Log;

class FixSQLite extends Command
{
    protected $signature = 'sqlite:fix';
    protected $description = 'Actualizar estructura de SQLite offline agregando columnas faltantes';

    public function handle()
    {
        $this->info('🔧 Iniciando corrección de estructura SQLite...');
        
        try {
            $offlineService = app(OfflineService::class);
            
            // Diagnóstico inicial
            $this->info('📊 Verificando estado actual...');
            $diagnostic = $offlineService->diagnosticSync();
            
            if (!$diagnostic['sqlite_available']) {
                $this->error('❌ SQLite no está disponible');
                return 1;
            }
            
            $this->info("📈 Estado actual: {$diagnostic['total_agendas']} agendas totales");
            
            // Recrear tabla con nueva estructura
            $this->info('🔄 Recreando tabla agendas...');
            $result = $offlineService->recreateAgendasTable();
            
            if ($result) {
                $this->info('✅ Estructura actualizada exitosamente');
                
                // Verificar después
                $newDiagnostic = $offlineService->diagnosticSync();
                $this->info("📈 Estado final: {$newDiagnostic['total_agendas']} agendas restauradas");
                
                return 0;
            } else {
                $this->error('❌ Error actualizando estructura');
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error('💥 Error crítico: ' . $e->getMessage());
            Log::error('Error en comando FixSQLite', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
