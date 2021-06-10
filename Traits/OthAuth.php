<?php

namespace App\Traits;

trait OtpAuth
{
    /**
     * Check OTP
     *
     * @return bool
     */
    public function checkOtp($otp)
    {
        logger(__METHOD__);
        \DB::enableQueryLog();
        $authenticator = $this->otp();
        logger(\DB::getQueryLog());
        if (is_null($authenticator)) {
            return response()->json(false,200);
        }
        $authenticator->markAsRead();
        if ($authenticator 
            && now()->lte($authenticator->data['expired_at'])
            && $authenticator->data['otp'] == $otp
        ) {
            return response()->json(true,200);
        }
        return response()->json(false,200);
    }

    /**
     * Get OTP data
     *
     * @return \Illuminate\Notifications\DatabaseNotification
     */
    public function otp()
    {
        return $this->notifications()
                ->where('type', 'LIKE', '%SendOtp%')
                ->whereNull('read_at')
                ->first();
    }
}