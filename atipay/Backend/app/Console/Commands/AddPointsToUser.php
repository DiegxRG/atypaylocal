<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\MonthlyUserPoint;
use Illuminate\Console\Command;

class AddPointsToUser extends Command
{
    protected $signature = 'add:points {username} {points}';
    protected $description = 'Add monthly points to a user for testing';

    public function handle()
    {
        $username = $this->argument('username');
        $points = (int)$this->argument('points');

        $this->info("➕ Agregando puntos al usuario...");
        $this->info("---");

        // Buscar el usuario
        $user = User::where('username', $username)->orWhere('email', $username)->first();

        if (!$user) {
            $this->error("❌ Usuario '{$username}' no encontrado");
            return;
        }

        $this->info("✅ Usuario encontrado: {$user->username}");

        // Obtener mes y año actual
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Buscar o crear el registro de puntos mensuales
        $monthlyPoints = MonthlyUserPoint::updateOrCreate(
            [
                'user_id' => $user->id,
                'month' => $currentMonth,
                'year' => $currentYear
            ],
            [
                'points' => $points // Asignar los puntos
            ]
        );

        $this->info("");
        $this->info("✅ Puntos agregados correctamente!");
        $this->info("---");
        $this->line("Usuario: {$user->username}");
        $this->line("Puntos: {$monthlyPoints->points}");
        $this->line("Mes: {$currentMonth}/{$currentYear}");
        $this->info("");

        // Verificar si califica
        $minPoints = 100; // Valor por defecto
        if ($monthlyPoints->points >= $minPoints) {
            $this->info("✅ ¡El usuario CALIFICA para retirar comisiones!");
        } else {
            $this->warn("⚠️  El usuario aún NO califica.");
            $this->line("   Puntos necesarios: {$minPoints}");
            $this->line("   Puntos actuales: {$monthlyPoints->points}");
            $this->line("   Faltan: " . ($minPoints - $monthlyPoints->points) . " puntos");
        }

        $this->info("");
        $this->info("💡 Ahora puedes:");
        $this->line("  1. Ir al Dashboard del usuario");
        $this->line("  2. Ver el estado de calificación");
        $this->line("  3. Intentar crear un retiro");
    }
}
