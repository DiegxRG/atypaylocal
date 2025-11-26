<?php

namespace App\Http\Controllers\Api\Investments;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Requests\Investments\StoreInvestmentRequest;
use App\Services\InvestmentService;
use Illuminate\Http\JsonResponse;
use App\Models\Investment;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    protected InvestmentService $investmentService;

    public function __construct(InvestmentService $investmentService)
    {
        $this->investmentService = $investmentService;
    }

    /**
     * Listar mis inversiones (todas: pendientes, activas, finalizadas)
     */
    public function index(): JsonResponse
    {
        $user = auth('api')->user();
        $investments = $this->investmentService->getUserInvestments($user);

        $investments->transform(function ($investment) {
            $investment = $this->investmentService->autoUpdateEarnings($investment);

            // Redondear valores a 2 decimales antes de devolver
            $investment->daily_earning   = round($investment->daily_earning, 2);
            $investment->total_earning   = round($investment->total_earning, 2);
            $investment->already_earned  = round($investment->already_earned, 2);

            return $investment;
        });

        return response()->json($investments);
    }

    /**
     * Registrar nueva inversión
     */
    public function store(StoreInvestmentRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        $data = [
            'promotion_id' => $request->input('promotion_id'),
        ];

        try {
            $investment = $this->investmentService->store($user, $data);

            // Redondear valores
            // Nota: daily_earning será 0 aquí, lo cual es correcto para 'pending'
            $investment->daily_earning   = round($investment->daily_earning, 2);
            $investment->total_earning   = round($investment->total_earning, 2);
            $investment->already_earned  = round($investment->already_earned, 2);

            return response()->json([
                'message' => 'Inversión registrada correctamente. Pendiente de validación del administrador.',
                'investment' => $investment
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Ver ganancias diarias de una inversión específica
     */
    public function dailyGains($id): JsonResponse
    {
        $user = auth('api')->user();
        $investment = Investment::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        // No es necesario llamar a autoUpdateEarnings aquí, 
        // porque getInvestmentDailyGains ya lo hace internamente.
        // $this->investmentService->autoUpdateEarnings($investment); 

        $gains = $this->investmentService->getInvestmentDailyGains($user, $id);

        // Redondear ganancias diarias
        foreach ($gains['gains_by_day'] as &$day) {
            $day['gain'] = round($day['gain'], 2);
        }

        return response()->json($gains);
    }

    /**
     * Ver ganancias mensuales de una inversión específica
     */
    public function monthlyGains($id): JsonResponse
    {
        $user = auth('api')->user();
        
        // getInvestmentGains ya llama a autoUpdateEarnings internamente.
        $gains = $this->investmentService->getInvestmentGains($user, $id);

        // Redondear ganancias mensuales
        foreach ($gains['gains_by_month'] as &$month) {
            $month['gain'] = round($month['gain'], 2);
        }

        return response()->json($gains);
    }

    public function withdrawEarnings($id): JsonResponse
    {
        /**
         * @var \App\Models\User $user
         */
        $user = auth('api')->user();
        $investment = Investment::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        try {
            // withdrawEarnings ya llama a autoUpdateEarnings internamente.
            $monto = $this->investmentService->withdrawEarnings($user, $investment);

            return response()->json([
                'message'      => 'Ganancias retiradas exitosamente.',
                'monto'        => $monto, // Ya está redondeado por el service
                'nuevo_saldo'  => round($user->atipay_money, 2)
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    //
    // --- FUNCIÓN DE ADMIN CORREGIDA ---
    //
    public function getActiveSummaryForAdmin(Request $request)
    {
        try {
            $activeInvestments = Investment::with(['user', 'promotion'])
                ->where('status', 'active')
                ->latest()
                ->get();

            $summary = $activeInvestments->map(function ($investment) {

                // 1. ¡CORRECTO! Sincroniza las ganancias primero.
                $investment = $this->investmentService->autoUpdateEarnings($investment);

                // --- INICIO DE LA LÓGICA CORREGIDA ---
                
                // 2. Usar las fechas REALES de la inversión
                $startDate = Carbon::parse($investment->start_date); // ¡CORRECTO!
                $endDate = Carbon::parse($investment->end_date);     // ¡CORRECTO!
                $now = Carbon::now();

                // 3. Calcular días pasados (desde el inicio real)
                // Usamos min() para no contar días si 'now' es después de 'endDate'
                // Cálculo corregido — sin +1 y sin offsets que suman un día extra
                $daysPassed = $startDate->diffInDays($now);

               
                // 4. Calcular días totales (desde el inicio real)
                 // +1 porque contamos el día de inicio y fin
                $totalDays = $startDate->diffInDays($endDate) + 1;
                $daysRemaining = max($totalDays - $daysPassed, 0);
                
                // --- FIN DE LA LÓGICA CORREGIDA ---

                return [
                    // Identificadores
                    'id' => $investment->id,

                    // Usuario
                    'usuario_nombre' => $investment->user?->username ?? 'Usuario Desconocido',
                    'usuario_email'  => $investment->user?->email ?? null,

                    // Datos de la inversión
                    'promocion_nombre' => $investment->promotion?->name ?? 'Sin Promoción',
                    'monto_invertido'  => $investment->promotion 
                        ? round($investment->promotion->atipay_price_promotion, 2) 
                        : 0,

                    // Ganancias (Ahora 100% correctas)
                    'retorno_diario_calculado' => round($investment->daily_earning, 2),
                    'retorno_total_estimated'  => round($investment->total_earning, 2),
                    'retorno_total_generado'   => round($investment->already_earned, 2),

                    // Fechas (Ahora 100% correctas)
                    'fecha_inicio'     => $startDate->toDateString(),
                    'fecha_fin'        => $endDate->toDateString(), // Bueno tenerlo
                    'dias_transcurridos' => round($daysPassed, 0),// el daysPassed calculado para que se vea de forma correcta
                    'dias_restantes'     => round($daysRemaining, 0),
   

                    // Estado
                    'estado' => $investment->status,

                    // Historial de ganancias (Opcional, puede ser lento si hay muchas)
                    // 'ultimos_7_dias' => array_slice($dailyReturns['gains_by_day'], -7)
                ];
            });

            return response()->json($summary);

        } catch (\Exception $e) {
            \Log::error('Error en getActiveSummaryForAdmin: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener el resumen de inversiones activas.'], 500);
        }
    }



    /** * Acciones Admin 
     */

    /** * Solicitudes de inversiones pendientes
     */
    public function pending()
    {
        $pending = Investment::with('user', 'promotion')
                        ->where('status', 'pending')
                        ->latest()
                        ->get();
        
        // Redondeamos para mostrar, aunque 'daily_earning' será 0
        $pending->transform(function ($investment) {
            $investment->total_earning   = round($investment->total_earning, 2);
            $investment->daily_earning   = round($investment->daily_earning, 2);
            return $investment;
        });

        return response()->json($pending);
    }

    /** * Solicitudes de inversiones activas
     */
    public function active()
    {
        $active = Investment::with('user', 'promotion')
                        ->where('status', 'active')
                        ->latest()
                        ->get();

        // Es BUENA PRÁCTICA actualizar ganancias aquí también
        $active->transform(function ($investment) {
            $investment = $this->investmentService->autoUpdateEarnings($investment);
            
            $investment->daily_earning   = round($investment->daily_earning, 2);
            $investment->total_earning   = round($investment->total_earning, 2);
            $investment->already_earned  = round($investment->already_earned, 2);
            return $investment;
        });

        return response()->json($active);
    }

    /** * Solicitudes de inversiones aprobadas
     */
    public function approve(Request $request, $id)
    {
        $investment = Investment::findOrFail($id);

        if ($investment->status !== 'pending') {
            return response()->json(['error' => 'Esta inversión ya fue validada.'], 400);
        }

        $this->investmentService->approve($investment, $request->admin_message);

        return response()->json(['message' => 'Inversión aprobada exitosamente.']);
    }

    /** * Solicitudes de inversiones rechazadas
     */
    public function reject(Request $request, $id)
    {
        $investment = Investment::findOrFail($id);
        
        if ($investment->status !== 'pending') {
            return response()->json(['error' => 'Esta inversión ya fue validada.'], 400);
        }

        $this->investmentService->reject($investment, $request->input('admin_message'));

        return response()->json(['message' => 'Inversión rechazada correctamente y saldo reembolsado.']);
    }
}