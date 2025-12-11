<?php

namespace BookStack\Access;

use BookStack\Activity\ActivityType;
use BookStack\Exceptions\LoginAttemptInvalidUserException;
use BookStack\Exceptions\StoppedAuthenticationException;
use BookStack\Facades\Activity;
use BookStack\Facades\Theme;
use BookStack\Theming\ThemeEvents;
use BookStack\Users\Models\User;
use Exception;

class LoginCustomService extends LoginService
{
    /**
     * Log the given user into the system.
     * Will start a login of the given user but will prevent if there's
     * a reason to (MFA or Unconfirmed Email).
     * Returns a boolean to indicate the current login result.
     *
     * @throws StoppedAuthenticationException|LoginAttemptInvalidUserException
     */
    public function login(User $user, string $method, bool $remember = false): void
    {
        if ($user->isGuest()) {
            throw new LoginAttemptInvalidUserException('Login not allowed for guest user');
        }
        if ($this->awaitingEmailConfirmation($user) || $this->needsMfaVerification($user)) {
            $this->setLastLoginAttemptedForUser($user, $method, $remember);

            throw new StoppedAuthenticationException($user, $this);
        }

        $this->clearLastLoginAttempted();
        auth()->login($user, $remember);
        Activity::add(ActivityType::AUTH_LOGIN, "{$method}; {$user->logDescriptor()}");
        Theme::dispatch(ThemeEvents::AUTH_LOGIN, $method, $user);

        // Authenticate on all session guards if a likely admin
        if ($user->can('users-manage') && $user->can('user-roles-manage')) {
            $guards = ['standard', 'ldap', 'saml2', 'oidc'];
            foreach ($guards as $guard) {
                auth($guard)->login($user);
            }
        }
    }
}
