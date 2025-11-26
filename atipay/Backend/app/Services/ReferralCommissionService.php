<?php

namespace App\Services;

use App\Models\User;
use App\Models\ReferralCommission;
use App\Models\CommissionSetting;
use App\Models\CommissionWithdrawal;
use App\Models\MonthlyUserPoint;
use Illuminate\Support\Facades\DB;

class ReferralCommissionService
{
    const MAX_LEVEL = 5;
    const REQUIRED_MONTHLY_POINTS = 100;

    /**
     * Procesar comisiones cuando un usuario genera puntos (ej. por compra).
     */
    public function process(User $referrerUser, int $pointsGenerated, string $sourceType = 'purchase'): void
    {
        $referralChain = $this->getReferralChain($referrerUser);
    
        DB::transaction(function () use ($referralChain, $referrerUser, $pointsGenerated, $sourceType) {
            $month = now()->month;
            $year = now()->year;
    
            foreach ($referralChain as $level => $uplineUser) {
                $percentage = CommissionSetting::getPercentageForLevel($level);
                if ($percentage <= 0) continue;
    
                $commissionAmount = ($pointsGenerated * $percentage) / 100;
    
                $qualifies = $this->qualifiesForCommission($uplineUser, $month, $year);
    
                // Crear comisión SIEMPRE
                ReferralCommission::create([
                    'user_id'          => $uplineUser->id,
                    'referred_user_id' => $referrerUser->id,
                    'level'            => $level,
                    'commission_amount'=> $commissionAmount,
                    'points_generated' => $pointsGenerated,
                    'source_type'      => $sourceType,
                    'month'            => $month,
                    'year'             => $year,
                    'withdrawn'        => false,
                    'locked'           => !$qualifies,
                ]);
            }
    
            // Guardar puntos personales del usuario que generó la acción
            $this->addPersonalPoints($referrerUser, $pointsGenerated, $month, $year);
        });
    }

    /**
     * Obtener la cadena de referidos hacia arriba hasta 5 niveles.
     */
    private function getReferralChain(User $user): array
    {
        $chain = [];
        $current = $user;
        $level = 1;

        while ($current->referrer && $level <= self::MAX_LEVEL) {
            $chain[$level] = $current->referrer;
            $current = $current->referrer;
            $level++;
        }

        return $chain;
    }

    /**
     * Verifica si el usuario tiene al menos 100 puntos personales este mes.
     */
    private function qualifiesForCommission(User $user, int $month, int $year): bool
    {
        $points = MonthlyUserPoint::where('user_id', $user->id)
            ->where('month', $month)
            ->where('year', $year)
            ->value('points');

        return $points >= self::REQUIRED_MONTHLY_POINTS;
    }

    /**
     * Sumar puntos personales del usuario.
     */
    private function addPersonalPoints(User $user, int $points, int $month, int $year): void
    {
        $monthly = MonthlyUserPoint::firstOrNew([
            'user_id' => $user->id,
            'month' => $month,
            'year' => $year,
        ]);
    
        $monthly->points = ($monthly->points ?? 0) + $points;
        $monthly->save();
    
        // Si alcanzó 100 puntos este mes, desbloquear comisiones
        if ($monthly->points >= self::REQUIRED_MONTHLY_POINTS) {
            // Activar usuario si estaba inactivo
            if ($user->status === 'inactive') {
                $user->status = 'active';
                $user->save();
            }
    
            // Desbloquear comisiones bloqueadas de este mes
            ReferralCommission::where('user_id', $user->id)
                ->where('month', $month)
                ->where('year', $year)
                ->where('locked', true)
                ->update(['locked' => false]);
        }
    }

