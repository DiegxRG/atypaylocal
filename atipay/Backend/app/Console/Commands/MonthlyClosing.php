<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SystemSetting;
use App\Models\MonthlyUserPoint;
use App\Models\ReferralCommission;
use Illuminate\Support\Facades\Log;

class MonthlyClosing extends Command
{
    /**
     * El nombre y la firma del comando en la consola.
     * Acepta mes y año opcionales (ej: atipay:monthly-closing 10 2025)
     */
    protected $signature = 'atipay:monthly-closing {month?} {year?}';

    /**
     * La descripción del comando.
     */
    protected $description = 'Evalúa los puntos mensuales y libera las comisiones si se cumple la meta';

    /**
     * Aquí ocurre la magia.
     */
    public function handle()
    {
        // 1. Definir qué mes vamos a cerrar (por defecto, el mes anterior al actual)
        $month = $this->argument('month') ?? now()->subMonth()->month;
        $year = $this->argument('year') ?? now()->subMonth()->year;

        $this->info("Iniciando cierre para el mes: $month/$year");

        // 2. Obtener la meta de puntos desde la Base de Datos
        $setting = SystemSetting::where('key', 'min_monthly_points')->first();
        $minPoints = $setting ? (int)$setting->value : 76; // 76 por defecto si no encuentra nada

        $this->info("Meta de puntos configurada: $minPoints");

        // 3. Buscar todos los usuarios que tienen puntos registrados en ese mes
        $usersRecords = MonthlyUserPoint::where('month', $month)
                                        ->where('year', $year)
                                        ->get();

        $count = 0;

        // 4. Recorrer usuario por usuario
        foreach ($usersRecords as $record) {
            // Si sus puntos superan o igualan la meta...
            if ($record->points >= $minPoints) {
                
                // ...Desbloqueamos sus comisiones (locked = 0)
                ReferralCommission::where('user_id', $record->user_id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->update(['locked' => 0]); // 0 significa DESBLOQUEADO
                
                $count++;
                $this->info("Usuario ID {$record->user_id} CALIFICÓ con {$record->points} puntos.");
            } else {
                $this->warn("Usuario ID {$record->user_id} NO calificó ({$record->points} pts).");
            }
        }

        $this->info("Cierre completado. Se desbloquearon comisiones de $count usuarios.");
        Log::info("Cierre mensual ejecutado para $month/$year. $count usuarios calificados.");
    }
}