<?php

namespace App\Http\Controllers\Api\Closing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Models\MonthlyUserPoint;
use App\Models\SystemSetting;

class MonthlyClosingController extends Controller
{
    // 1. Estado actual del usuario (Semáforo)
    public function myStatus(Request $request)
    {
        $user = $request->user();
        
        // SEGURIDAD: Si no hay usuario, devolvemos 401 en lugar de rompernos con 500
        if (!$user) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        try {
            $month = now()->month;
            $year = now()->year;

            $setting = SystemSetting::where('key', 'min_monthly_points')->first();
            $minPoints = $setting ? (int)$setting->value : 76;

            $record = MonthlyUserPoint::where('user_id', $user->id)
                        ->where('month', $month)
                        ->where('year', $year)
                        ->first();

            $currentPoints = $record ? $record->points : 0;

            return response()->json([
                'current_points' => $currentPoints,
                'min_points' => $minPoints,
                'qualified' => $currentPoints >= $minPoints,
                'month' => $month,
                'year' => $year
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // 2. Historial anual para gráficos
    public function getHistory(Request $request)
    {
        $user = $request->user();

        // SEGURIDAD CRÍTICA: Esto evita el error "id on null"
        if (!$user) {
            return response()->json(['error' => 'No autorizado. Token inválido.'], 401);
        }

        try {
            $year = now()->year;

            // Traer puntos
            $points = MonthlyUserPoint::where('user_id', $user->id)
                        ->where('year', $year)
                        ->get();

            $history = [];
            $meses = [
                1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
                7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
            ];

            for ($i = 1; $i <= 12; $i++) {
                $record = $points->where('month', $i)->first();
                $history[] = [
                    'month' => $i,
                    'name' => $meses[$i],
                    'points' => $record ? (int)$record->points : 0,
                    'qualified' => $record ? ($record->points >= 76) : false
                ];
            }

            return response()->json($history);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    // 3. Forzar cierre (Admin)
    public function forceClosing(Request $request)
    {
        Artisan::call('atipay:monthly-closing', [
            'month' => $request->month,
            'year' => $request->year
        ]);
        return response()->json(['message' => 'Ejecutado']);
    }
}