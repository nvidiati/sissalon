<?php

namespace App\Notifications;

use App\Booking;
use App\ZoomMeeting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\NexmoMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class OnlineBookingConfirmation extends BaseNotification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */

    private $booking;
    private $meeting;

    public function __construct(Booking $booking, ZoomMeeting $meeting)
    {
        parent::__construct();
        $this->booking = $booking;
        $this->meeting = $meeting;
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
        $date = $this->booking->date_time ? $this->booking->date_time->format($this->booking->company->date_format.' '.$this->booking->company->time_format) : '';

        $mail = new MailMessage();

        $mail->subject(__('email.bookingConfirmation.subject').' '.config('app.name').'!')
            ->greeting(__('email.hello').' '.ucwords($notifiable->name).'!')
            ->line(__('email.bookingConfirmation.text'))
            ->line(__('app.booking').' #'.$this->booking->id)
            ->line(__('app.booking').' '.__('app.date').' - '.$date)
            ->line(__('app.meetingPassword').' - '.$this->meeting->password);

        return $mail->action(__('app.joinMeeting'), url($this->meeting->join_link))
            ->line(__('email.thankyouNote'))
            ->salutation(new HtmlString(__('email.regards').',<br>'.config('app.name')));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    /* @phpstan-ignore-next-line */
    public function toArray()
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
    // @codingStandardsIgnoreStart
    /* @phpstan-ignore-next-line */
    public function toNexmo($notifiable)
    {
        $date = $this->booking->date_time ? $this->booking->date_time->format($this->booking->company->date_format.' '.$this->booking->company->time_format) : '';

        if(is_null($this->booking->deal_id))
        {
            /* @phpstan-ignore-next-line */
            return (new NexmoMessage)
                ->content(
                    __('email.bookingConfirmation.text')."\n".
                    __('app.booking').' #'.$this->booking->id."\n".
                    __('app.booking').' '.__('app.date').' - '.$date)->unicode();
        }
        else
        {
            /* @phpstan-ignore-next-line */
            return (new NexmoMessage)
                ->content(
                    __('email.bookingConfirmation.text')."\n".
                    __('app.booking').' #'.$this->booking->id."\n"
                )->unicode();
        }
    }

    // @codingStandardsIgnoreLine
    /* @phpstan-ignore-next-line */
    public function toMsg91($notifiable)
    {
        $date = $this->booking->date_time ? $this->booking->date_time->format($this->booking->company->date_format.' '.$this->booking->company->time_format) : '';

        if(is_null($this->booking->deal_id))
        {
            return (new \Craftsys\Notifications\Messages\Msg91SMS)
                ->from($this->smsSetting->msg91_from)
                ->content(
                    __('email.bookingConfirmation.text')."\n".
                    __('app.booking').' #'.$this->booking->id."\n".
                    __('app.booking').' '.__('app.date').' - '.$date);
        }
        else
        {
            return (new \Craftsys\Notifications\Messages\Msg91SMS)
                ->from($this->smsSetting->msg91_from)
                ->content(
                    __('email.bookingConfirmation.text')."\n".
                    __('app.booking').' #'.$this->booking->id."\n"
                );
        }

    }
    // @codingStandardsIgnoreEnd

}
