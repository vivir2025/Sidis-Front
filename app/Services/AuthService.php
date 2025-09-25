<?php
// app/Services/AuthService.php (MÉTODO LOGIN CORREGIDO)
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Services\{ApiService, OfflineService};

class AuthService
{
    protected $apiService;
    protected $offlineService;

    public function __construct(ApiService $apiService, OfflineService $offlineService)
    {
        $this->apiService = $apiService;
        $this->offlineService = $offlineService;
    }

  
    /**
     * ✅ LOGIN MODIFICADO - SIN RESTRICCIÓN DE SEDE
     */
    public function login(array $credentials)
    {
        // Guardar contraseña temporalmente para uso offline
        session(['temp_password_for_offline' => $credentials['password']]);

        Log::info('🔍 Intentando login con sede seleccionada', [
            'login' => $credentials['login'],
            'sede_id' => $credentials['sede_id'],
            'has_password' => !empty($credentials['password'])
        ]);

        // 1. Intentar login online primero
        $response = $this->apiService->post('/auth/login', $credentials);

        Log::info('🔍 Respuesta completa de ApiService', [
            'response_keys' => array_keys($response),
            'success' => $response['success'] ?? 'no definido',
            'has_data' => isset($response['data']),
            'has_offline_flag' => isset($response['offline'])
        ]);

        // 2. ✅ VERIFICAR SI LA RESPUESTA ES EXITOSA
        if (isset($response['success']) && $response['success'] === true) {
            
            Log::info('✅ Respuesta exitosa de la API');

            if (!isset($response['data'])) {
                Log::error('❌ Respuesta exitosa pero sin data', ['response' => $response]);
                return [
                    'success' => false,
                    'error' => 'Error en la respuesta del servidor: estructura de datos inválida'
                ];
            }

            $responseData = $response['data'];

            if (!isset($responseData['usuario'])) {
                Log::error('❌ Respuesta sin datos de usuario', [
                    'response_data_keys' => array_keys($responseData),
                    'full_response' => $response
                ]);
                return [
                    'success' => false,
                    'error' => 'Error en la respuesta del servidor: datos de usuario no encontrados'
                ];
            }

            $userData = $responseData['usuario'];
            $token = $responseData['token'] ?? null;

            if (!$token) {
                Log::error('❌ Respuesta sin token', ['response_data' => $responseData]);
                return [
                    'success' => false,
                    'error' => 'Error en la respuesta del servidor: token faltante'
                ];
            }

            $expiresAt = isset($responseData['expires_at']) 
                ? \Carbon\Carbon::parse($responseData['expires_at'])
                : now()->addHours(8);

            // ✅ NORMALIZAR DATOS DE USUARIO
            $userData = $this->normalizeUserData($userData);

            // ✅ GUARDAR SEDE SELECCIONADA EN LA SESIÓN
            session([
                'usuario' => $userData,
                'api_token' => $token,
                'token_expires_at' => $expiresAt,
                'sede_id' => $credentials['sede_id'], // ✅ SEDE SELECCIONADA
                'is_offline' => false
            ]);

            // Guardar para uso offline futuro
            $this->syncMasterDataAfterLogin();
            $this->offlineService->storeUserData($userData);
            $this->apiService->setToken($token);

            // ✅ GUARDAR CONTRASEÑA PARA OFFLINE
            $tempPassword = session('temp_password_for_offline');
            if ($tempPassword) {
                $this->offlineService->savePasswordHash($userData['login'], $tempPassword);
                session()->forget('temp_password_for_offline');
            }

            Log::info('✅ Login exitoso (online)', [
                'usuario_id' => $userData['id'],
                'login' => $userData['login'],
                'sede_seleccionada' => $credentials['sede_id'],
                'sede_nombre' => $userData['sede']['nombre'] ?? 'N/A'
            ]);

            return [
                'success' => true,
                'message' => 'Bienvenido a ' . ($userData['sede']['nombre'] ?? 'la sede') . ' - ' . $userData['nombre_completo'],
                'usuario' => $userData,
                'offline' => false
            ];
        }

        // 3. ✅ VERIFICAR SI ES ERROR DE CONEXIÓN (offline)
        if (isset($response['offline']) && $response['offline'] === true) {
            Log::info('🔌 Sin conexión, intentando login offline');
            return $this->attemptOfflineLogin($credentials);
        }

        // 4. ✅ VERIFICAR SI NO HAY CONEXIÓN AL SERVIDOR
        if (!$this->apiService->isOnline()) {
            Log::info('🔌 Servidor no disponible, intentando login offline');
            return $this->attemptOfflineLogin($credentials);
        }

        // 5. ✅ ERROR DE CREDENCIALES U OTRO ERROR
        $errorMessage = 'Error de autenticación';
        
        if (isset($response['errors'])) {
            if (isset($response['errors']['login'])) {
                $errorMessage = is_array($response['errors']['login']) 
                    ? $response['errors']['login'][0] 
                    : $response['errors']['login'];
            }
        } elseif (isset($response['error'])) {
            $errorMessage = $response['error'];
        } elseif (isset($response['message'])) {
            $errorMessage = $response['message'];
        }

        Log::warning('❌ Login fallido', [
            'error' => $errorMessage,
            'response' => $response,
            'login' => $credentials['login']
        ]);

        session()->forget('temp_password_for_offline');
        
        return [
            'success' => false,
            'error' => $errorMessage
        ];
    }

private function syncMasterDataAfterLogin(): void
{
    try {
        Log::info('🔄 Sincronizando datos maestros después del login');
        
        $response = $this->apiService->get('/master-data/all');
        
        if ($response['success'] && isset($response['data'])) {
            $this->offlineService->syncMasterDataFromApi($response['data']);
            
            Log::info('✅ Datos maestros sincronizados después del login', [
                'tables_count' => count($response['data']),
                'procesos_count' => count($response['data']['procesos'] ?? []),
                'brigadas_count' => count($response['data']['brigadas'] ?? [])
            ]);
        } else {
            Log::warning('⚠️ No se pudieron obtener datos maestros después del login');
        }
        
    } catch (\Exception $e) {
        Log::error('❌ Error sincronizando datos maestros después del login', [
            'error' => $e->getMessage()
        ]);
    }
}
    /**
     * ✅ NUEVO: Normalizar datos de usuario
     */
    private function normalizeUserData(array $userData): array
    {
        // Asegurar que tenemos todos los campos necesarios
        return [
            'id' => $userData['id'] ?? null,
            'uuid' => $userData['uuid'] ?? null,
            'documento' => $userData['documento'] ?? null,
            'nombre' => $userData['nombre'] ?? null,
            'apellido' => $userData['apellido'] ?? null,
            'nombre_completo' => $userData['nombre_completo'] ?? 
                               (($userData['nombre'] ?? '') . ' ' . ($userData['apellido'] ?? '')),
            'correo' => $userData['correo'] ?? $userData['email'] ?? null,
            'telefono' => $userData['telefono'] ?? null,
            'login' => $userData['login'] ?? null,
            'registro_profesional' => $userData['registro_profesional'] ?? null,
            
            // Información de relaciones
            'sede_id' => $userData['sede_id'] ?? null,
            'sede' => $userData['sede'] ?? null,
            'rol_id' => $userData['rol_id'] ?? null,
            'rol' => $userData['rol'] ?? null,
            'especialidad_id' => $userData['especialidad_id'] ?? null,
            'especialidad' => $userData['especialidad'] ?? null,
            'estado_id' => $userData['estado_id'] ?? null,
            'estado' => $userData['estado'] ?? null,
            
            // Permisos y roles
            'permisos' => $userData['permisos'] ?? [],
            'tipo_usuario' => $userData['tipo_usuario'] ?? [],
            
            // Timestamps
            'ultimo_acceso' => now()->toDateTimeString(),
            'created_at' => $userData['created_at'] ?? null,
            'updated_at' => $userData['updated_at'] ?? null,
        ];
    }

