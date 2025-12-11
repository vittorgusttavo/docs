<?php

namespace BookStack\Users;

use BookStack\Users\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserRepoCustom extends UserRepo
{
    
    /**
     * Create a new basic instance of user with the given pre-validated data.
     *
     * @param array{name: string, email: string, password: ?string, external_auth_id: ?string, language: ?string, roles: ?array} $data
     */
    public function createWithoutActivity(array $data, bool $emailConfirmed = false): User
    {
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = Hash::make(empty($data['password']) ? Str::random(32) : $data['password']);
        $user->email_confirmed = $emailConfirmed;
        $user->external_auth_id = $data['external_auth_id'] ?? '';
        #   Código incluido
        $user->id_client = $data['id_client'];
        $user->active = false;
        // $user->dt_expire = 
        // $user->ip_client = $data['ip_client'];

        $user->refreshSlug();
        $user->save();

        if (!empty($data['language'])) {
            setting()->putUser($user, 'language', $data['language']);
        }

        if (isset($data['roles'])) {
            $this->setUserRoles($user, $data['roles']);
        }

        $this->downloadAndAssignUserAvatar($user);

        return $user;
    }
}
