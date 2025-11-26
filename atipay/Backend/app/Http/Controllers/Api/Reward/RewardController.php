<?php

namespace App\Http\Controllers\Api\Reward;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rewards\StoreRewardRequest;
use App\Http\Requests\Rewards\UpdateRewardRequest;
use App\Services\RewardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class RewardController extends Controller
{
    protected RewardService $rewardService;

    public function __construct(RewardService $rewardService)
    {
        $this->rewardService = $rewardService;
    }

    /**
     * Listar todas las recompensas
     */
    public function index(): JsonResponse
    {
        $rewards = $this->rewardService->getAll();
        return response()->json($rewards, 200);
    }

    /**
     * Crear una nueva recompensa
     */
    public function store(StoreRewardRequest $request): JsonResponse
    {
        $reward = $this->rewardService->store($request->validated());

        return response()->json([
            'message' => 'Recompensa creada exitosamente',
            'data' => [
                'id' => $reward->id,
                'name' => $reward->name,
                'description' => $reward->description,
                'redeem_points' => $reward->redeem_points,
                'stock' => $reward->stock,
                'image_url' => $reward->image_url,
            ]
        ], 201);
    }

    public function requestRedeem(int $id): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $result = $this->rewardService->requestReward($id, $user->id);
        $status = $result['success'] ? 201 : 400;

        return response()->json($result, $status);
    }
    
    // Admin: ver todas las solicitudes
   // Admin: ver todas las solicitudes
    public function requests(): JsonResponse
    {
        $requests = \App\Models\RewardRequest::with([
            'user:id,username,email',
            'reward:id,name,reward_image'
        ])
            ->orderBy('created_at', 'desc')
            ->get()
            // 👇 AGREGAMOS ESTO: Mapeamos los datos para asegurar que la fecha vaya incluida
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'user' => $request->user,
                    'reward' => $request->reward,
                    'status' => $request->status,
                    'admin_message' => $request->admin_message,
                    'created_at' => $request->created_at, // <--- ¡ESTO ES LO IMPORTANTE!
                ];
            });

        return response()->json($requests);
    }
    
    public function myRequests(): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['message' => 'Usuario no autenticado'], 401);
        }

        // Verificar que sea partner (role_id == 2, por ejemplo)
        if ($user->role_id !== 2) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $requests = \App\Models\RewardRequest::with([
            'reward:id,name,reward_image'
        ])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    public function approveRequest(int $id): JsonResponse
    {
        $message = request('admin_message'); 
        $result = $this->rewardService->approveRequest($id, $message);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function rejectRequest(int $id): JsonResponse
    {
        $message = request('admin_message'); 
        $result = $this->rewardService->rejectRequest($id, $message);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Canjear una recompensa
     */
    public function redeem(int $id): JsonResponse
    {
        $user = auth('api')->user(); 

        if (!$user) {
            return response()->json(['message' => 'Usuario no autenticado'], 401);
        }

        $result = $this->rewardService->redeemReward($id, $user->id);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 400);
        }

        return response()->json([
            'message' => $result['message'],
            'reward' => [
                'id' => $result['data']['reward']->id,
                'name' => $result['data']['reward']->name,
                'description' => $result['data']['reward']->description,
                'redeem_points' => $result['data']['reward']->redeem_points,
                'stock' => $result['data']['reward']->stock,
                'image_url' => $result['data']['reward']->image_url,
            ],
            'remaining_points' => $result['data']['remaining_points']
        ], 200);
    }

    /**
     * Mostrar una recompensa específica
     */
    public function show(int $id): JsonResponse
    {
        $reward = $this->rewardService->getById($id);

        if (!$reward) {
            return response()->json(['message' => 'Recompensa no encontrada'], 404);
        }

        return response()->json($reward, 200);
    }

    /**
     * Actualizar una recompensa
     */
    public function update(UpdateRewardRequest $request, int $id): JsonResponse
    {
        $reward = $this->rewardService->update($id, $request->validated());

        if (!$reward) {
            return response()->json(['message' => 'Recompensa no encontrada'], 404);
        }

        return response()->json([
            'message' => 'Recompensa actualizada exitosamente',
            'data' => [
                'id' => $reward->id,
                'name' => $reward->name,
                'description' => $reward->description,
                'redeem_points' => $reward->redeem_points,
                'stock' => $reward->stock,
                'image_url' => $reward->image_url,
            ]
        ], 200);
    }

    /**
     * Eliminar una recompensa
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->rewardService->delete($id);

        if (!$deleted) {
            return response()->json(['message' => 'Recompensa no encontrada'], 404);
        }

        return response()->json(['message' => 'Recompensa eliminada exitosamente'], 200);
    }
}