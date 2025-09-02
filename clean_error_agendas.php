<?php
// clean_error_agendas.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "🔍 Limpiando agendas con error...\n";
    
    $dbPath = storage_path('app/offline/offline_data.sqlite');
    echo "📁 Base de datos: $dbPath\n";
    
    // Configurar conexión
    config(['database.connections.offline_temp' => [
        'driver' => 'sqlite',
        'database' => $dbPath,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]]);
    
    // Buscar agendas con error
    $errorAgendas = DB::connection('offline_temp')
        ->table('agendas')
        ->where('sync_status', 'error')
        ->select('id', 'uuid', 'fecha', 'consultorio', 'hora_inicio', 'etiqueta', 'sync_status', 'error_message', 'created_at')
        ->get();
    
    echo "📋 Agendas con estado 'error': " . $errorAgendas->count() . "\n\n";
    
    if ($errorAgendas->count() > 0) {
        echo "📝 Detalles de las agendas con error:\n";
        foreach ($errorAgendas as $agenda) {
            echo "  - ID: {$agenda->id}\n";
            echo "    UUID: {$agenda->uuid}\n";
            echo "    Fecha: {$agenda->fecha}\n";
            echo "    Consultorio: {$agenda->consultorio}\n";
            echo "    Hora: {$agenda->hora_inicio}\n";
            echo "    Estado: {$agenda->sync_status}\n";
            if ($agenda->error_message) {
                echo "    Error: {$agenda->error_message}\n";
            }
            echo "    Creado: {$agenda->created_at}\n";
            echo "  ─────────────────────────────────────\n";
        }
        
        echo "\n¿Qué deseas hacer?\n";
        echo "1. Eliminar todas las agendas con error\n";
        echo "2. Cambiar estado de 'error' a 'pending' para reintentar\n";
        echo "3. Ver más detalles y decidir individualmente\n";
        echo "4. Cancelar\n";
        echo "Selecciona una opción (1-4): ";
        
        $handle = fopen("php://stdin", "r");
        $option = trim(fgets($handle));
        fclose($handle);
        
        switch ($option) {
            case '1':
                $deleted = DB::connection('offline_temp')
                    ->table('agendas')
                    ->where('sync_status', 'error')
                    ->delete();
                
                echo "✅ Se eliminaron $deleted agendas con error\n";
                break;
                
            case '2':
                $updated = DB::connection('offline_temp')
                    ->table('agendas')
                    ->where('sync_status', 'error')
                    ->update([
                        'sync_status' => 'pending',
                        'error_message' => null,
                        'updated_at' => now()
                    ]);
                
                echo "✅ Se actualizaron $updated agendas de 'error' a 'pending'\n";
                echo "💡 Ahora puedes ejecutar la sincronización nuevamente\n";
                break;
                
            case '3':
                foreach ($errorAgendas as $agenda) {
                    echo "\n📋 Agenda ID: {$agenda->id} - {$agenda->fecha} {$agenda->hora_inicio}\n";
                    echo "¿Qué hacer con esta agenda? (d=eliminar, p=pending, s=saltar): ";
                    
                    $handle = fopen("php://stdin", "r");
                    $action = trim(fgets($handle));
                    fclose($handle);
                    
                    if ($action === 'd') {
                        DB::connection('offline_temp')
                            ->table('agendas')
                            ->where('id', $agenda->id)
                            ->delete();
                        echo "  ✅ Eliminada\n";
                    } elseif ($action === 'p') {
                        DB::connection('offline_temp')
                            ->table('agendas')
                            ->where('id', $agenda->id)
                            ->update([
                                'sync_status' => 'pending',
                                'error_message' => null,
                                'updated_at' => now()
                            ]);
                        echo "  ✅ Cambiada a pending\n";
                    } else {
                        echo "  ⏭️ Saltada\n";
                    }
                }
                break;
                
            case '4':
            default:
                echo "❌ Operación cancelada\n";
                break;
        }
        
        // Mostrar estado final
        echo "\n📊 Estado final:\n";
        $finalStatus = DB::connection('offline_temp')
            ->table('agendas')
            ->select('sync_status', DB::raw('COUNT(*) as count'))
            ->groupBy('sync_status')
            ->get();
        
        foreach ($finalStatus as $status) {
            echo "  - {$status->sync_status}: {$status->count}\n";
        }
    }
    
} catch (\Exception $e) {
    echo "💥 Error: " . $e->getMessage() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
}
