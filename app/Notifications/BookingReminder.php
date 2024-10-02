<?php

namespace App\Notifications;

use App\Booking;
use Illuminate\Support\HtmlString;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\NexmoMessage;

class BookingReminder extends BaseNotification
{

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $booking;

    public function __construct(Booking $booking)
    {
        parent::__construct();
        $this->booking = $booking;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $via = ['mail'];

        if ($this->smsSetting->nexmo_status == 'active' && $notifiable->mobile_verified == 1) {
            array_push($via, 'nexmo');
        }

        if ($this->smsSetting->msg91_status == 'active' && $notifiable->mobile_verified == 1) {
            array_push($via, 'msg91');
        }

        return $via;
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
            ->subject(__('email.bookingReminder.subject').' '.config('app.name').'!')
            ->greeting(__('email.hello').' '.ucwords($notifiable->name).'!')
            ->line(__('email.bookingReminder.text'))
            ->line(__('app.booking').' '.__('app.date').' - '.$this->booking->date_time->format($this->booking->company->date_format.' '.$this->booking->company->time_format))
            ->action(__('email.loginAccount'), url('/login'))
            ->line(__('email.thankyouNote'))
            ->salutation(new HtmlString(__('email.regards').',<br>'.config('app.name')));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    // @codingStandardsIgnoreLine
    // @codingStandardsIgnoreStart
    /* @phpstan-ignore-next-line */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

    /**
     * Get the Nexmo / SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return NexmoMessage
     */
    // @codingStandardsIgnoreLine
    /* @phpstan-ignore-next-line */
    public function toNexmo($notifiable)
    {
        /* @phpstan-ignore-next-line */
        return (new NexmoMessage)
            ->content(
                        __('email.bookingReminder.text').":\n".
                        __('app.booking').' '.__('app.date').' - '.$this->booking->date_time->format($this->booking->company->date_format.' '.$this->booking->company->time_format))
            ->unicode();
    }

    // @codingStandardsIgnoreLine
    /* @phpstan-ignore-next-line */
    public function toMsg91($notifiable)
    {
        /* @phpstan-ignore-next-line */
            return (new \Craftsys\Notifications\Messages\Msg91SMS)
                ->from($this->smsSetting->msg91_from)
                ->content(
                    __('email.bookingReminder.text').":\n".
                    __('app.booking').' '.__('app.date').' - '.$this->booking->date_time->format($this->booking->company->date_format.' '.$this->booking->company->time_format));
    }
    // @codingStandardsIgnoreEnd

}
