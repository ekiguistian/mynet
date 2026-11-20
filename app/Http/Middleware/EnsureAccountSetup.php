<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountSetup
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->mustCompleteAccountSetup() && ! $request->is('admin/profile-setup*')) {
            return redirect('/admin/profile-setup');
        }

        return $next($request);
    }
}
