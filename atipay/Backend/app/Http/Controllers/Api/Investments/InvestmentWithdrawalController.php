<?php

namespace App\Http\Controllers\Api\Investments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investments\RequestWithdrawalRequest;
use App\Services\InvestmentWithdrawalService;
use App\Models\Investment;
use App\Models\InvestmentWithdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvestmentWithdrawalController extends Controller
{
    protected InvestmentWithdrawalService $withdrawalService;

    public function __construct(InvestmentWithdrawalService $withdrawalService)
    {
        $this->withdrawalService = $withdrawalService;
    }

    /**
     * Listar retiros del usuario
     */
    public function index(): JsonResponse
    {
        $user = auth('api')->user();
        $withdrawals = $this->withdrawalService->getUserWithdrawals($user);
        return response()->json($withdrawals);
    }

    /**
     * Solicitar retiro de ganancia
     */
    public function store(RequestWithdrawalRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        $investment = Investment::where('id', $request->input('investment_id'))
                                ->where('user_id', $user->id)
                                ->first();

        if (!$investment) {
            return response()->json(['error' => 'Inversión no encontrada o no te pertenece.'], 404);
        }

        $withdrawal = $this->withdrawalService->requestWithdrawal($investment, $request->input('amount'));

        return response()->json([
            'message' => 'Solicitud de retiro registrada correctamente. Pendiente de aprobación.',
            'withdrawal' => $withdrawal
        ], 201);
    }

    /**
    * Acciones Admin 
    */

    // Listar todos los retiros
    public function all()
    {
        return response()->json(
            InvestmentWithdrawal::with('user', 'investment')->latest()->get()
        );
    }

    // Aprobar retiro
    public function approve($id)
    {
        $withdrawal = InvestmentWithdrawal::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return response()->json(['error' => 'Este retiro ya fue procesado.'], 400);
        }

        $withdrawal->status = 'approved';
        $withdrawal->admin_message = 'Retiro aprobado manualmente.';
        $withdrawal->save();

        return response()->json(['message' => 'Retiro aprobado correctamente.']);
    }

    // Rechazar retiro
    public function reject(Request $request, $id)
    {
        $withdrawal = InvestmentWithdrawal::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return response()->json(['error' => 'Este retiro ya fue procesado.'], 400);
        }

        $withdrawal->status = 'rejected';
        $withdrawal->admin_message = $request->input('admin_message');
        $withdrawal->save();

        return response()->json(['message' => 'Retiro rechazado correctamente.']);
    }

    public function getByUser()
    {
        $user = auth('api')->user();
        $userId = $user->id;
        $withdrawals = InvestmentWithdrawal::with([
            'investment.promotion:id,name',
            'investment.user:id,username'
        ])
        ->whereHas('investment', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->get()
        ->map(function ($withdrawal) {
            return [
                'id' => $withdrawal->id,
                'investment_id' => $withdrawal->investment_id,
                'amount' => $withdrawal->amount,
                'transferred_at' => $withdrawal->transferred_at,
                'status' => $withdrawal->status ?? null,
                'username' => $withdrawal->investment->user->username ?? null,
                'promotion_name' => $withdrawal->investment->promotion->name ?? null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $withdrawals
        ]);
    }

}
