<?php

namespace BookStack\Access\Controllers;

use BookStack\Exceptions\StoppedAuthenticationException;
use BookStack\Exceptions\UserRegistrationException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use BookStack\Access\RegistrationCustomService;
use BookStack\Access\LoginService;
use BookStack\Access\SocialDriverManager;
use BookStack\Activity\Models\Activity;
use BookStack\Auth\Models\User;

class RegisterCustomController extends RegisterController
{
     public function __construct(
        protected SocialDriverManager $socialDriverManager,
        protected RegistrationCustomService $registrationCustomService,
        protected LoginService $loginService
    ) {
        parent::__construct($socialDriverManager, $registrationCustomService, $loginService);
    }
    /**
     * Handle a registration request for the application.
     *
     * @throws UserRegistrationException
     * @throws StoppedAuthenticationException
     */
    public function postRegister(Request $request)
    {
        $this->registrationCustomService->ensureRegistrationAllowed();
        $this->validator($request->all())->validate();
        $userData = $request->all();
        // $userData["ip_client"] = $request->getClientIp();
        try {
            #   Comentado o registro padrão, pois o sistema atuara diferente para criação de usuário
            // $user = $this->registrationService->registerUser($userData);
            // $this->loginService->login($user, auth()->getDefaultDriver());
            $user = $this->registrationCustomService->registerUser($userData, null, true);
            return view('auth-custom.register-after-confirm', []);
            // return redirect('/login')->with('success', 'Conta criada com sucesso! Um Administrador irá ativar a sua conta.');
        } catch (UserRegistrationException $exception) {
            if ($exception->getMessage()) {
                $this->logRegisterError($exception, $userData);
                $this->showErrorNotification($exception->getMessage());
            }
            return redirect($exception->redirectLocation);
        }

        $this->showSuccessNotification(trans('auth.register_success'));

         return view('auth-custom.register', [
            'socialDrivers' => $socialDrivers,
        ]);
    }

    /**
     * Get a validator for an incoming registration request.
     */
    protected function validator(array $data): ValidatorContract
    {
        return Validator::make($data, [
            'name'     => ['required', 'min:2', 'max:100'],
            'email'    => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::default()],
            // Basic honey for bots that must not be filled in
            'username' => ['prohibited'],
            'id_client' => ['required', 'min:1', 'max:9'],
            'accept_term' => ['accepted'],
        ]);
    }

    /**
     * Show the application registration form.
     *
     * @throws UserRegistrationException
     */
    public function getRegister()
    {
        $this->registrationService->ensureRegistrationAllowed();
        $socialDrivers = $this->socialDriverManager->getActive();

        return view('auth-custom.register', [
            'socialDrivers' => $socialDrivers,
        ]);
    }
    /**
     * Log success/error at Register
     * @param object $user
     */
    public function logRegisterError($e, $userData){
        $log = new Activity();
        $log->forceFill([
            'type'         => 'register_erro',
            'user_id'      => 2,
            'ip'           => request()->ip(),
            'detail'       => $userData['email'],
            'observation'  => $e->getMessage(),
        ])->save();
    }

}
