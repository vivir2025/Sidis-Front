<?php
// app/Console/Commands/DiagnoseApiConnection.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DiagnoseApiConnection extends Command
{
    protected $signature = 'api:diagnose';
    protected $description = 'Diagnóstico completo de conectividad API';

    public function handle()
    {
        $baseUrl = 'http://sidis.nacerparavivir.org/api/v1';
        
        $this->info('🏥 DIAGNÓSTICO COMPLETO DE CONECTIVIDAD API');
        $this->info('================================================');
        $this->info("URL Base: {$baseUrl}");
        $this->info('');

        // Test 1: Ping básico al dominio
        $this->info('🌐 Test 1: Conectividad básica al dominio');
        $this->testDomainConnectivity('sidis.nacerparavivir.org');

        // Test 2: Probar URL base sin /api/v1
        $this->info('');
        $this->info('🌍 Test 2: Conectividad a la URL base del sitio');
        $this->testUrl('http://sidis.nacerparavivir.org');

        // Test 3: Probar endpoint /api/v1 (sin health)
        $this->info('');
        $this->info('🔗 Test 3: Endpoint API base');
        $this->testUrl('http://sidis.nacerparavivir.org/api/v1');

        // Test 4: Probar endpoint /health
        $this->info('');
        $this->info('❤️ Test 4: Health endpoint');
        $this->testUrl('http://sidis.nacerparavivir.org/api/v1/health');

        // Test 5: Probar con diferentes timeouts
        $this->info('');
        $this->info('⏱️ Test 5: Pruebas con diferentes timeouts');
        $this->testWithTimeouts('http://sidis.nacerparavivir.org/api/v1/health');

        // Test 6: Verificar headers de respuesta
        $this->info('');
        $this->info('📋 Test 6: Headers de respuesta');
        $this->testHeaders('http://sidis.nacerparavivir.org/api/v1/health');

        // Test 7: Verificar desde el servidor
        $this->info('');
        $this->info('🖥️ Test 7: Información del servidor');
        $this->showServerInfo();
    }

    private function testDomainConnectivity($domain)
    {
        $this->info("Probando conectividad a: {$domain}");
        
        // Test con ping (si está disponible)
        if (function_exists('exec')) {
            $output = [];
            $return_var = 0;
            @exec("ping -c 1 {$domain} 2>&1", $output, $return_var);
            
            if ($return_var === 0) {
                $this->info("✅ Ping exitoso");
            } else {
                $this->error("❌ Ping falló");
                $this->line("Output: " . implode("\n", $output));
            }
        } else {
            $this->warning("⚠️ Función exec() no disponible para ping");
        }
    }

    private function testUrl($url)
    {
        $this->info("Probando: {$url}");
        
        try {
            $start = microtime(true);
            $response = Http::timeout(30)->get($url);
            $duration = round((microtime(true) - $start) * 1000, 2);
            
            $this->info("Status: {$response->status()}");
            $this->info("Tiempo: {$duration}ms");
            $this->info("Tamaño: " . strlen($response->body()) . " bytes");
            
            if ($response->successful()) {
                $this->info("✅ Respuesta exitosa");
                
                // Si es JSON, mostrar estructura
                try {
                    $json = $response->json();
                    $this->info("JSON Keys: " . implode(', ', array_keys($json)));
                    if (isset($json['status'])) {
                        $this->info("Status en JSON: " . $json['status']);
                    }
                } catch (\Exception $e) {
                    $this->info("Respuesta no es JSON válido");
                    $this->info("Primeros 200 caracteres: " . substr($response->body(), 0, 200));
                }
            } else {
                $this->error("❌ Error HTTP: {$response->status()}");
                $this->error("Body: " . substr($response->body(), 0, 200));
            }
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->error("❌ Error de conexión: " . $e->getMessage());
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $this->error("❌ Error de petición: " . $e->getMessage());
        } catch (\Exception $e) {
            $this->error("❌ Error general: " . $e->getMessage());
        }
    }

    private function testWithTimeouts($url)
    {
        $timeouts = [5, 10, 30, 60];
        
        foreach ($timeouts as $timeout) {
            $this->info("Probando con timeout de {$timeout}s...");
            
            try {
                $start = microtime(true);
                $response = Http::timeout($timeout)->get($url);
                $duration = round((microtime(true) - $start) * 1000, 2);
                
                if ($response->successful()) {
                    $this->info("✅ Exitoso en {$duration}ms");
                    break; // Si funciona con este timeout, no probar más
                } else {
                    $this->error("❌ Status: {$response->status()}");
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Falló: " . $e->getMessage());
            }
        }
    }

    private function testHeaders($url)
    {
        try {
            $response = Http::timeout(30)->get($url);
            
            $this->info("Headers de respuesta:");
            foreach ($response->headers() as $key => $values) {
                $this->line("  {$key}: " . implode(', ', $values));
            }
            
        } catch (\Exception $e) {
            $this->error("No se pudieron obtener headers: " . $e->getMessage());
        }
    }

    private function showServerInfo()
    {
        $this->info("Información del servidor:");
        $this->line("PHP Version: " . PHP_VERSION);
        $this->line("Laravel Version: " . app()->version());
        $this->line("Environment: " . app()->environment());
        
        // Verificar extensiones necesarias
        $extensions = ['curl', 'openssl', 'json'];
        foreach ($extensions as $ext) {
            $status = extension_loaded($ext) ? '✅' : '❌';
            $this->line("Extensión {$ext}: {$status}");
        }
        
        // Verificar configuración de red
        $this->line("allow_url_fopen: " . (ini_get('allow_url_fopen') ? '✅' : '❌'));
        $this->line("User Agent: " . ini_get('user_agent'));
        
        // Verificar proxy settings
        $proxy = getenv('HTTP_PROXY') ?: getenv('http_proxy');
        if ($proxy) {
            $this->line("HTTP Proxy: {$proxy}");
        } else {
            $this->line("HTTP Proxy: No configurado");
        }
    }
}
