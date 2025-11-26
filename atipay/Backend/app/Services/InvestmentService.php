<?php

namespace App\Services;

use App\Models\Investment;
use App\Models\InvestmentWithdrawal;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvestmentService
{
    /**
     * Inversiones de un usuario
     */
    public function getUserInvestments(User $user)
    {
        return $user->investments()->with('promotion', 'withdrawals')->latest()->get();
    }

    /**
     * Crear una nueva solicitud de inversión.
     */
    public function store(User $user, array $data): Investment
    {
        return DB::transaction(function () use ($user, $data) {

            $promotion = Promotion::findOrFail($data['promotion_id']);
            $price = $promotion->atipay_price_promotion;

            if ($user->atipay_money < $price) {
                throw new \Exception('Saldo insuficiente para realizar esta inversión.');
            }

            // Descontar saldo
            $user->atipay_money -= $price;
            $user->save();

            // Calcular ganancia total
            $totalEarning = $price * ($promotion->percentaje / 100);

            return Investment::create([
                'user_id'        => $user->id,
                'promotion_id'   => $promotion->id,
                'status'         => 'pending',
                'daily_earning'  => 0,
                'total_earning'  => round($totalEarning, 2),
                'already_earned' => 0,
            ]);
        });
    }

    /**
     * Aprobación de inversiones.
     * REPARADA: ahora usa meses reales y evita decimales extraños en días.
     */
    public function approve(Investment $investment, string $adminMessage = null): void
    {
        if ($investment->status !== 'pending') {
            return;
        }

        // Fecha base (a medianoche)
        $startDate = now()->startOfDay();
        $durationMonths = $investment->promotion->duration_months;

        // FIN REAL DE MES (calendario), evitando desbordes
        $endDate = $startDate->copy()
            ->addMonthsNoOverflow($durationMonths)
            ->endOfDay();

        // Días completos entre inicio y fin (sin horas contaminando)
        $totalDays = $startDate->diffInDays($endDate) + 1;

        // Ganancia diaria precisa
        $dailyEarning = $totalDays > 0 ? $investment->total_earning / $totalDays : 0;

        DB::transaction(function () use ($investment, $startDate, $endDate, $dailyEarning, $adminMessage) {
            $investment->update([
                'status'         => 'active',
                'approved_at'    => $startDate,
                'start_date'     => $startDate,
                'end_date'       => $endDate,
                'daily_earning'  => $dailyEarning,
                'already_earned' => 0,
                'last_earned_at' => null,
                'admin_message'  => $adminMessage,
            ]);

            // Sumar puntos al socio
            $promotion = $investment->promotion;
            $user = $investment->user;

            if ($promotion->points_earned > 0) {
                $user->accumulated_points += $promotion->points_earned;
                $user->save();
            }
        });
    }

    /**
     * Rechazo de inversiones
     */
    public function reject(Investment $investment, string $adminMessage = null): void
    {
        DB::transaction(function () use ($investment, $adminMessage) {
            if ($investment->status === 'rejected') {
                return;
            }

            $investment->update([
                'status'        => 'rejected',
                'rejected_at'   => now(),
                'approved_at'   => null,
                'admin_message' => $adminMessage,
            ]);

            // Regresar dinero
            $user = $investment->user;
            $user->atipay_money += $investment->promotion->atipay_price_promotion;
            $user->save();
        });
    }

    /**
     * Actualización automática de ganancias.
     */
    public function autoUpdateEarnings(Investment $investment)
    {
        if ($investment->status !== 'active' || !$investment->start_date) {
            return $investment;
        }

        $endDate = Carbon::parse($investment->end_date)->endOfDay();
        $startDate = Carbon::parse($investment->start_date)->startOfDay();

        $effectiveNow = now()->min($endDate)->startOfDay();

        if ($effectiveNow->lt($startDate)) {
            return $investment;
        }

        $lastPaidDay = $investment->last_earned_at
            ? Carbon::parse($investment->last_earned_at)->startOfDay()
            : null;

        $dayToStartCounting = $lastPaidDay
            ? $lastPaidDay->copy()->addDay()
            : $startDate;

        $dayToStopCounting = $effectiveNow;

        if ($dayToStopCounting->lt($dayToStartCounting)) {
            return $investment;
        }

        $daysToPay = $dayToStartCounting->diffInDays($dayToStopCounting) + 1;

        if ($daysToPay <= 0) {
            return $investment;
        }

        $earned = $daysToPay * $investment->daily_earning;
        $newTotal = $investment->already_earned + $earned;

        $investment->already_earned = min($newTotal, $investment->total_earning);
        $investment->last_earned_at = $dayToStopCounting;
        $investment->save();

        return $investment;
    }

    /**
     * Obtener ganancias (vista mensual)
     */
    public function getInvestmentGains(User $user, int $investmentId): array
    {
        $investment = $user->investments()->with('promotion')->where('id', $investmentId)->firstOrFail();
        $this->autoUpdateEarnings($investment->refresh());

        if (!in_array($investment->status, ['active', 'completed'])) {
            throw new \Exception('La inversión no está activa o completada.');
        }

        $promotion = $investment->promotion;
        $durationMonths = $promotion->duration_months;
        $startDate = Carbon::parse($investment->start_date)->startOfDay();
        $now = now();

        $gains = [];

        for ($i = 1; $i <= $durationMonths; $i++) {
            $monthStart = $startDate->copy()->addMonthsNoOverflow($i - 1)->startOfDay();
            $monthEnd   = $monthStart->copy()->addMonthNoOverflow()->subSecond()->endOfDay();
            $daysInMonth = $monthStart->diffInDays($monthEnd) + 1;

            $monthlyGain = $investment->daily_earning * $daysInMonth;

            if ($now->greaterThanOrEqualTo($monthEnd)) {
                $status = 'completado';
                $gain = $monthlyGain;
            } elseif ($now->between($monthStart, $monthEnd)) {
                $status = 'en curso';
                $daysElapsed = $monthStart->diffInDays($now->startOfDay()) + 1;
                $gain = $investment->daily_earning * min($daysElapsed, $daysInMonth);
            } else {
                $status = 'pendiente';
                $gain = 0;
            }

            $gains[] = [
                'month'  => $i,
                'period' => $monthStart->format('Y-m-d H:i:s') . ' a ' . $monthEnd->format('Y-m-d H:i:s'),
                'gain'   => round($gain, 2),
                'status' => $status
            ];
        }

        return [
            'investment_id'   => $investment->id,
            'price'           => $promotion->atipay_price_promotion,
            'percentaje'      => $promotion->percentaje,
            'duration_months' => $durationMonths,
            'daily_earning'   => $investment->daily_earning,
            'gains_by_month'  => $gains,
            'total_projected' => $investment->total_earning,
            'already_earned'  => round($investment->already_earned, 2),
        ];
    }

    /**
     * Vista diaria
     */
    public function getInvestmentDailyGains(User $user, int $investmentId): array
    {
        $investment = $user->investments()->with('promotion')->where('id', $investmentId)->firstOrFail();
        $this->autoUpdateEarnings($investment->refresh());

        if (!in_array($investment->status, ['active', 'completed'])) {
            throw new \Exception('La inversión no está activa o completada.');
        }

        $startDate = Carbon::parse($investment->start_date)->startOfDay();
        $endDate   = Carbon::parse($investment->end_date)->endOfDay();
        $now       = now()->startOfDay();

        $gains = [];
        $current = $startDate;
        $totalAccumulated = 0;

        while ($current->lte($endDate)) {
            if ($now->gte($current)) {
                $status = $now->eq($current) ? 'hoy' : 'completado';
                $gain = $investment->daily_earning;
                $totalAccumulated += $gain;
            } else {
                $status = 'pendiente';
                $gain = 0;
            }

            $gains[] = [
                'date'   => $current->format('Y-m-d H:i:s'),
                'gain'   => round($gain, 2),
                'status' => $status,
            ];

            $current->addDay();
        }

        $totalAccumulated = min($totalAccumulated, $investment->total_earning);

        return [
            'investment_id'     => $investment->id,
            'daily_earning'     => $investment->daily_earning,
            'gains_by_day'      => $gains,
            'total_accumulated' => round($totalAccumulated, 2),
            'total_projected'   => $investment->total_earning,
            'already_earned'    => round($investment->already_earned, 2),
        ];
    }

    /**
     * Retirar ganancias
     */
    public function withdrawEarnings(User $user, Investment $investment): float
    {
        if (!in_array($investment->status, ['active', 'completed'])) {
            throw new \Exception('La inversión no está activa o completada.');
        }

        $this->autoUpdateEarnings($investment->refresh());

        $available = round($investment->already_earned, 2);

        if ($available <= 0) {
            throw new \Exception('No tienes ganancias disponibles para retirar.');
        }

        return DB::transaction(function () use ($user, $investment, $available) {

            $user->atipay_money = round($user->atipay_money + $available, 2);
            $user->save();

            InvestmentWithdrawal::create([
                'investment_id' => $investment->id,
                'amount'        => $available,
                'transferred_at'=> now(),
                'user_id'       => $user->id,
            ]);

            $investment->already_earned = 0;
            $investment->save();

            return $available;
        });
    }
}
