<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CybersourceController extends Controller
{
    public function process(Request $request)
    {
        flash(translate('Cybersource payment gateway is not available.'))->warning();
        return back();
    }

    public function callback(Request $request)
    {
        flash(translate('Cybersource payment could not be completed.'))->warning();
        return redirect('/');
    }

    public function webhook(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => translate('Cybersource payment gateway is not available.'),
        ], 503);
    }

    public function getCancel()
    {
        flash(translate('Payment cancelled'))->warning();
        return redirect('/');
    }
}
