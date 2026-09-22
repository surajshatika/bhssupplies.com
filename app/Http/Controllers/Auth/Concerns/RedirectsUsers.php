<?php

namespace App\Http\Controllers\Auth\Concerns;

/**
 * Local replacement for Illuminate\Foundation\Auth\RedirectsUsers — see
 * AuthenticatesUsers.php for why this exists.
 */
trait RedirectsUsers
{
    public function redirectPath()
    {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return property_exists($this, 'redirectTo') ? $this->redirectTo : '/';
    }
}
