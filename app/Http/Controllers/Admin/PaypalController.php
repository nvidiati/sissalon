<?php
namespace App\Http\Controllers\Admin;

use App\Company;
use App\GatewayAccountDetail;
use App\Helper\Reply;
use App\Package;
use App\PaymentGatewayCredentials;
use App\PaypalInvoice;
use App\Subscription;
use App\Traits\StripeSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PayPal\Api\Agreement;
use PayPal\Api\AgreementStateDescriptor;
use PayPal\Api\Currency;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\Plan;
use PayPal\Common\PayPalModel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;

/** All Paypal Details class **/

use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use Carbon\Carbon;
use App\User;
use App\Http\Controllers\AdminBaseController;
use PayPal\Auth\OAuthTokenCredential;
use GuzzleHttp\Client;
use GuzzleHttp\Middleware;

class PaypalController extends AdminBaseController
{
    private $api_context;
    use StripeSettings;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $credential = PaymentGatewayCredentials::withoutGlobalScope(CompanyScope::class)->first();

        /** setup PayPal api context **/
        config(['paypal.settings.mode' => $credential->paypal_mode]);
        config(['paypal.client_id' => $credential->paypal_client_id]);
        config(['paypal.secret' => $credential->paypal_secret]);
        $paypal_conf = Config::get('paypal');

        $this->oAuthTokenCredential = new OAuthTokenCredential($credential->paypal_client_id, $credential->paypal_secret);

        $this->_api_context = new ApiContext($this->oAuthTokenCredential);

        $this->_api_context->setConfig($paypal_conf['settings']);

