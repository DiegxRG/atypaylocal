<?php

namespace App\Http\Controllers\Api\Purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\PurchaseRequestService;
use Tymon\JWTAuth\Facades\JWTAuth;

class PurchaseRequestController extends Controller
{
    protected PurchaseRequestService $service;

    public function __construct(PurchaseRequestService $service)
    {
        $this->service = $service;
    }

    /**
     * Listar purchase requests del usuario autenticado
     */
    public function index(): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $data = $this->service->listUserPurchaseRequests($user);

            return response()->json([
                'message' => 'Purchase requests obtenidas correctamente',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
