<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ProtectScribeDocs
{
    public function handle(Request $request, Closure $next)
    {
        $allowedPassword = env('SCRIBE_DOCS_PASSWORD', 'isoft2025');

        // ✅ If already authenticated in this session
        if (session('scribe_authenticated')) {
            return $next($request);
        }

        // ✅ When user submits password via POST
        if ($request->isMethod('post')) {
            if ($request->input('password') === $allowedPassword) {
                session(['scribe_authenticated' => true]);
                return redirect('/docs');
            } else {
                $error = '❌ Incorrect password. Please try again.';
            }
        }

        // ✅ Show password form (POST-based, no password in URL)
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
                </body>
            </html>
        ');
    }
}
