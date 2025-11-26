<?php

namespace App\Services;

use App\Models\User;
use App\Models\Parametry;
use App\Models\Role;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthUserService
{ 
    public function registerAdmin(array $data)
    {
        $referredBy = null;
        $partnerRoleId = Role::where('name', User::ROLE_PARTNER)->value('id');

        $paramUserLimit = Parametry::where('name', 'Usuarios')->first();

        if ($paramUserLimit) {
            $maxUsers = (int) $paramUserLimit->quantity;
            $currentUsers = User::count();

            if ($maxUsers > 0 && $currentUsers >= $maxUsers) {
                throw ValidationException::withMessages([
                    'limit' => ["Se ha alcanzado el número máximo permitido de usuarios. No se pueden registrar más usuarios en este momento, contacte a soporte administrativo."]
                ]);
            }
        }

        if (!empty($data['reference_code'])) {
            $referrer = User::where('reference_code', $data['reference_code'])->first();

            if (!$referrer) {
                throw ValidationException::withMessages([
                    'reference_code' => ['El código de referencia no es válido.']
                ]);
            }

            $param = Parametry::where('name', 'Registro')->first();

            if ($param && $param->state == 1) {
                if ($referrer->accumulated_points < 100) {
                    throw ValidationException::withMessages([
                        'reference_code' => ['El usuario que proporcionó este código no cumple con los requisitos para referir.']
                    ]);
                }
            }

            $referredBy = $referrer->id;
        }

        return User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'password' => $data['password'],
            'role_id' => $partnerRoleId,
            'status' => 'inactive',
            'reference_code' => Str::random(8),
            'referred_by' => $referredBy,
            'registration_date' => now('America/Lima')->toDateString(),
            'registration_time' => now('America/Lima')->format('h:i:s A'),
        ]);
    }


    public function login(array $credentials)
    {
        try {
            $loginField = filter_var($credentials['username'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    
            // Primero, verificar que el usuario existe
            $user = User::where($loginField, $credentials['username'])->first();
            
            if (!$user) {
                throw new \Exception('Usuario no encontrado.');
            }
    
            // Verificar que la contraseña sea correcta
            if (!Hash::check($credentials['password'], $user->password)) {
                throw new \Exception('Contraseña incorrecta.');
            }
    
            // Intentar generar el token
            $attempt = [
                $loginField => $credentials['username'],
                'password'  => $credentials['password'],
            ];
    
            if (!$token = JWTAuth::attempt($attempt)) {
                throw new \Exception('No se pudo generar el token de autenticación. Por favor, intenta de nuevo.');
            }
    
            $user = $user->load('role');
    
            // Revisar inactividad: si pasaron 3 meses desde su último login
            if ($user->last_login_at && Carbon::parse($user->last_login_at)->lt(now()->subMonths(3))) {
                $user->login_inactive = true;
                $user->save();
            }
    
            // 🚫 Si login_inactive está true => no puede acceder
            if ($user->login_inactive) {
                throw new \Exception('Tu cuenta está inactiva por inactividad prolongada. Contacta a un administrador.');
            }
    
            // Si todo está bien, actualizamos el último login
            $user->last_login_at = now('America/Lima');
            $user->save();
    
            return [
                'token' => $token,
                'user'  => $user
            ];
    
        } catch (JWTException $e) {
            throw new \Exception('Error en JWT: ' . $e->getMessage());
        }
    }
    
    public function deactivateUserByAdmin(int $userId)
    {
        $user = User::find($userId);
    
        if (!$user) {
            throw new \Exception("El usuario con ID {$userId} no existe.");
        }
    
        if ($user->hasRole(User::ROLE_ADMIN)) {
            throw new \Exception("No puedes dar de baja a un Admin.");
        }
    
        $user->login_inactive = true;
        $user->reference_code_valid = false;
        $user->save();
    
        return $user;
    }
    
    public function reactivateUserByAdmin(int $userId)
    {
        $user = User::find($userId);
    
        if (!$user) {
            throw new \Exception("El usuario con ID {$userId} no existe.");
        }
    
        $user->login_inactive = false;
        $user->reference_code_valid = true;
        $user->save();
    
        return $user;
    }

    public function findUserByIdentifier(string $identifier): ?User
    {
        $user = User::where('username', $identifier)
            ->orWhere('phone_number', $identifier)
            ->orWhere('reference_code', $identifier)
            ->first();

        if (!$user) {
            throw new \Exception("Usuario no encontrado.");
        }

        return $user;
    }

    public function getUsersByRolePartnerOrAdmin()
    {
        $partnerRoleId = Role::where('name', User::ROLE_PARTNER)->value('id');
        $adminRoleId   = Role::where('name', User::ROLE_ADMIN)->value('id');


        //$users = User::whereIn('role_id', [$partnerRoleId, $adminRoleId])
            //->with(['role', 'referrer.role'])
            //->orderBy('username')
            //->get();
        $users = User::whereIn('role_id', [$partnerRoleId, $adminRoleId])
            ->whereNotIn('id', [10, 11, 14]) 
            ->with(['role', 'referrer.role'])
            ->orderBy('username')
            ->get();


        // Mapeo para la respuesta
        return $users->map(function ($user) {
            return [
                'id'                => $user->id,
                'username'          => $user->username,
                'email'             => $user->email,
                'phone_number'      => $user->phone_number,
                'role_id'           => $user->role_id,
                'status'            => $user->status,
                'atipay_money'      => $user->atipay_money,
                'accumulated_points'=> $user->accumulated_points,
                'reference_code'    => $user->reference_code,
                'referred_by'       => $user->referred_by,
                'registration_date' => $user->registration_date,
                'registration_time' => $user->registration_time,
                'referral_url'      => $user->referral_url,
                'role' => [
                    'id'   => $user->role->id,
                    'name' => $user->role->name,
                ],
  
                'referrer' => $user->referrer ? [
                    'username' => $user->referrer->username,
                ] : null,
                'login_inactive' => $user->login_inactive,
            ];
        });
    }

    public function updatePartnerByAdmin(int $partnerId, array $data)
    {
        $partnerRoleId = Role::where('name', User::ROLE_PARTNER)->value('id');
    
        $user = User::with('role')
            ->where('id', $partnerId)
            ->where('role_id', $partnerRoleId)
            ->first();
    
        if (!$user) {
            throw new \Exception("El usuario con ID {$partnerId} no es un partner o no existe.");
        }
    
        $user->update($data);
    
        return $user;
    }

    public function updateOwnProfile(array $data)
    {
        $user = JWTAuth::parseToken()->authenticate();
    
        if (!$user) {
            throw new \Exception('Usuario no autenticado.');
        }
    
        // Solo partners y admins pueden editar su perfil
        if (!$user->hasRole(User::ROLE_PARTNER) && !$user->hasRole(User::ROLE_ADMIN)) {
            throw new \Exception('No tienes permiso para editar tu perfil.');
        }
    
        // Si no es admin, no puede modificar el phone_number
        if (!$user->hasRole(User::ROLE_ADMIN) && isset($data['phone_number'])) {
            unset($data['phone_number']);
        }
    
        $user->update($data);
    
        return $user;
    }

    public function refreshToken()
    {
        return JWTAuth::parseToken()->refresh();
    }

    public function getUser()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                throw new \Exception('Usuario no autenticado.');
            }
            return $user;
        } catch (JWTException $e) {
            throw new \Exception('Error de token: ' . $e->getMessage());
        }
    }

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());  
            return 'Cierre de Sesión Exitosa';  
        } catch (JWTException $e) {
            throw new JWTException('No se pudo invalidar el token: ' . $e->getMessage());
        }
    }
}
