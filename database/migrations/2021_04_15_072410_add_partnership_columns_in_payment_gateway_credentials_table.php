<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPartnershipColumnsInPaymentGatewayCredentialsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_gateway_credentials', function (Blueprint $table) {
            $table->enum('paypal_commission_status', ['active', 'deactive'])->default('deactive')->after('paypal_status');
            $table->integer('paypal_commission_percentage')->nullable()->after('paypal_commission_status');
            $table->text('paypal_partnership_details')->nullable()->after('paypal_commission_percentage');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_gateway_credentials', function (Blueprint $table) {
            //
        });
    }

}
