<?php

namespace App\Http\Controllers\Api\Commissions;

use App\Http\Controllers\Controller;
use App\Models\ReferralCommission;
use App\Services\ReferralCommissionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CommissionSummaryController extends Controller
{
    protected ReferralCommissionService $commissionService;

    public function __construct(ReferralCommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }
    /**
     * Generar resumen de comisiones por usuario - ADMIN
     */
    public function summaryByUser(): JsonResponse
    {
        $summary = ReferralCommission::select('user_id', DB::raw('SUM(commission_amount) as total'))
            ->groupBy('user_id')
            ->with('user:id,username,email')
            ->orderByDesc('total')
            ->get();

        return response()->json($summary);
    }
    
    /**
     * Resumen de comisiones de la red del usuario autenticado.
     */
    public function myNetworkCommissions(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $summary = $this->commissionService->getNetworkCommissions($user);

        return response()->json([
            'user'    => $user->id,
            'summary' => $summary,
        ]);
    }
}