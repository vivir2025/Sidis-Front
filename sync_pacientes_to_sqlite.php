<?php
/**
 * Script temporal para sincronizar pacientes de la API a SQLite
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "=== SINCRONIZACIÓN DE PACIENTES ===\n\n";

try {
    $apiService = app(\App\Services\ApiService::class);
    $db = DB::connection('offline');
    
    echo "🔍 Verificando conexión a la API...\n";
    
    if (!$apiService->isOnline()) {
        echo "❌ API no disponible. No se puede sincronizar.\n";
        exit(1);
    }
    
    echo "✅ API disponible\n\n";
    
    // Obtener pacientes de la API
    echo "📥 Obteniendo pacientes desde la API...\n";
    $response = $apiService->get('/pacientes');
    
    if (!$response['success']) {
        echo "❌ Error al obtener pacientes: " . ($response['error'] ?? 'Error desconocido') . "\n";
        exit(1);
    }
    
    $pacientes = $response['data'] ?? [];
    $total = count($pacientes);
    
    echo "📊 Total de pacientes en API: {$total}\n\n";
    
    if ($total === 0) {
        echo "⚠️ No hay pacientes en la API\n";
        exit(0);
    }
    
    echo "💾 Guardando pacientes en SQLite...\n";
    
    $guardados = 0;
    $errores = 0;
    
    foreach ($pacientes as $index => $paciente) {
        try {
            // Verificar si ya existe
            $existe = $db->table('pacientes')
                ->where('id', $paciente['id'])
                ->exists();
            
            if ($existe) {
                // Actualizar
                $db->table('pacientes')
                    ->where('id', $paciente['id'])
                    ->update([
                        'tipo_documento_id' => $paciente['tipo_documento_id'] ?? null,
                        'documento' => $paciente['documento'] ?? null,
                        'primer_nombre' => $paciente['primer_nombre'] ?? null,
                        'segundo_nombre' => $paciente['segundo_nombre'] ?? null,
                        'primer_apellido' => $paciente['primer_apellido'] ?? null,
                        'segundo_apellido' => $paciente['segundo_apellido'] ?? null,
                        'fecha_nacimiento' => $paciente['fecha_nacimiento'] ?? null,
                        'genero' => $paciente['genero'] ?? null,
                        'telefono' => $paciente['telefono'] ?? null,
                        'correo' => $paciente['correo'] ?? null,
                        'direccion' => $paciente['direccion'] ?? null,
                        'departamento_id' => $paciente['departamento_id'] ?? null,
                        'municipio_id' => $paciente['municipio_id'] ?? null,
                        'zona_residencial_id' => $paciente['zona_residencial_id'] ?? null,
                        'estado_id' => $paciente['estado_id'] ?? 1,
                        'sede_id' => $paciente['sede_id'] ?? null,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'sync_status' => 'synced',
                    ]);
            } else {
                // Insertar
                $db->table('pacientes')->insert([
                    'id' => $paciente['id'],
                    'tipo_documento_id' => $paciente['tipo_documento_id'] ?? null,
                    'documento' => $paciente['documento'] ?? null,
                    'primer_nombre' => $paciente['primer_nombre'] ?? null,
                    'segundo_nombre' => $paciente['segundo_nombre'] ?? null,
                    'primer_apellido' => $paciente['primer_apellido'] ?? null,
                    'segundo_apellido' => $paciente['segundo_apellido'] ?? null,
                    'fecha_nacimiento' => $paciente['fecha_nacimiento'] ?? null,
                    'genero' => $paciente['genero'] ?? null,
                    'telefono' => $paciente['telefono'] ?? null,
                    'correo' => $paciente['correo'] ?? null,
                    'direccion' => $paciente['direccion'] ?? null,
                    'departamento_id' => $paciente['departamento_id'] ?? null,
                    'municipio_id' => $paciente['municipio_id'] ?? null,
                    'zona_residencial_id' => $paciente['zona_residencial_id'] ?? null,
                    'estado_id' => $paciente['estado_id'] ?? 1,
                    'sede_id' => $paciente['sede_id'] ?? null,
                    'created_at' => $paciente['created_at'] ?? date('Y-m-d H:i:s'),
                    'updated_at' => $paciente['updated_at'] ?? date('Y-m-d H:i:s'),
                    'sync_status' => 'synced',
                ]);
            }
            
            $guardados++;
            
            if (($index + 1) % 100 === 0) {
                echo "  ✓ Procesados: " . ($index + 1) . " / {$total}\n";
            }
            
        } catch (\Exception $e) {
            $errores++;
            echo "  ❌ Error con paciente ID {$paciente['id']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ Sincronización completada\n";
    echo "📊 Guardados: {$guardados}\n";
    echo "❌ Errores: {$errores}\n";
    
    // Verificar total
    $totalGuardados = $db->table('pacientes')->count();
    echo "\n📈 Total de pacientes en SQLite: {$totalGuardados}\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . "\n";
    echo "🔢 Línea: " . $e->getLine() . "\n";
    echo "\n" . $e->getTraceAsString() . "\n";
}
