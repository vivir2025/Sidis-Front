<?php
// app/Console/Commands/ClearOfflineTables.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClearOfflineTables extends Command
{
    protected $signature = 'offline:clear-tables {table?} {--all : Limpiar todas las tablas}';
    protected $description = 'Limpiar tablas específicas de SQLite offline';

    public function handle()
    {
        try {
            $dbPath = storage_path('app/offline/offline_data.sqlite');
            
            if (!file_exists($dbPath)) {
                $this->error('❌ Base de datos SQLite no encontrada');
                return 1;
            }
            
            $pdo = new \PDO("sqlite:{$dbPath}");
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            $table = $this->argument('table');
            $all = $this->option('all');
            
            if ($all) {
                return $this->clearAllTables($pdo);
            }
            
            if (!$table) {
                return $this->showAvailableTables($pdo);
            }
            
            return $this->clearSpecificTable($pdo, $table);
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
    
    private function showAvailableTables(\PDO $pdo): int
    {
        $this->info('📋 Tablas disponibles:');
        
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
        $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
            $count = $countStmt->fetchColumn();
            $this->line("  - {$table} ({$count} registros)");
        }
        
        $this->info("\n💡 Uso:");
        $this->line("  php artisan offline:clear-tables agendas");
        $this->line("  php artisan offline:clear-tables citas");
        $this->line("  php artisan offline:clear-tables --all");
        
        return 0;
    }
    
    private function clearSpecificTable(\PDO $pdo, string $table): int
    {
        // Verificar que la tabla existe
        $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = ?");
        $stmt->execute([$table]);
        
        if (!$stmt->fetch()) {
            $this->error("❌ La tabla '{$table}' no existe");
            return 1;
        }
        
        // Contar registros antes
        $countStmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
        $beforeCount = $countStmt->fetchColumn();
        
        if ($beforeCount == 0) {
            $this->info("✅ La tabla '{$table}' ya está vacía");
            return 0;
        }
        
        $this->info("📊 La tabla '{$table}' tiene {$beforeCount} registros");
        
        if (!$this->confirm("¿Deseas eliminar todos los registros de la tabla '{$table}'?")) {
            $this->info('❌ Operación cancelada');
            return 0;
        }
        
        // Limpiar tabla
        $pdo->exec("DELETE FROM {$table}");
        $pdo->exec("DELETE FROM sqlite_sequence WHERE name='{$table}'"); // Reset autoincrement
        
        $this->info("✅ Tabla '{$table}' limpiada exitosamente");
        
        Log::info("Tabla SQLite limpiada", [
            'table' => $table,
            'records_deleted' => $beforeCount
        ]);
        
        return 0;
    }
    
    private function clearAllTables(\PDO $pdo): int
    {
        $this->warn('⚠️  ADVERTENCIA: Esto eliminará TODOS los datos offline');
        
        if (!$this->confirm('¿Estás seguro de que quieres limpiar TODAS las tablas?')) {
            $this->info('❌ Operación cancelada');
            return 0;
        }
        
        // Obtener todas las tablas
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
        $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        $totalDeleted = 0;
        
        foreach ($tables as $table) {
            try {
                $countStmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
                $count = $countStmt->fetchColumn();
                
                if ($count > 0) {
                    $pdo->exec("DELETE FROM {$table}");
                    $totalDeleted += $count;
                    $this->line("✅ {$table}: {$count} registros eliminados");
                }
            } catch (\Exception $e) {
                $this->error("❌ Error limpiando tabla {$table}: " . $e->getMessage());
            }
        }
        
        // Reset autoincrement
        $pdo->exec("DELETE FROM sqlite_sequence");
        
        $this->info("🏁 Total de registros eliminados: {$totalDeleted}");
        
        Log::info("Todas las tablas SQLite limpiadas", [
            'total_deleted' => $totalDeleted,
            'tables_count' => count($tables)
        ]);
        
        return 0;
    }
}
