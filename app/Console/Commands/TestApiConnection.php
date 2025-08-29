<?php
// app/Console/Commands/TestApiConnection.php - CORREGIDO
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ApiService;
use Illuminate\Support\Facades\Http;

class TestApiConnection extends Command
{
    protected $signature = 'api:test';
    protected $description = 'Probar conexión con la API';

    public function handle()
    {
        $apiService = app(ApiService::class);
        
        $this->info('🔍 Probando conexión con API...');
        $this->info('URL Base: ' . config('api.base_url'));
        
        // Test 1: Health endpoint
        $this->info('');
        $this->info('❤️ Test 1: Health endpoint');
        try {
            $response = Http::timeout(10)->get(config('api.base_url') . '/health');
            $this->info("Status: {$response->status()}");
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info("✅ Response: " . json_encode($data, JSON_PRETTY_PRINT));
                
                // ✅ CORREGIR: Verificar estructura real
                if (isset($data['success']) && $data['success'] === true && 
                    isset($data['data']['status']) && $data['data']['status'] === 'ok') {
                    $this->info("✅ Health check PASSED (estructura con success.data.status)");
                } elseif (isset($data['status']) && $data['status'] === 'ok') {
                    $this->info("✅ Health check PASSED (estructura directa)");
                } else {
                    $this->error("❌ Health check structure invalid");
                    $this->info("Expected: success.data.status = 'ok' OR status = 'ok'");
                }
            } else {
                $this->error("❌ Health endpoint failed with status: {$response->status()}");
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
        
        // Test 2: Login endpoint
        $this->info('');
        $this->info('🔐 Test 2: Login endpoint');
        try {
            $response = Http::timeout(10)->get(config('api.base_url') . '/auth/login');
            $this->info("Status: {$response->status()}");
            
            if ($response->status() === 405) {
                $this->info("✅ Login endpoint exists (405 Method Not Allowed is expected for GET)");
            } elseif ($response->status() === 422) {
                $this->info("✅ Login endpoint exists (422 Validation Error is expected without data)");
            } else {
                $this->info("⚠️ Unexpected status for login endpoint: {$response->status()}"); // ✅ CORREGIR
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
        
        // Test 3: Pacientes endpoint
        $this->info('');
        $this->info('👥 Test 3: Pacientes endpoint');
        try {
            $response = Http::timeout(10)->get(config('api.base_url') . '/pacientes');
            $this->info("Status: {$response->status()}");
            
            if ($response->status() === 401) {
                $this->info("✅ Pacientes endpoint exists (401 Unauthorized is expected without token)");
            } elseif ($response->status() === 500) {
                $this->info("⚠️ Server error - endpoint exists but has internal error"); // ✅ CORREGIR
            } else {
                $this->info("⚠️ Unexpected status: {$response->status()}"); // ✅ CORREGIR
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
        
        // Test 4: ApiService methods
        $this->info('');
        $this->info('⚙️ Test 4: ApiService methods');
        
        // Limpiar cache primero
        \Cache::forget('api_online_status');
        
        $isOnline = $apiService->forceConnectionCheck();
        $this->info("forceConnectionCheck(): " . ($isOnline ? '✅ ONLINE' : '❌ OFFLINE'));
        
        $cachedStatus = \Cache::get('api_online_status');
        $this->info("Cache status: " . ($cachedStatus ? '✅ ONLINE' : '❌ OFFLINE'));
        
        // Test 5: Stats
        $this->info('');
        $this->info('📊 Test 5: API Stats');
        $stats = $apiService->getStats();
        $this->info("Stats: " . json_encode($stats, JSON_PRETTY_PRINT));
        
        // Resumen final
        $this->info('');
        if ($isOnline) {
            $this->info('🎉 RESULTADO: API está ONLINE y funcionando correctamente');
        } else {
            $this->error('💥 RESULTADO: API está OFFLINE o no responde correctamente');
        }
    }
}