        $this->pageTitle = 'modules.paymentSetting.paypal';
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
    public function paymentWithpaypal(Request $request, $invoiceId, $type)
    {
        $package = Package::where('id', $invoiceId)->first();

        if($type == 'annual'){
            $totalAmount = $package->annual_price;
            $frequency = 'year';
            $cycle = 0;
        }
        else{
            $totalAmount = $package->monthly_price;
            $frequency = 'month';
            $cycle = 0;
        }

        $this->companyName = company()->company_name;

        $plan = new Plan();
        $plan->setName('#'.$package->name)
            ->setDescription( __('messages.paymentForPackage') .'#' .$package->name)
            ->setType('INFINITE');

        $paymentDefinition = new PaymentDefinition();
        $paymentDefinition->setName( __('messages.paymentForPackage') .'#' .$package->name)
            ->setType('REGULAR')
            ->setFrequency(strtoupper($frequency))
            ->setFrequencyInterval(1)
            ->setCycles($cycle)
            ->setAmount(new Currency(array('value' => $totalAmount, 'currency' => $package->currency->currency_code)));

        $merchantPreferences = new MerchantPreferences();
        $merchantPreferences->setReturnUrl(route('admin.paypal-recurring').'?success=true&invoice_id='.$invoiceId)
            ->setCancelUrl(route('admin.paypal-recurring').'?success=false&invoice_id='.$invoiceId)
            ->setAutoBillAmount('yes')
            ->setInitialFailAmountAction('CONTINUE')
            ->setMaxFailAttempts('0');

        $plan->setPaymentDefinitions(array($paymentDefinition));
        $plan->setMerchantPreferences($merchantPreferences);

        try {
            $output = $plan->create($this->api_context);

        } catch (\Exception $ex) {

            if (Config::get('app.debug')) {
                Session::put('error', __('error.connectionTimeOut'));
                return Redirect::route('admin.billing.index');
            }
            else {
                Session::put('error', __('error.errorMessage'));
                return Redirect::route('admin.billing.index');
            }
        }

        try {
            $patch = new Patch();
            $value = new PayPalModel('{
               "state":"ACTIVE"
             }');
            $patch->setOp('replace')
                ->setPath('/')
                ->setValue($value);

            $patchRequest = new PatchRequest();
            $patchRequest->addPatch($patch);
            $output->update($patchRequest, $this->api_context);
            $newPlan = Plan::get($output->getId(), $this->api_context);

        } catch (Exception $ex) {

            if (Config::get('app.debug')) {
                Session::put('error', __('error.connectionTimeOut'));
                return Redirect::route('admin.billing.index');
            }
            else {
                Session::put('error', __('error.errorMessage'));
                return Redirect::route('admin.billing.index');
            }
        }
        $company = Company::findOrFail(company()->id);


        // Calculating next billing date
        $today = Carbon::now()->addDays(1); // Payment will deduct after 1 day

        $startingDate = $today->toIso8601String();


        $agreement = new Agreement();
        $agreement->setName($package->name)
            ->setDescription( __('messages.paymentForPackage') .'#' .$package->name)
            ->setStartDate($startingDate);

        $plan1 = new Plan();
        $plan1->setId($newPlan->getId());
        $agreement->setPlan($plan1);

        // Add Payer
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        $agreement->setPayer($payer);

        // Create Agreement
        try {
            // Please note that as the agreement has not yet activated, we wont be receiving the ID just yet.
            $agreement = $agreement->create($this->api_context);

            $approvalUrl = $agreement->getApprovalLink();
        } catch (\Exception $ex) {

            if (Config::get('app.debug')) {
                Session::put('error', __('error.connectionTimeOut'));
                return Redirect::route('admin.billing.index');
            }
            else {
                Session::put('error', __('error.errorMessage'));
                return Redirect::route('admin.billing.index');
            }
        }
        /** add payment ID to session **/
        Session::put('paypal_payment_id', $newPlan->getId());

        $paypalInvoice = new PaypalInvoice();
        $paypalInvoice->company_id = company()->id;
        $paypalInvoice->package_id = $package->id;
        $paypalInvoice->currency_id = company()->currency_id;
        $paypalInvoice->total = $totalAmount;
        $paypalInvoice->status = 'pending';
        $paypalInvoice->plan_id = $newPlan->getId();
        $paypalInvoice->billing_frequency = $frequency;
        $paypalInvoice->billing_interval = 1;
        $paypalInvoice->save();

        if(isset($approvalUrl)) {
            /** redirect to paypal **/
            return Redirect::away($approvalUrl);
        }

        Session::put('error', __('error.unknownError'));
        return Redirect::route('admin.billing.index');

    }

    public function getPaymentStatus(Request $request)
    {
        /** Get the payment ID before session clear **/
        $payment_id = Session::get('paypal_payment_id');
        $invoice_id = Session::get('invoice_id');
        $clientPayment = PaypalInvoice::where('plan_id', $payment_id)->first();
        /** clear the session payment ID **/
        Session::forget('paypal_payment_id');

        if (empty($request->PayerID) || empty($request->token)) {
            Session::put('error', __('error.paymentFailed'));
            return redirect(route('admin.billing.index'));
        }

        $payment = Payment::get($payment_id, $this->api_context);
        /** PaymentExecution object includes information necessary **/
        /** to execute a PayPal account payment. **/
        /** The payer_id is added to the request query parameters **/
        /** when the user is redirected from paypal back to your site **/
        $execution = new PaymentExecution();
        $execution->setPayerId(request()->get('PayerID'));
        /**Execute the payment **/
        $result = $payment->execute($execution, $this->api_context);

        if ($result->getState() == 'approved') {

            /** it's all right **/
            /** Here Write your database logic like that insert record or value in database if you want **/
            $clientPayment->paid_on = Carbon::now();
            $clientPayment->status = 'paid';
            $clientPayment->save();

            Session::put('success', __('messages.paymentSuccess'));
            return Redirect::route('admin.billing.index');
        }

        Session::put('error', __('error.paymentFailed'));

        return Redirect::route('admin.billing.index');
    }

