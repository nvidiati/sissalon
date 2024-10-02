<?php
namespace App\Http\Controllers\Front;

use App\Booking;
use App\Company;
use App\GatewayAccountDetail;
use App\Helper\Reply;
use App\Http\Controllers\Controller;
use App\Notifications\BookingConfirmation;
use App\Notifications\NewBooking;
use App\PaymentGatewayCredentials;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;

/** All Paypal Details class **/
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Transaction;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use PayPal\Api\Order;

class PaypalController extends Controller
{
    private $api_context;
    private $PAYPAL_SANDBOX_URL = 'https://api-m.sandbox.paypal.com/v2/checkout';
    private $PAYPAL_LIVE_URL = 'https://api-m.paypal.com/v2/checkout';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->credential = PaymentGatewayCredentials::first();
        config([
            'paypal.settings.mode' => $this->credential->paypal_mode,
            'paypal.client_id' => $this->credential->paypal_client_id,
            'paypal.secret' => $this->credential->paypal_secret,
        ]);
        /** setup PayPal api context **/
        $paypal_conf = Config::get('paypal');
        $this->oAuthTokenCredential = new OAuthTokenCredential($paypal_conf['client_id'], $paypal_conf['secret']);
        $this->paypalUrl = $this->credential->paypal_mode === 'sandbox' ? $this->PAYPAL_SANDBOX_URL : $this->PAYPAL_LIVE_URL;
        $this->_api_context = new ApiContext($this->oAuthTokenCredential);
        $this->_api_context->setConfig($paypal_conf['settings']);
        $this->pageTitle = 'Paypal';
    }

    /**
     * Show the application paywith paypalpage.
     *
     * @return \Illuminate\Http\Response
     */
    public function payWithPaypal()
    {
        return view('paywithpaypal', $this->data);
    }

    /**
     * Store a details of payment with paypal.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function paymentWithpaypal(Request $request, $bookingId = null)
    {
        if ($bookingId == null) {
            $invoice = Booking::where(['user_id' => Auth::user()->id])->latest()->first();
        }
        else {
            $invoice = Booking::where(['id' => $bookingId, 'user_id' => $this->user->id])->first();
        }

        $setting = Company::where('id', $invoice->company_id)->first();
        $currency = $setting->currency;

        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

            $item_1 = new Item();

            $item_1->setName( __('messages.paymentForPackage') .'#' .$invoice->id) /** item name **/
                ->setCurrency($currency->currency_code)
                ->setQuantity(1)
                ->setPrice($invoice->amount_to_pay); /** unit price **/

            $item_list = new ItemList();
            $item_list->setItems(array($item_1));

            $amount = new Amount();
            $amount->setCurrency($currency->currency_code)
                ->setTotal($invoice->amount_to_pay);

            $transaction = new Transaction();
            $transaction->setAmount($amount)
                ->setItemList($item_list)
                ->setDescription(__('messages.paymentForPackage') .'#' .$invoice->id);

            $redirect_urls = new RedirectUrls();
            $redirect_urls->setReturnUrl(route('front.status')) /** Specify return URL **/
                ->setCancelUrl(route('front.status', ['cancel']));

            $payment = new Payment();
            $payment->setIntent('Sale')
                ->setPayer($payer)
                ->setRedirectUrls($redirect_urls)
                ->setTransactions(array($transaction));

            $credential = PaymentGatewayCredentials::first();

        try {
            config(['paypal.secret' => $credential->paypal_secret]);
            $payment->create($this->api_context);
        } catch (\PayPal\Exception\PayPalConnectionException $ex) {
            if (Config::get('app.debug')) {
                Session::put('error', __('messages.connectionTimeout'));
                return $this->redirectToErrorPage($bookingId);
                /** $err_data = json_decode($ex->getData(), true); **/
                /** exit; **/
            }
            else {
                Session::put('error', __('messages.inconvenientError'));
                return $this->redirectToErrorPage($bookingId);
            }
        }
        catch (Exception $ex) {
            if (Config::get('app.debug')) {
                Session::put('error', $ex->getMessage() );
                return $this->redirectToErrorPage($bookingId);
            }
            else {
                Session::put('error', __('messages.inconvenientError'));
                return $this->redirectToErrorPage($bookingId);
            }
        }

        foreach($payment->getLinks() as $link) {
            if($link->getRel() == 'approval_url') {
                $redirect_url = $link->getHref();
                break;
            }
        }

        // Add payment ID to session //
        Session::put('paypal_payment_id', $payment->getId());
        Session::put('invoice_id', $invoice->id);

        // Save details in database and redirect to paypal
        $clientPayment = new \App\Payment();
        $clientPayment->booking_id = $invoice->id;
        $clientPayment->currency_id = $currency->id;
        $clientPayment->amount = $invoice->amount_to_pay;
        $clientPayment->amount_paid = $invoice->amount_to_pay;
        $clientPayment->amount_remaining = $invoice->amount_to_pay ? 0 : $invoice->amount_to_pay;
        $clientPayment->transaction_id = $payment->getId();
        $clientPayment->gateway = 'PayPal';
        $clientPayment->save();

        if(isset($redirect_url)) {
            /** redirect to paypal **/
            return Redirect::away($redirect_url);
        }

            Session::put('error', __('messages.unknownError'));
            return $this->redirectToErrorPage($bookingId);
    }

    public function getPaymentStatus(Request $request, $status=null)
    {
        /** Get the payment ID before session clear **/
        $payment_id = Session::get('paypal_payment_id');
        $invoice_id = Session::get('invoice_id');
        $clientPayment = \App\Payment::where('transaction_id', $payment_id)->first();
        $setting = Company::first();
        $currency = $setting->currency;
        /** clear the session payment ID **/
        Session::forget('paypal_payment_id');

        if (empty($request->PayerID) || empty($request->token) || $status == 'cancel') {
            Session::put('error', 'Payment failed');
            return $this->redirectToErrorPage($clientPayment->booking_id);
        }

        $payment = Payment::get($payment_id, $this->api_context);
        /** PaymentExecution object includes information necessary **/
        /** to execute a PayPal account payment. **/
        /** The payer_id is added to the request query parameters **/
        /** when the user is redirected from paypal back to your site **/
        $execution = new PaymentExecution();
        $execution->setPayerId(request()->input('PayerID'));

        try {
            /** Execute the payment **/
            $result = $payment->execute($execution, $this->api_context);

            if ($result->getState() == 'approved') {

                /** it's all right **/
                /** Here Write your database logic like that insert record or value in database if you want **/
                $clientPayment->status = 'completed';
                $clientPayment->paid_on = Carbon::now();
                $clientPayment->save();

                $invoice = Booking::findOrFail($invoice_id);
                $invoice->payment_gateway = 'PayPal';
                $invoice->save();

                // Send email notifications
                $company = Booking::where(['user_id' => Auth::user()->id])->latest()->first();
                $admins = User::allAdministrators()->where('company_id', $company->company_id)->first();
                Notification::send($admins, new NewBooking($invoice));

                $user = User::findOrFail($invoice->user_id);
                $user->notify(new BookingConfirmation($invoice));

                Session::put('success', __('messages.paymentSuccessAmount') . $invoice->formated_amount_to_pay);
                return $this->redirectToPayment($invoice_id);
            }
        } catch (\Exception $ex) {
            Session::put('error', 'Payment failed');
            return $this->redirectToErrorPage($clientPayment->booking_id);
        }

        Session::put('error', 'Payment failed');

        return $this->redirectToErrorPage($clientPayment->booking_id);
    }

    public function redirectToPayment($id)
    {
        if ($id == null) {
            return redirect()->route('front.payment.success');
        }

        return redirect()->route('front.payment.success', $id);
    }

    public function redirectToErrorPage($id)
    {
        if ($id == null) {
            return redirect()->route('front.payment.fail');
        }

        return redirect()->route('front.payment.fail', $id);
    }

    public function createOrder()
    {
        $accessToken = $this->oAuthTokenCredential->getAccessToken(config('paypal.settings'));
        $merchantCredential = GatewayAccountDetail::activeConnectedOfGateway('paypal')->first();
        $createOrderUrl = $this->paypalUrl.'/orders';
        $booking = Booking::where([
            'user_id' => $this->user->id
        ])->latest()->first();

        $setting = Company::where('id', $booking->company_id)->first();
        $currency = $setting->currency;

        $client = new Client();

        $clientPayment = new \App\Payment();
        $clientPayment->transfer_status = 'not_transferred';
        $clientPayment->gateway = 'PayPal';

        $orderDetails = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $currency->currency_code,
                        'value' => $booking->amount_to_pay,
                        'breakdown' => [
                            'item_total' => [
                              'currency_code' => $currency->currency_code,
                              'value' => $booking->amount_to_pay
                            ]
                        ]
                    ],
                    'payee' => [
                        'email_address' => $this->credential->paypal_partnership_details['account_email'],
                    ],
                    'items' => [
                        [
                            'name' => 'Payment for booking #'.$booking->id,
                            'quantity' => '1',
                            'unit_amount' => [
                                'currency_code' => $currency->currency_code,
                                'value' => $booking->amount_to_pay
                            ]
                        ]
                    ]
                ],
            ],
            'application_context' => [
                'return_url' => route('front.payment.success'),
                'cancel_url' => route('front.payment.fail')
            ]
        ];

        if (!is_null($merchantCredential))
        {
            if ($this->credential->paypal_commission_status === 'active')
            {
                $commission = (string)round(($this->credential->paypal_commission_percentage) / 100 * $booking->amount_to_pay, 2);

                $paymentInstruction = [
                    'disbursement_mode' => 'INSTANT',
                    'platform_fees' => [
                        [
                            'amount' => [
                                'currency_code' => $currency->currency_code,
                                'value' => $commission,
                            ],
                        ],
                    ],
                ];
                $orderDetails['purchase_units'][0] = Arr::add($orderDetails['purchase_units'][0], 'payment_instruction', $paymentInstruction);
            }

            $orderDetails['purchase_units'][0]['payee'] = [
                'merchant_id' => $merchantCredential->account_id
            ];
        }

        $response = $client->post($createOrderUrl, [
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken,
                'PayPal-Partner-Attribution-Id' => $this->credential->paypal_partnership_details['bn_code']
            ],
            'json' => $orderDetails
        ])->getBody()->getContents();

        $orderId = json_decode($response)->id;

        $clientPayment->booking_id = $booking->id;
        $clientPayment->currency_id = $currency->id;
        $clientPayment->amount = $booking->amount_to_pay;
        $clientPayment->transaction_id = $orderId;
        $clientPayment->save();

        Session::put('booking', $booking);
        Session::put('currency', $currency);

        return Reply::dataOnly(['id' => $orderId]);
    }

    public function captureOrder($orderId)
    {
        $accessToken = $this->oAuthTokenCredential->getAccessToken(config('paypal.settings'));

        $client = new Client();
        $captureOrderUrl = $this->paypalUrl.'/orders/'.$orderId.'/capture';
        $booking = Session::get('booking');
        $currency = Session::get('currency');

        $clientPayment = \App\Payment::where('transaction_id', $orderId)->first();

        try {
            $response = json_decode($client->post($captureOrderUrl, [
                'headers' => [
                    'Authorization' => 'Bearer '.$accessToken,
                    'PayPal-Partner-Attribution-Id' => $this->credential->paypal_partnership_details['bn_code'],
                    'Content-Type' => 'application/json'
                ]
            ])->getBody()->getContents());
        }
        catch (\Exception $ex) {

            Session::put('error', 'Payment failed');
            return Reply::redirect(route('front.payment.fail', $clientPayment->booking_id));
        }

        if ($response->status === 'COMPLETED') {
            /** it's all right **/
            /** Here Write your database logic like that insert record or value in database if you want **/
            $clientPayment->status = 'completed';

            if (isset($response->purchase_units[0]->payment_instruction) && $response->purchase_units[0]->payment_instruction->disbursement_mode == 'INSTANT') {
                $clientPayment->transfer_status = 'transferred';
            }

            $clientPayment->paid_on = Carbon::now();
            $clientPayment->save();

            $booking = Booking::findOrFail($booking->id);
            $booking->payment_gateway = 'PayPal';
            $booking->save();

            // send email notifications
            $company = Booking::where(['user_id' => Auth::user()->id])->latest()->first();
            $admins = User::allAdministrators()->where('company_id', $company->company_id)->first();
            Notification::send($admins, new NewBooking($booking));

            $user = User::findOrFail($booking->user_id);
            $user->notify(new BookingConfirmation($booking));
        }

        Session::forget('booking_id');
        Session::put('success', __('messages.paymentSuccessAmount') .$currency->currency_symbol.$booking->amount_to_pay);

        return Reply::redirect(route('front.payment.success', $clientPayment->booking_id));
    }

}
