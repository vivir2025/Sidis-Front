<?php
// debug_sync_error.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

try {
    echo "🔍 Debugeando error de sincronización...\n";
    
    $dbPath = storage_path('app/offline/offline_data.sqlite');
    
    // Configurar conexión
    config(['database.connections.offline_temp' => [
        'driver' => 'sqlite',
        'database' => $dbPath,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]]);
    
    // Obtener la agenda con error
    $agenda = DB::connection('offline_temp')
        ->table('agendas')
        ->where('sync_status', 'error')
        ->first();
    
    if (!$agenda) {
        echo "❌ No se encontró agenda con error\n";
        exit;
    }
    
    echo "📋 Agenda con error encontrada:\n";
    echo "  - UUID: {$agenda->uuid}\n";
    echo "  - Fecha: {$agenda->fecha}\n";
    echo "  - Consultorio: {$agenda->consultorio}\n";
    echo "  - Error: {$agenda->error_message}\n\n";
    
    // Preparar datos para envío
    $agendaData = [
        'uuid' => $agenda->uuid,
        'sede_id' => $agenda->sede_id,
        'modalidad' => $agenda->modalidad,
        'fecha' => $agenda->fecha,
        'consultorio' => $agenda->consultorio,
        'hora_inicio' => $agenda->hora_inicio,
        'hora_fin' => $agenda->hora_fin,
        'intervalo' => $agenda->intervalo,
        'etiqueta' => $agenda->etiqueta,
        'estado' => $agenda->estado,
        'proceso_id' => $agenda->proceso_id,
        'usuario_id' => $agenda->usuario_id,
        'brigada_id' => $agenda->brigada_id,
        'cupos_disponibles' => $agenda->cupos_disponibles
    ];
    
    echo "📤 Datos a enviar:\n";
    echo json_encode($agendaData, JSON_PRETTY_PRINT) . "\n\n";
    
    // Verificar configuración de API
    $apiUrl = config('app.api_url');
    $token = session('api_token');
    
    echo "🔧 Configuración de API:\n";
    echo "  - URL: $apiUrl\n";
    echo "  - Token: " . ($token ? "✅ Presente (" . strlen($token) . " chars)" : "❌ No encontrado") . "\n\n";
    
    if (!$token) {
        echo "❌ No hay token de API. ¿Estás logueado?\n";
        exit;
    }
    
    // Test de conectividad
    echo "🔍 Probando conectividad con API...\n";
    try {
        $healthResponse = Http::timeout(10)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->get($apiUrl . '/health');
        
        echo "  - Health check: " . ($healthResponse->successful() ? "✅ OK" : "❌ FAIL") . "\n";
        echo "  - Status: " . $healthResponse->status() . "\n";
        
        if (!$healthResponse->successful()) {
            echo "  - Response: " . $healthResponse->body() . "\n";
        }
    } catch (\Exception $e) {
        echo "  - Health check error: " . $e->getMessage() . "\n";
    }
    
    echo "\n🚀 Intentando crear agenda en API...\n";
    
    try {
        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post($apiUrl . '/agendas', $agendaData);
        
        echo "📊 Respuesta de la API:\n";
        echo "  - Status: " . $response->status() . "\n";
        echo "  - Successful: " . ($response->successful() ? "✅ Sí" : "❌ No") . "\n";
        echo "  - Headers: " . json_encode($response->headers(), JSON_PRETTY_PRINT) . "\n";
        echo "  - Body: " . $response->body() . "\n\n";
        
        if ($response->successful()) {
            echo "✅ La agenda se puede sincronizar correctamente!\n";
            echo "💡 El problema podría estar en el manejo de la respuesta en el código\n";
        } else {
            echo "❌ Error en la sincronización:\n";
            
            // Analizar diferentes tipos de error
            if ($response->status() === 401) {
                echo "  - Error 401: Token inválido o expirado\n";
            } elseif ($response->status() === 422) {
                echo "  - Error 422: Datos de validación incorrectos\n";
                $errors = $response->json('errors');
                if ($errors) {
                    echo "  - Errores específicos:\n";
                    foreach ($errors as $field => $messages) {
                        echo "    * $field: " . implode(', ', $messages) . "\n";
                    }
                }
            } elseif ($response->status() === 500) {
                echo "  - Error 500: Error interno del servidor\n";
            }
        }
        
    } catch (\Exception $e) {
        echo "💥 Excepción durante la llamada a la API:\n";
        echo "  - Mensaje: " . $e->getMessage() . "\n";
        echo "  - Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    echo "\n¿Quieres cambiar esta agenda de 'error' a 'pending' para reintentar? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if ($line === 'y' || $line === 'Y') {
        DB::connection('offline_temp')
            ->table('agendas')
            ->where('id', $agenda->id)
            ->update([
                'sync_status' => 'pending',
                'error_message' => null,
                'updated_at' => now()
            ]);
        
        echo "✅ Agenda cambiada a 'pending'\n";
    }
    
} catch (\Exception $e) {
    echo "💥 Error: " . $e->getMessage() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
}
