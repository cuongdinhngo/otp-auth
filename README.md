# Laravel OTP AUTH

This package allows you to authenticate with one time password access (OTP).

Example Usage:

```php
Route::get("/notify", function(){
    return App\Models\User::find(1)->notify(new App\Authentication\SendOtp('mail', 4, 10));
});

Route::get("/auth-otp/{otp}", function(){
    return App\Models\User::authByOtp(request()->otp, '84905279285');
});

Route::get("/check-otp/{otp}", function(){
    return App\Models\User::find(1)->checkOtp(request()->otp);
});
```

## Contents

- [Installation](#installation)
- [Usage](#usage)
	- [Generate OTP](#generate-otp)
	- [Verify OTP](#verify-otp)
	- [Basic identification](#basic-identification)
- [Testing](#testing)


## Installation

1- Add the package to your dependencies.

```
$ composer require cuongnd88/otp-auth
```

2- Run the command:

```php
php artisan auth:otp {ClassName}
```

Example:

```php
php artisan auth:otp Authentication/SendOtp
```

_`SendOtp` class and `HasOtpAuth` trait are auto-generated at `app/Authentication` directory._

_`CreateNotificationsTable` class is alseo auto-generated at `app/database/migrations`._


3- Apply the migrations:

_Will create a table called `notifications` to store generated OTP information._

```
$ php artisan migrate
```


## Usage

### Generate OTP

You can generate OTP via email or SMS

```php
Route::get("/notify", function(){
    return App\Models\User::find(1)->notify(new App\Authentication\SendOtp(['mail', 'nexmo']));
});
```

This package allows you to alter OTP length and lifetime

```php
Route::get("/notify", function(){
	$length = 4;
	$liftime = 10; //minutes
    return App\Models\User::find(1)->notify(new App\Authentication\SendOtp(['mail', 'nexmo']), $length, $liftime);
});
```


**OTP default length**: The default length is `6`.

**OTP default lifetime**: The default lifetime is `1` minute.

There is the detail of auto-generate `SentOTP` class:

```php
<?php

namespace App\Authentication;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Twilio\Rest\Client as TwilioClient;
use App\Channels\TwilioChannel;

class SendOtp extends Notification
{
    use Queueable;

    protected $defaultChannels = ['database'];

    protected $otp;

    protected $lifeTime;

    const OPT_LIFETIME = 1;

    const OPT_LENGTH = 6;

    /**
     * Construct
     *
     * @param array|string $channels
     * @param integer|string $otpLength
     * @param integer|string $lifeTime
     */
    public function __construct($channels = null, $otpLength = null, $lifeTime = null)
    {
        $this->otp = $this->generateOtp($otpLength ?? self::OPT_LENGTH);
        $this->lifeTime = $lifeTime ?? self::OPT_LIFETIME;
        $this->defaultChannels = $this->verifyChannels($channels);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return $this->defaultChannels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('Your OTP is '.$this->otp)
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'otp' => $this->otp,
            'expired_at' => now()->addMinutes($this->lifeTime)->toDateTimeString(),
        ];
    }

    /**
     * Get the Nexmo / SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     *
     * @return mixed
     */
    public function toTwilio($notifiable)
    {
        $account_sid = env('TWILIO_ACCOUNT_ID');
        $auth_token = env('TWILIO_AUTH_TOKEN');
        $twilio_number = env('TWILIO_FROM_NUMBER');

        $client = new TwilioClient($account_sid, $auth_token);
        $client->messages->create(
            $notifiable->phone_number,
            array(
                'from' => $twilio_number,
                'body' => 'OTP AUTH is '.$this->otp
            )
        );
    }

    /**
     * Generate OTP
     *
     * @param integer|string $n
     *
     * @return string
     */
    public function generateOtp($n)
    {
        $generator = "0987654321";
        $result = "";

        for ($i = 1; $i <= $n; $i++) {
            $result .= substr($generator, (rand()%(strlen($generator))), 1);
        }
        return $result;
    }

    /**
     * Verify channels
     *
     * @param string|array $channels
     *
     * @return array
     */
    public function verifyChannels($channels)
    {
        if ($channels && is_array($channels)) {
            return array_merge($this->defaultChannels, $channels);
        }
        if ($channels && is_string($channels)) {
            array_push($this->defaultChannels, $channels);
        }
        return $this->defaultChannels;
    }
}

```

### Verify OTP

After sent OTP via your configed methods, you call `authByOtp` to authenticate

```php
Route::get("/auth-otp/{otp}", function(){
    return App\Models\User::authByOtp(request()->otp, '84905123456');
});
```
Based on your credentials, you might authenticate with email or phone number

#### Set up the credentials:

In this case, you can apply `User` model which must use `HasOtpAuth` trait

```php
. . . .
use App\Authentication\HasOtpAuth;

class User extends Authenticatable
{
    use Notifiable;
    use HasOtpAuth;

    protected $credential = 'mobile';

. . . .
```

Let see more detail `HasOtpAuth` trait

```php
<?php

namespace App\Authentication;

trait HasOtpAuth
{
    /**
     * Check OTP
     *
     * @return bool
     */
    public function checkOtp($otp)
    {
        $authenticator = $this->otp();
        return $this->validateOtp($authenticator, $otp);
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

    /**
     * Validate OTP
     *
     * @param \Illuminate\Notifications\DatabaseNotification $authenticator
     * @param mixed $otp
     *
     * @return void
     */
    public function validateOtp($authenticator, $otp)
    {
        $result = false;
        if (is_null($authenticator)) {
            return response()->json($result,200);
        }
        if ($authenticator
            && now()->lte($authenticator->data['expired_at'])
            && $authenticator->data['otp'] == $otp
        ) {
            $result = true;
        }
        $authenticator->markAsRead();
        return response()->json($result,200);
    }

    /**
     * Authenticate by OTP
     *
     * @param string $otp
     * @param string $credentialValue
     * @return void
     */
    public static function authByOtp($otp, $credentialValue)
    {
        $model = new static;
        $credentialName = property_exists($model,'credential') ? $model->credential : 'email';

        $authenticator = $model->where($credentialName, '=', $credentialValue)->first();
        if (is_null($authenticator)) {
            return response()->json(false,200);
        }

        $authenticator = $authenticator->notifications()
                    ->where('type', 'LIKE', '%SendOtp%')
                    ->whereNull('read_at')
                    ->first();

        return $model->validateOtp($authenticator, $otp);
    }
}
```

### Basic identification

In some cases, you just need to identify the right access, you might need to execute `checkOtp` method

```php
Route::get("/check-otp/{otp}", function(){
    return auth()->user->checkOtp(request()->otp);
});
```

## Credits

- Ngo Dinh Cuong

[LinkedIn](https://www.linkedin.com/in/ngodinhcuong/)
