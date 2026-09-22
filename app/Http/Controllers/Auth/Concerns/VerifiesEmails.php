<?php

namespace App\Http\Controllers\Auth\Concerns;

use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Local replacement for Illuminate\Foundation\Auth\VerifiesEmails — see
 * AuthenticatesUsers.php for why this exists. App\Http\Controllers\Auth\
 * VerificationController overrides show()/resend() itself, so this trait
 * only backfills the signed-URL verify() handler.
 */
trait VerifiesEmails
{
    use RedirectsUsers;

    public function verify(Request $request)
    {
        if (!hash_equals((string) $request->route('id'), (string) $request->user()->getKey())) {
            throw new AuthorizationException;
        }

        if (!hash_equals((string) $request->route('hash'), sha1($request->user()->getEmailForVerification()))) {
            throw new AuthorizationException;
        }

        if ($request->user()->hasVerifiedEmail()) {
            return $request->wantsJson()
                ? new JsonResponse([], 204)
                : redirect($this->redirectPath());
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        if ($response = $this->verified($request)) {
            return $response;
        }

        return $request->wantsJson()
            ? new JsonResponse([], 204)
            : redirect($this->redirectPath())->with('verified', true);
    }

    protected function verified(Request $request)
    {
        //
    }
}
