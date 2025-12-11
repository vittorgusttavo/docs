<?php

namespace BookStack\Access;

use BookStack\Activity\ActivityType;
use BookStack\Exceptions\UserRegistrationException;
use BookStack\Facades\Activity;
use BookStack\Facades\Theme;
use BookStack\Theming\ThemeEvents;
use BookStack\Users\Models\User;
use BookStack\Users\UserRepo;
use BookStack\Users\UserRepoCustom;
use Exception;
use Illuminate\Support\Str;

class RegistrationCustomService extends RegistrationService
{
    public function __construct(
        protected UserRepoCustom $userRepoCustom,
        protected EmailConfirmationService $emailConfirmationService
    ) {
        parent::__construct($userRepoCustom, $emailConfirmationService);
    }
}
