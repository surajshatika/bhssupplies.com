<?php

namespace App\Http\Controllers\Auth\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

/**
 * Local replacement for Illuminate\Foundation\Auth\SendsPasswordResetEmails
 * — see AuthenticatesUsers.php for why this exists. App\Http\Controllers\
 * Auth\ForgotPasswordController overrides sendResetLinkEmail() itself with
 * its own verification-code flow, so this trait only backfills
 * showLinkRequestForm() and the (unused-here) broker helpers.
 */
trait SendsPasswordResetEmails
{
    public function showLinkRequestForm()
    {
        return view('auth.' . (get_setting('authentication_layout_select') ?: 'boxed') . '.reset_password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $response = $this->broker()->sendResetLink(
            $request->only('email')
        );

        return $response == Password::RESET_LINK_SENT
            ? $this->sendResetLinkResponse($request, $response)
            : $this->sendResetLinkFailedResponse($request, $response);
    }

    protected function sendResetLinkResponse(Request $request, $response)
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['message' => trans($response)], 200);
        }

        return back()->with('status', trans($response));
    }

    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['message' => trans($response)], 422);
        }

        return back()->withErrors(['email' => trans($response)]);
    }

    public function broker()
    {
        return Password::broker();
    }
}
