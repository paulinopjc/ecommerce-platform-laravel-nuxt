<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private function getGoogleClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect_uri'));
        $client->addScope('openid');
        $client->addScope('email');
        $client->addScope('profile');

        return $client;
    }

    public function redirect(Request $request): RedirectResponse
    {
        $client = $this->getGoogleClient();
        $client->setState(json_encode([
            'redirect' => $request->query('redirect', '/'),
        ]));
        $client->setPrompt('select_account');
        return redirect()->away($client->createAuthUrl());
    }

    public function callback(Request $request): RedirectResponse
    {
        $frontendUrl = rtrim(config('app.frontend_url'), '/');

        if (!$request->has('code')) {
            return redirect()->away(
                $frontendUrl . '/login?error=' . urlencode('Missing authorization code')
            );
        }

        try {
            $client = $this->getGoogleClient();
            $token = $client->fetchAccessTokenWithAuthCode($request->query('code'));

            if (isset($token['error'])) {
                return redirect()->away(
                    $frontendUrl . '/login?error=' . urlencode('Google authentication failed')
                );
            }

            $payload = $client->verifyIdToken($token['id_token']);
            if (!$payload || !isset($payload['email'])) {
                return redirect()->away(
                    $frontendUrl . '/login?error=' . urlencode('Invalid Google token')
                );
            }

            $email = strtolower($payload['email']);
            $user = User::where('email', $email)->first();

            if (!$user) {
                // Auto-create customer accounts
                $user = User::create([
                    'name' => $payload['name'] ?? $email,
                    'email' => $email,
                    'google_id' => $payload['sub'],
                    'role' => User::ROLE_CUSTOMER,
                    'email_verified_at' => now(),
                ]);
            } else {
                if (!$user->google_id) {
                    $user->update(['google_id' => $payload['sub']]);
                }
            }

            if (!$user->is_active) {
                return redirect()->away(
                    $frontendUrl . '/login?error=' . urlencode('Account disabled')
                );
            }

            $sanctumToken = $user->createToken('auth')->plainTextToken;

            $state = json_decode($request->query('state', '{}'), true);
            $redirectPath = $state['redirect'] ?? '/';

            $userObj = json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]);

            $hash = http_build_query([
                'token' => $sanctumToken,
                'user' => $userObj,
                'redirect' => $redirectPath,
            ]);

            return redirect()->away($frontendUrl . '/auth/callback#' . $hash);
        } catch (\Exception $e) {
            return redirect()->away(
                $frontendUrl . '/login?error=' . urlencode('Authentication failed')
            );
        }
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
