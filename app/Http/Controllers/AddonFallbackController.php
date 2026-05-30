<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AddonFallbackController extends Controller
{
    public function unavailable(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => translate('This addon is not available.'),
            ], 404);
        }

        if (function_exists('flash')) {
            flash(translate('This addon is not available.'))->warning();
        }

        return redirect()->route('home');
    }
}
