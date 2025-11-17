<?php

namespace Equdna\SwiftAuth\Guards;

use Equdna\SwiftAuth\Models\SwiftToken;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class SwiftTokenGuard implements Guard
{
    protected ?Authenticatable $user = null;

    public function __construct(
        protected UserProvider $provider,
        protected Request $request
    ) {
    }

    public function check(): bool
    {
        return !is_null($this->user());
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?Authenticatable
    {
        if ($this->user) {
            return $this->user;
        }

        $token = $this->bearerToken();
        if (!$token) {
            return null;
        }

        $swiftToken = SwiftToken::findToken($token);
        if (!$swiftToken) {
            return null;
        }

        $this->request->attributes->set('swift_token', $swiftToken);

        $model = $this->provider->retrieveById($swiftToken->user_id);
        $this->user = $model;

        $swiftToken->forceFill(['last_used_at' => now()])->save();

        return $this->user;
    }

    public function id(): ?int
    {
        return $this->user()?->getAuthIdentifier();
    }

    public function validate(array $credentials = []): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);
        if (!$user) {
            return false;
        }

        return $this->provider->validateCredentials($user, $credentials);
    }

    public function setUser(Authenticatable $user): void
    {
        $this->user = $user;
    }

    public function hasUser(): bool
    {
        return !is_null($this->user);
    }

    protected function bearerToken(): ?string
    {
        $header = $this->request->header('Authorization', '');
        if (preg_match('/Bearer\\s+(.*)$/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
