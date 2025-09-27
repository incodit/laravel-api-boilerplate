<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class EncryptedEmailUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by the given credentials.
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials) || 
            (count($credentials) === 1 && str_contains($this->firstCredentialKey($credentials), 'password'))) {
            return null;
        }

        // For password reset, use our custom email lookup
        if (isset($credentials['email'])) {
            $emailHash = hash_hmac('sha256', mb_strtolower(trim($credentials['email'])), config('app.key'));
            return $this->createModel()->newQuery()->where('email_x', $emailHash)->first();
        }

        // For other cases, use the parent method
        return parent::retrieveByCredentials($credentials);
    }

    /**
     * Get the first key from the credential array.
     */
    protected function firstCredentialKey(array $credentials): string
    {
        foreach ($credentials as $key => $value) {
            return $key;
        }

        return '';
    }
}