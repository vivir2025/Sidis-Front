<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\{AuthService, ApiService};
use Illuminate\Support\Facades\{Validator, Log, Cache};

class AuthController extends Controller
{
    protected $authService;
    protected $apiService;

    public function __construct(AuthService $authService, ApiService $apiService)
    {
        $this->authService = $authService;
        $this->apiService = $apiService;
    }

    /**
     * Mostrar formulario de login
     */
    public function showLogin()
    {
        // Verificar si ya está autenticado
        if ($this->authService->check()) {
            // ✅ REDIRIGIR SEGÚN EL ROL AL ESTAR YA AUTENTICADO
            $redirectRoute = $this->authService->getDashboardRoute();
            return redirect($redirectRoute);
        }

        $isOnline = $this->apiService->isOnline();
        $sedes = $this->getSedes($isOnline);

        return view('auth.login', compact('isOnline', 'sedes'));
    }

    /**
     * ✅ LOGIN MODIFICADO
     */
    public function login(Request $request)
    {
        // Validación sin cambios
        $validator = Validator::make($request->all(), [
            'login' => 'required|string|max:50',
            'password' => 'required|string|min:4',
            'sede_id' => 'required|integer'
        ], [
            'login.required' => 'El usuario es obligatorio',
            'login.max' => 'El usuario no puede tener más de 50 caracteres',
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 4 caracteres',
            'sede_id.required' => 'Debe seleccionar una sede',
            'sede_id.integer' => 'Sede inválida'
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }

        $credentials = $request->only('login', 'password', 'sede_id');

        try {
            Log::info('Iniciando login desde controller', [
                'login' => $credentials['login'],
                'sede_id' => $credentials['sede_id'],
                'api_online' => $this->apiService->isOnline()
            ]);

            session(['temp_password_for_offline' => $credentials['password']]);

            // ✅ LOGIN SIN RESTRICCIÓN DE SEDE
            $result = $this->authService->login($credentials);

            Log::info('Resultado de AuthService::login', [
                'result' => $result,
                'login' => $credentials['login']
            ]);

            session()->forget('temp_password_for_offline');

            if ($result['success']) {
                $message = $result['message'];
                
                if (isset($result['offline']) && $result['offline']) {
                    $message .= ' (Modo Offline)';
                }

                Log::info('Login exitoso desde web', [
                    'usuario' => $result['usuario']['login'] ?? 'unknown',
                    'rol' => $result['usuario']['rol']['nombre'] ?? 'unknown',
                    'sede_seleccionada' => $credentials['sede_id'],
                    'sede_nombre' => $result['usuario']['sede']['nombre'] ?? 'unknown',
                    'offline' => $result['offline'] ?? false,
                    'ip' => $request->ip()
                ]);

                if ($request->has('remember')) {
                    $this->rememberLoginData($credentials);
                }

                $redirectRoute = $this->authService->getDashboardRoute();
                
                Log::info('Redirigiendo usuario según rol', [
                    'usuario' => $result['usuario']['login'] ?? 'unknown',
                    'rol' => $result['usuario']['rol']['nombre'] ?? 'unknown',
                    'sede_nombre' => $result['usuario']['sede']['nombre'] ?? 'unknown',
                    'redirect_route' => $redirectRoute
                ]);

                return redirect()
                    ->intended($redirectRoute)
                    ->with('success', $message);
            }

            Log::warning('Login fallido desde web', [
                'login' => $credentials['login'],
                'sede_id' => $credentials['sede_id'],
                'error' => $result['error'],
                'ip' => $request->ip()
            ]);

            $errorMessage = $result['error'] ?? 'Error desconocido en el login';

            return back()
                ->withErrors(['login' => $errorMessage])
                ->withInput($request->except('password'));

        } catch (\Exception $e) {
            session()->forget('temp_password_for_offline');
            
            Log::error('Error crítico en login', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'login' => $credentials['login'] ?? 'unknown',
                'ip' => $request->ip()
            ]);

            return back()
                ->withErrors(['login' => 'Error interno del sistema. Intente nuevamente.'])
                ->withInput($request->except('password'));
        }
    }

