<?php

namespace App\Services\Auth;

use App\Models\Customer;
use App\Models\Shop;
use App\Models\Trader;
use App\Models\User;
use App\Models\Verification;
use App\Support\OracleId;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        protected EmailVerificationService $emailVerificationService,
    ) {
    }

    public function registerCustomer(array $data): array
    {
        return DB::connection('oracle')->transaction(function () use ($data) {
            $userId = OracleId::next('USERS', 'user_id', 'U');
            $user = User::create([
                'user_id' => $userId,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => strtolower($data['email']),
                'password' => Hash::make($data['password']),
                'phone_num' => $data['phone_num'],
                'address' => $data['address'],
                'created_at' => now(),
                'email_verified' => false,
            ]);

            $customer = Customer::create(['customer_id' => $userId]);
            $verification = $this->emailVerificationService->sendSignupCode($user);

            return compact('user', 'customer', 'verification');
        });
    }

    public function registerTrader(array $data): array
    {
        return DB::connection('oracle')->transaction(function () use ($data) {
            $userId = OracleId::next('USERS', 'user_id', 'U');
            $user = User::create([
                'user_id' => $userId,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => strtolower($data['email']),
                'password' => Hash::make($data['password']),
                'phone_num' => $data['phone_num'],
                'address' => $data['address'],
                'created_at' => now(),
                'email_verified' => false,
            ]);

            $trader = Trader::create([
                'trader_id' => $userId,
                'admin_id' => $data['admin_id'] ?? $userId,
            ]);
            $shop = Shop::create([
                'shop_name' => $data['shop_name'],
                'location' => $data['location'],
                'trader_id' => $userId,
            ]);
            $verification = $this->emailVerificationService->sendSignupCode($user);

            return compact('user', 'trader', 'shop', 'verification');
        });
    }

    public function login(array $credentials, bool $remember = false): User
    {
        $user = $this->attemptLogin($credentials, $remember, allowUnverified: false);
        if (!$user) {
            abort(401, 'Invalid credentials');
        }

        return $user;
    }

    public function attemptLogin(array $credentials, bool $remember = false, bool $allowUnverified = false): ?User
    {
        $email = strtolower(trim((string) ($credentials['email'] ?? '')));
        $password = (string) ($credentials['password'] ?? '');

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (!$user || ! $this->passwordMatches($password, (string) $user->getAuthPassword())) {
            return null;
        }

        if (!$allowUnverified && ! $this->emailVerificationService->isUserEmailVerified($user)) {
            return null;
        }

        if (strtoupper((string) ($user->account_status ?? 'ACTIVE')) !== 'ACTIVE') {
            return null;
        }

        Auth::login($user, $remember);

        return $user;
    }

    public function userNeedsEmailVerification(?User $user): bool
    {
        return $user !== null && ! $this->emailVerificationService->isUserEmailVerified($user);
    }

    /** Supports bcrypt hashes and legacy plain-text passwords in Oracle. */
    public function passwordMatches(string $plain, string $stored): bool
    {
        if ($stored === '' || $plain === '') {
            return false;
        }

        if ($this->storedPasswordIsHashed($stored)) {
            try {
                return Hash::check($plain, $stored);
            } catch (\RuntimeException) {
                return false;
            }
        }

        return hash_equals($stored, $plain);
    }

    protected function storedPasswordIsHashed(string $stored): bool
    {
        return str_starts_with($stored, '$2y$')
            || str_starts_with($stored, '$2a$')
            || str_starts_with($stored, '$2b$')
            || str_starts_with($stored, '$argon2');
    }

    public function logout(): void
    {
        Auth::logout();
    }

    public function verifyEmailToken(string $token): Verification
    {
        $verification = Verification::query()
            ->where('verification_token', $token)
            ->firstOrFail();

        if ($verification->expires_at && $verification->expires_at->isPast()) {
            abort(422, 'Verification token expired');
        }

        $verification->is_verified = true;
        $verification->status = EmailVerificationService::STATUS_USED;
        $verification->save();

        $user = User::query()->find($verification->user_id);
        if ($user) {
            $user->email_verified = true;
            $user->email_verified_at = now();
            $user->save();
        }

        return $verification;
    }
}
