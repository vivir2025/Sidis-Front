<?php
// app/Http/Controllers/UsuarioController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\{AuthService, ApiService};
use Illuminate\Support\Facades\{Validator, Log};
use App\Services\OfflineService;  // ✅ DEBE estar aquí

class UsuarioController extends Controller
{
    protected $apiService;
    protected $authService;
    protected $offlineService;

    /**
     * ✅ Ahora SÍ puedes inyectar OfflineService
     */
    public function __construct(
        ApiService $apiService,
        AuthService $authService,
        OfflineService $offlineService
    ) {
        $this->apiService = $apiService;
        $this->authService = $authService;
        $this->offlineService = $offlineService;
    }

    /**
     * Mostrar listado de usuarios
     */
    /**
 * Mostrar listado de usuarios
 */
public function index(Request $request)
{
    try {
        Log::info('📋 [FRONTEND] Cargando listado de usuarios', [
            'filtros' => $request->all()
        ]);

        // ✅ VERIFICAR CONEXIÓN
        $isOnline = $this->apiService->isOnline();
        $sedeId = $this->authService->sedeId();
        
        $filters = [
            'sede_id' => $request->get('sede_id', $sedeId),
            'rol_id' => $request->get('rol_id'),
            'estado_id' => $request->get('estado_id'),
            'search' => $request->get('search'),
            'per_page' => $request->get('per_page', 15)
        ];

        $usuarios = [];
        $pagination = null;

        if ($isOnline) {
            // ✅ MODO ONLINE
            Log::info('🌐 [FRONTEND] Modo online - obteniendo usuarios desde API');
            
            $response = $this->apiService->get('/usuarios', $filters);
            
            if ($response['success']) {
                $usuarios = $response['data'] ?? [];
                $pagination = $response['pagination'] ?? null;
                
                Log::info('✅ [FRONTEND] Usuarios obtenidos de la API', [
                    'total' => count($usuarios)
                ]);
            } else {
                Log::warning('⚠️ [FRONTEND] No se pudieron obtener usuarios de la API', [
                    'error' => $response['error'] ?? 'Error desconocido'
                ]);
            }
        } else {
            // ✅ MODO OFFLINE
            Log::info('📴 [FRONTEND] Modo offline - usuarios desde almacenamiento local');
            
            $usuarios = $this->offlineService->getAllUsuariosOffline($filters);
            
            Log::info('✅ [FRONTEND] Usuarios obtenidos offline', [
                'total' => count($usuarios)
            ]);
        }

        // ✅ OBTENER DATOS MAESTROS PARA FILTROS
        $masterData = $this->getMasterData();
        
        // ✅ EXTRAER DATOS ESPECÍFICOS PARA LA VISTA
        $especialidades = $masterData['especialidades'] ?? [];
        $sedes = $masterData['sedes'] ?? [];
        $roles = $masterData['roles'] ?? [];

        Log::info('✅ [FRONTEND] Vista de usuarios preparada', [
            'total_usuarios' => count($usuarios),
            'modo' => $isOnline ? 'online' : 'offline',
            'especialidades_count' => count($especialidades),
            'sedes_count' => count($sedes),
            'roles_count' => count($roles)
        ]);

        // ✅ PASAR TODAS LAS VARIABLES A LA VISTA
        return view('usuarios.index', [
            'usuarios' => $usuarios,
            'especialidades' => $especialidades,
            'sedes' => $sedes,
            'roles' => $roles,
            'offline' => !$isOnline,  // ✅ AGREGADO
            'isOnline' => $isOnline   // ✅ AGREGADO
        ]);

    } catch (\Exception $e) {
        Log::error('❌ [FRONTEND] Error en listado de usuarios', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return back()->with('error', 'Error al cargar usuarios: ' . $e->getMessage());
    }
}

    /**
 * Obtener todos los usuarios desde almacenamiento offline
 */
public function getUsuariosOffline(): array
{
    try {
        $usuarios = [];
        
        // ✅ BUSCAR EN SQLite PRIMERO
        if ($this->isSQLiteAvailable()) {
            $usuariosRaw = DB::connection('offline')->table('usuarios')
                ->orderBy('nombre_completo')
                ->get();
            
            if ($usuariosRaw->isNotEmpty()) {
                $usuarios = $usuariosRaw->map(function($usuario) {
                    $usuario = (array) $usuario;
                    return $this->enrichUsuarioRelations($usuario);
                })->toArray();
                
                Log::info('✅ Usuarios encontrados en SQLite offline', [
                    'cantidad' => count($usuarios)
                ]);
                
                return $usuarios;
            }
        }
        
        // ✅ FALLBACK A JSON
        $path = $this->storagePath . "/usuarios";
        
        if (is_dir($path)) {
            $archivos = glob($path . "/*.json");
            
            foreach ($archivos as $archivo) {
                // Saltar archivos de control
                if (strpos($archivo, 'master_data.json') !== false || 
                    strpos($archivo, 'sync_info.json') !== false ||
                    strpos($archivo, 'full_sync_status.json') !== false) {
                    continue;
                }
                
                $content = file_get_contents($archivo);
                $usuario = json_decode($content, true);
                
                if ($usuario && isset($usuario['uuid'])) {
                    $usuarios[] = $this->enrichUsuarioRelations($usuario);
                }
            }
            
            // Ordenar por nombre
            usort($usuarios, function($a, $b) {
                return strcmp($a['nombre_completo'] ?? '', $b['nombre_completo'] ?? '');
            });
            
            Log::info('✅ Usuarios encontrados en JSON offline', [
                'cantidad' => count($usuarios)
            ]);
        }

        return $usuarios;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo usuarios offline', [
            'error' => $e->getMessage()
        ]);
        return [];
    }
}

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        try {
            Log::info('📝 [FRONTEND] Cargando formulario de creación de usuario');

            $isOnline = $this->apiService->isOnline();
            
            if (!$isOnline) {
                Log::warning('⚠️ [FRONTEND] Intento de crear usuario sin conexión');
                
                return redirect()
                    ->route('usuarios.index')
                    ->withErrors(['error' => 'Debe estar en línea para crear usuarios']);
            }

            $usuario = $this->authService->usuario();
            $sedeActual = $this->authService->sedeActual();
            
            // Obtener datos maestros
            $masterData = $this->getMasterData();

            Log::info('✅ [FRONTEND] Formulario de creación cargado exitosamente');

            return view('usuarios.create', compact(
                'masterData',
                'sedeActual',
                'usuario',
                'isOnline'
            ));

        } catch (\Exception $e) {
            Log::error('❌ [FRONTEND] Error en usuarios.create', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withErrors([
                'error' => 'Error cargando formulario: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Guardar nuevo usuario
     */
    public function store(Request $request)
    {
        try {
            Log::info('📝 [FRONTEND] Iniciando creación de usuario', [
                'login' => $request->login,
                'documento' => $request->documento,
                'rol_id' => $request->rol_id,
                'especialidad_id' => $request->especialidad_id,
                'tiene_firma' => $request->filled('firma'),
                'longitud_firma' => $request->filled('firma') ? strlen($request->firma) : 0
            ]);

            if (!$this->apiService->isOnline()) {
                Log::warning('⚠️ [FRONTEND] Intento de crear usuario sin conexión');
                
                return back()
                    ->withErrors(['error' => 'Debe estar en línea para crear usuarios'])
                    ->withInput();
            }

            // Validación
            $validator = Validator::make($request->all(), [
                'sede_id' => 'required|integer',
                'documento' => 'required|string|max:15',
                'nombre' => 'required|string|max:50',
                'apellido' => 'required|string|max:50',
                'telefono' => 'required|string|max:10',
                'correo' => 'required|email|max:60',
                'login' => 'required|string|max:50',
                'password' => 'required|string|min:6|confirmed',
                'rol_id' => 'required|integer',
                'estado_id' => 'required|integer',
                'especialidad_id' => 'nullable|string', // ✅ Acepta UUID
                'registro_profesional' => 'nullable|string|max:50',
                'firma' => 'nullable|string',
            ], [
                'documento.required' => 'El documento es obligatorio',
                'nombre.required' => 'El nombre es obligatorio',
                'apellido.required' => 'El apellido es obligatorio',
                'correo.email' => 'El correo debe ser válido',
                'password.min' => 'La contraseña debe tener al menos 6 caracteres',
                'password.confirmed' => 'Las contraseñas no coinciden',
            ]);

            if ($validator->fails()) {
                Log::warning('⚠️ [FRONTEND] Errores de validación', [
                    'errores' => $validator->errors()->toArray()
                ]);

                return back()
                    ->withErrors($validator)
                    ->withInput();
            }

            // Preparar datos para enviar a la API
            $userData = $request->only([
                'sede_id', 'documento', 'nombre', 'apellido', 'telefono',
                'correo', 'login', 'password', 'password_confirmation',
                'rol_id', 'estado_id', 'registro_profesional'
            ]);
            
            // Manejar especialidad_id (UUID)
            if ($request->filled('especialidad_id')) {
                $userData['especialidad_id'] = $request->especialidad_id;
                
                Log::info('🔄 [FRONTEND] Especialidad ID incluida', [
                    'valor' => $request->especialidad_id,
                    'tipo' => gettype($request->especialidad_id),
                    'es_uuid' => $this->esUuid($request->especialidad_id)
                ]);
            }

            // ✅ Manejar la firma digital (base64)
            if ($request->filled('firma')) {
                $firmaBase64 = $request->firma;
                
                Log::info('🖼️ [FRONTEND] Procesando firma', [
                    'longitud_original' => strlen($firmaBase64),
                    'primeros_30_chars' => substr($firmaBase64, 0, 30)
                ]);
                
                // Verificar si la firma comienza con "data:image/"
                if (strpos($firmaBase64, 'data:image/') === 0) {
                    // Extraer la parte de base64 después del prefijo
                    $matches = [];
                    if (preg_match('/^data:image\/\w+;base64,(.+)$/', $firmaBase64, $matches)) {
                        // Usar solo la parte de base64 sin el prefijo
                        $userData['firma'] = $matches[1];
                        
                        Log::info('✅ [FRONTEND] Firma procesada - prefijo removido', [
                            'longitud_sin_prefijo' => strlen($userData['firma']),
                            'prefijo_detectado' => true
                        ]);
                    } else {
                        $userData['firma'] = $firmaBase64;
                        
                        Log::warning('⚠️ [FRONTEND] No se pudo extraer base64 del prefijo');
                    }
                } else {
                    $userData['firma'] = $firmaBase64;
                    
                    Log::info('ℹ️ [FRONTEND] Firma sin prefijo data:image', [
                        'longitud' => strlen($firmaBase64)
                    ]);
                }
            }

            // Log de datos a enviar
            Log::info('📤 [FRONTEND] Enviando datos a la API', [
                'login' => $userData['login'],
                'documento' => $userData['documento'],
                'rol_id' => $userData['rol_id'],
                'especialidad_id' => $userData['especialidad_id'] ?? 'no especificada',
                'tiene_firma' => isset($userData['firma']),
                'longitud_firma' => isset($userData['firma']) ? strlen($userData['firma']) : 0
            ]);

            // Enviar a la API
            $response = $this->apiService->post('/usuarios', $userData);

            Log::info('📥 [FRONTEND] Respuesta de la API recibida', [
                'success' => $response['success'] ?? false,
                'tiene_errores' => isset($response['errors']),
                'tiene_error' => isset($response['error'])
            ]);

            if ($response['success']) {
                $nombreCompleto = $userData['nombre'] . ' ' . $userData['apellido'];
                
                Log::info('✅ [FRONTEND] Usuario creado exitosamente', [
                    'usuario_creado' => $response['data']['login'] ?? $userData['login'],
                    'nombre_completo' => $nombreCompleto,
                    'creado_por' => $this->authService->usuario()['login'] ?? 'unknown'
                ]);

                return redirect()
                    ->route('usuarios.index')
                    ->with('success', "✅ Usuario '{$nombreCompleto}' creado exitosamente con login: {$userData['login']}");
            }

            // Manejar errores de la API
            $errorMessage = 'Error al crear usuario';
            
            if (isset($response['errors'])) {
                Log::warning('⚠️ [FRONTEND] Errores de validación de la API', [
                    'errors' => $response['errors']
                ]);
                
                return back()
                    ->withErrors($response['errors'])
                    ->withInput();
            }
            
            if (isset($response['error'])) {
                $errorMessage = $response['error'];
                
                Log::error('❌ [FRONTEND] Error de la API al crear usuario', [
                    'error' => $errorMessage
                ]);
            }

            return back()
                ->withErrors(['error' => $errorMessage])
                ->withInput();

        } catch (\Exception $e) {
            Log::error('❌ [FRONTEND] Error creando usuario', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return back()
                ->withErrors(['error' => 'Error interno del sistema: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
 * Mostrar detalle de usuario
 */
public function show(string $uuid)
{
    try {
        Log::info('🔍 [FRONTEND] Mostrando detalle de usuario', ['uuid' => $uuid]);

        $isOnline = $this->apiService->isOnline();

        if ($isOnline) {
            $response = $this->apiService->get("/usuarios/{$uuid}");
            
            if (!$response['success']) {
                Log::warning('⚠️ [FRONTEND] Usuario no encontrado en API', ['uuid' => $uuid]);
                
                return redirect()
                    ->route('usuarios.index')
                    ->withErrors(['error' => 'Usuario no encontrado']);
            }
            
            $usuario = $response['data'];
            
            // ✅ Log detallado de la firma
            Log::info('✅ [FRONTEND] Usuario obtenido de la API', [
                'uuid' => $uuid,
                'login' => $usuario['login'] ?? 'unknown',
                'tiene_firma' => $usuario['tiene_firma'] ?? false,
                'firma_presente' => isset($usuario['firma']),
                'firma_longitud' => isset($usuario['firma']) ? strlen($usuario['firma']) : 0,
                'firma_tiene_prefijo' => isset($usuario['firma']) && strpos($usuario['firma'], 'data:image/') === 0
            ]);
            
        } else {
            $usuario = $this->getUsuarioOffline($uuid);
            
            if (!$usuario) {
                Log::warning('⚠️ [FRONTEND] Usuario no encontrado offline', ['uuid' => $uuid]);
                
                return redirect()
                    ->route('usuarios.index')
                    ->withErrors(['error' => 'Usuario no encontrado offline']);
            }
            
            Log::info('📴 [FRONTEND] Usuario obtenido offline', ['uuid' => $uuid]);
        }

        return view('usuarios.show', compact('usuario', 'isOnline'));

    } catch (\Exception $e) {
        Log::error('❌ [FRONTEND] Error mostrando usuario', [
            'uuid' => $uuid,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return redirect()
            ->route('usuarios.index')
            ->withErrors(['error' => 'Error cargando usuario']);
    }
}



    /**
     * Mostrar formulario de edición
     */
    public function edit(string $uuid)
    {
        try {
            Log::info('✏️ [FRONTEND] Cargando formulario de edición', ['uuid' => $uuid]);

            $isOnline = $this->apiService->isOnline();

            if (!$isOnline) {
                Log::warning('⚠️ [FRONTEND] Intento de editar usuario sin conexión', ['uuid' => $uuid]);
                
                return redirect()
                    ->route('usuarios.index')
                    ->withErrors(['error' => 'Debe estar en línea para editar usuarios']);
            }

            $response = $this->apiService->get("/usuarios/{$uuid}");
            
            if (!$response['success']) {
                Log::warning('⚠️ [FRONTEND] Usuario no encontrado para edición', ['uuid' => $uuid]);
                
                return redirect()
                    ->route('usuarios.index')
                    ->withErrors(['error' => 'Usuario no encontrado']);
            }

            $usuario = $response['data'];
            $masterData = $this->getMasterData();

            Log::info('✅ [FRONTEND] Formulario de edición cargado', [
                'uuid' => $uuid,
                'login' => $usuario['login'] ?? 'unknown'
            ]);

            return view('usuarios.edit', compact('usuario', 'masterData', 'isOnline'));

        } catch (\Exception $e) {
            Log::error('❌ [FRONTEND] Error en usuarios.edit', [
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);
            
            return redirect()
                ->route('usuarios.index')
                ->withErrors(['error' => 'Error cargando formulario de edición']);
        }
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, string $uuid)
    {
        try {
            Log::info('📝 [FRONTEND] Iniciando actualización de usuario', [
                'uuid' => $uuid,
                'campos' => array_keys($request->except(['_token', '_method'])),
                'tiene_firma' => $request->filled('firma'),
                'eliminar_firma' => $request->boolean('eliminar_firma')
            ]);

            if (!$this->apiService->isOnline()) {
                Log::warning('⚠️ [FRONTEND] Intento de actualizar usuario sin conexión', ['uuid' => $uuid]);
                
                return back()
                    ->withErrors(['error' => 'Debe estar en línea para actualizar usuarios'])
                    ->withInput();
            }

            $validator = Validator::make($request->all(), [
                'sede_id' => 'sometimes|required|integer',
                'documento' => 'sometimes|required|string|max:15',
                'nombre' => 'sometimes|required|string|max:50',
                'apellido' => 'sometimes|required|string|max:50',
                'telefono' => 'sometimes|required|string|max:10',
                'correo' => 'sometimes|required|email|max:60',
                'login' => 'sometimes|required|string|max:50',
                'password' => 'nullable|string|min:6|confirmed',
                'rol_id' => 'sometimes|required|integer',
                'estado_id' => 'sometimes|required|integer',
                'especialidad_id' => 'nullable|string', // ✅ Acepta UUID
                'registro_profesional' => 'nullable|string|max:50',
                'firma' => 'nullable|string',
                'eliminar_firma' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                Log::warning('⚠️ [FRONTEND] Errores de validación en actualización', [
                    'uuid' => $uuid,
                    'errores' => $validator->errors()->toArray()
                ]);

                return back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $userData = $request->except(['_token', '_method']);

            // ✅ Manejar la firma digital (base64) si existe
            if ($request->filled('firma')) {
                $firmaBase64 = $request->firma;
                
                Log::info('🖼️ [FRONTEND] Procesando firma en actualización', [
                    'uuid' => $uuid,
                    'longitud' => strlen($firmaBase64)
                ]);
                
                // Verificar si la firma comienza con "data:image/"
                if (strpos($firmaBase64, 'data:image/') === 0) {
                    // Extraer la parte de base64 después del prefijo
                    $matches = [];
                    if (preg_match('/^data:image\/\w+;base64,(.+)$/', $firmaBase64, $matches)) {
                        // Usar solo la parte de base64 sin el prefijo
                        $userData['firma'] = $matches[1];
                        
                        Log::info('✅ [FRONTEND] Firma actualizada - prefijo removido', [
                            'uuid' => $uuid,
                            'longitud_sin_prefijo' => strlen($userData['firma'])
                        ]);
                    } else {
                        $userData['firma'] = $firmaBase64;
                        
                        Log::warning('⚠️ [FRONTEND] No se pudo extraer base64 en actualización');
                    }
                } else {
                    $userData['firma'] = $firmaBase64;
                    
                    Log::info('ℹ️ [FRONTEND] Firma actualizada sin prefijo', [
                        'uuid' => $uuid,
                        'longitud' => strlen($firmaBase64)
                    ]);
                }
            }

            // Log para especialidad
            if ($request->filled('especialidad_id')) {
                Log::info('🔄 [FRONTEND] Especialidad ID en actualización', [
                    'uuid' => $uuid,
                    'especialidad_id' => $request->especialidad_id,
                    'es_uuid' => $this->esUuid($request->especialidad_id)
                ]);
            }

            // Si se solicita eliminar la firma
            if ($request->has('eliminar_firma') && $request->eliminar_firma) {
                $userData['eliminar_firma'] = true;
                Log::info('🗑️ [FRONTEND] Solicitud de eliminación de firma', ['uuid' => $uuid]);
            }

            Log::info('📤 [FRONTEND] Enviando actualización a la API', [
                'uuid' => $uuid,
                'campos_actualizados' => array_keys($userData)
            ]);

            $response = $this->apiService->put("/usuarios/{$uuid}", $userData);

            Log::info('📥 [FRONTEND] Respuesta de actualización recibida', [
                'uuid' => $uuid,
                'success' => $response['success'] ?? false
            ]);

            if ($response['success']) {
                Log::info('✅ [FRONTEND] Usuario actualizado exitosamente', ['uuid' => $uuid]);
                
                return redirect()
                    ->route('usuarios.index')
                    ->with('success', 'Usuario actualizado exitosamente');
            }

            if (isset($response['errors'])) {
                Log::warning('⚠️ [FRONTEND] Errores de la API en actualización', [
                    'uuid' => $uuid,
                    'errors' => $response['errors']
                ]);
                
                return back()
                    ->withErrors($response['errors'])
                    ->withInput();
            }

            Log::error('❌ [FRONTEND] Error actualizando usuario', [
                'uuid' => $uuid,
                'error' => $response['error'] ?? 'Error desconocido'
            ]);

            return back()
                ->withErrors(['error' => $response['error'] ?? 'Error al actualizar usuario'])
                ->withInput();

        } catch (\Exception $e) {
            Log::error('❌ [FRONTEND] Error actualizando usuario', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withErrors(['error' => 'Error interno del sistema'])
                ->withInput();
        }
    }

    /**
     * Eliminar usuario
     */
    public function destroy(string $uuid)
    {
        try {
            Log::info('🗑️ [FRONTEND] Eliminando usuario', ['uuid' => $uuid]);

            if (!$this->apiService->isOnline()) {
                Log::warning('⚠️ [FRONTEND] Intento de eliminar usuario sin conexión', ['uuid' => $uuid]);
                
                return back()->withErrors([
                    'error' => 'Debe estar en línea para eliminar usuarios'
                ]);
            }

            $response = $this->apiService->delete("/usuarios/{$uuid}");

            if ($response['success']) {
                Log::info('✅ [FRONTEND] Usuario eliminado exitosamente', ['uuid' => $uuid]);
                
                return redirect()
                    ->route('usuarios.index')
                    ->with('success', 'Usuario eliminado exitosamente');
            }

            Log::error('❌ [FRONTEND] Error eliminando usuario', [
                'uuid' => $uuid,
                'error' => $response['error'] ?? 'Error desconocido'
            ]);

            return back()->withErrors([
                'error' => $response['error'] ?? 'Error al eliminar usuario'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [FRONTEND] Error eliminando usuario', [
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors([
                'error' => 'Error interno del sistema'
            ]);
        }
    }

    /**
     * ✅ Validar si un string es UUID
     */
    private function esUuid(string $valor): bool
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        return preg_match($pattern, $valor) === 1;
    }

    /**
     * Obtener datos maestros
     */
    private function getMasterData(): array
    {
        try {
            Log::info('📊 [FRONTEND] Obteniendo master data');

            if ($this->apiService->isOnline()) {
                $response = $this->apiService->get('/master-data/all');
                
                Log::info('📥 [FRONTEND] Respuesta de master-data/all', [
                    'success' => $response['success'] ?? false,
                    'has_data' => isset($response['data']),
                    'data_keys' => isset($response['data']) ? array_keys($response['data']) : []
                ]);
                
                if ($response['success'] && isset($response['data'])) {
                    $masterData = $response['data'];
                    
                    // Verificar y completar roles si están vacíos
                    if (empty($masterData['roles'])) {
                        Log::warning('⚠️ [FRONTEND] Master data sin roles, obteniendo directamente');
                        $roles = $this->getRolesFromApi();
                        if (!empty($roles)) {
                            $masterData['roles'] = $roles;
                        }
                    }
                    
                    // Verificar y completar especialidades si están vacías
                    if (empty($masterData['especialidades'])) {
                        Log::warning('⚠️ [FRONTEND] Master data sin especialidades, obteniendo directamente');
                        $especialidades = $this->getEspecialidadesFromApi();
                        if (!empty($especialidades)) {
                            $masterData['especialidades'] = $especialidades;
                
                        }
                    }
                    
                    Log::info('✅ [FRONTEND] Master data obtenida exitosamente', [
                        'roles_count' => count($masterData['roles'] ?? []),
                        'especialidades_count' => count($masterData['especialidades'] ?? []),
                        'estados_count' => count($masterData['estados'] ?? []),
                        'sedes_count' => count($masterData['sedes'] ?? [])
                    ]);
                    
                    return $masterData;
                }
            }

            // Fallback a datos offline
            Log::info('📴 [FRONTEND] Usando datos offline para master data');
            $offlineService = app(\App\Services\OfflineService::class);
            $masterData = $offlineService->getMasterDataOffline();
            
            // Intentar completar roles y especialidades incluso en modo offline
            if (empty($masterData['roles'])) {
                $roles = $this->getRolesFromApi();
                if (!empty($roles)) {
                    $masterData['roles'] = $roles;
                }
            }
            
            if (empty($masterData['especialidades'])) {
                $especialidades = $this->getEspecialidadesFromApi();
                if (!empty($especialidades)) {
                    $masterData['especialidades'] = $especialidades;
                }
            }
            
            return $masterData;

        } catch (\Exception $e) {
            Log::error('❌ [FRONTEND] Error obteniendo master data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Retornar estructura mínima con datos por defecto
            return $this->getDefaultMasterData();
        }
    }
    
    /**
     * Obtener roles directamente de la API
     */
    private function getRolesFromApi(): array
    {
        try {
            Log::info('🔄 [FRONTEND] Obteniendo roles directamente');

            $response = $this->apiService->get('/master-data/roles');
            
            Log::info('📥 [FRONTEND] Respuesta de /master-data/roles', [
                'success' => $response['success'] ?? false,
                'has_data' => isset($response['data']),
                'data_type' => isset($response['data']) ? gettype($response['data']) : 'null',
                'data_count' => isset($response['data']) && is_array($response['data']) ? count($response['data']) : 0
            ]);
            
            if (isset($response['success']) && $response['success'] && isset($response['data'])) {
                if (is_array($response['data']) && !empty($response['data'])) {
                    Log::info('✅ [FRONTEND] Roles obtenidos exitosamente', [
                        'count' => count($response['data'])
                    ]);
                    return $response['data'];
                }
            }
            
            Log::warning('⚠️ [FRONTEND] No se pudieron obtener roles, usando valores por defecto');
            return $this->getDefaultRoles();
            
        } catch (\Exception $e) {
            Log::error('❌ [FRONTEND] Error obteniendo roles directamente', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->getDefaultRoles();
        }
    }
    
    /**
     * Obtener especialidades directamente de la API
     */
    private function getEspecialidadesFromApi(): array
    {
        try {
            Log::info('🔄 [FRONTEND] Obteniendo especialidades directamente');

            $response = $this->apiService->get('/master-data/especialidades');
            
            Log::info('📥 [FRONTEND] Respuesta de /master-data/especialidades', [
                'success' => $response['success'] ?? false,
                'has_data' => isset($response['data']),
                'data_type' => isset($response['data']) ? gettype($response['data']) : 'null',
                'data_count' => isset($response['data']) && is_array($response['data']) ? count($response['data']) : 0
            ]);
            
            if (isset($response['success']) && $response['success'] && isset($response['data'])) {
                if (is_array($response['data']) && !empty($response['data'])) {
                    Log::info('✅ [FRONTEND] Especialidades obtenidas exitosamente', [
                        'count' => count($response['data'])
                    ]);
                    return $response['data'];
                }
            }
            
            Log::warning('⚠️ [FRONTEND] No se pudieron obtener especialidades');
            return [];
            
        } catch (\Exception $e) {
            Log::error('❌ [FRONTEND] Error obteniendo especialidades directamente', [
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Obtener datos maestros por defecto
     */
    private function getDefaultMasterData(): array
    {
        Log::info('📋 [FRONTEND] Usando master data por defecto');

        return [
            'roles' => $this->getDefaultRoles(),
            'especialidades' => [],
            'estados' => [
                ['id' => 1, 'nombre' => 'ACTIVO'],
                ['id' => 2, 'nombre' => 'INACTIVO']
            ],
            'sedes' => []
        ];
    }
    
    /**
     * Obtener roles por defecto
     */
    private function getDefaultRoles(): array
    {
        return [
            ['id' => 1, 'nombre' => 'ADMINISTRADOR'],
            ['id' => 2, 'nombre' => 'MÉDICO'],
            ['id' => 3, 'nombre' => 'ENFERMERA'],
            ['id' => 4, 'nombre' => 'RECEPCIONISTA']
        ];
    }

    /**
     * Obtener usuarios offline
     */

    /**
     * Obtener usuario offline por UUID
     */
private function getUsuarioOffline(string $uuid): ?array
{
    Log::info('📴 [FRONTEND] Obteniendo usuario offline', ['uuid' => $uuid]);
    
    $offlineService = app(\App\Services\OfflineService::class);
    return $offlineService->getUsuarioByUuid($uuid);
}

/**
 * Mostrar vista de sincronización
 */
public function sincronizar()
{
    $sedes = $this->apiService->get('/master-data/sedes')['data'] ?? [];
    return view('usuarios.sincronizar', compact('sedes'));
}

/**
 * Ejecutar sincronización
 */
public function ejecutarSincronizacion(Request $request)
{
    try {
        $sedeId = $request->sede_id;
        $filters = $sedeId ? ['sede_id' => $sedeId] : [];

        $response = $this->apiService->get('/usuarios/all', $filters);

        if (!$response['success']) {
            return back()->withErrors(['error' => 'Error obteniendo usuarios de la API']);
        }

        $usuarios = $response['data'] ?? [];
        $total = count($usuarios);
        $conFirma = 0;

        foreach ($usuarios as $usuario) {
            $this->offlineService->storeUsuarioCompleto($usuario);
            
            if (!empty($usuario['firma'])) {
                $conFirma++;
            }
        }

        return redirect()
            ->route('usuarios.index')
            ->with('success', "✅ Sincronizados {$total} usuarios ({$conFirma} con firma)");

    } catch (\Exception $e) {
        Log::error('Error en sincronización manual', ['error' => $e->getMessage()]);
        
        return back()->withErrors(['error' => 'Error en sincronización: ' . $e->getMessage()]);
    }
}

}
