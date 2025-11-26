<?php

namespace App\Console\Commands;

use App\Models\Parametry;
use Illuminate\Console\Command;

class InitializeCommissionPoints extends Command
{
    protected $signature = 'init:commission-points';
    protected $description = 'Initialize or update commission points minimum requirement';

    public function handle()
    {
        $this->info("🔧 Inicializando configuración de puntos de comisión...");
        $this->info("---");

        // Buscar o crear el parámetro de puntos de comisión mínimos
        $param = Parametry::updateOrCreate(
            ['name' => 'Puntos Comisión Mínimos'],
            [
                'quantity' => 100, // 100 puntos por defecto
                'state' => 1,      // Activo
            ]
        );

        $this->info("✅ Configuración lista!");
        $this->info("---");
        $this->line("Parámetro: Puntos Comisión Mínimos");
        $this->line("Cantidad: {$param->quantity} puntos");
        $this->line("Estado: " . ($param->state ? 'Activo' : 'Inactivo'));
        $this->info("");
        $this->info("💡 El admin puede cambiar esto en el panel de administración");
    }
}
