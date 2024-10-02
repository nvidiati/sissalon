<?php

namespace App\Http\Controllers\Front;

use App\Tax;
use App\User;
use App\Booking;
use App\Company;
use App\Currency;
use App\Payment;
use Carbon\Carbon;
use Stripe\Stripe;
use App\ZoomMeeting;
use App\Helper\Reply;
use App\GlobalSetting;
use App\Scopes\CompanyScope;
use Illuminate\Http\Request;
use App\GatewayAccountDetail;
use App\Notifications\NewBooking;
use App\PaymentGatewayCredentials;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Notifications\OnlineNewBooking;
use Illuminate\Support\Facades\Session;
use App\Notifications\BookingConfirmation;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OnlineBookingConfirmation;
use Exception;

class StripeController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->stripeCredentials = PaymentGatewayCredentials::withoutGlobalScope(CompanyScope::class)->first();

        /** setup Stripe credentials **/
        Stripe::setApiKey($this->stripeCredentials->stripe_secret);
        $this->pageTitle = 'Stripe';
    }

    public function createAccountLink()
    {
        $account = \Stripe\Account::create([
            'country' => 'US',
            'type' => 'express',
        ]);

        $account_links = \Stripe\AccountLink::create([
            'account' => $account->id,
            'type' => 'account_onboarding',
            'return_url' => route('admin.returnStripeSuccess'),
            'refresh_url' => route('admin.refreshLink', $account->id),
        ]);

        $link_expire_at = Carbon::createFromTimestamp($account_links->created)->addDays(7)->diffForHumans();

        $expireDate = Carbon::createFromTimestamp($account_links->created)->addDays(7)->toDateTimeString();

        $company = $this->user->company;
        $company->stripe_id = $account->id;
        $company->save();

        $details = GatewayAccountDetail::where('company_id', $company->id)->first();
        $details = $details ? $details : new GatewayAccountDetail();
        $details->company_id = $company->id;
        $details->account_id = $account->id;
        $details->link = $account_links->url;
        $details->link_expire_at = $expireDate;
        $details->gateway = 'Stripe';
        $details->account_status = 'active';
        $details->connection_status = 'not_connected';
        $details->save();

        return Reply::successWithData(__('messages.createdSuccessfully'), ['details' => $details, 'link_expire_at' => $link_expire_at]);
    }

    public function checkStripeAmount(Request $request)
    {

        // Get exchange rates
        $amountToPay = (float)floatval(preg_replace('/[^\d.]/', '', $request->amountToPay));
        $to_currency = Company::withoutGlobalScope(CompanyScope::class)->find($request->companyId)->currency->exchange_rate;
        $from_currency = GlobalSetting::first()->currency->exchange_rate;

        try {
            // Convert amount
            $value = ($amountToPay * $to_currency) / $from_currency;
        } catch (Exception $e) {
            // Prevent invalid conversion or division by zero errors
            $value = $amountToPay;
        }

        $amount = round($value, 2);

        $usdAmount = $to_currency / 2;

        return Reply::dataOnly(['amount' => $amount, 'usdAmount' => $usdAmount]);
    }

    /**
     * Store a details of payment with paypal.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function paymentWithStripe(Request $request)
    {
        $tax_amount = Tax::active()->first();

        $booking = Booking::with('items')->whereId($request->booking_id)->first();
        $paymentCredentials = PaymentGatewayCredentials::withoutGlobalScope(CompanyScope::class)->first();
        $stripeAccountDetails = GatewayAccountDetail::activeConnectedOfGateway('stripe')->first();

        $line_items = [];

        foreach ($booking->items as $key => $value) {
            $name = ($value->business_service_id == null) ? $value->product->name ?? 'deal' : $value->businessService->name;
            $price = ($value->business_service_id == null) ? $value->unit_price * 100 : ($value->unit_price * $tax_amount->percent) + $value->unit_price * 100;

            if ($booking->coupon) {

                $couponDiscount = (round(currencyConvertedPrice($value->company_id, $price), 0) * $booking->coupon->amount) / 100;
                $itemAmt = round(currencyConvertedPrice($value->company_id, $price), 0) - $couponDiscount;

                $line_items[] = [
                    'name' => $name,
                    'amount' => round($itemAmt, 0),
                    'currency' => $this->settings->currency->currency_code,
                    'quantity' => $value->quantity,
                ];
            }
            else {
                $line_items[] = [
                    'name' => $name,
                    'amount' => round(currencyConvertedPrice($value->company_id, $price), 0),
                    'currency' => $this->settings->currency->currency_code,
                    'quantity' => $value->quantity,
                ];
            }
        }

        $amount = $booking->converted_amount_to_pay * 100;

        $name = Auth::user()->name;
        $destination = $stripeAccountDetails ? $stripeAccountDetails->account_id : '';

        $applicationFee = round((($amount / 100) * $paymentCredentials->stripe_commission_percentage), 0);
        $data = [];

        if ($destination != null && $destination != '') {
            $data = [
                'payment_method_types' => ['card'],
                'line_items' => [$line_items],
                'payment_intent_data' => [
                    'application_fee_amount' => $applicationFee,
                    'transfer_data' => [
                        'destination' => $destination,
                    ],
                ],
                'success_url' => route('front.afterStripePayment', $request->return_url),
                'cancel_url' => route('front.payment-gateway'),
            ];
        }
        elseif ($destination == null && $destination == '') {
            $data = [
                'payment_method_types' => ['card'],
                'line_items' => [$line_items],
                'success_url' => route('front.afterStripePayment', $request->return_url),
                'cancel_url' => route('front.payment-gateway'),
            ];
        }

        $session = \Stripe\Checkout\Session::create($data);

        session(['stripe_session' => $session]);

        return Reply::dataOnly(['id' => $session->id]);
    }

    public function afterStripePayment(Request $request, $return_url, $bookingId = null)
    {
        $session_data = session('stripe_session');
        $session = \Stripe\Checkout\Session::retrieve($session_data->id);

        $payment_method = \Stripe\PaymentIntent::retrieve(
            $session->payment_intent,
            []
        );

        if ($bookingId == null) {
            $invoice = Booking::where([
                'user_id' => Auth::user()->id,
            ])
                ->latest()
                ->first();
        }
        else {
            $invoice = Booking::where(['id' => $bookingId, 'user_id' => Auth::user()->id])->first();
        }

        $saCredentials = PaymentGatewayCredentials::withoutGlobalScope(CompanyScope::class)->first();

        $currency = GlobalSetting::first()->currency;

        $payment = new Payment();
        $payment->booking_id = $invoice->id;
        $payment->company_id = $invoice->company_id;
        $payment->currency_id = $invoice->currency_id;
        $payment->customer_id = $this->user->id;
        $payment->amount = $invoice->amount_to_pay;
        $payment->amount_paid = $invoice->amount_to_pay;
        $payment->amount_remaining = $invoice->amount_to_pay ? 0 : $invoice->amount_to_pay;
        $payment->gateway = 'Stripe';
        $payment->transaction_id = $payment_method->id;

        if ($payment_method->transfer_data && !is_null($payment_method->transfer_data->destination)) { /** @phpstan-ignore-line */
            $payment->transfer_status = 'transferred';
        }
        else {
            $payment->transfer_status = 'not_transferred';
        }

        $payment->paid_on = Carbon::now();
        $payment->status = $payment_method->status == 'succeeded' ? 'completed' : 'pending';
        $payment->commission = $saCredentials->stripe_commission_status === 'active' ? round(($invoice->amount_to_pay / 100) * $saCredentials->stripe_commission_percentage) : 0;
        $payment->save();

        $invoice->payment_gateway = 'Stripe';
        $invoice->payment_status = 'completed';
        $invoice->save();

        // send email notifications

        $admins = User::allAdministrators()->where('company_id', $invoice->company_id)->first();
        Notification::send($admins, new NewBooking($invoice));

        $user = User::findOrFail($invoice->user_id);
        $user->notify(new BookingConfirmation($invoice));

        if($invoice->booking_type === 'online' && $invoice->status === 'approved')
        {
            $meeting = ZoomMeeting::where('booking_id', $invoice->id)->first();
            $admins = User::where('id', $meeting->host_id)->where('company_id', $invoice->company_id)->first();
            Notification::send($admins, new OnlineNewBooking($invoice, $meeting));

            $user = User::findOrFail($invoice->user_id);
            $user->notify(new OnlineBookingConfirmation($invoice, $meeting));
        }

        Session::put('success', __('messages.paymentSuccessAmount') . $invoice->formated_amount_to_pay);

        if ($return_url == 'bookingPage') {

            return redirect()->route('admin.bookings.index');

        }elseif ($return_url == 'calendarPage') {

            return redirect()->route('admin.calendar');
        }
        return $this->redirectToPayment($bookingId, 'Payment success');
    }

    public function redirectToPayment($id, $message)
    {
        if ($id == null) {
            return redirect()->route('front.payment.success')->with(['message' => $message]);
        }

        return redirect()->route('front.payment.success')->with(['id' => $id, 'message' => $message]);
    }

    public function redirectToErrorPage($id, $message)
    {

        Session::put('error', __('messages.errorMessage'));

        if ($id == null) {
            return Reply::redirect(route('front.payment.fail'), $message);
        }

        return Reply::redirect(route('front.payment.fail', $id), $message);
    }

}
