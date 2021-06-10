<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendOtp extends Notification
{
    use Queueable;

    protected $defaultChannels = ['database'];

    protected $otp;

    const OPT_LIFETIME = 1;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($otp, $channels = null)
    {
        $this->otp = $otp;
        $this->defaultChannels = $this->verifyChannels($channels);
    }

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
            'expired_at' => now()->addMinutes(self::OPT_LIFETIME)->toDateTimeString(),
        ];
    }
}
