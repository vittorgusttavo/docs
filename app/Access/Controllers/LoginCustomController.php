<?php

namespace BookStack\Access\Controllers;

use BookStack\Access\LoginCustomService;
use BookStack\Access\SocialDriverManager;
use BookStack\Exceptions\LoginAttemptException;
use BookStack\Exceptions\LoginCustomException;
use BookStack\Facades\Activity;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;
use DateTime;

class LoginCustomController extends LoginController
{
    public function __construct(
        protected SocialDriverManager $socialDriverManager,
        protected LoginCustomService $loginCustomService,
    ) {
        parent::__construct($socialDriverManager, $loginCustomService);
    }
    /**
     * Handle a login request to the application.
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);
        $username = $request->get($this->username());
        
        // Check login throttling attempts to see if they've gone over the limit
        if ($this->hasTooManyLoginAttempts($request)) {
            Activity::logFailedLogin($username);
            return $this->sendLockoutResponse($request);
        }

        try {
            $result = auth()->attempt($this->credentials($request), $request->filled('remember'));
            if($result){
                $user = auth()->user();
                $this->verifyStatus($user);
                $this->verifyDateExpired($user);
                
            }
            
            if ($this->attemptLogin($request)) {
                return $this->sendLoginResponse($request);
            }
            
        } catch (LoginAttemptException $exception) {
            auth()->logout();
            Activity::logFailedLogin($username);
            return $this->sendLoginAttemptExceptionResponse($exception, $request);
        } catch (LoginCustomException $exception) {
            auth()->logout();
            $this->showErrorNotification($exception->getMessage());

            return redirect('/login');
        } 

        // On unsuccessful login attempt, Increment login attempts for throttling and log failed login.
        $this->incrementLoginAttempts($request);
        Activity::logFailedLogin($username);

        // Throw validation failure for failed login
        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ])->redirectTo('/login');
    }

     private function verifyStatus($userData){
        if(boolval($userData->active)){
            return;
        }
        // var_dump('teste');die;
        throw new LoginCustomException('Conta em ativação.');
    }

    private function verifyDateExpired($userData){
        if($userData->dt_expire){
            $dataNow = new DateTime();
            $dataExpire = new DateTime($userData->dt_expire);
            if($dataNow > $dataExpire) {
                throw new LoginCustomException('Usuário expirado, necessário a reativação');
            }
        }
    }
}
