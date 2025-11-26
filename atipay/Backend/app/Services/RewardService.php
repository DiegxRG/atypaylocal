<?php

namespace App\Services;

use App\Models\Reward;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Service\Image\SaveImage;
use App\Http\Service\Image\DeleteImage;

class RewardService
{
    use SaveImage, DeleteImage;

    /**
     * Obtener todos los rewards
     */
    public function getAll()
    {
        return Reward::all();
    }

    /**
     * Obtener un reward por ID
     */
    public function getById(int $id): ?Reward
    {
        return Reward::findOrFail($id);
    }

    /**
     * Crear un nuevo reward
     */
    public function store(array $data): Reward
    {
        return DB::transaction(function () use ($data) {
            if (request()->hasFile('reward_image') && request()->file('reward_image') instanceof UploadedFile) {
                $path = $this->upload(request()->file('reward_image'), 'rewards');
                $data['reward_image'] = $path;
            }

            return Reward::create($data);
        });
    }

    public function requestReward(int $rewardId, int $userId): array
    {
        return DB::transaction(function () use ($rewardId, $userId) {
            $user = User::findOrFail($userId);
            $reward = Reward::findOrFail($rewardId);

            if ($user->accumulated_points < $reward->redeem_points) {
                return ['success' => false, 'message' => 'No tienes suficientes puntos'];
            }

            if ($reward->stock <= 0) {
                return [
                    'success' => false,
                    'message' => 'La recompensa ya no está disponible, stock agotado',
                    'error' => [
                        'reward_id' => $reward->id,
                        'name' => $reward->name,
                        'stock' => $reward->stock,
                        'image_url' => $reward->image_url,
                    ]
                ];
            }

            // Restar stock y puntos inmediatamente
            $reward->stock -= 1;
            $reward->save();

            $user->accumulated_points -= $reward->redeem_points;
            $user->save();

            // Crear solicitud pendiente
            $request = \App\Models\RewardRequest::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'status' => 'pending',
            ]);

            return [
                'success' => true,
                'message' => 'Solicitud de canje enviada, pendiente de aprobación',
                'data' => $request
            ];
        });
    }

    public function approveRequest(int $requestId, string $message = null): array
    {
        return DB::transaction(function () use ($requestId, $message) {
            $request = \App\Models\RewardRequest::findOrFail($requestId);

            if ($request->status !== 'pending') {
                return ['success' => false, 'message' => 'La solicitud ya fue procesada'];
            }

            $request->update([
                'status' => 'approved',
                'admin_message' => $message,
            ]);

            return ['success' => true, 'message' => 'Solicitud aprobada'];
        });
    }

    public function rejectRequest(int $requestId, string $message = null): array
    {
        return DB::transaction(function () use ($requestId, $message) {
            $request = \App\Models\RewardRequest::findOrFail($requestId);

            if ($request->status !== 'pending') {
                return ['success' => false, 'message' => 'La solicitud ya fue procesada'];
            }

            // Reintegrar stock y puntos
            $reward = $request->reward;
            $user = $request->user;

            $reward->stock += 1;
            $reward->save();

            $user->accumulated_points += $reward->redeem_points;
            $user->save();

            $request->update([
                'status' => 'rejected',
                'admin_message' => $message,
            ]);

            return ['success' => true, 'message' => 'Solicitud rechazada y recursos reintegrados'];
        });
    }

    public function redeemReward(int $rewardId, int $userId): array
    {
        return DB::transaction(function () use ($rewardId, $userId) {
            $user = User::findOrFail($userId);
            $reward = Reward::findOrFail($rewardId);

            if ($user->accumulated_points < $reward->redeem_points) {
                return [
                    'success' => false,
                    'message' => 'No tienes suficientes puntos para canjear esta recompensa'
                ];
            }

            if ($reward->stock <= 0) {
                return [
                    'success' => false,
                    'message' => 'No hay stock disponible para esta recompensa'
                ];
            }

            // Restar stock
            $reward->stock -= 1;
            $reward->save();

            // Restar puntos
            $user->accumulated_points -= $reward->redeem_points;
            $user->save();

            return [
                'success' => true,
                'message' => 'Recompensa canjeada exitosamente',
                'data' => [
                    'reward' => $reward,
                    'remaining_points' => $user->accumulated_points
                ]
            ];
        });
    }

    /**
     * Actualizar un reward
     */
    public function update(int $id, array $data): Reward
    {
        return DB::transaction(function () use ($id, $data) {
            $reward = Reward::findOrFail($id);

            if (request()->hasFile('reward_image') && request()->file('reward_image') instanceof UploadedFile) {
                // Eliminar imagen anterior si existe
                if ($reward->reward_image && Storage::disk('public')->exists($reward->reward_image)) {
                    $this->deleteImage($reward->reward_image);
                }

                $path = $this->upload(request()->file('reward_image'), 'rewards');
                $data['reward_image'] = $path;
            }

            $reward->update($data);

            return $reward;
        });
    }

    /**
     * Eliminar un reward
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $reward = Reward::findOrFail($id);

            if ($reward->reward_image && Storage::disk('public')->exists($reward->reward_image)) {
                $this->deleteImage($reward->reward_image);
            }

            return $reward->delete();
        });
    }
}
