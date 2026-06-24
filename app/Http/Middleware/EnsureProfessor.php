<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfessor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isProfessor()) {
            abort(403);
        }

        return $next($request);
    }
}
