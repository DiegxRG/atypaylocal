<?php

namespace App\Http\Controllers\Api\Promotions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\StorePromotionRequest;
use App\Http\Requests\Promotions\UpdatePromotionRequest;
use App\Services\PromotionService;
use Illuminate\Http\JsonResponse;

class PromotionController extends Controller
{
    protected $promotionService;

    public function __construct(PromotionService $promotionService)
    {
        $this->promotionService = $promotionService;
    }

    public function index(): JsonResponse
    {
        $promotions = $this->promotionService->getAll();
        return response()->json($promotions);
    }

    public function store(StorePromotionRequest $request): JsonResponse
    {
        $promotion = $this->promotionService->create($request->validated());
        return response()->json($promotion, 201);
    }

    public function show($id): JsonResponse
    {
        $promotion = $this->promotionService->getById($id);
        if (!$promotion) {
            return response()->json(['message' => 'Promocion no encontrada'], 404);
        }
        return response()->json($promotion);
    }

    public function update(UpdatePromotionRequest $request, $id): JsonResponse
    {
        $promotion = $this->promotionService->update($id, $request->validated());
        if (!$promotion) {
            return response()->json(['message' => 'Promocion no encontrada'], 404);
        }
        return response()->json($promotion);
    }

    public function destroy($id): JsonResponse
    {
        $deleted = $this->promotionService->delete($id);
        return $deleted
            ? response()->json(['message' => 'Promocion eliminada con exito'])
            : response()->json(['message' => 'Promocion no encontrada'], 404);
    }
}
