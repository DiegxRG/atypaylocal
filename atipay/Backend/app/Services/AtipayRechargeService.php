<?php

namespace App\Services;

use App\Models\AtipayRecharge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Http\Service\Image\SaveImage;
use App\Http\Service\Image\DeleteImage;
use Illuminate\Http\UploadedFile;

class AtipayRechargeService
{
    use SaveImage;

    /**
     * Crear nueva solicitud de recarga
     */
    public function create(array $data): AtipayRecharge
    {
        if (request()->hasFile('proof_image') && request()->file('proof_image') instanceof UploadedFile) {
            // Guardar imagen y generar URL accesible
            $path = $this->upload(request()->file('proof_image'), 'atipay-recharges');
            $data['proof_image_path'] = $path;
        }

        $data['request_date'] = now('America/Lima')->toDateString();
        $data['request_time'] = now('America/Lima')->format('h:i:s');

        return AtipayRecharge::create($data);
    }

    /**
     * Obtener recargas del usuario autenticado
     */
    public function getUserRecharges(int $userId)
    {
        return AtipayRecharge::where('user_id', $userId)->latest()->get();
    }

    /**
     * Obtener todas las recargas (Admin)
     */
    public function getAll()
    {
        return AtipayRecharge::with(['user', 'approver'])->latest()->get();
    }

    /**
     * Obtener una recarga por ID
     */
    public function getById(int $id): ?AtipayRecharge
    {
        return AtipayRecharge::with(['user', 'approver'])->find($id);
    }

    /**
     * Aprobar una recarga y asignar Atipays
     */
    public function approveRecharge(int $id, int $adminId): ?AtipayRecharge
    {
        return DB::transaction(function () use ($id, $adminId) {
            $recharge = AtipayRecharge::findOrFail($id);

            if ($recharge->status !== 'pending') {
                throw new \Exception('La recarga ya fue procesada.');
            }

            // Actualizar recarga
            $recharge->update([
                'status' => 'approved',
                'approved_by' => $adminId,
                'atipays_granted' => $recharge->amount,
                'processed_date' => now('America/Lima')->toDateString(),
                'processed_time' => now('America/Lima')->format('h:i:s'),
            ]);

            // Actualizar saldo unificado y puntos
            $user = $recharge->user;
            $user->atipay_money += $recharge->amount;
            $user->save();

            return $recharge;
        });
    }

    /**
     * Rechazar recarga
     */
    public function rejectRecharge(int $id, int $adminId): ?AtipayRecharge
    {
        $recharge = AtipayRecharge::findOrFail($id);

        if ($recharge->status !== 'pending') {
            throw new \Exception('La recarga ya fue procesada.');
        }

        $recharge->update([
            'status' => 'rejected',
            'approved_by' => $adminId,
            'processed_date' => now('America/Lima')->toDateString(),
            'processed_time' => now('America/Lima')->format('h:i:s'),
        ]);

        return $recharge;
    }
}
