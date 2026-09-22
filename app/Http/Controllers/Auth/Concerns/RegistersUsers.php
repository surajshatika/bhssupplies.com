<?php

namespace App\Http\Controllers\Auth\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Local replacement for Illuminate\Foundation\Auth\RegistersUsers — see
 * AuthenticatesUsers.php for why this exists. App\Http\Controllers\Auth\
 * RegisterController overrides register()/registered() itself, so this
 * trait mainly backfills guard()/redirectPath()/showRegistrationForm().
 */
trait RegistersUsers
{
    use RedirectsUsers;

    public function showRegistrationForm()
    {
        return view('auth.' . (get_setting('authentication_layout_select') ?: 'boxed') . '.user_login');
    }

    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        event(new \Illuminate\Auth\Events\Registered($user = $this->create($request->all())));

        $this->guard()->login($user);

        return $request->wantsJson()
            ? new JsonResponse([], 201)
            : redirect($this->redirectPath());
    }

    protected function registered(Request $request, $user)
    {
        //
    }

    public function guard()
    {
        return Auth::guard();
    }
}
