<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;

class QRDiagnosticController extends Controller
{
    public function testQR()
    {
        try {
            // Test 1: Generar QR simple
            $testUrl = url('/test-qr');
            
            // Intentar diferentes configuraciones
            $results = [];
            
            // Test 1: QR simple
            try {
                $qr1 = QrCode::size(200)->generate($testUrl);
                $results['test1'] = [
                    'success' => true,
                    'size' => strlen($qr1),
                    'method' => 'QrCode::size()->generate()'
                ];
            } catch (\Exception $e) {
                $results['test1'] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'method' => 'QrCode::size()->generate()'
                ];
            }
            
            // Test 2: QR con formato
            try {
                $qr2 = QrCode::format('png')->size(200)->generate($testUrl);
                $results['test2'] = [
                    'success' => true,
                    'size' => strlen($qr2),
                    'method' => 'QrCode::format()->size()->generate()'
                ];
            } catch (\Exception $e) {
                $results['test2'] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'method' => 'QrCode::format()->size()->generate()'
                ];
            }
            
            // Test 3: QR con formato, tamaño y margen
            try {
                $qr3 = QrCode::format('png')->size(300)->margin(2)->generate($testUrl);
                $results['test3'] = [
                    'success' => true,
                    'size' => strlen($qr3),
                    'method' => 'QrCode::format()->size()->margin()->generate()'
                ];
            } catch (\Exception $e) {
                $results['test3'] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'method' => 'QrCode::format()->size()->margin()->generate()'
                ];
            }
            
            // Test 4: QR con base64
            try {
                $qr4 = base64_encode(QrCode::format('png')->size(300)->margin(2)->generate($testUrl));
                $results['test4'] = [
                    'success' => true,
                    'size' => strlen($qr4),
                    'method' => 'base64_encode(QrCode::format()->size()->margin()->generate())',
                    'preview' => 'data:image/png;base64,' . substr($qr4, 0, 100) . '...'
                ];
            } catch (\Exception $e) {
                $results['test4'] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'method' => 'base64_encode(QrCode::format()->size()->margin()->generate())'
                ];
            }
            
            // Test 5: Verificar si existe la clase
            $results['class_exists'] = [
                'QrCode' => class_exists('SimpleSoftwareIO\QrCode\Facades\QrCode'),
                'QrCodeGenerator' => class_exists('SimpleSoftwareIO\QrCode\Generator')
            ];
            
            // Test 6: Verificar versión del paquete
            $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);
            $qrPackageInfo = null;
            
            foreach ($composerLock['packages'] as $package) {
                if ($package['name'] === 'simplesoftwareio/simple-qrcode') {
                    $qrPackageInfo = $package;
                    break;
                }
            }
            
            $results['package_info'] = $qrPackageInfo;
            
            // Generar una imagen QR para mostrar en la vista
            $qrImageBase64 = null;
            if ($results['test4']['success']) {
                $qrImageBase64 = 'data:image/png;base64,' . $qr4;
            }
            
            return view('diagnostics.qr-test', [
                'results' => $results,
                'qrImage' => $qrImageBase64,
                'testUrl' => $testUrl
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en diagnóstico de QR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}