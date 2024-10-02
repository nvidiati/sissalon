<?php

namespace App\Notifications;

use Illuminate\Support\HtmlString;
use Illuminate\Notifications\Messages\MailMessage;

class EndPackage extends BaseNotification
{
    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    // @codingStandardsIgnoreLine
    public function via($notifiable)
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
        return (new MailMessage)
            ->subject(__('email.packageEnd.subject').' '.config('app.name').'!')
            ->greeting(__('email.dear').' '.ucwords($notifiable->name).'!')
            ->line(__('email.packageEnd.text'))
            ->action(__('email.loginAccount'), url('/login'))
            ->line(__('email.thankyouNote'))
            ->salutation(new HtmlString(__('email.regards').',<br>'.config('app.name')));
    }

}
