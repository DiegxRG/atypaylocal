<?php

namespace App\Http\Controllers\Api\Referrals;

use App\Http\Controllers\Controller;
use App\Models\MonthlyUserPoint;
use App\Models\ReferralCommission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReferralController extends Controller
{
    /**
     * Ver puntos generados por afiliados del usuario autenticado por mes
     */
    public function myMonthlyPoints(): JsonResponse
    {
        $userId = Auth::id();

        $points = MonthlyUserPoint::where('user_id', $userId)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get(['month', 'year', 'points']);

        return response()->json($points);
    }

    /**
     * Ver comisiones ganadas por nivel
     */
    public function myCommissionsByLevel(): JsonResponse
    {
        $userId = Auth::id();

        $commissions = ReferralCommission::where('user_id', $userId)
            ->select('level', DB::raw('SUM(commission_amount) as total'))
            ->groupBy('level')
            ->orderBy('level')
            ->get();

        return response()->json($commissions);
    }

    /**
     * Ver red de afiliados de cualquier usuario (por nivel) - ADMIN
     */
    public function myReferralNetwork()
    {
        $user = auth('api')->user();
    
        if (!in_array($user->role->name, [User::ROLE_PARTNER, User::ROLE_ADMIN])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver esta información.'
            ], 403);
        }
    
        $levels = [];
        $currentLevelUsers = [$user->id];
    
        for ($level = 1; $level <= 5; $level++) {
            $referrals = User::whereIn('referred_by', $currentLevelUsers)
                ->select('id', 'username', 'email', 'registration_date', 'registration_time')
                ->get()
                ->map(function ($referral) {
                    return [
                        'id'                => $referral->id,
                        'username'          => $referral->username,
                        'email'             => $referral->email,
                        'registration_date' => $referral->registration_date,
                        'registration_time' => $referral->registration_time,
                        'referral_url'      => url("atipay/{$referral->username}/reference-code-register"),
                    ];
                });
    
            if ($referrals->isEmpty()) {
                break;
            }
    
            $levels["nivel_$level"] = $referrals;
            $currentLevelUsers = $referrals->pluck('id')->toArray();
        }
    
        return response()->json([
            'success' => true,
            'data'    => $levels,
        ]);
    }

    public function myReferralLevelsCount()
    {
        $user = auth('api')->user();
        $levels = [];

        $currentLevelUsers = [$user->id]; 

        for ($level = 1; $level <= 5; $level++) {
            $nextLevelUsers = User::whereIn('referred_by', $currentLevelUsers)->pluck('id')->toArray();
            $levels["nivel_$level"] = count($nextLevelUsers);
            $currentLevelUsers = $nextLevelUsers;
        }

        return response()->json([
            'success' => true,
            'data' => $levels,
        ]);
    }
}