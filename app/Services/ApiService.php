<?php
// app/Services/ApiService.php (MÉTODO CORREGIDO)
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class ApiService
{
    protected $baseUrl;
    protected $timeout;
    protected $token;

    public function __construct()
    {
        $this->baseUrl = config('api.base_url');
        $this->timeout = config('api.timeout', 30);
        
    }

    /**
     * ✅ CORREGIDO: Verificar conectividad usando el endpoint /health
     */
    public function isOnline(): bool
    {
        return Cache::remember('api_online_status', 30, function () {
            try {
                $response = Http::timeout(5)->get($this->baseUrl . '/health');
                
                Log::debug('API Health Check', [
                    'url' => $this->baseUrl . '/health',
                    'status' => $response->status(),
                    'success' => $response->successful(),
                    'response' => $response->json()
                ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    // ✅ CORREGIR: La API responde con success.data.status, no directamente status
                    if (isset($data['success']) && $data['success'] === true && 
                        isset($data['data']['status']) && $data['data']['status'] === 'ok') {
                        return true;
                    }
                    
                    // También aceptar estructura directa por compatibilidad
                    if (isset($data['status']) && $data['status'] === 'ok') {
                        return true;
                    }
                }
                
                return false;
                
            } catch (\Exception $e) {
                Log::debug('API health check failed', [
                    'error' => $e->getMessage(),
                    'url' => $this->baseUrl . '/health'
                ]);
                return false;
            }
        });
    }

    /**
     * ✅ NUEVO: Verificar conectividad sin cache
     */
    public function checkConnection(): bool
    {
        try {
            Log::info('Checking API connection', ['url' => $this->baseUrl . '/health']);
            
            $response = Http::timeout(10)->get($this->baseUrl . '/health');
            
            Log::info('API connection response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body()
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // ✅ CORREGIR: Verificar la estructura real
                $isOnline = false;
                
                // Estructura: {"success": true, "data": {"status": "ok", ...}}
                if (isset($data['success']) && $data['success'] === true && 
                    isset($data['data']['status']) && $data['data']['status'] === 'ok') {
                    $isOnline = true;
                }
                
                // También aceptar estructura directa
                if (isset($data['status']) && $data['status'] === 'ok') {
                    $isOnline = true;
                }
                
                // Actualizar cache
                Cache::put('api_online_status', $isOnline, 30);
                
                return $isOnline;
            }
            
            Cache::put('api_online_status', false, 30);
            return false;
            
        } catch (\Exception $e) {
            Log::warning('API connection check failed', [
                'error' => $e->getMessage(),
                'url' => $this->baseUrl . '/health'
            ]);
            
            Cache::put('api_online_status', false, 30);
            return false;
        }
    }

    /**
     * ✅ CORREGIDO: Obtener headers para las peticiones
     */
    protected function getHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'FrontSidis/1.0'
        ];

       // ✅ OBTENER TOKEN DINÁMICAMENTE
    $token = session('api_token');
    
    if ($token) {
        $headers['Authorization'] = 'Bearer ' . $token;
        
        // ✅ DEBUG: Log del token
        Log::info('🔑 Token incluido en headers', [
            'has_token' => true,
            'token_length' => strlen($token),
            'token_preview' => substr($token, 0, 20) . '...',
            'session_id' => session()->getId()
        ]);
    } else {
        Log::warning('⚠️ No hay token en sesión para incluir en headers', [
            'session_all_keys' => array_keys(session()->all()),
            'session_id' => session()->getId()
        ]);
    }

    return $headers;
}

    /**
     * ✅ CORREGIDO: Realizar petición GET
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->makeRequest('GET', $endpoint, $params);
    }

    /**
     * ✅ CORREGIDO: Realizar petición POST
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->makeRequest('POST', $endpoint, $data);
    }

    /**
     * ✅ CORREGIDO: Realizar petición PUT
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->makeRequest('PUT', $endpoint, $data);
    }

    /**
     * ✅ CORREGIDO: Realizar petición DELETE
     */
    public function delete(string $endpoint): array
    {
        return $this->makeRequest('DELETE', $endpoint);
    }

   /**
 * ✅ CORREGIDO: Realizar petición HTTP genérica
 */
