<?php
// app/Console/Commands/TestPacientesEndpoint.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ApiService;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class TestPacientesEndpoint extends Command
{
    protected $signature = 'api:test-pacientes {--user-id=1}';
    protected $description = 'Probar específicamente el endpoint de pacientes';

    public function handle()
    {
        $userId = $this->option('user-id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("Usuario con ID {$userId} no encontrado");
            return;
        }
        
        $this->info('🏥 PROBANDO ENDPOINT DE PACIENTES');
        $this->info('================================');
        $this->info("Usuario: {$user->name} (ID: {$user->id})");
        $this->info("Token: " . ($user->api_token ? 'SÍ' : 'NO'));
        $this->info('');
        
        $baseUrl = config('api.base_url');
        $endpoint = $baseUrl . '/pacientes';
        
        // Test 1: Sin autenticación
        $this->info('🔓 Test 1: Sin autenticación');
        try {
            $response = Http::timeout(10)->get($endpoint);
            $this->info("Status: {$response->status()}");
            $this->info("Response: " . substr($response->body(), 0, 200));
            
            if ($response->status() === 401) {
                $this->info("✅ Correcto: 401 Unauthorized sin token");
            } else {
                $this->error("❌ Inesperado: Debería ser 401");
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
        
        // Test 2: Con autenticación
        $this->info('');
        $this->info('🔐 Test 2: Con autenticación');
        
        if (!$user->api_token) {
            $this->error("❌ Usuario no tiene token API");
            $this->info("Intentando generar token...");
            
            // Intentar login para obtener token
            try {
                $loginResponse = Http::timeout(10)->post($baseUrl . '/auth/login', [
                    'email' => $user->email,
                    'password' => 'password' // Cambiar por la contraseña real
                ]);
                
                if ($loginResponse->successful()) {
                    $loginData = $loginResponse->json();
                    if (isset($loginData['data']['token'])) {
                        $token = $loginData['data']['token'];
                        $user->api_token = $token;
                        $user->save();
                        $this->info("✅ Token obtenido y guardado");
                    }
                } else {
                    $this->error("❌ Login falló: " . $loginResponse->body());
                    return;
                }
            } catch (\Exception $e) {
                $this->error("❌ Error en login: " . $e->getMessage());
                return;
            }
        }
        
        // Probar con token
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $user->api_token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->timeout(30)->get($endpoint);
            
            $this->info("Status: {$response->status()}");
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info("✅ Respuesta exitosa");
                
                if (isset($data['data']) && is_array($data['data'])) {
                    $count = count($data['data']);
                    $this->info("📊 Pacientes encontrados: {$count}");
                    
                    if ($count > 0) {
                        $first = $data['data'][0];
                        $this->info("Primer paciente: " . json_encode($first, JSON_PRETTY_PRINT));
                    }
                } else {
                    $this->info("Estructura de respuesta:");
                    $this->info(json_encode($data, JSON_PRETTY_PRINT));
                }
            } else {
                $this->error("❌ Error: Status {$response->status()}");
                $this->error("Response: " . $response->body());
                
                // Verificar si es problema de token
                if ($response->status() === 401) {
                    $this->error("🔑 Token inválido o expirado");
                    $this->info("Token actual: " . substr($user->api_token, 0, 20) . "...");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
        
        // Test 3: Verificar headers de respuesta
        $this->info('');
        $this->info('📋 Test 3: Headers de respuesta');
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $user->api_token,
                'Accept' => 'application/json'
            ])->timeout(10)->get($endpoint);
            
            foreach ($response->headers() as $key => $values) {
                $this->line("  {$key}: " . implode(', ', $values));
            }
        } catch (\Exception $e) {
            $this->error("No se pudieron obtener headers: " . $e->getMessage());
        }
    }
}
