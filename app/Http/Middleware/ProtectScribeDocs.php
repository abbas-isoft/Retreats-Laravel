<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ProtectScribeDocs
{
    public function handle(Request $request, Closure $next)
    {
        $allowedPassword = env('SCRIBE_DOCS_PASSWORD', 'isoft2025');
        // lifetime in minutes
        $lifetimeMinutes = (int) env('SCRIBE_SESSION_LIFETIME', 30);

        // If session says authenticated AND we have a timestamp, check expiry
        if (session('scribe_authenticated') && session('scribe_authenticated_at')) {
            $authenticatedAt = (int) session('scribe_authenticated_at');
            $now = time();
            $ageSeconds = $now - $authenticatedAt;

            if ($ageSeconds <= $lifetimeMinutes * 60) {
                // Refresh timestamp to extend session on activity
                session(['scribe_authenticated_at' => $now]);
                return $next($request);
            }

            // session expired — remove flags
            session()->forget(['scribe_authenticated', 'scribe_authenticated_at']);
        }

        // When user submits password via POST
        if ($request->isMethod('post')) {
            if ($request->input('password') === $allowedPassword) {
                $now = time();
                session([
                    'scribe_authenticated'    => true,
                    'scribe_authenticated_at' => $now,
                ]);
                // Redirect to the docs page (or back) after successful login
                return redirect('/docs');
            } else {
                $error = '❌ Incorrect password. Please try again.';
            }
        }

        // Show password form (POST-based, no password in URL)
        return response('
            <html>
                <head><title>Protected Docs</title></head>
                <body style="font-family:sans-serif;text-align:center;margin-top:100px;">
                    <h2>🔒 Enter Password to Access API Docs</h2>
                    '.(!empty($error) ? '<p style="color:red;">'.$error.'</p>' : '').'
                    <form method="POST">
                        <input type="hidden" name="_token" value="'.csrf_token().'">
                        <input type="password" name="password" placeholder="Enter Password" style="padding:8px;width:250px;">
                        <button type="submit" style="padding:8px 15px;margin-left:10px;">Access</button>
                    </form>
                    <p style="margin-top:12px;font-size:12px;color:#666;">Session expires after '.$lifetimeMinutes.' minutes of inactivity.</p>
                </body>
            </html>
        ');
    }
}