protected function makeRequest(string $method, string $endpoint, array $data = []): array
{
    // ✅ AGREGAR DEBUG DEL TOKEN
    $token = session('api_token');
    
    Log::info('🔐 ApiService - Token debug', [
        'endpoint' => $endpoint,
        'method' => $method,
        'has_token' => !empty($token),
        'token_length' => $token ? strlen($token) : 0,
        'session_id' => session()->getId()
    ]);
    
    // ✅ VERIFICAR CONEXIÓN ANTES DE HACER LA PETICIÓN
    if (!$this->checkConnection()) {
        Log::warning('API request blocked - offline', [
            'method' => $method,
            'endpoint' => $endpoint
        ]);
        
        return [
            'success' => false,
            'error' => 'Sin conexión al servidor',
            'offline' => true
        ];
    }

    try {
        // ✅ OBTENER HEADERS CON TOKEN
        $headers = $this->getHeaders();
        
        // ✅ DEBUG: Verificar que el token esté en los headers
        Log::info("🔍 Headers preparados para petición", [
            'method' => $method,
            'endpoint' => $endpoint,
            'has_authorization' => isset($headers['Authorization']),
            'authorization_preview' => isset($headers['Authorization']) ? substr($headers['Authorization'], 0, 30) . '...' : 'NO_AUTH',
            'headers_count' => count($headers)
        ]);

        // ✅ USAR Http::withHeaders() CON LOS HEADERS QUE INCLUYEN EL TOKEN
        $httpClient = Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->retry(2, 1000);

        $url = $this->baseUrl . $endpoint;
        
        // ✅ CORREGIR: Verificar token desde sesión, no desde propiedad
        Log::info("API {$method} Request", [
            'url' => $url,
            'has_token' => isset($headers['Authorization']),
            'data_keys' => array_keys($data)
        ]);

        // Realizar la petición según el método
        switch (strtoupper($method)) {
            case 'GET':
                $response = $httpClient->get($url, $data);
                break;
            case 'POST':
                $response = $httpClient->post($url, $data);
                break;
            case 'PUT':
                $response = $httpClient->put($url, $data);
                break;
            case 'DELETE':
                $response = $httpClient->delete($url);
                break;
            default:
                throw new \InvalidArgumentException("Método HTTP no soportado: {$method}");
        }

        Log::info("API {$method} Response", [
            'url' => $url,
            'status' => $response->status(),
            'successful' => $response->successful()
        ]);

        // ✅ CORREGIDO: Devolver directamente la respuesta de la API
        if ($response->successful()) {
            $responseData = $response->json();
            
            // ✅ DEBUG: Log de la respuesta real
            Log::info("📥 API Response Data", [
                'endpoint' => $endpoint,
                'response_keys' => array_keys($responseData),
                'has_success' => isset($responseData['success']),
                'success_value' => $responseData['success'] ?? 'not set'
            ]);
            
            // ✅ DEVOLVER LA RESPUESTA TAL COMO VIENE DE LA API
            return $responseData;
        }

        // ✅ MANEJAR ERRORES HTTP ESPECÍFICOS (INCLUYENDO 404)
        $statusCode = $response->status();
        $errorBody = $response->body();
        $errorMessage = "HTTP request returned status code {$statusCode}: {$errorBody}";
        
        Log::error("❌ API {$method} Request failed", [
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'error' => $errorMessage
        ]);

        // ✅ DEVOLVER ERROR ESPECÍFICO CON STATUS CODE
        return [
            'success' => false,
            'error' => $errorMessage,        // ← ERROR COMPLETO
            'status_code' => $statusCode,    // ← STATUS CODE SEPARADO
            'raw_response' => $errorBody
        ];

    } catch (RequestException $e) {
        // ✅ VERIFICAR SI ES ERROR HTTP O ERROR DE CONEXIÓN REAL
        if ($e->response) {
            // Es un error HTTP (4xx, 5xx) - NO es error de conexión
            $statusCode = $e->response->status();
            $errorBody = $e->response->body();
            $errorMessage = "HTTP request returned status code {$statusCode}: {$errorBody}";
            
            Log::error("❌ API {$method} HTTP Error", [
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
                'error' => $errorMessage
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'status_code' => $statusCode,
                'raw_response' => $errorBody
            ];
        }

        // ✅ ERROR DE CONEXIÓN REAL (sin respuesta del servidor)
        Log::error("❌ API {$method} Connection Error", [
            'endpoint' => $endpoint,
            'error' => $e->getMessage()
        ]);

        Cache::put('api_online_status', false, 30);

        return [
            'success' => false,
            'error' => 'Error de conexión con el servidor: ' . $e->getMessage(),
            'offline' => true
        ];

    } catch (\Exception $e) {
        Log::error("❌ API {$method} Exception", [
            'endpoint' => $endpoint,
            'error' => $e->getMessage()
        ]);

        return [
            'success' => false,
            'error' => 'Error interno: ' . $e->getMessage()
        ];
    }
}

    /**
     * ✅ NUEVO: Forzar verificación de conexión
     */
    public function forceConnectionCheck(): bool
    {
        Cache::forget('api_online_status');
        return $this->checkConnection();
    }

    /**
     * Establecer token de autenticación
     */
   public function setToken(string $token): void
{
    // ✅ NO GUARDAR EN PROPIEDAD, SOLO EN SESIÓN
    session(['api_token' => $token]);
    
    Log::info('🔑 API Token establecido', [
        'token_length' => strlen($token),
        'token_preview' => substr($token, 0, 10) . '...',
        'session_id' => session()->getId()
    ]);
}
    /**
     * Limpiar token
     */
    public function clearToken(): void
    {
        $this->token = null;
        session()->forget('api_token');
        Cache::forget('api_online_status');
        
        Log::info('API Token limpiado');
    }

    /**
     * ✅ NUEVO: Obtener información del token actual
     */
    public function getTokenInfo(): ?array
{
    $token = session('api_token'); // ✅ OBTENER DINÁMICAMENTE
    
    if (!$token) {
        return null;
    }

    return [
        'has_token' => true,
        'token_length' => strlen($token),
        'token_preview' => substr($token, 0, 10) . '...',
        'expires_at' => session('token_expires_at')
    ];
}


    /**
     * ✅ NUEVO: Obtener URL base actual
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * ✅ NUEVO: Obtener estadísticas de la API
     */
    public function getStats(): array
    {
        return [
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'has_token' => !empty($this->token),
            'is_online' => $this->isOnline(),
            'cache_status' => Cache::get('api_online_status'),
            'last_check' => now()->toISOString()
        ];
    }
}
