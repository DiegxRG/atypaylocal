<?php

namespace App\Services;

use App\Models\Investment;
use App\Models\InvestmentWithdrawal;
use App\Models\User;

class InvestmentWithdrawalService
{
    /**
     * Solicitar un retiro de una inversión activa
    */
    public function requestWithdrawal(Investment $investment, float $amount): InvestmentWithdrawal
    {
        if ($investment->status !== 'active') {
            throw new \Exception('La inversión no está activa.');
        }

        return InvestmentWithdrawal::create([
            'investment_id' => $investment->id,
            'amount' => $amount,
            'status' => 'pending',
        ]);
    }

    /**
     * Aprobar un retiro
    */
    public function approve(InvestmentWithdrawal $withdrawal): void
    {
        $withdrawal->update([
            'status' => 'approved',
            'processed_at' => now(),
        ]);
    }

    /**
     * Obtener todos los retiros de un usuario
    */ 
    public function getUserWithdrawals(User $user)
    {
        return InvestmentWithdrawal::whereHas('investment', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->latest()->get();
    }

    /**
     * Rechazar un retiro con mensaje opcional del admin
    */ 
    public function reject(InvestmentWithdrawal $withdrawal, string $adminMessage = null): void
    {
        $withdrawal->update([
            'status' => 'rejected',
            'admin_message' => $adminMessage,
        ]);
    }
}
