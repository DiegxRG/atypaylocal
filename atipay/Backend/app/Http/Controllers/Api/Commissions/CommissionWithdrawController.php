<?php

namespace App\Http\Controllers\Api\Commissions;

use App\Http\Controllers\Controller;
use App\Services\ReferralCommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionWithdrawController extends Controller
{
    protected ReferralCommissionService $commissionService;

    public function __construct(ReferralCommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    /**
     * Retirar las comisiones del mes actual del usuario autenticado.
     */
    public function withdraw(Request $request): JsonResponse
    {
        $user = auth('api')->user();
    
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $month = now()->month;
        $year  = now()->year;

        $result = $this->commissionService->withdrawMonthlyCommissions($user, $month, $year);

        return response()->json($result);
    }

    /**
     * Ver historial de retiros del usuario autenticado.
     */
    public function history(Request $request): JsonResponse
    {
        $user = auth('api')->user();
    
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $history = \App\Models\CommissionWithdrawal::where('user_id', $user->id)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($item) {
                $item->origen = 'Compra Referidos';
                return $item;
            });

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }
}
