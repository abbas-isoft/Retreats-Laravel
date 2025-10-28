<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ProtectScribeDocs
{
    public function handle(Request $request, Closure $next)
    {
        $password = env('SCRIBE_DOCS_PASSWORD', null);

        // agar password .env me nahi hai to allow
        if (empty($password)) {
            return $next($request);
        }

        // Browser auth check
        if ($request->getUser() || $request->getPassword()) {
            if ($request->getPassword() === $password) {
                return $next($request);
            }
        }

        // Wrong ya missing password par popup dikhao
        $headers = ['WWW-Authenticate' => 'Basic realm="Protected API Docs"'];
        return response('Unauthorized', 401, $headers);
    }
}
