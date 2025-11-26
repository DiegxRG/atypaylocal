<?php

namespace App\Services;

use App\Models\User;
use App\Models\PurchaseRequest;

class PurchaseRequestService
{
    protected ReferralCommissionService $referralService;

    public function __construct(ReferralCommissionService $referralService)
    {
        $this->referralService = $referralService;
    }

    /**
     * Listar purchase requests de un usuario, aplicando lógica de puntos y estado.
     */
    public function listUserPurchaseRequests(User $user)
    {
        $currentPoints = $this->referralService->getCurrentMonthlyPoints($user);

        // Retornar listado de purchase requests
        return [
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'status' => $user->status,
                'monthly_points' => $currentPoints,
            ],
            'purchase_requests' => PurchaseRequest::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
        ];
    }
}
