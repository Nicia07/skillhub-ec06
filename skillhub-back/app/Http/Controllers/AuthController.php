<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * L'inscription et la connexion sont deleguees au microservice Spring
     * Boot SSO : c'est lui qui stocke le mot de passe (hash BCrypt) et
     * verifie les identifiants, Laravel ne fait plus ce controle localement.
     * La table "users" reste la source de verite pour les donnees metier
     * (role, pseudo, cles etrangeres formations/inscriptions/signalements),
     * et un token JWT Laravel (Tymon) continue d'etre emis pour ne pas
     * casser les routes deja protegees par ce systeme.
     */
    private function ssoRequest()
    {
        return Http::withHeaders([
            'X-Master-Key' => config('services.sso.master_key'),
        ])->timeout(config('services.sso.timeout'));
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pseudo' => 'required|string|max:50',
            'email' => 'required|string|email|max:191|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:apprenant,formateur',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        try {
            $ssoResponse = $this->ssoRequest()->post(
                rtrim(config('services.sso.base_url'), '/').'/api/auth/register',
                $request->only('email', 'password', 'role')
            );
        } catch (ConnectionException $e) {
            return response()->json(['message' => 'Microservice SSO indisponible'], 503);
        }

        if ($ssoResponse->failed()) {
            return response()->json(
                $ssoResponse->json() ?? ['message' => 'Inscription SSO impossible'],
                $ssoResponse->status()
            );
        }

        $user = User::create([
            'pseudo' => $request->pseudo,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(compact('user', 'token'), 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $ssoResponse = $this->ssoRequest()->post(
                rtrim(config('services.sso.base_url'), '/').'/api/auth/login',
                $credentials
            );
        } catch (ConnectionException $e) {
            return response()->json(['message' => 'Microservice SSO indisponible'], 503);
        }

        if ($ssoResponse->failed()) {
            return response()->json(['error' => 'Identifiants invalides'], 401);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (! $user) {
            return response()->json(['error' => 'Identifiants invalides'], 401);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function logout(Request $request)
    {
        auth()->logout();

        return response()->json(['message' => 'Déconnexion réussie']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