     /**
     * ✅ LOGIN OFFLINE MODIFICADO - SIN RESTRICCIÓN DE SEDE
     */
    private function attemptOfflineLogin(array $credentials): array
    {
        $login = $credentials['login'];
        $password = $credentials['password'];
        $sedeId = $credentials['sede_id'];
        
        // ✅ BUSCAR USUARIO SIN RESTRICCIÓN DE SEDE
        $offlineUser = $this->offlineService->getOfflineUser($login);
        
        if (!$offlineUser) {
            return [
                'success' => false,
                'error' => 'Usuario no encontrado para acceso offline. Debe conectarse online primero.'
            ];
        }

        // ✅ NO VERIFICAR SEDE DEL USUARIO, PERMITIR CUALQUIER SEDE
        // (El usuario puede acceder a cualquier sede offline)

        // ✅ VERIFICAR CONTRASEÑA OFFLINE
        if (!$this->validateOfflinePassword($login, $password)) {
            Log::warning('Intento de login offline con contraseña incorrecta', [
                'login' => $login
            ]);
            
            return [
                'success' => false,
                'error' => 'Contraseña incorrecta'
            ];
        }

        // ✅ NORMALIZAR DATOS OFFLINE Y ASIGNAR SEDE SELECCIONADA
        $userData = $this->normalizeUserData($offlineUser);
        
        // ✅ SOBRESCRIBIR SEDE CON LA SELECCIONADA
        $userData['sede_id'] = $sedeId;
        $userData['sede'] = [
            'id' => $sedeId,
            'nombre' => $this->getSedeNameById($sedeId) // Método auxiliar
        ];

        // Guardar sesión offline
        session([
            'usuario' => $userData,
            'api_token' => null,
            'sede_id' => $sedeId, // ✅ SEDE SELECCIONADA
            'is_offline' => true
        ]);

        session()->forget('temp_password_for_offline');

        Log::info('Login offline exitoso', [
            'login' => $login,
            'sede_seleccionada' => $sedeId,
            'sede_nombre' => $userData['sede']['nombre']
        ]);

        return [
            'success' => true,
            'message' => 'Acceso offline exitoso a ' . $userData['sede']['nombre'] . ' - ' . ($userData['nombre_completo'] ?? 'Usuario'),
            'usuario' => $userData,
            'offline' => true
        ];
    }

