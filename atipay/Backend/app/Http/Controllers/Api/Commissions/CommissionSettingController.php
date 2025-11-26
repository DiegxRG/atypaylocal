<?php

namespace App\Http\Controllers\Api\Commissions;

use App\Http\Controllers\Controller;
use App\Models\CommissionSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\CommissionSettingService;
use App\Http\Requests\Commissions\CommissionSettingRequest;

class CommissionSettingController extends Controller
{
    protected CommissionSettingService $service;

    public function __construct(CommissionSettingService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        return response()->json($this->service->getAll());
    }

    public function updateOrCreate(CommissionSettingRequest $request): JsonResponse
    {
        $setting = $this->service->updateOrCreate(
            $request->input('level'),
            $request->input('percentage')
        );

        return response()->json([
            'message' => 'Configuración actualizada exitosamente.',
            'data' => $setting
        ]);
    }

    public function destroy(int $level): JsonResponse
    {
        $deleted = $this->service->delete($level);

        if (!$deleted) {
            return response()->json(['error' => 'Nivel no encontrado.'], 404);
        }

        return response()->json(['message' => "Nivel $level eliminado exitosamente."]);
    }
}
