<?php
// app/Console/Commands/CleanDuplicateAgendas.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\OfflineService;

class CleanDuplicateAgendas extends Command
{
    protected $signature = 'agendas:clean-duplicates';
    protected $description = 'Limpiar agendas duplicadas y marcar como sincronizadas';

    public function handle()
    {
        $this->info('🧹 Limpiando agendas duplicadas...');
        
        try {
            // ✅ CONFIGURAR CONEXIÓN SQLITE DINÁMICAMENTE
            $this->configureSQLiteConnection();
            
            // Verificar si hay agendas pendientes
            $pendingCount = DB::connection('offline')
                ->table('agendas')
                ->where('sync_status', 'pending')
                ->count();
            
            if ($pendingCount === 0) {
                $this->info('✅ No hay agendas pendientes para limpiar');
                return 0;
            }
            
            $this->info("📊 Encontradas {$pendingCount} agendas pendientes");
            
            // Mostrar algunas agendas pendientes para confirmación
            $sampleAgendas = DB::connection('offline')
                ->table('agendas')
                ->where('sync_status', 'pending')
                ->select('uuid', 'fecha', 'consultorio', 'etiqueta')
                ->limit(5)
                ->get();
            
            $this->info('📋 Muestra de agendas pendientes:');
            foreach ($sampleAgendas as $agenda) {
                $this->line("  - {$agenda->fecha} | {$agenda->consultorio} | {$agenda->etiqueta}");
            }
            
            // Pedir confirmación
            if (!$this->confirm('¿Deseas marcar todas estas agendas como sincronizadas?')) {
                $this->info('❌ Operación cancelada');
                return 0;
            }
            
            // Marcar todas las agendas pendientes como sincronizadas
            $updated = DB::connection('offline')
                ->table('agendas')
                ->where('sync_status', 'pending')
                ->update([
                    'sync_status' => 'synced',
                    'updated_at' => now()
                ]);
            
            $this->info("✅ {$updated} agendas marcadas como sincronizadas");
            
            Log::info('Agendas duplicadas limpiadas via comando', [
                'count' => $updated,
                'executed_by' => 'artisan_command'
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Error en comando clean-duplicates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
    
    /**
     * ✅ CONFIGURAR CONEXIÓN SQLITE DINÁMICAMENTE
     */
    private function configureSQLiteConnection(): void
    {
        $storagePath = storage_path('app/offline');
        $dbPath = $storagePath . '/offline_data.sqlite';
        
        // Crear directorio si no existe
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
        
        // Crear archivo SQLite si no existe
        if (!file_exists($dbPath)) {
            touch($dbPath);
            $this->info("📁 Archivo SQLite creado: {$dbPath}");
        }
        
        // Configurar conexión dinámicamente
        config(['database.connections.offline' => [
            'driver' => 'sqlite',
            'database' => $dbPath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        
        // Probar la conexión
        DB::connection('offline')->getPdo();
        
        $this->info('✅ Conexión SQLite configurada correctamente');
    }
}
