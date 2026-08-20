<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SsoProfileController extends Controller
{
    // Route protegee par le middleware "sso" : n'est atteinte que si le
    // microservice Spring Boot a valide le token JWT transmis.
    public function show(Request $request)
    {
        return response()->json([
            'message' => 'Acces autorise via authentification SSO',
            'user' => $request->attributes->get('sso_user'),
        ]);
    }
}
