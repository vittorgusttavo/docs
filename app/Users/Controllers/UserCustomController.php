<?php

namespace BookStack\Users\Controllers;

use BookStack\Access\SocialDriverManager;
use BookStack\Access\UserInviteException;
use BookStack\Exceptions\ImageUploadException;
use BookStack\Exceptions\UserUpdateException;
use BookStack\Http\Controller;
use BookStack\Uploads\ImageRepo;
use BookStack\Users\Models\Role;
use BookStack\Users\Queries\UsersAllPaginatedAndSorted;
use BookStack\Users\UserRepo;
use BookStack\Util\SimpleListOptions;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserCustomController extends UserController
{
    public function edit(int $id, SocialDriverManager $socialDriverManager)
    {
        $this->checkPermission('users-manage');

        $user = $this->userRepo->getById($id);
        $user->load(['apiTokens', 'mfaValues']);
        // var_dump($user->dt_expire);die;
        $user->dt_expire_view = $user->dt_expire ? date('d/m/Y', strtotime($user->dt_expire)) : '--';
        $authMethod = ($user->system_name) ? 'system' : config('auth.method');

        $activeSocialDrivers = $socialDriverManager->getActive();
        $mfaMethods = $user->mfaValues->groupBy('method');
        $this->setPageTitle(trans('settings.user_profile'));
        $roles = Role::query()->orderBy('display_name', 'asc')->get();

        return view('users-custom.edit', [
            'user'                => $user,
            'activeSocialDrivers' => $activeSocialDrivers,
            'mfaMethods'          => $mfaMethods,
            'authMethod'          => $authMethod,
            'roles'               => $roles,
        ]);
    }

    /**
     * Update the specified user in storage.
     *
     * @throws UserUpdateException
     * @throws ImageUploadException
     * @throws ValidationException
     */
    public function update(Request $request, int $id)
    {
        $this->preventAccessInDemoMode();
        $this->checkPermission('users-manage');
        // var_dump($request);
        $validated = $this->validate($request, [
            'name'             => ['min:1', 'max:100'],
            'email'            => ['min:2', 'email', 'unique:users,email,' . $id],
            'password'         => ['required_with:password_confirm', Password::default()],
            'password-confirm' => ['same:password', 'required_with:password'],
            'language'         => ['string', 'max:15', 'alpha_dash'],
            'roles'            => ['array'],
            'roles.*'          => ['integer'],
            'external_auth_id' => ['string'],
            'profile_image'    => array_merge(['nullable'], $this->getImageValidationRules()),
            'active'           => ['integer']
        ]);
        
        $user = $this->userRepo->getById($id);
        $user= $this->setColumnsCustom($user, $validated);
        $this->userRepo->update($user, $validated, true);

        // Save profile image if in request
        if ($request->hasFile('profile_image')) {
            $imageUpload = $request->file('profile_image');
            $this->imageRepo->destroyImage($user->avatar);
            $image = $this->imageRepo->saveNew($imageUpload, 'user', $user->id);
            $user->image_id = $image->id;
            $user->save();
        }

        // Delete the profile image if reset option is in request
        if ($request->has('profile_image_reset')) {
            $this->imageRepo->destroyImage($user->avatar);
            $user->image_id = 0;
            $user->save();
        }

        return redirect('/settings/users');
    }

    private function setColumnsCustom($user, array $data) {
        $date = date('Y-m-d', strtotime('+90 days'));
        // var_dump($date);die;
        $status = boolval($data["active"]);
        $user->active = $status ? true : false;
        $user->dt_expire = $status ? $date : null;
        // $user->active = false;
        return $user;
    }
}
