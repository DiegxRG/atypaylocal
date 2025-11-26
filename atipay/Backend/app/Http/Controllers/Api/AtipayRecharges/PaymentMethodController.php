<?php

namespace App\Http\Controllers\Api\AtipayRecharges;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    /**
     * Listar todos los métodos de pago disponibles
     */
    public function index()
    {
        return response()->json(PaymentMethod::all());
    }

    /**
     * Crear nuevo método de pago (solo admin)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:payment_methods,name',
            'fields' => 'nullable|array',
        ]);

        $method = PaymentMethod::create($data);

        return response()->json([
            'message' => 'Método de pago creado correctamente.',
            'data' => $method
        ], 201);
    }

    /**
     * Actualizar método de pago (solo admin)
     */
    public function update(Request $request, $id)
    {
        $method = PaymentMethod::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|unique:payment_methods,name,' . $method->id,
            'fields' => 'nullable|array',
        ]);

        $method->update($data);

        return response()->json([
            'message' => 'Método de pago actualizado correctamente.',
            'data' => $method
        ]);
    }

    /**
     * Eliminar método de pago (solo admin)
     */
    public function destroy($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->delete();

        return response()->json([
            'message' => 'Método de pago eliminado correctamente.'
        ]);
    }
}
