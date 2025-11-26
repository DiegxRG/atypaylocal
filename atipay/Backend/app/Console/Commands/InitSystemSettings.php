<?php

namespace App\Console\Commands;

use App\Models\SystemSetting;
use Illuminate\Console\Command;

class InitSystemSettings extends Command
{
    protected $signature = 'init:system-settings';
    protected $description = 'Initialize system settings for commission points';

    public function handle()
    {
        $this->info("🔧 Inicializando configuración del sistema...");
        $this->info("---");

        // Crear o actualizar la configuración de puntos mínimos
        $setting = SystemSetting::updateOrCreate(
            ['key' => 'min_monthly_points'],
            [
                'value' => 100,
                'description' => 'Puntos mínimos mensuales para calificar y recibir comisiones'
            ]
        );

        $this->info("✅ Configuración del sistema inicializada!");
        $this->info("---");
        $this->line("Clave: min_monthly_points");
        $this->line("Valor: {$setting->value} puntos");
        $this->line("Descripción: {$setting->description}");
        $this->info("");
        $this->info("💡 El admin puede cambiar esto en la API:");
        $this->line("  POST /api/admin/qualification/update");
        $this->line("  Body: {\"points\": 150}");
    }
}