    /**
     * ✅ NUEVO: Obtener nombre de sede por ID
     */
    private function getSedeNameById(int $sedeId): string
    {
        try {
            // Intentar obtener desde cache o datos maestros
            $sedes = cache('sedes_cache', []);
            
            foreach ($sedes as $sede) {
                if (isset($sede['id']) && $sede['id'] == $sedeId) {
                    return $sede['nombre'];
                }
            }
            
            // Sedes por defecto
            $defaultSedes = [
                1 => 'Cajibio',
                2 => 'Piendamo', 
                3 => 'Morales'
            ];
            
            return $defaultSedes[$sedeId] ?? "Sede #{$sedeId}";
            
        } catch (\Exception $e) {
            Log::warning('Error obteniendo nombre de sede', [
                'sede_id' => $sedeId,
                'error' => $e->getMessage()
            ]);
            return "Sede #{$sedeId}";
        }
    }

    /**
     * ✅ NUEVO: Cambiar sede sin cerrar sesión
     */
    public function cambiarSede(int $nuevaSedeId): array
    {
        try {
            if (!$this->check()) {
                return [
                    'success' => false,
                    'error' => 'Usuario no autenticado'
                ];
            }

            // Si está online, notificar al servidor
            if (!$this->isOffline() && $this->apiService->isOnline()) {
                $response = $this->apiService->post('/auth/cambiar-sede', [
                    'sede_id' => $nuevaSedeId
                ]);
                
                if ($response['success']) {
                    $userData = $response['data']['usuario'];
                    
                    // Actualizar sesión con nueva sede
                    session([
                        'usuario' => $userData,
                        'sede_id' => $nuevaSedeId
                    ]);
                    
                    return [
                        'success' => true,
                        'message' => $response['message'],
                        'usuario' => $userData
                    ];
                }
            }

            // Cambio offline
            $usuario = session('usuario');
            $usuario['sede_id'] = $nuevaSedeId;
            $usuario['sede'] = [
                'id' => $nuevaSedeId,
                'nombre' => $this->getSedeNameById($nuevaSedeId)
            ];

            session([
                'usuario' => $usuario,
                'sede_id' => $nuevaSedeId
            ]);

            return [
                'success' => true,
                'message' => 'Cambiado a ' . $usuario['sede']['nombre'],
                'usuario' => $usuario
            ];

        } catch (\Exception $e) {
            Log::error('Error cambiando sede', [
                'nueva_sede_id' => $nuevaSedeId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Error interno al cambiar sede'
            ];
        }
    }

       /**
     * ✅ OBTENER SEDE ACTUAL DE LA SESIÓN
     */
    public function sedeActual(): ?array
    {
        $usuario = $this->usuario();
        return $usuario['sede'] ?? null;
    }

    /**
     * ✅ OBTENER ID DE SEDE ACTUAL
     */
    public function sedeId(): ?int
    {
        return session('sede_id') ?? $this->usuario()['sede_id'] ?? null;
    }

    /**
     * ✅ NUEVO: Validar contraseña offline
     */
    private function validateOfflinePassword(string $login, string $password): bool
    {
        $passwordData = $this->offlineService->getData('passwords/' . $login . '.json');
        
        if (!$passwordData || !isset($passwordData['password_hash'])) {
            Log::warning('No se encontró hash de contraseña offline para usuario', ['login' => $login]);
            return false;
        }

        $isValid = Hash::check($password, $passwordData['password_hash']);
        
        Log::info('Validación de contraseña offline', [
            'login' => $login,
            'valid' => $isValid
        ]);

        return $isValid;
    }

    /**
     * ✅ VERIFICAR SI EL USUARIO ESTÁ AUTENTICADO
     */
    public function check(): bool
    {
        return session()->has('usuario');
    }

    /**
     * ✅ OBTENER DATOS DEL USUARIO AUTENTICADO
     */
    public function usuario(): ?array
    {
        return session('usuario');
    }

    /**
     * ✅ VERIFICAR SI ESTÁ EN MODO OFFLINE
     */
    public function isOffline(): bool
    {
        return session('is_offline', false);
    }

    /**
     * ✅ OBTENER ID DEL USUARIO
     */
    public function id(): ?int
    {
        $usuario = $this->usuario();
        return $usuario['id'] ?? null;
    }

    /**
     * ✅ OBTENER UUID DEL USUARIO
     */
    public function uuid(): ?string
    {
        $usuario = $this->usuario();
        return $usuario['uuid'] ?? null;
    }

    /**
     * ✅ OBTENER NOMBRE COMPLETO DEL USUARIO
     */
    public function nombreCompleto(): ?string
    {
        $usuario = $this->usuario();
        return $usuario['nombre_completo'] ?? null;
    }

    /**
     * ✅ OBTENER ROL DEL USUARIO
     */
    public function rol(): ?array
    {
        $usuario = $this->usuario();
        return $usuario['rol'] ?? null;
    }

    /**
     * ✅ OBTENER SEDE DEL USUARIO
     */
    public function sede(): ?array
    {
        $usuario = $this->usuario();
        return $usuario['sede'] ?? null;
    }

    /**
     * ✅ VERIFICAR SI EL USUARIO TIENE UN PERMISO ESPECÍFICO
     */
    public function hasPermission(string $permission): bool
    {
        $usuario = $this->usuario();
        $permisos = $usuario['permisos'] ?? [];
        
        return in_array($permission, $permisos);
    }

    /**
     * ✅ VERIFICAR SI EL USUARIO TIENE UN ROL ESPECÍFICO
     */
    public function hasRole(string $role): bool
    {
        $usuario = $this->usuario();
        $rol = $usuario['rol']['nombre'] ?? '';
        
        return strtolower($rol) === strtolower($role);
    }

    /**
     * ✅ CERRAR SESIÓN
     */
    public function logout()
    {
        // Si está online, intentar logout en la API
        if (!$this->isOffline() && $this->apiService->isOnline()) {
            try {
                $this->apiService->post('/auth/logout');
            } catch (\Exception $e) {
                Log::warning('Error al hacer logout en API', ['error' => $e->getMessage()]);
            }
        }

        $this->apiService->clearToken();
        session()->flush();
        
        Log::info('Logout exitoso', [
            'was_offline' => $this->isOffline()
        ]);
    }

    /**
     * ✅ REFRESCAR TOKEN DE AUTENTICACIÓN
     */
    public function refreshToken(): array
    {
        if ($this->isOffline()) {
            return [
                'success' => false, 
                'error' => 'No disponible en modo offline'
            ];
        }

        if (!$this->apiService->isOnline()) {
            return [
                'success' => false,
                'error' => 'Sin conexión al servidor'
            ];
        }

        try {
            $response = $this->apiService->post('/auth/refresh');

            if ($response['success']) {
                $responseData = $response['data'] ?? [];
                $token = $responseData['token'] ?? $responseData['access_token'] ?? null;
                
                $expiresAt = isset($responseData['expires_at']) 
                    ? \Carbon\Carbon::parse($responseData['expires_at'])
                    : now()->addHours(8);

                if (!$token) {
                    return [
                        'success' => false, 
                        'error' => 'Token no recibido en refresh'
                    ];
                }

                session([
                    'api_token' => $token,
                    'token_expires_at' => $expiresAt
                ]);

                $this->apiService->setToken($token);

                Log::info('Token refrescado exitosamente');

                return ['success' => true];
            }

            return [
                'success' => false, 
                'error' => $response['error'] ?? 'Error al refrescar token'
            ];

        } catch (\Exception $e) {
            Log::error('Error al refrescar token', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => 'Error de conexión al refrescar token'
            ];
        }
    }

    /**
     * ✅ VERIFICAR SI EL TOKEN ESTÁ PRÓXIMO A EXPIRAR
     */
    public function tokenNeedsRefresh(): bool
    {
        if ($this->isOffline()) {
            return false;
        }

        $expiresAt = session('token_expires_at');
        
        if (!$expiresAt) {
            return true; // Si no hay fecha de expiración, mejor refrescar
        }

        try {
            $expiresAt = \Carbon\Carbon::parse($expiresAt);
            // Refrescar si expira en los próximos 10 minutos
            return $expiresAt->diffInMinutes(now()) <= 10;
        } catch (\Exception $e) {
            Log::warning('Error al verificar expiración de token', ['error' => $e->getMessage()]);
            return true;
        }
    }

    /**
     * ✅ SINCRONIZAR CUANDO HAY CONEXIÓN
     */
    public function syncWhenOnline(): bool
    {
        if (!$this->apiService->isOnline()) {
            return false;
        }

        try {
            $this->offlineService->syncPendingChanges();
            
            Log::info('Sincronización completada exitosamente');
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error en sincronización', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * ✅ OBTENER INFORMACIÓN DE LA SESIÓN
     */
    public function getSessionInfo(): array
    {
        return [
            'authenticated' => $this->check(),
            'offline' => $this->isOffline(),
            'usuario' => $this->usuario(),
            'token_expires_at' => session('token_expires_at'),
            'needs_refresh' => $this->tokenNeedsRefresh(),
            'api_online' => $this->apiService->isOnline()
        ];
    }

    /**
     * ✅ ACTUALIZAR ÚLTIMO ACCESO
     */
    public function updateLastAccess(): void
    {
        $usuario = session('usuario');
        if ($usuario) {
            $usuario['ultimo_acceso'] = now()->toDateTimeString();
            session(['usuario' => $usuario]);
        }
    }

    /**
     * ✅ VERIFICAR SI LA SESIÓN ES VÁLIDA
     */
    public function isSessionValid(): bool
    {
        if (!$this->check()) {
            return false;
        }

        // En modo offline, la sesión siempre es válida mientras exista
        if ($this->isOffline()) {
            return true;
        }

        // En modo online, verificar si el token no ha expirado
        $expiresAt = session('token_expires_at');
        if (!$expiresAt) {
            return false;
        }

        try {
            $expiresAt = \Carbon\Carbon::parse($expiresAt);
            return $expiresAt->isFuture();
        } catch (\Exception $e) {
            Log::warning('Error al verificar validez de sesión', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function syncPendingDataWhenOnline(): bool
{
    if (!$this->apiService->isOnline()) {
        return false;
    }

    try {
        // Sincronizar pacientes pendientes
        $pacienteService = app(\App\Services\PacienteService::class);
        $result = $pacienteService->syncPendingPacientes();
        
        if ($result['success'] && $result['synced_count'] > 0) {
            Log::info('✅ Sincronización automática completada', [
                'synced_count' => $result['synced_count']
            ]);
            return true;
        }
        
        return false;
        
    } catch (\Exception $e) {
        Log::error('❌ Error en sincronización automática', [
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

public function hasValidToken(): bool
{
    try {
        // ✅ VERIFICAR SI HAY TOKEN EN SESIÓN
        $token = session('api_token');
        
        if (empty($token)) {
            Log::info('🔐 No hay token en sesión');
            return false;
        }
        
        // ✅ VERIFICAR SI EL TOKEN NO HA EXPIRADO
        $expiresAt = session('token_expires_at');
        if ($expiresAt && now()->isAfter($expiresAt)) {
            Log::info('🔐 Token expirado', [
                'expires_at' => $expiresAt,
                'current_time' => now()
            ]);
            return false;
        }
        
        // ✅ VERIFICAR SI HAY USUARIO AUTENTICADO
        $usuario = session('usuario');
        if (empty($usuario)) {
            Log::info('🔐 No hay usuario en sesión');
            return false;
        }
        
        Log::info('🔐 Token válido encontrado', [
            'token_length' => strlen($token),
            'usuario_id' => $usuario['id'] ?? 'unknown'
        ]);
        
        return true;
        
    } catch (\Exception $e) {
        Log::error('❌ Error verificando token válido', [
            'error' => $e->getMessage()
        ]);
        return false;
    }
}
/**
 * ✅ VERIFICAR SI EL USUARIO ES PROFESIONAL EN SALUD
 */
public function isProfesionalEnSalud(): bool
{
    $usuario = $this->usuario();
    if (!$usuario) return false;
    
    $rolNombre = strtolower($usuario['rol']['nombre'] ?? '');
    
    return in_array($rolNombre, [
        'profesional en salud', 
        'medico', 
        'doctor', 
        'profesional',
        'médico'
    ]);
}

/**
 * ✅ OBTENER TIPO DE DASHBOARD SEGÚN ROL
 */
public function getDashboardRoute(): string
{
    if ($this->isProfesionalEnSalud()) {
        return route('cronograma.index');
    }
    
    // Otros roles van al dashboard normal
    return route('dashboard');
}
}
