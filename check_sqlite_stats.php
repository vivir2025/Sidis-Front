<?php
/**
 * Script para verificar estadísticas en SQLite
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICACIÓN DE ESTADÍSTICAS SQLITE ===\n\n";

try {
    $db = DB::connection('offline');
    $sqlitePath = storage_path('app/offline/offline_data.sqlite');
    
    echo "📁 Ruta SQLite: {$sqlitePath}\n";
    echo "✅ Archivo existe: " . (file_exists($sqlitePath) ? 'SÍ' : 'NO') . "\n";
    echo "📊 Tamaño archivo: " . (file_exists($sqlitePath) ? number_format(filesize($sqlitePath) / 1024 / 1024, 2) . ' MB' : 'N/A') . "\n\n";
    
    // Verificar tablas
    echo "=== TABLAS EXISTENTES ===\n";
    $tables = $db->select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    foreach ($tables as $table) {
        echo "  - {$table->name}\n";
    }
    echo "\n";
    
    // Contar registros en cada tabla
    echo "=== CONTEO DE REGISTROS ===\n";
    
    // Pacientes
    try {
        $totalPacientes = $db->table('pacientes')->count();
        echo "👥 PACIENTES: {$totalPacientes}\n";
        
        if ($totalPacientes > 0) {
            $activosPacientes = $db->table('pacientes')->where('estado_id', 1)->count();
            echo "   └─ Activos: {$activosPacientes}\n";
            
            // Verificar columnas
            $samplePaciente = $db->table('pacientes')->first();
            if ($samplePaciente) {
                echo "   └─ Columnas: " . implode(', ', array_keys((array)$samplePaciente)) . "\n";
            }
        }
    } catch (\Exception $e) {
        echo "❌ Error en pacientes: " . $e->getMessage() . "\n";
    }
    
    // Agendas
    try {
        $totalAgendas = $db->table('agendas')->count();
        echo "📅 AGENDAS: {$totalAgendas}\n";
    } catch (\Exception $e) {
        echo "❌ Error en agendas: " . $e->getMessage() . "\n";
    }
    
    // Citas
    try {
        $totalCitas = $db->table('citas')->count();
        echo "📋 CITAS: {$totalCitas}\n";
    } catch (\Exception $e) {
        echo "❌ Error en citas: " . $e->getMessage() . "\n";
    }
    
    // Usuarios
    try {
        $totalUsuarios = $db->table('usuarios')->count();
        echo "👤 USUARIOS: {$totalUsuarios}\n";
    } catch (\Exception $e) {
        echo "❌ Error en usuarios: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== VERIFICACIÓN DE FECHAS ===\n";
    try {
        // Verificar formato de created_at
        $pacienteConFecha = $db->table('pacientes')
            ->whereNotNull('created_at')
            ->first();
        
        if ($pacienteConFecha) {
            echo "📆 Ejemplo created_at: {$pacienteConFecha->created_at}\n";
            
            // Probar query de mes actual
            $nuevosMes = $db->table('pacientes')
                ->whereRaw("strftime('%m', created_at) = ?", [date('m')])
                ->whereRaw("strftime('%Y', created_at) = ?", [date('Y')])
                ->count();
            echo "📊 Pacientes nuevos este mes (método SQLite): {$nuevosMes}\n";
        }
    } catch (\Exception $e) {
        echo "❌ Error verificando fechas: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ Verificación completada\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR GENERAL: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . "\n";
    echo "🔢 Línea: " . $e->getLine() . "\n";
}