    public function payWithPaypalRecurrring(Request $requestObject)
    {
        /** Get the payment ID before session clear **/
        $payment_id = Session::get('paypal_payment_id');
        $clientPayment = PaypalInvoice::where('plan_id', $payment_id)->first();
        $company = company();
        /** clear the session payment ID **/
        Session::forget('paypal_payment_id');

        if($requestObject->get('success') == true && $requestObject->has('token') && $requestObject->get('success') != 'false' )
        {
            $token = $requestObject->get('token');
            $agreement = new Agreement();

            try {
                // Execute Agreement
                // Execute the agreement by passing in the token
                $agreement->execute($token, $this->api_context);


                if($agreement->getState() == 'Active' || $agreement->getState() == 'Pending') {

                    $this->cancelSubscription();
                    // Calculating next billing date
                    $today = Carbon::now();


                    $clientPayment->transaction_id = $agreement->getId();

                    if($agreement->getState() == 'Active') {
                        $clientPayment->status = 'paid';
                    }

                    $clientPayment->paid_on = Carbon::now();
                    $clientPayment->save();

                    $company->package_id = $clientPayment->package_id;
                    $company->package_type = ($clientPayment->billing_frequency == 'year') ? 'annual' : 'monthly';
                    $company->status = 'active';// Set company status active
                    $company->licence_expire_on = null;
                    $company->save();

                    if( $company->package_type == 'monthly') {
                        $today = $today->addMonth();
                    }
                    else {
                        $today = $today->addYear();
                    }

                    $clientPayment->next_pay_date = $today->format('Y-m-d');
                    $clientPayment->save();

                    // Send superadmin notification
                    $generatedBy = User::whereNull('company_id')->get();
                    // Notification::send($generatedBy, new CompanyUpdatedPlan($company, $clientPayment->package_id));

                    Session::put('success', __('messages.paymentSuccessDone'));
                    return Redirect::route('admin.billing.index');
                }

                Session::put('error', __('error.paymentFailed'));

                return Redirect::route('admin.billing.index');

            } catch (PayPalConnectionException $ex) {
                $errCode = $ex->getCode();
                $errData = json_decode($ex->getData());

                if ($errCode == 400 && $errData->name == 'INVALID_CURRENCY'){
                    Session::put('error', $errData->message);
                    return Redirect::route('admin.billing.index');
                }
                elseif (Config::get('app.debug')) {
                    Session::put('error', __('error.connectionTimeout'));
                    return Redirect::route('admin.billing.index');
                }
                else {
                    Session::put('error', __('error.errorMessage'));
                    return Redirect::route('admin.billing.index');
                }
            }

        }
        else if($requestObject->get('fail') == true || $requestObject->get('success') == 'false')
        {
            Session::put('error', __('error.paymentFailed'));

            return Redirect::route('admin.billing.index');

        }else {
            abort(403);
        }

    }

    public function cancelAgreement()
    {
        $paypalInvoice = PaypalInvoice::whereNotNull('transaction_id')->whereNull('end_on')
            ->where('id', company()->id)->first();

        $agreementId = $paypalInvoice->transaction_id;
        $agreement = new Agreement();

        $agreement->setId($agreementId);
        $agreementStateDescriptor = new AgreementStateDescriptor();
        $agreementStateDescriptor->setNote( __('messages.cancelAgreement'));

        try {
            $agreement->cancel($agreementStateDescriptor, $this->_apiContext);
            $cancelAgreementDetails = Agreement::get($agreement->getId(), $this->_apiContext);

            // Set subscription end date
            $paypalInvoice->end_on = Carbon::parse($cancelAgreementDetails->agreement_details->final_payment_date)->format('Y-m-d H:i:s');
            $paypalInvoice->save();
        } catch (\Exception $ex) {
            $ex = $ex;
        }

    }

