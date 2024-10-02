<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helper\Reply;
use App\Http\Requests\Payment\UpdateCredentialSetting;
use App\PaymentGatewayCredentials;
use App\Http\Controllers\SuperAdminBaseController;

class PaymentCredentialSettingController extends SuperAdminBaseController
{

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateCredentialSetting  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // @codingStandardsIgnoreLine
    public function update(UpdateCredentialSetting $request, $id)
    {
        if($request->razorpay_status != 'active' && $request->stripe_status != 'active' && $request->paypal_status != 'active' && $request->offline_payment != 1){
            return Reply::error(__('messages.paymentActiveRequired'));
        }

        $data = $request->all();

        $data['paypal_partnership_details'] = [
            'account_email' => $request->paypal_account_email,
            'bn_code' => $request->paypal_bn_code,
            'partner_merchant_id' => $request->partner_merchant_id,
        ];

        $credential = PaymentGatewayCredentials::first();

        $credential->update($data);

        return Reply::success(__('messages.updatedSuccessfully'));
    }

}
