<?php

namespace App\Services;

use App\Models\CommissionSetting;

class CommissionSettingService
{
    /**
     * Obtener todos los niveles configurados
     */
    public function getAll()
    {
        return CommissionSetting::orderBy('level')->get();
    }

    /**
     * Crear o actualizar un nivel de comisión
     */
    public function updateOrCreate(int $level, float $percentage): CommissionSetting
    {
        return CommissionSetting::updateOrCreate(
            ['level' => $level],
            ['percentage' => $percentage]
        );
    }

    /**
     * Eliminar un nivel de comisión
     */
    public function delete(int $level): bool
    {
        $setting = CommissionSetting::where('level', $level)->first();

        if (!$setting) {
            return false;
        }

        return $setting->delete();
    }
}