    public function cancelSubscription()
    {
        $company = company();
        $stripe = DB::table('stripe_invoices')
            ->join('packages', 'packages.id', 'stripe_invoices.package_id')
            ->selectRaw('stripe_invoices.id , "Stripe" as method, stripe_invoices.pay_date as paid_on ,stripe_invoices.next_pay_date')
            ->whereNotNull('stripe_invoices.pay_date')
            ->where('stripe_invoices.company_id', company()->id);

        $allInvoices = DB::table('paypal_invoices')
            ->join('packages', 'packages.id', 'paypal_invoices.package_id')
            ->selectRaw('paypal_invoices.id, "Paypal" as method, paypal_invoices.paid_on,paypal_invoices.next_pay_date')
            ->where('paypal_invoices.status', 'paid')
            ->whereNull('paypal_invoices.end_on')
            ->where('paypal_invoices.company_id', company()->id)
            ->union($stripe)
            ->get();

        $firstInvoice = $allInvoices->sortByDesc(function ($temp, $key) {
            return Carbon::parse($temp->paid_on)->getTimestamp();
        })->first();

        if(!is_null($firstInvoice) && $firstInvoice->method == 'Paypal'){
            $credential = PaymentGatewayCredentials::withoutGlobalScope(CompanyScope::class)->first();
            config(['paypal.settings.mode' => $credential->paypal_mode]);
            $paypal_conf = Config::get('paypal');
            $api_context = new ApiContext(new OAuthTokenCredential($credential->paypal_client_id, $credential->paypal_secret));
            $api_context->setConfig($paypal_conf['settings']);

            $paypalInvoice = PaypalInvoice::whereNotNull('transaction_id')->whereNull('end_on')
                ->where('company_id', company()->id)->where('status', 'paid')->first();

            if($paypalInvoice){
                $agreementId = $paypalInvoice->transaction_id;
                $agreement = new Agreement();

                $agreement->setId($agreementId);
                $agreementStateDescriptor = new AgreementStateDescriptor();
                $agreementStateDescriptor->setNote(__('messages.cancelAgreement'));

                try {
                    $agreement->cancel($agreementStateDescriptor, $api_context);
                    $cancelAgreementDetails = Agreement::get($agreement->getId(), $api_context);

                    // Set subscription end date
                    $paypalInvoice->end_on = Carbon::parse($cancelAgreementDetails->agreement_details->final_payment_date)->format('Y-m-d H:i:s');
                    $paypalInvoice->save();

                    $company->licence_expire_on = $paypalInvoice->end_on;
                    $company->save();

                } catch (\Exception $ex) {
                    $ex = $ex;
                }
            }

        }elseif(!is_null($firstInvoice) && $firstInvoice->method == 'Stripe'){
            $this->setStripConfigs();

            $subscription = Subscription::where('company_id', company()->id)->whereNull('ends_at')->first();

            if($subscription){
                try {
                    $company->subscription('main')->cancel();
                    $company->licence_expire_on = $subscription->ends_at;
                    $company->save();

                } catch (\Exception $ex) {
                    $ex = $ex;
                }
            }
        }
    }

    public function paypalInvoiceDownload($id)
    {
        $this->invoice = PaypalInvoice::with(['company','currency','package'])->findOrFail($id);
        $this->company = company();
        $this->global = $this->superadmin;
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('paypal-invoice.invoice-1', $this->data);
        $filename = $this->invoice->paid_on->format($this->global->date_format).'-'.$this->invoice->next_pay_date->format($this->global->date_format);
        return $pdf->download($filename . '.pdf');
    }

