<?php
// app/Console/Commands/LimpiarCupsContratadosInvalidos.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LimpiarCupsContratadosInvalidos extends Command
{
    protected $signature = 'citas:limpiar-cups-invalidos {--dry-run : Solo mostrar sin actualizar}';
    protected $description = 'Limpia UUIDs inválidos de cups_contratado en citas (desde API o SQLite)';

    public function handle()
    {
        $this->info('🔍 Buscando citas con CUPS contratado inválido...');
        
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('⚠️ Modo DRY-RUN: No se realizarán cambios');
        }

        // ✅ OPCIÓN 1: Limpiar desde SQLite (almacenamiento offline)
        if ($this->limpiarDesdeSQLite($dryRun)) {
            $this->info('✅ Limpieza desde SQLite completada');
        }

        // ✅ OPCIÓN 2: Limpiar desde API (si está online)
        if ($this->limpiarDesdeAPI($dryRun)) {
            $this->info('✅ Limpieza desde API completada');
        }

        return 0;
    }

    /**
     * Limpiar citas inválidas desde SQLite
     */
    private function limpiarDesdeSQLite(bool $dryRun): bool
    {
        try {
            $this->line("\n📦 Verificando SQLite...");

            // Verificar si SQLite está disponible
            $sqlitePath = storage_path('app/offline/database.sqlite');
            
            if (!file_exists($sqlitePath)) {
                $this->warn('⚠️ Base de datos SQLite no encontrada');
                return false;
            }

            // Buscar citas con UUIDs inválidos
            $citasInvalidas = DB::connection('offline')
                ->table('citas')
                ->whereNotNull('cups_contratado_uuid')
                ->where('cups_contratado_uuid', '!=', '')
                ->get()
                ->filter(function ($cita) {
                    // Filtrar UUIDs que NO cumplan el formato correcto
                    return !$this->isValidUUID($cita->cups_contratado_uuid);
                });

            if ($citasInvalidas->isEmpty()) {
                $this->info('✅ No se encontraron citas con CUPS inválido en SQLite');
                return true;
            }

            $this->warn("⚠️ Encontradas {$citasInvalidas->count()} citas con CUPS inválido en SQLite");

            $bar = $this->output->createProgressBar($citasInvalidas->count());

            foreach ($citasInvalidas as $cita) {
                $this->newLine();
                $this->line("📋 Cita UUID: {$cita->uuid}");
                $this->line("   CUPS inválido: {$cita->cups_contratado_uuid}");
                $this->line("   Fecha: {$cita->fecha}");
                $this->line("   Estado: {$cita->estado}");

                if (!$dryRun) {
                    // Actualizar a NULL
                    DB::connection('offline')
                        ->table('citas')
                        ->where('uuid', $cita->uuid)
                        ->update([
                            'cups_contratado_uuid' => null,
                            'updated_at' => now()->toISOString()
                        ]);

                    $this->info("   ✅ Actualizado a NULL");
                    
                    Log::info('🧹 CUPS inválido limpiado en SQLite', [
                        'cita_uuid' => $cita->uuid,
                        'cups_invalido' => $cita->cups_contratado_uuid
                    ]);
                } else {
                    $this->comment("   🔍 [DRY-RUN] Se establecería a NULL");
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            return true;

        } catch (\Exception $e) {
            $this->error("❌ Error limpiando SQLite: {$e->getMessage()}");
            Log::error('Error en limpieza SQLite', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Limpiar citas inválidas desde API
     */
    private function limpiarDesdeAPI(bool $dryRun): bool
    {
        try {
            $this->line("\n🌐 Verificando API...");

            $apiUrl = config('app.api_url');
            
            if (!$apiUrl) {
                $this->warn('⚠️ URL de API no configurada');
                return false;
            }

            // Verificar conectividad
            if (!$this->checkAPIConnection($apiUrl)) {
                $this->warn('⚠️ API no disponible');
                return false;
            }

            $this->info('✅ API disponible, obteniendo citas...');

            // Obtener citas desde la API
            $response = Http::withToken(session('api_token'))
                ->get("{$apiUrl}/api/citas", [
                    'per_page' => 1000,
                    'with_deleted' => false
                ]);

            if (!$response->successful()) {
                $this->error('❌ Error obteniendo citas desde API');
                return false;
            }

            $citas = collect($response->json('data', []));
            
            // Filtrar citas con CUPS inválido
            $citasInvalidas = $citas->filter(function ($cita) {
                return !empty($cita['cups_contratado_uuid']) && 
                       !$this->isValidUUID($cita['cups_contratado_uuid']);
            });

            if ($citasInvalidas->isEmpty()) {
                $this->info('✅ No se encontraron citas con CUPS inválido en API');
                return true;
            }

            $this->warn("⚠️ Encontradas {$citasInvalidas->count()} citas con CUPS inválido en API");

            $bar = $this->output->createProgressBar($citasInvalidas->count());

            foreach ($citasInvalidas as $cita) {
                $this->newLine();
                $this->line("📋 Cita UUID: {$cita['uuid']}");
                $this->line("   CUPS inválido: {$cita['cups_contratado_uuid']}");
                $this->line("   Fecha: {$cita['fecha']}");
                $this->line("   Estado: {$cita['estado']}");

                if (!$dryRun) {
                    // Actualizar vía API
                    $updateResponse = Http::withToken(session('api_token'))
                        ->patch("{$apiUrl}/api/citas/{$cita['uuid']}", [
                            'cups_contratado_uuid' => null
                        ]);

                    if ($updateResponse->successful()) {
                        $this->info("   ✅ Actualizado en API");
                        
                        Log::info('🧹 CUPS inválido limpiado en API', [
                            'cita_uuid' => $cita['uuid'],
                            'cups_invalido' => $cita['cups_contratado_uuid']
                        ]);
                    } else {
                        $this->error("   ❌ Error actualizando en API");
                    }
                } else {
                    $this->comment("   🔍 [DRY-RUN] Se establecería a NULL en API");
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            return true;

        } catch (\Exception $e) {
            $this->error("❌ Error limpiando desde API: {$e->getMessage()}");
            Log::error('Error en limpieza API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Validar formato UUID
     */
    private function isValidUUID(string $uuid): bool
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        return preg_match($pattern, $uuid) === 1;
    }

    /**
     * Verificar conexión con API
     */
    private function checkAPIConnection(string $apiUrl): bool
    {
        try {
            $response = Http::timeout(5)->get("{$apiUrl}/api/health");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
