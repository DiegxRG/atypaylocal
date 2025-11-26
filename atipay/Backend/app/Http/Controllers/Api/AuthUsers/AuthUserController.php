<?php

namespace App\Http\Controllers\Api\AuthUsers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthUsers\UpdatePartnerByAdminRequest;
use App\Http\Requests\AuthUsers\UpdateOwnProfileRequest;
use App\Http\Requests\AuthUsers\UpdateAdminProfileRequest;
use App\Http\Requests\AuthUsers\RegisterAuthRequest;
use App\Http\Requests\AuthUsers\LoginAuthRequest;
use App\Services\AuthUserService;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Models\Parametry;
use Illuminate\Http\Request;

class AuthUserController extends Controller
{
    protected $authService;

    public function __construct(AuthUserService $authService)
    {
        $this->authService = $authService;
    }

    public function index(): JsonResponse
    {
        $users = $this->authService->getUsersByRolePartnerOrAdmin();
        return response()->json($users);
    }

    public function registerUser(RegisterAuthRequest $request)
    {
        try {
            // Llamamos al servicio para registrar un nuevo admin
            $this->authService->registerAdmin($request->all());

            return response()->json(['message' => 'Usuario creado con éxito'], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }

    public function loginUser(LoginAuthRequest $request)
    {
        try {
            $result = $this->authService->login($request->only(['username', 'password']));

            return response()->json([
                'token' => $result['token'],
                'expires_in' => JWTAuth::factory()->getTTL() * 60, // Convertir a segundos
                'role' => $result['user']->role->name
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'message' => $e->getMessage()
            ], 401); 
        }
    }

    public function updatePartner(UpdatePartnerByAdminRequest $request, int $id)
    {
        try {
            $user = $this->authService->updatePartnerByAdmin($id, $request->validated());

            return response()->json([
                'message' => 'Usuario partner actualizado con éxito',
                'data'    => [
                    'id'       => $user->id,
                    'username' => $user->username,
                    'email'    => $user->email,
                    'phone_number' => $user->phone_number,
                    'status'   => $user->status,
                    'role'     => $user->role->name,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getState(): JsonResponse
    {
        $param = Parametry::find(1);

        if (!$param) {
            return response()->json(['error' => 'Registro no encontrado.'], 404);
        }

        return response()->json([
            'success' => true,
            'state' => $param->state,
        ], 200);
    }

    public function updateState(Request $request)
    {
        $request->validate([
            'state' => 'required|boolean',
        ]);

        $param = Parametry::find(1);

        if (!$param) {
            return response()->json(['error' => 'Registro no encontrado.'], 404);
        }

        $param->state = $request->state;
        $param->save();

        return response()->json(['message' => 'Estado actualizado correctamente.', 'param' => $param], 200);
    }

    public function getUserQuantity(): JsonResponse
    {
        $param = \App\Models\Parametry::where('name', 'Usuarios')->first();

        if (!$param) {
            return response()->json(['error' => 'Registro de usuarios no encontrado.'], 404);
        }

        return response()->json([
            'success' => true,
            'quantity' => $param->quantity,
        ], 200);
    }
    public function updateUserQuantity(Request $request): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $param = \App\Models\Parametry::where('name', 'Usuarios')->first();

        if (!$param) {
            return response()->json(['error' => 'Registro de usuarios no encontrado.'], 404);
        }

        $param->quantity = $request->quantity;
        $param->save();

        return response()->json([
            'message' => 'Cantidad actualizada correctamente.',
            'quantity' => $param->quantity,
        ], 200);
    }

    public function updateOwnProfile(UpdateOwnProfileRequest $request)
    {
        try {
            $user = $this->authService->updateOwnProfile($request->validated());

            return response()->json([
                'message' => 'Perfil actualizado con éxito',
                'data'    => [
                    'id'       => $user->id,
                    'username' => $user->username,
                    'email'    => $user->email,
                    'phone_number' => $user->phone_number,
                    'status'   => $user->status,
                    'role'     => $user->role->name,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateOwnAdminProfile(UpdateAdminProfileRequest $request)
    {
        try {
            $user = $this->authService->updateOwnProfile($request->validated());

            return response()->json([
                'message' => 'Perfil de administrador actualizado con éxito',
                'data'    => [
                    'id'       => $user->id,
                    'username' => $user->username,
                    'email'    => $user->email,
                    'phone_number' => $user->phone_number,
                    'status'   => $user->status,
                    'role'     => $user->role->name,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function findUser(string $identifier)
    {
        try {
            $user = $this->authService->findUserByIdentifier($identifier);

            return response()->json([
                'id'            => $user->id,
                'username'      => $user->username,
                'email'         => $user->email,
                'phone_number'  => $user->phone_number,
                'reference_code'=> $user->reference_code,
                'status'        => $user->status,
                'role'          => $user->role->name,
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
    
    public function deactivate($id)
    {
        try {
            $user = $this->authService->deactivateUserByAdmin($id); 
            return response()->json([
                'message' => 'Usuario desactivado correctamente',
                'user'    => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    
    public function reactivate($id)
    {
        try {
            $user = $this->authService->reactivateUserByAdmin($id); 
            return response()->json([
                'message' => 'Usuario reactivado correctamente',
                'user'    => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function refreshToken(): JsonResponse
    {
        try {
            // Llamamos al servicio para refrescar el token
            $newToken = $this->authService->refreshToken();
            return new JsonResponse([
                'token' => $newToken,
                'expires_in' => JWTAuth::factory()->getTTL()
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getUser()
    {
        try {
            // Llamamos al servicio para obtener el usuario
            $user = $this->authService->getUser();
            return response()->json($user, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }

    public function logout()
    {
        try {
            // Llamamos al servicio para hacer logout
            $message = $this->authService->logout();
            return response()->json(['message' => $message], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