    // Connect seller account
    public function createPaypalAccountLink()
    {
        $accessToken = $this->oAuthTokenCredential->getAccessToken(config('paypal.settings'));

        $client = new Client();

        $response = $client->post('https://api-m.sandbox.paypal.com/v2/customer/partner-referrals', [
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken,
            ],
            'json' => [
                'partner_config_override' => [
                    'partner_logo_url' => 'https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_111x69.jpg',
                    'return_url' => route('admin.paypal.storeMerchantDetails'),
                    'return_url_description' => 'the url to return the merchant after the paypal onboarding process.',
                    'action_renewal_url' => 'https://testenterprises.com/renew-exprired-url',
                    'show_add_credit_card' => true
                ],
                'operations' => [
                    [
                        'operation' => 'API_INTEGRATION',
                        'api_integration_preference' => [
                            'rest_api_integration' => [
                                'integration_method' => 'PAYPAL',
                                'integration_type' => 'THIRD_PARTY',
                                'third_party_details' => [
                                    'features' => [
                                        'PAYMENT',
                                        'REFUND'
                                    ],
                                ]
                            ]
                        ]
                    ]
                ],
                'products' => [
                    'EXPRESS_CHECKOUT'
                ],
                'legal_consents' => [
                    [
                        'type' => 'SHARE_DATA_CONSENT',
                        'granted' => true
                    ]
                ]
            ]
        ])->getBody()->getContents();
        
        $action_url = collect(json_decode($response)->links)
        ->filter(function($link) {
            return $link->rel == 'action_url';
        })->first()->href;

        GatewayAccountDetail::updateOrCreate([
            'company_id' => $this->user->id,
            'gateway' => 'paypal'
        ],
        [
            'link' => $action_url,
            'account_status' => 'active'
        ]);

        return Reply::successWithData(__('messages.newLinkGenerated'), ['action_url' => $action_url]);
    }

    public function storeMerchantDetails(Request $request)
    {
        $gateway = GatewayAccountDetail::whereGateway('paypal');

        if ($request->has('consentStatus') && $request->has('isEmailConfirmed') && $request->consentStatus == 'true' && $request->isEmailConfirmed == 'true')
        {
            $gateway->update([
                'account_id' => $request->merchantIdInPayPal,
                'connection_status' => 'connected',
                'account_status' => 'active'
            ]);

            return redirect(route('admin.settings.index').'#payment')->with(__('messages.accountConnectedSuccessfully'));
        }
        elseif ($request->has('consentStatus') && $request->consentStatus == 'true') {

            $gateway->update([
                'account_id' => $request->merchantIdInPayPal,
                'connection_status' => 'connected',
                'account_status' => 'inactive'
            ]);
        }

        return redirect(route('admin.settings.index').'#payment')->with(__('messages.accountConnectionFailed'));
    }

    // Connect seller account after verify email id
    public function verifyMerchantDetails()
    {
        $accessToken = $this->oAuthTokenCredential->getAccessToken(config('paypal.settings'));
        
        $credentialSetting = PaymentGatewayCredentials::withoutGlobalScope('company')->first();
        $partnerMerchantId = $credentialSetting->paypal_partnership_details ? $credentialSetting->paypal_partnership_details['partner_merchant_id'] : '';
        
        $credentialSettings = GatewayAccountDetail::ofGateway('paypal')->first();
        $sellerMerchantId = $credentialSettings ? $credentialSettings->account_id : '';
        
        $client = new Client();

        if (!is_null($partnerMerchantId) && !is_null($sellerMerchantId)) {
            $response = $client->get('https://api-m.sandbox.paypal.com/v1/customer/partners/'.$partnerMerchantId.'/merchant-integrations/'.$sellerMerchantId, [
                'headers' => [
                    'Authorization' => 'Bearer '.$accessToken,
                ],
            ])->getBody()->getContents();
        }

        if (!is_null($response) && json_decode($response)->primary_email_confirmed === 'true' && !is_null($sellerMerchantId)) {

            $accountDetail = GatewayAccountDetail::where('company_id', $this->user->company_id)->where('gateway', 'paypal')->where('account_id', $sellerMerchantId)->first();

            $accountDetail->account_status = 'active';

            $accountDetail->save();

            return Reply::success(__('messages.EmailVerifiedTrue'));
        }

        return Reply::error(__('messages.EmailVerifiedFalse'));
    }

}