    /**
     * Consultar puntos actuales del usuario y aplicar la lógica de reseteo mensual.
     */
    public function getCurrentMonthlyPoints(User $user): int
    {
        $now = now();
        $month = $now->month;
        $year  = $now->year;

        // Buscar el registro del mes actual
        $monthly = MonthlyUserPoint::where('user_id', $user->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        // Día 1 → resetear estado y puntos
        if ($now->day === 1) {
            if (!$monthly) {
                // crear registro vacío
                $monthly = MonthlyUserPoint::create([
                    'user_id' => $user->id,
                    'month'   => $month,
                    'year'    => $year,
                    'points'  => 0,
                ]);
            } else {
                // resetear puntos
                $monthly->points = 0;
                $monthly->save();
            }

            // cambiar estado del usuario a inactivo
            if ($user->status !== 'inactive') {
                $user->status = 'inactive';
                $user->save();
            }
        }

        // Si no hay registro aún (ej. primer login del mes), devolver 0
        if (!$monthly) {
            return 0;
        }

        // Activar si pasó los 100 puntos
        if ($monthly->points >= self::REQUIRED_MONTHLY_POINTS && $user->status === 'inactive') {
            $user->status = 'active';
            $user->save();
        }

        return $monthly->points;
    }
    
    /**
     * Comisiones sin retirar de meses anteriores
     */
    public function getUnwithdrawnHistory(User $user)
    {
        return ReferralCommission::where('user_id', $user->id)
            ->where('withdrawn', false)
            ->where(function ($q) {
                $q->where('month', '<', now()->month)
                  ->orWhere('year', '<', now()->year);
            })
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->groupBy(fn($c) => $c->month . '-' . $c->year)
            ->map(function ($commissions) {
                return [
                    'total'       => $commissions->sum('commission_amount'),
                    'comisiones'  => $commissions,
                ];
            });
    }
    
    /**
     * Total de comisiones acumuladas de la red (retiradas y pendientes).
     */
    public function getNetworkCommissions(User $user)
    {
        $month = now()->month;
        $year  = now()->year;
    
        $commissions = ReferralCommission::where('user_id', $user->id)
            ->where('month', $month)   
            ->where('year', $year)    
            ->selectRaw('
                SUM(CASE WHEN withdrawn = false THEN commission_amount ELSE 0 END) as pendientes,
                SUM(CASE WHEN withdrawn = true  THEN commission_amount ELSE 0 END) as retiradas,
                level
            ')
            ->groupBy('level')
            ->get();
    
        return [
            'total_pendientes' => $commissions->sum('pendientes'),
            'total_retiradas'  => $commissions->sum('retiradas'),
            'detalle_por_nivel'=> $commissions,
        ];
    }
    
    public function withdrawMonthlyCommissions(User $user, int $month, int $year)
    {
        $points = MonthlyUserPoint::where('user_id', $user->id)
            ->where('month', $month)
            ->where('year', $year)
            ->value('points');
    
        if ($points < self::REQUIRED_MONTHLY_POINTS) {
            return [
                'success' => false,
                'message' => 'No alcanzaste los 100 puntos para retirar este mes.',
            ];
        }
    
        $total = ReferralCommission::where('user_id', $user->id)
            ->where('month', $month)
            ->where('year', $year)
            ->where('withdrawn', false)
            ->where('locked', false)
            ->sum('commission_amount');
    
        if ($total <= 0) {
            return [
                'success' => false,
                'message' => 'No tienes comisiones disponibles para retirar este mes.',
            ];
        }
    
        DB::transaction(function () use ($user, $month, $year, $total) {
            // 1. Marcar como retiradas
            ReferralCommission::where('user_id', $user->id)
                ->where('month', $month)
                ->where('year', $year)
                ->where('withdrawn', false)
                ->where('locked', false)
                ->update(['withdrawn' => true]);
    
            // 2. Sumar al balance del usuario
            $user->atipay_money += $total;
            $user->save();
    
            // 3. Crear registro en historial de retiros
            CommissionWithdrawal::create([
                'user_id'     => $user->id,
                'amount'      => $total,
                'month'       => $month,
                'year'        => $year,
                'withdrawn_at'=> now(),
            ]);
        });
    
        return [
            'success' => true,
            'message' => "Has retirado tus comisiones del mes.",
            'amount'  => $total,
        ];
    }

}
