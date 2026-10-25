<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = config('pulso.admin.user');
        $password = config('pulso.admin.password');

        // Sem credenciais configuradas: liberado só em ambiente local.
        if (! $user || ! $password) {
            if (app()->environment('local')) {
                return $next($request);
            }

            abort(403, 'Defina PULSO_ADMIN_USER e PULSO_ADMIN_PASSWORD para acessar o painel.');
        }

        // As duas comparações são feitas antes do "if" para não vazar,
        // pelo tempo de resposta, qual dos dois campos errou.
        $validUser = hash_equals($user, (string) $request->getUser());
        $validPassword = hash_equals($password, (string) $request->getPassword());

        if (! ($validUser && $validPassword)) {
            return response('Acesso restrito.', 401, [
                'WWW-Authenticate' => 'Basic realm="Pulso", charset="UTF-8"',
            ]);
        }

        return $next($request);
    }
}