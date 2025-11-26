<?php

namespace App\Services;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Collection;

class PromotionService
{
    public function getAll(): Collection
    {
        return Promotion::all();
    }

    public function getById(int $id): ?Promotion
    {
        return Promotion::find($id);
    }

    public function create(array $data): Promotion
    {
        return Promotion::create($data);
    }

    public function update(int $id, array $data): ?Promotion
    {
        $promotion = Promotion::find($id);
        if ($promotion) {
            $promotion->update($data);
        }
        return $promotion;
    }

    public function delete(int $id): bool
    {
        $promotion = Promotion::find($id);
        if ($promotion) {
            return $promotion->delete();
        }
        return false;
    }
}
