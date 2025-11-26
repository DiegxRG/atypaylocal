<?php

namespace App\Services;

use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WithdrawalService
{
    /**
     * Crear una nueva solicitud de retiro
     */
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $userId = auth('api')->id();
            $user   = User::lockForUpdate()->find($userId);

            // Validar monto mínimo
            if ($data['amount'] < 20) {
                return [
                    'error' => true,
                    'message' => 'El monto mínimo para retirar es de S/20 soles.',
                    'min_amount' => 20
                ];
            }

            // Validar saldo disponible
            if ($user->atipay_money < $data['amount']) {
                return [
                    'error' => true,
                    'message' => 'No tiene saldo suficiente para retirar.',
                    'available_balance' => $user->atipay_money
                ];
            }

            // Validar campos según el método
            if (in_array($data['method'], ['yape', 'plin']) && empty($data['phone_number'])) {
                return [
                    'error' => true,
                    'message' => 'Debe ingresar un número de celular para Yape o Plin.'
                ];
            }

            if (in_array($data['method'], ['transferencia_bancaria', 'transferencia_electronica']) && empty($data['account_number'])) {
                return [
                    'error' => true,
                    'message' => 'Debe ingresar un número de cuenta para transferencia.'
                ];
            }

            $commission = $data['amount'] * 0.10;
            $netAmount  = $data['amount'] - $commission;

            // Descontar inmediatamente el saldo
            $user->decrement('atipay_money', $data['amount']);

            $withdrawal = Withdrawal::create([
                'user_id'        => $userId,
                'method'         => $data['method'],
                'holder'         => $data['holder'],
                'phone_number'   => $data['phone_number'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'amount'         => $data['amount'],
                'commission'     => $commission,
                'net_amount'     => $netAmount,
                'status'         => 'earring',
                'date'           => Carbon::today(),
            ]);

            return [
                'error' => false,
                'withdrawal' => $withdrawal
            ];
        });
    }

    /**
     * Cambiar el estado de una solicitud (por el admin)
     */
    public function updateStatus(int $id, string $status): ?Withdrawal
    {
        return DB::transaction(function () use ($id, $status) {
            $withdrawal = Withdrawal::with('user')->lockForUpdate()->find($id);

            if (!$withdrawal) {
                throw new \Exception('Solicitud de retiro no encontrada.');
            }

            if (in_array($withdrawal->status, ['approved', 'rejected'])) {
                throw new \Exception('Este retiro ya ha sido procesado.');
            }

            $user = $withdrawal->user;

            if ($status === 'rejected') {
                // Devolver el dinero al usuario
                $user->increment('atipay_money', $withdrawal->amount);
            }

            $withdrawal->status = $status;
            $withdrawal->save();

            return $withdrawal;
        });
    }

    public function getByUser(int $userId)
    {
        return Withdrawal::where('user_id', $userId)->latest()->get();
    }

    public function getAll()
    {
        return Withdrawal::with('user')->latest()->get();
    }

    public function getById(int $id): ?Withdrawal
    {
        return Withdrawal::with('user')->find($id);
    }
}
