<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Contructs\UserContruct;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class AuthController extends Controller
{
    private $userRepository;

    public function __construct(UserContruct $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(Request $request)
    {
        $userStore = $this->userRepository->store($request->all());
        return $userStore;
    }

    public function register_in_manager(Request $request)
    {
        $userStore = $this->userRepository->store_in_manager($request->all());
        return $userStore;
    }

    public function register_other(Request $request)
    {
        $userStore = $this->userRepository->store_other($request->all());
        return $userStore;
    }

    public function register_other_update(Request $request)
    {
        $userStore = $this->userRepository->reg_other($request->all());
        return $userStore;
    }

    public function login(Request $request)
    {
        $userGet = $this->userRepository->findUser($request->all());
        return $userGet;
    }
    public function userInfo()
    {
        // return "info";

        $userInfo = $this->userRepository->findUserInfo();
        return $userInfo;
    }
    public function emailVerified($id)
    {
        $verified = $this->userRepository->findVerified($id);
        return $verified;
    }
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $forgot = $this->userRepository->forgotPassword($request->only('email'));
        return $forgot;
    }
    public function passwordReset($token)
    {

        $verified = $this->userRepository->resetPassword($token);
        return redirect($verified);
        //return $verified;
    }
}
