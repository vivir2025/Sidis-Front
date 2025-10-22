<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, Log};
use Illuminate\Support\Str;

class FirmaQRController extends Controller
{
    /**
     * Generar datos para firma con QR
     */
    public function generarQR(Request $request)
    {
        try {
            // Log para depuración
            Log::info('🔄 Iniciando generación de datos para firma QR', [
                'usuario_id' => auth()->id(),
                'ip' => $request->ip()
            ]);
            
            // Generar token único
            $sessionToken = 'firma_' . time() . '_' . Str::random(10);
            
            // URL para firmar desde el celular
            $urlFirma = route('firma.movil', ['token' => $sessionToken]);
            
            // Log para depuración
            Log::info('🔗 URL para firma móvil generada', [
                'url' => $urlFirma,
                'token' => $sessionToken
            ]);
            
            // Guardar sesión en cache (expira en 5 minutos)
            Cache::put("firma_session_{$sessionToken}", [
                'usuario_id' => auth()->id(),
                'firmado' => false,
                'firma' => null,
                'created_at' => now()->toDateTimeString()
            ], 300); // 5 minutos
            
            Log::info('✅ Datos para QR generados correctamente', [
                'session_token' => $sessionToken,
                'usuario_id' => auth()->id(),
                'expira_en' => '5 minutos'
            ]);
            
            // ✅ DEVOLVER JSON PARA EL MODAL
            return response()->json([
                'success' => true,
                'session_id' => $sessionToken,
                'url' => $urlFirma,
                'expira_en' => 300 // segundos
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error generando datos para QR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error generando datos para QR: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verificar si se firmó desde el celular
     */
    public function verificarFirma(string $token)
    {
        try {
            $session = Cache::get("firma_session_{$token}");
            
            if (!$session) {
                Log::warning('⚠️ Sesión no encontrada al verificar firma', [
                    'token' => $token
                ]);
                
                return response()->json([
                    'firmado' => false,
                    'error' => 'Sesión expirada'
                ]);
            }
            
            Log::info('🔍 Verificando estado de firma', [
                'token' => $token,
                'firmado' => $session['firmado']
            ]);
            
            return response()->json([
                'firmado' => $session['firmado'],
                'firma' => $session['firma'] ?? null
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error verificando firma', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'firmado' => false,
                'error' => 'Error verificando firma'
            ], 500);
        }
    }
    
 /**
 * Mostrar página móvil para firmar
 */
public function mostrarPaginaMovil($token)
{
    try {
        Log::info('📱 Accediendo a página móvil de firma', [
            'token' => $token,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
        
        // Verificar que la sesión existe en caché
        $sessionData = Cache::get("firma_session_{$token}");
        
        if (!$sessionData) {
            Log::warning('⚠️ Sesión no encontrada o expirada', [
                'token' => $token
            ]);
            
            return view('usuarios.firma-movil-expirada', [
                'token' => $token,
                'mensaje' => 'La sesión ha expirado o no es válida. Por favor, genera un nuevo código QR.'
            ]);
        }
        
        Log::info('✅ Sesión válida encontrada', [
            'token' => $token,
            'created_at' => $sessionData['created_at']
        ]);
        
        // Mostrar vista de firma móvil
        return view('usuarios.firma-movil', [
            'token' => $token,
            'sessionData' => $sessionData
        ]);
        
    } catch (\Exception $e) {
        Log::error('❌ Error mostrando página móvil', [
            'token' => $token,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return view('usuarios.firma-movil-expirada', [
            'token' => $token,
            'mensaje' => 'Error al cargar la página de firma: ' . $e->getMessage()
        ]);
    }
}

    
    /**
     * Guardar firma desde celular
     */
    public function guardarFirmaMovil(Request $request, string $token)
    {
        try {
            Log::info('💾 Intentando guardar firma desde móvil', [
                'token' => $token,
                'ip' => $request->ip()
            ]);
            
            $session = Cache::get("firma_session_{$token}");
            
            if (!$session) {
                Log::warning('⚠️ Sesión expirada al intentar guardar firma', [
                    'token' => $token
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Sesión expirada'
                ], 400);
            }
            
            if ($session['firmado']) {
                Log::warning('⚠️ Intento de firmar sesión ya firmada', [
                    'token' => $token
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Ya se firmó anteriormente'
                ], 400);
            }
            
            $request->validate([
                'firma' => 'required|string'
            ]);
            
            // Actualizar sesión
            $session['firmado'] = true;
            $session['firma'] = $request->firma;
            $session['firmado_at'] = now()->toDateTimeString();
            
            Cache::put("firma_session_{$token}", $session, 300);
            
            Log::info('✅ Firma guardada exitosamente desde móvil', [
                'token' => $token,
                'usuario_id' => $session['usuario_id'],
                'firmado_at' => $session['firmado_at']
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Firma guardada exitosamente'
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Error de validación al guardar firma', [
                'token' => $token,
                'errors' => $e->errors()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Datos de firma inválidos',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('❌ Error guardando firma móvil', [
                'token' => $token,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error guardando firma'
            ], 500);
        }
    }
}
