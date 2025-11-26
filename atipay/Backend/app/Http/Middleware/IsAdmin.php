<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        if ($user->role && $user->role->name === User::ROLE_ADMIN) {
            return $next($request);
        }

        return response()->json(['message' => 'No eres Admin'], 403);
    }
}
