<?php

namespace App\Http\Controllers\Api\AtipayRecharges;

use App\Http\Controllers\Controller;
use App\Http\Requests\AtipayRecharges\StoreAtipayRechargeRequest;
use App\Services\AtipayRechargeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AtipayRechargeController extends Controller
{
    protected AtipayRechargeService $rechargeService;

    public function __construct(AtipayRechargeService $rechargeService)
    {
        $this->rechargeService = $rechargeService;
    }

    /**
     * Crear una nueva solicitud de recarga (por socio)
     */
    public function store(StoreAtipayRechargeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth('api')->id(); // ID del socio autenticado

        $recharge = $this->rechargeService->create($data);

        return response()->json([
            'message' => 'Recarga enviada correctamente. En espera de aprobación.',
            'data' => $recharge
        ], 201);
    }

    /**
     * Listar recargas del usuario autenticado
     */
    public function myRecharges(): JsonResponse
    {
        $userId = auth('api')->id();
        $recharges = $this->rechargeService->getUserRecharges($userId);

        return response()->json($recharges);
    }

    /**
     * Listar todas las recargas (admin)
     */
    public function index(): JsonResponse
    {
        $recharges = $this->rechargeService->getAll();
        return response()->json($recharges);
    }

    /**
     * Mostrar una recarga específica
     */
    public function show($id): JsonResponse
    {
        $recharge = $this->rechargeService->getById($id);
        if (!$recharge) {
            return response()->json(['message' => 'Recarga no encontrada'], 404);
        }

        return response()->json($recharge);
    }

    /**
     * Aprobar una recarga (admin)
     */
    public function approve($id): JsonResponse
    {
        try {
            $adminId = auth('api')->id();
            $recharge = $this->rechargeService->approveRecharge($id, $adminId);

            return response()->json([
                'message' => 'Recarga aprobada y saldo actualizado.',
                'data' => $recharge
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Rechazar una recarga (admin)
     */
    public function reject($id): JsonResponse
    {
        try {
            $adminId = auth('api')->id();
            $recharge = $this->rechargeService->rejectRecharge($id, $adminId);

            return response()->json([
                'message' => 'Recarga rechazada correctamente.',
                'data' => $recharge
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