    /**
     * ✅ NUEVO: Cambiar sede sin cerrar sesión
     */
    public function cambiarSede(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'sede_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->authService->cambiarSede($request->sede_id);
            
            if ($result['success']) {
                Log::info('Cambio de sede exitoso', [
                    'usuario' => $this->authService->usuario()['login'] ?? 'unknown',
                    'nueva_sede_id' => $request->sede_id,
                    'sede_nombre' => $result['usuario']['sede']['nombre'] ?? 'unknown'
                ]);
            }
            
            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error cambiando sede', [
                'error' => $e->getMessage(),
                'usuario' => $this->authService->usuario()['login'] ?? 'unknown',
                'nueva_sede_id' => $request->sede_id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del sistema'
            ], 500);
        }
    }
      /**
     * ✅ OBTENER SEDES DISPONIBLES PARA CAMBIO
     */
    public function getSedesDisponibles(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        try {
            $sedes = $this->getSedes($this->apiService->isOnline());
            
            return response()->json([
                'success' => true,
                'data' => $sedes,
                'sede_actual' => $this->authService->sedeId(),
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo sedes disponibles', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo sedes',
                'data' => []
            ], 500);
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request)
    {
        try {
            $usuarioLogin = $this->authService->usuario()['login'] ?? 'unknown';
            
            // Si está online, intentar logout en API
            if (!$this->authService->isOffline() && $this->apiService->isOnline()) {
                $response = $this->apiService->post('/auth/logout');
                if (!$response['success']) {
                    Log::warning('Logout API falló, continuando con logout local', [
                        'error' => $response['error'] ?? 'Unknown error'
                    ]);
                }
            }

            $this->authService->logout();

            Log::info('Logout exitoso desde web', [
                'usuario' => $usuarioLogin,
                'ip' => $request->ip()
            ]);

            return redirect()
                ->route('login')
                ->with('success', 'Sesión cerrada correctamente');

        } catch (\Exception $e) {
            Log::error('Error en logout', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);
            
            // Forzar logout local aunque falle el remoto
            $this->authService->logout();
            
            return redirect()
                ->route('login')
                ->with('warning', 'Sesión cerrada localmente debido a un error');
        }
    }

    /**
     * Obtener sedes disponibles con cache
     */
    protected function getSedes(bool $isOnline = null)
    {
        $isOnline = $isOnline ?? $this->apiService->isOnline();
        
        if ($isOnline) {
            try {
                // Intentar obtener sedes del API
                $response = $this->apiService->get('/master-data/sedes');
                
                if ($response['success'] && isset($response['data'])) {
                    $sedes = $response['data']['data'] ?? $response['data'];
                    
                    // Guardar en cache para uso offline
                    Cache::put('sedes_cache', $sedes, now()->addHours(24));
                    
                    return $sedes;
                }
            } catch (\Exception $e) {
                Log::warning('Error obteniendo sedes de API', ['error' => $e->getMessage()]);
            }
        }

        // Intentar obtener desde cache
        $cachedSedes = Cache::get('sedes_cache');
        if ($cachedSedes) {
            return $cachedSedes;
        }

        // Sedes por defecto si no hay conexión ni cache
        $defaultSedes = [
            ['id' => 1, 'nombre' => 'Cajibio'],
            ['id' => 2, 'nombre' => 'Piendamo'],
            ['id' => 3, 'nombre' => 'Morales']
        ];

        Log::info('Usando sedes por defecto (sin conexión ni cache)');
        return $defaultSedes;
    }

    /**
     * Verificar estado de conexión
     */
    public function checkConnection(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        try {
            $isOnline = $this->apiService->isOnline();
            $isAuthenticated = $this->authService->check();
            $isOfflineMode = $this->authService->isOffline();

            $response = [
                'online' => $isOnline,
                'authenticated' => $isAuthenticated,
                'offline_mode' => $isOfflineMode,
                'timestamp' => now()->toISOString()
            ];

            // Si volvió la conexión y hay cambios pendientes
            if ($isOnline && $isAuthenticated && $isOfflineMode) {
                $response['can_sync'] = true;
                $response['message'] = 'Conexión restaurada. Puede sincronizar datos.';
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Error verificando conexión', ['error' => $e->getMessage()]);
            
            return response()->json([
                'online' => false,
                'error' => 'Error verificando conexión',
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Sincronizar datos
     */
    public function sync(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        if (!$this->authService->check()) {
            return response()->json([
                'success' => false, 
                'error' => 'No autenticado'
            ], 401);
        }

        if (!$this->apiService->isOnline()) {
            return response()->json([
                'success' => false,
                'error' => 'Sin conexión al servidor'
            ]);
        }

        try {
            $synced = $this->authService->syncWhenOnline();
            
            if ($synced) {
                // Actualizar estado de sesión a online
                session(['is_offline' => false]);
                
                Log::info('Sincronización exitosa', [
                    'usuario' => $this->authService->usuario()['login'] ?? 'unknown'
                ]);
            }
            
            return response()->json([
                'success' => $synced,
                'message' => $synced ? 'Sincronización exitosa' : 'No se pudo sincronizar',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error en sincronización', [
                'error' => $e->getMessage(),
                'usuario' => $this->authService->usuario()['login'] ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error durante la sincronización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recordar datos de login
     */
    protected function rememberLoginData(array $credentials): void
    {
        try {
            $dataToRemember = [
                'login' => $credentials['login'],
                'sede_id' => $credentials['sede_id']
            ];
            
            Cache::put('remembered_login_data', $dataToRemember, now()->addDays(30));
        } catch (\Exception $e) {
            Log::warning('Error guardando datos recordados', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Obtener datos recordados
     */
    public function getRememberedData(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $rememberedData = Cache::get('remembered_login_data', []);
        
        return response()->json($rememberedData);
    }

    /**
     * ✅ NUEVO: Verificar estado de sesión
     */
    public function sessionStatus(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        try {
            $user = $this->authService->usuario();
            $isOnline = $this->apiService->isOnline();
            $isOffline = $this->authService->isOffline();

            return response()->json([
                'authenticated' => true,
                'user' => [
                    'login' => $user['login'],
                    'nombre' => $user['nombre'],
                    'rol' => $user['rol'],
                    'sede_nombre' => $user['sede_nombre']
                ],
                'online' => $isOnline,
                'offline_mode' => $isOffline,
                'session_expires_at' => session('session_expires_at'),
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'authenticated' => false,
                'error' => 'Sesión inválida'
            ], 401);
        }
    }

    /**
     * ✅ NUEVO: Obtener sedes via AJAX
     */
    public function getSedesAjax(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        try {
            $isOnline = $this->apiService->isOnline();
            $sedes = $this->getSedesAjax($isOnline);
            
            return response()->json([
                'success' => true,
                'data' => $sedes,
                'online' => $isOnline,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo sedes via AJAX', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo sedes',
                'data' => []
            ], 500);
        }
    }

    /**
     * ✅ NUEVO: Mostrar perfil de usuario
     */
    public function showProfile()
    {
        $user = $this->authService->usuario();
        $isOnline = $this->apiService->isOnline();
        
        return view('auth.profile', compact('user', 'isOnline'));
    }

    /**
     * ✅ NUEVO: Actualizar perfil
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'email' => 'nullable|email|max:100'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $result = $this->authService->updateProfile($request->only('nombre', 'email'));
            
            if ($result['success']) {
                return back()->with('success', 'Perfil actualizado correctamente');
            }
            
            return back()->withErrors(['error' => $result['error']]);

        } catch (\Exception $e) {
            Log::error('Error actualizando perfil', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Error interno del sistema']);
        }
    }

    /**
     * ✅ NUEVO: Cambiar contraseña
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:4|confirmed',
            'new_password_confirmation' => 'required'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $result = $this->authService->changePassword(
                $request->current_password,
                $request->new_password
            );
            
            if ($result['success']) {
                return back()->with('success', 'Contraseña cambiada correctamente');
            }
            
            return back()->withErrors(['current_password' => $result['error']]);

        } catch (\Exception $e) {
            Log::error('Error cambiando contraseña', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Error interno del sistema']);
        }
    }

    /**
     * ✅ NUEVO: Obtener información del usuario autenticado
     */
    public function getUserInfo(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        try {
            if (!$this->authService->check()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No autenticado'
                ], 401);
            }

            $user = $this->authService->usuario();
            $isOnline = $this->apiService->isOnline();
            $isOffline = $this->authService->isOffline();

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'is_online' => $isOnline,
                    'is_offline' => $isOffline,
                    'is_profesional_salud' => $this->authService->isProfesionalEnSalud(),
                    'dashboard_route' => $this->authService->getDashboardRoute(),
                    'permissions' => $user['permisos'] ?? [],
                    'role' => $user['rol'] ?? null
                ],
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo información del usuario', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * ✅ NUEVO: Verificar permisos del usuario
     */
    public function checkPermissions(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        try {
            $permissions = $request->get('permissions', []);
            
            if (!$this->authService->check()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No autenticado'
                ], 401);
            }

            $results = [];
            foreach ($permissions as $permission) {
                $results[$permission] = $this->authService->hasPermission($permission);
            }

            return response()->json([
                'success' => true,
                'permissions' => $results,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error verificando permisos', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * ✅ NUEVO: Extender sesión
     */
    public function extendSession(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        try {
            if (!$this->authService->check()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No autenticado'
                ], 401);
            }

            // Actualizar último acceso
            $this->authService->updateLastAccess();

            // Si está online y el token necesita renovación, intentar renovarlo
            if (!$this->authService->isOffline() && 
                $this->apiService->isOnline() && 
                $this->authService->tokenNeedsRefresh()) {
                
                $refreshResult = $this->authService->refreshToken();
                
                if (!$refreshResult['success']) {
                    Log::warning('No se pudo renovar token durante extensión de sesión', [
                        'error' => $refreshResult['error']
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Sesión extendida',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error extendiendo sesión', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }
}
