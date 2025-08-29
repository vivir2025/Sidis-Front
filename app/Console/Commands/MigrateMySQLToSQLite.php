<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateMySQLToSQLite extends Command
{
    protected $signature = 'migrate:mysql-to-sqlite {--fresh : Limpiar base de datos antes de migrar}';
    protected $description = 'Migra datos de MySQL a SQLite';

    public function handle()
    {
        $this->info('🚀 Iniciando migración MySQL → SQLite...');

        if ($this->option('fresh')) {
            $this->info('🗑️ Limpiando base de datos SQLite...');
            $this->call('migrate:fresh');
        }

        // Configurar conexiones
        config(['database.connections.mysql_remote' => [
            'driver' => 'mysql',
            'host' => 'nacerparavivir.org',
            'port' => '3306',
            'database' => 'nacerpar_sidis',
            'username' => 'nacerpar',
            'password' => 'FdtPIFxkYoIJpWEv',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]]);

        try {
            // Obtener tablas de MySQL
            $tables = DB::connection('mysql_remote')->select('SHOW TABLES');
            $tableNames = [];
            
            foreach($tables as $table) {
                $tableName = array_values((array)$table)[0];
                if (!in_array($tableName, ['migrations', 'password_resets', 'failed_jobs'])) {
                    $tableNames[] = $tableName;
                }
            }

            $this->info("📋 Encontradas " . count($tableNames) . " tablas");

            foreach($tableNames as $tableName) {
                $this->info("🔄 Procesando: {$tableName}");
                
                try {
                    // Obtener estructura de la tabla
                    $columns = DB::connection('mysql_remote')->select("DESCRIBE {$tableName}");
                    
                    // Crear tabla en SQLite solo si no existe
                    if (!Schema::hasTable($tableName)) {
                        $this->createSQLiteTable($tableName, $columns);
                    }
                    
                    // Limpiar tabla existente
                    DB::table($tableName)->truncate();
                    
                    // Migrar datos
                    $data = DB::connection('mysql_remote')->table($tableName)->get();
                    
                    if ($data->count() > 0) {
                        // Insertar en lotes de 100
                        $chunks = $data->chunk(100);
                        foreach($chunks as $chunk) {
                            $insertData = [];
                            foreach($chunk as $row) {
                                $insertData[] = (array) $row;
                            }
                            DB::table($tableName)->insert($insertData);
                        }
                    }
                    
                    $this->info("✅ {$tableName}: {$data->count()} registros migrados");
                    
                } catch(\Exception $e) {
                    $this->error("❌ Error en {$tableName}: " . $e->getMessage());
                }
            }

            $this->info('🎉 ¡Migración completada!');
            
        } catch(\Exception $e) {
            $this->error('💥 Error general: ' . $e->getMessage());
        }
    }

    private function createSQLiteTable($tableName, $columns)
    {
        // Crear nueva tabla
        Schema::create($tableName, function($table) use ($columns) {
            foreach($columns as $column) {
                $name = $column->Field;
                $type = $this->convertMySQLTypeToSQLite($column->Type);
                $nullable = $column->Null === 'YES';
                $default = $column->Default;
                
                if ($column->Key === 'PRI' && $column->Extra === 'auto_increment') {
                    $table->id($name);
                } else {
                    switch($type) {
                        case 'integer':
                            $col = $table->integer($name);
                            break;
                        case 'text':
                            $col = $table->text($name);
                            break;
                        case 'datetime':
                            $col = $table->datetime($name);
                            break;
                        case 'date':
                            $col = $table->date($name);
                            break;
                        case 'boolean':
                            $col = $table->boolean($name);
                            break;
                        default:
                            $col = $table->string($name, 255);
                    }
                    
                    if ($nullable) $col->nullable();
                    if ($default !== null && $default !== '') $col->default($default);
                }
            }
        });
    }

    private function convertMySQLTypeToSQLite($mysqlType)
    {
        if (strpos($mysqlType, 'int') !== false) return 'integer';
        if (strpos($mysqlType, 'text') !== false) return 'text';
        if (strpos($mysqlType, 'datetime') !== false) return 'datetime';
        if (strpos($mysqlType, 'date') !== false) return 'date';
        if (strpos($mysqlType, 'tinyint(1)') !== false) return 'boolean';
        return 'string';
    }
}
