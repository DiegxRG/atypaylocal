<?php

namespace App\Http\Controllers\Api\Withdrawals;

use App\Http\Controllers\Controller;
use App\Http\Requests\Withdrawals\StoreWithdrawalRequest;
use App\Services\WithdrawalService;
use Illuminate\Http\JsonResponse;

class WithdrawalController extends Controller
{
    protected WithdrawalService $withdrawalService;

    public function __construct(WithdrawalService $withdrawalService)
    {
        $this->withdrawalService = $withdrawalService;
    }

    /**
     * Usuario: Crear nueva solicitud de retiro
     */
    public function store(StoreWithdrawalRequest $request): JsonResponse
    {
        $result = $this->withdrawalService->create($request->validated());

        if ($result['error']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'extra'   => $result['min_amount'] ?? $result['available_balance'] ?? null
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Solicitud enviada correctamente',
            'data'    => $result['withdrawal']
        ], 201);
    }

    /**
     * Usuario: Ver sus solicitudes de retiro
     */
    public function myWithdrawals(): JsonResponse
    {
        $userId = auth('api')->id();
        $withdrawals = $this->withdrawalService->getByUser($userId);
        return response()->json($withdrawals);
    }

    /**
     * Admin: Ver todas las solicitudes
     */
    public function index(): JsonResponse
    {
        $all = $this->withdrawalService->getAll();
        return response()->json($all);
    }

    /**
     * Admin: Ver detalle de una solicitud
     */
    public function show($id): JsonResponse
    {
        $withdrawal = $this->withdrawalService->getById($id);
        if (!$withdrawal) {
            return response()->json(['message' => 'Solicitud no encontrada'], 404);
        }

        return response()->json($withdrawal);
    }

    /**
     * Admin: Aprobar un retiro
     */
    public function approve($id): JsonResponse
    {
        $withdrawal = $this->withdrawalService->updateStatus($id, 'approved');
        return response()->json(['message' => 'Retiro aprobado correctamente', 'data' => $withdrawal]);
    }

    /**
     * Admin: Rechazar un retiro
     */
    public function reject($id): JsonResponse
    {
        $withdrawal = $this->withdrawalService->updateStatus($id, 'rejected');
        return response()->json(['message' => 'Retiro rechazado correctamente', 'data' => $withdrawal]);
    }
}