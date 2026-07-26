<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Auth\Events\PasswordReset;
use Auth;
use App\Models\Address;
use App\Models\Cart;
use App\Models\User;
use App\Services\SendSmsService;
use App\Services\SmsRateLimiterService;
use App\Utility\SmsUtility;
use Hash;
use Illuminate\Support\Facades\Session;

class OTPVerificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function verification(Request $request){
        if (Auth::check() && Auth::user()->email_verified_at == null) {
            return view('otp_systems.frontend.user_verification');
        }
        else {
            flash('You have already verified your number')->warning();
            return redirect()->route('home');
        }
    }


    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function verify_phone(Request $request){
        $user = Auth::user();
        if ($user->verification_code == $request->verification_code) {
            $user->email_verified_at = date('Y-m-d h:m:s');
            $user->save();

            flash('Your phone number has been verified successfully')->success();
            return redirect()->route('home');
        }
        else{
            flash('Invalid Code')->error();
            return back();
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function resend_verificcation_code(Request $request){
        $user = Auth::user();
        $limiter = app(SmsRateLimiterService::class);
        if ($limiter->tooManyAttempts($user->phone, 'phone_verification', 5, 600)) {
            flash(translate('Please wait before requesting another OTP.'))->warning();
            return back();
        }

        $user->verification_code = rand(100000,999999);
        $user->save();
        $sent = SmsUtility::phone_number_verification($user);
        $limiter->hit($user->phone, 'phone_verification');

        if (!$sent) {
            flash(translate('SMS could not be sent. Please try again.'))->error();
        }

        return back();
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    public function reset_password_with_code(Request $request)
    {
        $phone = "+{$request['country_code']}{$request['phone']}";
        
        if (($user = User::where('phone', $phone)->where('verification_code', $request->code)->first()) != null) {
            if ($request->password == $request->password_confirmation) {
                $user->password = Hash::make($request->password);
                $user->email_verified_at = date('Y-m-d h:m:s');
                $user->save();
                event(new PasswordReset($user));
                auth()->login($user, true);

                if (auth()->user()->user_type == 'admin' || auth()->user()->user_type == 'staff') {
                    flash("Password has been reset successfully")->success();
                    return redirect()->route('admin.dashboard');
                }
                flash("Password has been reset successfully")->success();
                return redirect()->route('home');
            } else {
                flash("Password and confirm password didn't match")->warning();
                return view('otp_systems.frontend.auth.passwords.reset_with_phone');
            }
        } else {
            flash("Verification code mismatch")->error();
            return view('otp_systems.frontend.auth.passwords.reset_with_phone');
        }
    }

    public function show_reset_password_form()
    {
        return view('otp_systems.frontend.auth.passwords.reset_with_phone');
    }

  
    /**
     * @param  User $user
     * @return void
     */

    public function send_code($user){
        if (!$user || !$user->phone) {
            return false;
        }

        if (!$user->verification_code) {
            $user->verification_code = rand(100000,999999);
            $user->save();
        }

        $limiter = app(SmsRateLimiterService::class);
        if ($limiter->tooManyAttempts($user->phone, 'phone_verification', 5, 600)) {
            return false;
        }

        $sent = SmsUtility::phone_number_verification($user);
        $limiter->hit($user->phone, 'phone_verification');
        return $sent;
    }

    public function account_opening($user, $password = null)
    {
        return $this->send_code($user);
    }

    /**
     * @param  Order $order
     * @return void
     */
    public function send_order_code($order){
        $phone = json_decode($order->shipping_address)->phone;
        if($phone != null){
            SmsUtility::order_placement($phone, $order);
        }
    }

    /**
     * @param  Order $order
     * @return void
     */
    public function send_delivery_status($order){
        $phone = json_decode($order->shipping_address)->phone;
        if($phone != null){
            SmsUtility::delivery_status_change($phone, $order);
        }
    }

    /**
     * @param  Order $order
     * @return void
     */
    public function send_payment_status($order){
        $phone = json_decode($order->shipping_address)->phone;
        if($phone != null){
            SmsUtility::payment_status_change($phone, $order);
        }
    }

    public function sendOtp(Request $request)
    {
        $phone = $this->requestPhone($request);
        $user = $phone ? User::where('phone', $phone)->first() : null;

        if (!$user) {
            flash(translate('No account found for this phone number.'))->error();
            return back();
        }

        $limiter = app(SmsRateLimiterService::class);
        if ($limiter->tooManyAttempts($phone, 'login_otp', 5, 600)) {
            flash(translate('Please wait before requesting another OTP.'))->warning();
            return back();
        }

        $user->verification_code = rand(100000, 999999);
        $user->save();
        $sent = SmsUtility::phone_number_verification($user);
        $limiter->hit($phone, 'login_otp');

        if (!$sent) {
            flash(translate('SMS could not be sent. Please try again.'))->error();
            return back();
        }

        Session::put('otp_login_phone', $phone);
        flash(translate('OTP sent successfully.'))->success();
        return redirect()->route('otp-verification-page');
    }

    public function otpVerificationPage()
    {
        $phone = Session::get('otp_login_phone');
        if (!$phone) {
            return redirect()->route('login');
        }

        return view('otp_systems.frontend.otp_login_verification', compact('phone'));
    }

    public function resendOtp($phone)
    {
        $phone = app(SendSmsService::class)->normalizePhone($phone);
        $user = $phone ? User::where('phone', $phone)->first() : null;
        if (!$user) {
            flash(translate('No account found for this phone number.'))->error();
            return back();
        }

        $request = new Request(['phone' => $phone]);
        return $this->sendOtp($request);
    }

    public function validateOtpCode(Request $request)
    {
        $phone = Session::get('otp_login_phone') ?: $this->requestPhone($request);
        $user = $phone ? User::where('phone', $phone)->first() : null;

        if (!$user || $user->verification_code != $request->verification_code) {
            flash(translate('Invalid OTP code.'))->error();
            return back();
        }

        $user->verification_code = null;
        $user->email_verified_at = $user->email_verified_at ?: now();
        $user->save();
        Auth::login($user, true);
        app(SmsRateLimiterService::class)->clear($phone, 'login_otp');
        Session::forget('otp_login_phone');

        return redirect()->route('home');
    }

    public function activate_otp_for_cashOnDelivery_and_wallet(Request $request)
    {
        if (!Auth::check() || $request->type !== 'activate_otp_for_cashOnDelivery_and_wallet') {
            return 0;
        }

        $user = Auth::user();
        $user->otp_activation_purchase_cod_wallet = (int) $request->value;
        $user->save();

        return 1;
    }

    public function sendPurchaseOtp(Request $request)
    {
        $phone = $this->checkoutPhone($request);
        if (!$phone) {
            return response()->json(['status' => false, 'message' => translate('Phone number is missing.')]);
        }

        $limiter = app(SmsRateLimiterService::class);
        if ($limiter->tooManyAttempts($phone, 'purchase_otp', 5, 600)) {
            return response()->json(['status' => false, 'message' => translate('Please wait before requesting another OTP.')]);
        }

        $code = (string) random_int(100000, 999999);
        Session::put('purchase_otp_phone', $phone);
        Session::put('purchase_otp_code', $code);
        Session::put('purchase_otp_expires_at', now()->addMinutes(10)->timestamp);
        Session::put('purchase_otp_attempts', 0);

        $message = 'Your ' . env('APP_NAME') . ' checkout OTP is ' . $code . '. It expires in 10 minutes.';
        $sent = SmsUtility::sendTemplate('phone_number_verification', $phone, [
            '[[code]]' => $code,
            '[[site_name]]' => env('APP_NAME'),
        ], 'purchase_otp', $message);
        $limiter->hit($phone, 'purchase_otp');

        return response()->json([
            'status' => (bool) $sent,
            'message' => $sent ? translate('OTP sent successfully.') : translate('SMS could not be sent. Please try again.'),
        ]);
    }

    public function verifyPurchaseOtp(Request $request)
    {
        $expiresAt = (int) Session::get('purchase_otp_expires_at', 0);
        $attempts = (int) Session::get('purchase_otp_attempts', 0);

        if (!$expiresAt || time() > $expiresAt) {
            $this->clearPurchaseOtp();
            return response()->json(['status' => false, 'message' => translate('OTP expired. Please request a new code.')]);
        }

        if ($attempts >= 5) {
            $this->clearPurchaseOtp();
            return response()->json(['status' => false, 'message' => translate('Too many invalid attempts. Please request a new code.')]);
        }

        Session::put('purchase_otp_attempts', $attempts + 1);

        if ((string) Session::get('purchase_otp_code') !== (string) $request->otp_code) {
            return response()->json(['status' => false, 'message' => translate('Invalid OTP code.')]);
        }

        app(SmsRateLimiterService::class)->clear((string) Session::get('purchase_otp_phone'), 'purchase_otp');
        $this->clearPurchaseOtp();

        return response()->json(['status' => true, 'message' => translate('OTP verified successfully.')]);
    }

    protected function requestPhone(Request $request): string
    {
        $raw = (string) $request->phone;
        if ($request->country_code && !str_starts_with($raw, '+')) {
            $raw = '+' . $request->country_code . $raw;
        }

        return app(SendSmsService::class)->normalizePhone($raw);
    }

    protected function checkoutPhone(Request $request): string
    {
        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->active()->first();
            if ($cart && $cart->address_id) {
                $address = Address::find($cart->address_id);
                if ($address && $address->phone) {
                    return app(SendSmsService::class)->normalizePhone($address->phone);
                }
            }

            return app(SendSmsService::class)->normalizePhone((string) Auth::user()->phone);
        }

        $shipping = Session::get('guest_shipping_info', []);
        return app(SendSmsService::class)->normalizePhone((string) ($shipping['phone'] ?? ''));
    }

    protected function clearPurchaseOtp(): void
    {
        Session::forget('purchase_otp_phone');
        Session::forget('purchase_otp_code');
        Session::forget('purchase_otp_expires_at');
        Session::forget('purchase_otp_attempts');
    }
}
