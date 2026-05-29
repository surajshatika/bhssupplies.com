<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CybersourceController extends Controller
{
    public function pay(Request $request)
    {
        return $this->unavailable();
    }

    public function process(Request $request)
    {
        return $this->unavailable();
    }

    public function callback(Request $request)
    {
        return $this->unavailable();
    }

    public function webhook(Request $request)
    {
        return $this->unavailable();
    }

    protected function unavailable()
    {
        return response()->json([
            'result' => false,
            'message' => translate('Cybersource payment gateway is not available.'),
        ], 503);
    }
}
