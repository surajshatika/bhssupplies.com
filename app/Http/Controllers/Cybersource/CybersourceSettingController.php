<?php

namespace App\Http\Controllers\Cybersource;

use App\Http\Controllers\Controller;

class CybersourceSettingController extends Controller
{
    public function configuration()
    {
        flash(translate('Cybersource addon files are not available on this installation.'))->warning();
        return redirect()->route('payment_method.index');
    }
}
