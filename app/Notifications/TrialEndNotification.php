<?php

namespace App\Notifications;

use Illuminate\Support\HtmlString;
use Illuminate\Notifications\Messages\MailMessage;

class TrialEndNotification extends BaseNotification
{
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $package;

    public function __construct($package)
    {
        parent::__construct();
        $this->package = $package;
    }

    public function via()
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mailMessage = (new MailMessage)
            ->subject(__('email.trialEnd.subject').' '.config('app.name').'!')
            ->greeting(__('email.dear').' '.ucwords($notifiable->name).'!');

        if (!is_null($this->package->trial_message)){
            $mailMessage->line($this->package->trial_message);
        }
        else {
            $mailMessage->line(__('email.trialEnd.text'));
        }

        $mailMessage->action(__('email.loginAccount'), url('/login'));
        $mailMessage->line(__('email.thankyouNote'));
        $mailMessage->salutation(new HtmlString(__('email.regards').',<br>'.config('app.name')));

        return $mailMessage;
    }

}
