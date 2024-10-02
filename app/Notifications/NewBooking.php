<?php

namespace App\Notifications;

use PDF;
use App\Booking;
use Illuminate\Support\HtmlString;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\NexmoMessage;

class NewBooking extends BaseNotification
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
        $date = $this->booking->date_time ? $this->booking->date_time->format($this->booking->company->date_format.' '.$this->booking->company->time_format) : '';
        $booking = $this->booking;
        $bookingPayments = $booking->bookingPayments;
        $totalPaid = $bookingPayments->sum('amount_paid');
        $totalPending = $booking->amount_to_pay - $totalPaid;
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('admin.booking.receipt', compact('booking', 'totalPaid', 'totalPending') );
        $filename = __('app.receipt').' #'.$this->booking->id;

        $mail = new MailMessage();

        $mail->subject(__('email.newBooking.subject').' '.config('app.name').'!')
            ->greeting(__('email.hello').' '.ucwords($notifiable->name).'!')
            ->line(__('email.newBooking.text'))
            ->line(__('app.booking').' #'.$this->booking->id);

        if(is_null($this->booking->deal_id)){
            $mail->line(__('app.booking').' '.__('app.date').' - '.$date);
        }

            $mail->action(__('email.loginAccount'), url('/login'))
                ->line(__('email.thankyouNote'));

        if(!is_null($this->booking->deal_id)){
            $mail->attachData($pdf->output(), $filename);
        }

            return $mail->salutation(new HtmlString(__('email.regards').',<br>'.config('app.name')));

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

        if(is_null($this->booking->deal_id)){
            /* @phpstan-ignore-next-line */
            return (new NexmoMessage)
                ->content(
                __('email.newBooking.text')."\n".
                __('app.booking').' #'.$this->booking->id."\n".
                __('app.booking').' '.__('app.date').' - '.$date)->unicode();
        }
        else
        {
            /* @phpstan-ignore-next-line */
            return (new NexmoMessage)
                ->content(
                __('email.newBooking.text')."\n".
                __('app.booking').' #'.$this->booking->id."\n")
                ->unicode();
        }
    }

    // @codingStandardsIgnoreLine
    /* @phpstan-ignore-next-line */
    public function toMsg91($notifiable)
    {
        $date = $this->booking->date_time ? $this->booking->date_time->format($this->booking->company->date_format.' '.$this->booking->company->time_format) : '';

        if(is_null($this->booking->deal_id)){
            return (new \Craftsys\Notifications\Messages\Msg91SMS)
                ->from($this->smsSetting->msg91_from)
                ->content(
                __('email.newBooking.text')."\n".
                __('app.booking').' #'.$this->booking->id."\n".
                __('app.booking').' '.__('app.date').' - '.$date);
        }
        else
        {
            return (new \Craftsys\Notifications\Messages\Msg91SMS)
                ->from($this->smsSetting->msg91_from)
                ->content(
                __('email.newBooking.text')."\n".
                __('app.booking').' #'.$this->booking->id."\n");
        }

    }

    // @codingStandardsIgnoreEnd

}
