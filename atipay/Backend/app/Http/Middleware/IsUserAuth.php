<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsUserAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        // NECESARIO para que $request->user() funcione
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}
