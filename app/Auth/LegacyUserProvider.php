<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

/**
 * Login come Yii: colonna `user`, solo attivo='Y'; password verificata contro l'hash (case-sensitive).
 */
class LegacyUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials['user']) || ! is_string($credentials['user'])) {
            return null;
        }

        return $this->newModelQuery()
            ->where('user', $credentials['user'])
            ->where('attivo', 'Y')
            ->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $stored = (string) $user->getAuthPassword();
        $plain = (string) ($credentials['password'] ?? '');

        return $stored !== '' && $plain !== '' && str_starts_with($stored, '$2y$') && Hash::check($plain, $stored);
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token)
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, #[\SensitiveParameter] $token): void {}
}
