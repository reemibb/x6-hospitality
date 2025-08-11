<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Models\LoginAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $maxAttempts = 5;
    protected $decayMinutes = 15;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $key = 'register_attempts:' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 3)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many registration attempts. Please try again later.'
                ], 429);
            }

            RateLimiter::hit($key, 300); 

            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role ?? 'guest',
                'profile_info' => $request->profile_info,
            ];

            if (Schema::hasColumn('users', 'email_verified_at')) {
                $userData['email_verified_at'] = null;
            }

            $user = User::create($userData);

            $token = $user->createToken(
                'auth_token',
                ['*'],
                now()->addHours(24) 
            )->plainTextToken;

            Log::info('User registered successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            $response = [
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'created_at' => $user->created_at,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_at' => now()->addHours(24)->toISOString(),
                ]
            ];

            if (Schema::hasColumn('users', 'email_verified_at')) {
                $response['data']['user']['email_verified'] = false;
                $response['message'] = 'User registered successfully. Please verify your email.';
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? 'unknown',
                'ip_address' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $email = $request->email;
        $ip = $request->ip();
        $rateLimitKey = 'login_attempts:' . $email . ':' . $ip;
        $userLockKey = 'user_locked:' . $email;

        try {
            if (Cache::has($userLockKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is temporarily locked due to multiple failed login attempts.',
                    'retry_after' => Cache::get($userLockKey . '_retry_after')
                ], 423);
            }

            if (RateLimiter::tooManyAttempts($rateLimitKey, $this->maxAttempts)) {
                $seconds = RateLimiter::availableIn($rateLimitKey);
                return response()->json([
                    'success' => false,
                    'message' => 'Too many login attempts.',
                    'retry_after' => $seconds
                ], 429);
            }

            $user = User::where('email', $email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                RateLimiter::hit($rateLimitKey, $this->decayMinutes * 60);
                $this->recordFailedLoginAttempt($email, $ip, $request->userAgent());

                $attempts = RateLimiter::attempts($rateLimitKey);
                if ($attempts >= $this->maxAttempts) {
                    $this->lockUserAccount($email);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'attempts_remaining' => max(0, $this->maxAttempts - $attempts)
                ], 401);
            }

            RateLimiter::clear($rateLimitKey);

            if ($request->single_session) {
                $user->tokens()->delete();
            }

            $deviceName = $request->device_name ?? ($request->userAgent() ?: 'Unknown Device');
            $token = $user->createToken(
                $deviceName,
                ['*'],
                now()->addDays(30) 
            );

            $this->recordSuccessfulLogin($user, $ip, $request->userAgent(), $token->accessToken->id);

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'last_login' => now()->toISOString(),
            ];

            if (Schema::hasColumn('users', 'email_verified_at')) {
                $userData['email_verified'] = !is_null($user->email_verified_at);
            }

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $userData,
                    'token' => $token->plainTextToken,
                    'token_type' => 'Bearer',
                    'expires_at' => now()->addDays(30)->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Login error', [
                'error' => $e->getMessage(),
                'email' => $email,
                'ip_address' => $ip
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $tokenId = $request->user()->currentAccessToken()->id;

            $request->user()->currentAccessToken()->delete();

            Log::info('User logged out', [
                'user_id' => $user->id,
                'token_id' => $tokenId,
                'ip_address' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Logout error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }

    public function logoutAll(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $user->tokens()->delete();

            Log::info('User logged out from all devices', [
                'user_id' => $user->id,
                'ip_address' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logged out from all devices successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Logout all error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }

    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $currentToken = $request->user()->currentAccessToken();
            
            $newToken = $user->createToken(
                $currentToken->name,
                ['*'],
                now()->addDays(30)
            );
            
            $currentToken->delete();

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $newToken->plainTextToken,
                    'token_type' => 'Bearer',
                    'expires_at' => now()->addDays(30)->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Token refresh error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed'
            ], 500);
        }
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'profile_info' => $user->profile_info,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];

        if (Schema::hasColumn('users', 'email_verified_at')) {
            $userData['email_verified'] = !is_null($user->email_verified_at);
        }

        if (Schema::hasColumn('users', 'last_login_at')) {
            $userData['last_login_at'] = $user->last_login_at;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $userData,
                'active_tokens' => $user->tokens()->count(),
            ]
        ]);
    }

    public function activeSessions(Request $request): JsonResponse
    {
        $user = $request->user();
        $tokens = $user->tokens()->get()->map(function ($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
                'expires_at' => $token->expires_at,
                'is_current' => $token->id === request()->user()->currentAccessToken()->id,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $tokens
        ]);
    }

    public function revokeToken(Request $request): JsonResponse
    {
        $request->validate([
            'token_id' => 'required|integer|exists:personal_access_tokens,id'
        ]);

        $user = $request->user();
        $token = $user->tokens()->find($request->token_id);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not found'
            ], 404);
        }

        $token->delete();

        return response()->json([
            'success' => true,
            'message' => 'Token revoked successfully'
        ]);
    }

    protected function recordFailedLoginAttempt($email, $ip, $userAgent): void
    {
        try {
            if (Schema::hasTable('login_attempts')) {
                LoginAttempt::create([
                    'email' => $email,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                    'successful' => false,
                    'attempted_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to record login attempt', ['error' => $e->getMessage()]);
        }
    }

    protected function recordSuccessfulLogin($user, $ip, $userAgent, $tokenId): void
    {
        try {
            if (Schema::hasTable('login_attempts')) {
                LoginAttempt::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                    'token_id' => $tokenId,
                    'successful' => true,
                    'attempted_at' => now(),
                ]);
            }

            if (Schema::hasColumn('users', 'last_login_at')) {
                $user->update(['last_login_at' => now()]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to record successful login', ['error' => $e->getMessage()]);
        }
    }

    protected function lockUserAccount($email): void
    {
        $lockDuration = 30 * 60; 
        $retryAfter = now()->addMinutes(30);
        
        Cache::put('user_locked:' . $email, true, $lockDuration);
        Cache::put('user_locked:' . $email . '_retry_after', $retryAfter->toISOString(), $lockDuration);
        
        Log::warning('User account locked', [
            'email' => $email,
            'locked_until' => $retryAfter->toISOString()
        ]);
    }
}
