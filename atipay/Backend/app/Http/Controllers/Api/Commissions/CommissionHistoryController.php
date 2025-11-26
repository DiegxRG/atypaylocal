<?php

namespace App\Http\Controllers\Api\Commissions;

use App\Http\Controllers\Controller;
use App\Services\ReferralCommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionHistoryController extends Controller
{
    protected ReferralCommissionService $commissionService;

    public function __construct(ReferralCommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    /**
     * Histórico de comisiones sin retirar de meses pasados
     */
    public function unwithdrawnHistory(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $history = $this->commissionService->getUnwithdrawnHistory($user);

        return response()->json([
            'user'    => $user->id,
            'history' => $history,
        ]);
    }
}
