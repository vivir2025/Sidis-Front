<?php
// fix_agendas_table.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "🔧 Configurando conexión SQLite...\n";
    
    // Configurar la conexión offline manualmente
    $dbPath = storage_path('app/offline/offline_data.sqlite');
    
    if (!file_exists($dbPath)) {
        echo "❌ No se encontró la base de datos en: $dbPath\n";
        exit(1);
    }
    
    // Configurar conexión temporal
    config(['database.connections.offline_temp' => [
        'driver' => 'sqlite',
        'database' => $dbPath,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]]);
    
    echo "✅ Conexión configurada: $dbPath\n";
    
    // Verificar columnas actuales
    $columns = DB::connection('offline_temp')->select("PRAGMA table_info(agendas)");
    $columnNames = array_column($columns, 'name');
    
    echo "📋 Columnas actuales (" . count($columnNames) . "): " . implode(', ', $columnNames) . "\n";
    
    // Agregar columnas faltantes
    $columnsToAdd = [
        'error_message' => 'ALTER TABLE agendas ADD COLUMN error_message TEXT NULL',
        'synced_at' => 'ALTER TABLE agendas ADD COLUMN synced_at DATETIME NULL',
        'operation_type' => 'ALTER TABLE agendas ADD COLUMN operation_type TEXT DEFAULT "create"',
        'original_data' => 'ALTER TABLE agendas ADD COLUMN original_data TEXT NULL'
    ];
    
    $added = 0;
    foreach ($columnsToAdd as $columnName => $sql) {
        if (!in_array($columnName, $columnNames)) {
            try {
                DB::connection('offline_temp')->statement($sql);
                echo "✅ Columna '{$columnName}' agregada\n";
                $added++;
            } catch (\Exception $e) {
                echo "❌ Error agregando '{$columnName}': " . $e->getMessage() . "\n";
            }
        } else {
            echo "ℹ️ Columna '{$columnName}' ya existe\n";
        }
    }
    
    // Verificar estructura final
    $finalColumns = DB::connection('offline_temp')->select("PRAGMA table_info(agendas)");
    $finalColumnNames = array_column($finalColumns, 'name');
    
    echo "\n📊 Estructura final (" . count($finalColumnNames) . " columnas):\n";
    foreach ($finalColumns as $col) {
        echo "  - {$col->name} ({$col->type})\n";
    }
    
    echo "\n🎉 Actualización completada! Se agregaron $added columnas.\n";
    
    // Verificar agendas pendientes
    $pendingCount = DB::connection('offline_temp')->table('agendas')
        ->where('sync_status', 'pending')
        ->count();
    
    echo "📋 Agendas pendientes de sincronización: $pendingCount\n";
    
} catch (\Exception $e) {
    echo "💥 Error: " . $e->getMessage() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
    echo "📁 Archivo: " . $e->getFile() . "\n";
}
