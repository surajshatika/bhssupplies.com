<?php

namespace App\Services\OTP;

use App\Contracts\SendSms;
use App\Utility\MimoUtility;

class Mimo implements SendSms{
    public function send($to, $from, $text, $template_id)
    {
        $token = MimoUtility::getToken();

        $response = MimoUtility::sendMessage($text, $to, $token);
        MimoUtility::logout($token);
        return $response;
    }
}
