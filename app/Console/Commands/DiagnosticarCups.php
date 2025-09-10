<?php
// php artisan make:command DiagnosticarCups

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Cups, CupsContratado, Contrato};

class DiagnosticarCups extends Command
{
    protected $signature = 'cups:diagnosticar {codigo}';
    protected $description = 'Diagnosticar CUPS y sus contratos';

    public function handle()
    {
        $codigo = $this->argument('codigo');
        
        // 1. Buscar CUPS
        $cups = Cups::where('codigo', $codigo)->first();
        
        if (!$cups) {
            $this->error("❌ CUPS con código {$codigo} no encontrado");
            return;
        }
        
        $this->info("✅ CUPS encontrado:");
        $this->table(['Campo', 'Valor'], [
            ['UUID', $cups->uuid],
            ['Código', $cups->codigo],
            ['Nombre', $cups->nombre],
            ['Estado', $cups->estado]
        ]);
        
        // 2. Buscar CUPS contratados
        $cupsContratados = CupsContratado::with(['contrato'])
            ->where('cups_id', $cups->id)
            ->get();
        
        $this->info("\n📋 CUPS Contratados encontrados: " . $cupsContratados->count());
        
        if ($cupsContratados->count() > 0) {
            $data = $cupsContratados->map(function($cc) {
                return [
                    $cc->uuid,
                    $cc->estado,
                    $cc->contrato->numero ?? 'N/A',
                    $cc->contrato->estado ?? 'N/A',
                    $cc->contrato->fecha_inicio ?? 'N/A',
                    $cc->contrato->fecha_fin ?? 'N/A',
                    $cc->contrato && $cc->contrato->fecha_inicio <= now() && $cc->contrato->fecha_fin >= now() ? '✅' : '❌'
                ];
            });
            
            $this->table([
                'UUID', 'Estado CC', 'Contrato #', 'Estado Contrato', 
                'Fecha Inicio', 'Fecha Fin', 'Vigente'
            ], $data);
        }
        
        // 3. Verificar contratos vigentes
        $contratosVigentes = Contrato::where('estado', 'ACTIVO')
            ->where('fecha_inicio', '<=', now())
            ->where('fecha_fin', '>=', now())
            ->count();
            
        $this->info("\n📊 Contratos vigentes en el sistema: {$contratosVigentes}");
    }
}
