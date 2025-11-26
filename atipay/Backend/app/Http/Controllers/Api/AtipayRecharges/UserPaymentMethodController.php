<?php

namespace App\Http\Controllers\Api\AtipayRecharges;

use App\Http\Controllers\Controller;
use App\Http\Requests\AtipayRecharges\StoreUserPaymentMethodRequest;
use App\Models\UserPaymentMethod;
use Illuminate\Support\Facades\Auth;

class UserPaymentMethodController extends Controller
{
    /**
     * Listar métodos configurados por el usuario autenticado
     */
    public function index()
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        // Si el user es admin, devuelve todos
        if ($user->hasRole('admin')) {
            $methods = UserPaymentMethod::with('method')->get();
        } else {
            // Los partners también ven TODO lo que haya creado el admin
            $methods = UserPaymentMethod::with('method')
                ->whereHas('user.role', function ($q) {
                    $q->where('name', 'admin');
                })
                ->orWhere('user_id', $user->id)
                ->get();
        }

        return response()->json($methods);
    }

    /**
     * Guardar un método de pago para el usuario autenticado
     */
    public function store(StoreUserPaymentMethodRequest $request)
    {
        $data = $request->validated();

        $userMethod = UserPaymentMethod::create([
            'user_id' => auth('api')->id(),
            'payment_method_id' => $data['payment_method_id'],
            'data' => $data['data']
        ]);

        return response()->json([
            'message' => 'Método de pago registrado correctamente.',
            'data' => $userMethod->load('method')
        ], 201);
    }

    /**
     * Actualizar un método de pago del usuario autenticado
     */
    public function update(StoreUserPaymentMethodRequest $request, $id)
    {
        $userId = auth('api')->id();
        $userMethod = UserPaymentMethod::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $data = $request->validated();
        $userMethod->update($data);

        return response()->json([
            'message' => 'Método de pago actualizado correctamente.',
            'data' => $userMethod->load('method')
        ]);
    }

    /**
     * Eliminar un método de pago del usuario autenticado
     */
    public function destroy($id)
    {
        $userId = auth('api')->id();
        $userMethod = UserPaymentMethod::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $userMethod->delete();

        return response()->json([
            'message' => 'Método de pago eliminado correctamente.'
        ]);
    }
}
