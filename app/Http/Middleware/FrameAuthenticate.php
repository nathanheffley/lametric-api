<?php

namespace App\Http\Middleware;

use App\Exceptions\FrameAuthenticationException;
use Closure;
use Illuminate\Http\Request;

class FrameAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $expected = 'Basic ' . base64_encode(config('auth.username') . ':' . config('auth.password'));

        throw_if($request->header('authorization') !== $expected, FrameAuthenticationException::class);

        return $next($request);
    }
}
