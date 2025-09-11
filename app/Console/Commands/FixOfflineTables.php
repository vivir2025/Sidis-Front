<?php
// app/Console/Commands/FixOfflineTables.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OfflineService;
use Illuminate\Support\Facades\DB;

class FixOfflineTables extends Command
{
    protected $signature = 'offline:fix-tables';
    protected $description = 'Corregir estructura de tablas offline';

    public function handle(OfflineService $offlineService)
    {
        $this->info('🔧 Corrigiendo estructura de tablas offline...');
        
        try {
            // 1. Verificar SQLite
            if (!$offlineService->isSQLiteAvailable()) {
                $this->error('❌ SQLite no disponible');
                return 1;
            }
            
            // 2. Recrear tabla cups_contratados
            $this->info('🔄 Recreando tabla cups_contratados...');
            DB::connection('offline')->statement('DROP TABLE IF EXISTS cups_contratados');
            $this->createCupsContratadosTable();
            $this->info('✅ Tabla cups_contratados recreada');
            
            // 3. Recrear tabla citas
            $this->info('🔄 Recreando tabla citas...');
            DB::connection('offline')->statement('DROP TABLE IF EXISTS citas');
            $this->createCitasTable();
            $this->info('✅ Tabla citas recreada');
            
            $this->info('🎉 Tablas corregidas exitosamente');
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
    
    private function createCupsContratadosTable(): void
    {
        DB::connection('offline')->statement('
            CREATE TABLE cups_contratados (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                contrato_id INTEGER,
                contrato_uuid TEXT,
                categoria_cups_id INTEGER,
                cups_id INTEGER,
                cups_uuid TEXT,
                cups_codigo TEXT,
                cups_nombre TEXT,
                tarifa TEXT,
                estado TEXT DEFAULT "ACTIVO",
                contrato_fecha_inicio DATE,
                contrato_fecha_fin DATE,
                contrato_estado TEXT,
                empresa_nombre TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
        
        // Índices
        DB::connection('offline')->statement('CREATE INDEX idx_cups_contratados_cups_uuid ON cups_contratados(cups_uuid)');
        DB::connection('offline')->statement('CREATE INDEX idx_cups_contratados_estado ON cups_contratados(estado)');
        DB::connection('offline')->statement('CREATE INDEX idx_cups_contratados_cups_codigo ON cups_contratados(cups_codigo)');
    }
    
    private function createCitasTable(): void
    {
        DB::connection('offline')->statement('
            CREATE TABLE citas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                sede_id INTEGER NOT NULL,
                fecha DATE NOT NULL,
                fecha_inicio DATETIME NOT NULL,
                fecha_final DATETIME NOT NULL,
                fecha_deseada DATE,
                motivo TEXT,
                nota TEXT,
                estado TEXT NOT NULL,
                patologia TEXT,
                paciente_id INTEGER,
                paciente_uuid TEXT,
                agenda_id INTEGER,
                agenda_uuid TEXT,
                cups_contratado_id TEXT,
                cups_contratado_uuid TEXT,
                usuario_creo_cita_id INTEGER,
                sync_status TEXT DEFAULT "synced",
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL
            )
        ');
    }
}
