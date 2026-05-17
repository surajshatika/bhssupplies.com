<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessSetting;

class AfricanPaymentGatewayController extends Controller
{
    public function configuration()
    {
        return view('backend.african_pg.configuration');
    }

    public function credentials_index()
    {
        return view('backend.african_pg.credentials');
    }
}
