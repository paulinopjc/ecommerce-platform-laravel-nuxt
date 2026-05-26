<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
            'mode'     => $request->query('mode', 'customer'), // 'customer' | 'user'
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
            $token  = $client->fetchAccessTokenWithAuthCode($request->query('code'));

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

            $state        = json_decode($request->query('state', '{}'), true);
            $mode         = $state['mode'] ?? 'customer';
            $redirectPath = $state['redirect'] ?? '/';
            $email        = strtolower($payload['email']);

            if ($mode === 'user') {
                return $this->handleUserCallback($email, $payload, $frontendUrl, $redirectPath);
            }

            return $this->handleCustomerCallback($email, $payload, $frontendUrl, $redirectPath);

        } catch (\Exception $e) {
            return redirect()->away(
                $frontendUrl . '/login?error=' . urlencode('Authentication failed')
            );
        }
    }

    private function handleUserCallback(
        string $email,
        array $payload,
        string $frontendUrl,
        string $redirectPath
    ): RedirectResponse {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->away(
                $frontendUrl . '/admin/login?error=' . urlencode('No backoffice account found for this Google address')
            );
        }

        if (!$user->google_id) {
            $user->update(['google_id' => $payload['sub']]);
        }

        if (!$user->is_active) {
            return redirect()->away(
                $frontendUrl . '/admin/login?error=' . urlencode('Account disabled')
            );
        }

        $sanctumToken = $user->createToken('auth')->plainTextToken;

        return $this->buildCallbackRedirect($frontendUrl, $sanctumToken, [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ], $redirectPath, 'user');
    }

    private function handleCustomerCallback(
        string $email,
        array $payload,
        string $frontendUrl,
        string $redirectPath
    ): RedirectResponse {
        $customer = Customer::where('email', $email)->first();

        if (!$customer) {
            $customer = Customer::create([
                'name'              => $payload['name'] ?? $email,
                'email'             => $email,
                'google_id'         => $payload['sub'],
                'email_verified_at' => now(),
                'is_active'         => true,
            ]);
        } else {
            if (!$customer->google_id) {
                $customer->update(['google_id' => $payload['sub']]);
            }
        }

        if (!$customer->is_active) {
            return redirect()->away(
                $frontendUrl . '/login?error=' . urlencode('Account disabled')
            );
        }

        $sanctumToken = $customer->createToken('auth')->plainTextToken;

        return $this->buildCallbackRedirect($frontendUrl, $sanctumToken, [
            'id'    => $customer->id,
            'name'  => $customer->name,
            'email' => $customer->email,
        ], $redirectPath, 'customer');
    }

    private function buildCallbackRedirect(
        string $frontendUrl,
        string $sanctumToken,
        array $principal,
        string $redirectPath,
        string $type
    ): RedirectResponse {
        $hash = http_build_query([
            'token'    => $sanctumToken,
            'user'     => json_encode($principal),
            'redirect' => $redirectPath,
            'type'     => $type,
        ]);

        return redirect()->away($frontendUrl . '/auth/callback#' . $hash);
    }

    public function me(Request $request): JsonResponse
    {
        $principal = $request->user();

        $data = [
            'id'    => $principal->id,
            'name'  => $principal->name,
            'email' => $principal->email,
            'type'  => $principal instanceof User ? 'user' : 'customer',
        ];

        if ($principal instanceof User) {
            $data['role'] = $principal->role;
        }

        return response()->json(['data' => $data]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
