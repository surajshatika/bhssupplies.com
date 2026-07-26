<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SendSmsService;
use App\Models\User;

class SmsController extends Controller
{
    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:send_bulk_sms'])->only('index');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    	$users = User::all();
        return view('backend.otp_systems.sms.index',compact('users'));
    }

    //send message to multiple users
    public function send(Request $request)
    {
        $request->validate([
            'user_phones' => 'required|array',
            'content' => 'required|string|max:1000',
        ]);

        $sent = 0;
        $failed = 0;
        foreach ($request->user_phones as $phone) {
            $ok = (new SendSmsService())->sendSMS($phone, env('APP_NAME'), $request->content, $request->template_id, [
                'context' => 'bulk_sms',
            ]);
            $ok ? $sent++ : $failed++;
        }

    	flash(translate('SMS processed. Sent: ') . $sent . translate(' Failed: ') . $failed)->success();
    	return redirect()->route('admin.dashboard');
    }
}
