<?php

namespace Modules\ConfigTransports\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TransportEditProtection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('system.transport_edit_protection', false)) {
            $environmentRole = config('system.environment_role', 'dev');

            if (in_array($environmentRole, ['qa', 'prod'])) {
                abort(403, 'Direct edits are not allowed in '.strtoupper($environmentRole).' environment. Use transport imports instead.');
            }
        }

        return $next($request);
    }
}

