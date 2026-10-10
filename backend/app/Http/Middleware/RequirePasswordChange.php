<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->must_change_password) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Debes cambiar tu contraseña antes de continuar.',
            'code' => 'password_change_required',
        ], Response::HTTP_FORBIDDEN);
    }
}
