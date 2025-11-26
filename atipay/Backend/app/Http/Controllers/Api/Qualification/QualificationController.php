<?php

namespace App\Http\Controllers\Api\Qualification;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\MonthlyUserPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QualificationController extends Controller
{
    /**
     * ADMIN: Actualizar la configuración de puntos mínimos
     */
    public function updateMinPoints(Request $request)
    {
        $request->validate(['points' => 'required|integer|min:0']);

        SystemSetting::updateOrCreate(
            ['key' => 'min_monthly_points'],
            ['value' => $request->points, 'description' => 'Puntos mínimos mensuales para calificar']
        );

        return response()->json(['message' => 'Configuración actualizada correctamente']);
    }

    /**
     * ADMIN: Obtener la configuración actual
     */
    public function getSettings()
    {
        $setting = SystemSetting::where('key', 'min_monthly_points')->first();
        return response()->json(['min_points' => $setting ? (int)$setting->value : 100]);
    }

    /**
     * USUARIO: Verificar mi estado de calificación del mes
     */
    public function checkMyStatus()
    {
        // Usar el guard `api` explícitamente (consistente con el middleware IsUserAuth)
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['error' => 'No autorizado'], 401);
        }
        
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // 1. Obtener la meta definida por el Admin
        $setting = SystemSetting::where('key', 'min_monthly_points')->first();
        $minPoints = $setting ? (int)$setting->value : 100;

        // 2. Buscar los puntos del usuario en la tabla 'monthly_user_points'
        $userPointsRecord = MonthlyUserPoint::where('user_id', $user->id)
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->first();

        $currentPoints = $userPointsRecord ? $userPointsRecord->points : 0;

        // 3. Evaluar si cumple
        $isQualified = $currentPoints >= $minPoints;

        return response()->json([
            'is_qualified' => $isQualified,
            'current_points' => $currentPoints,
            'required_points' => $minPoints,
            'month' => $currentMonth,
            'year' => $currentYear
        ]);
    }
}