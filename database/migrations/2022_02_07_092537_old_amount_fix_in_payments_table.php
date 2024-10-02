<?php

use App\Booking;
use App\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OldAmountFixInPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        $payments = Payment::all();

        foreach($payments as $payment)
        {
            $booking = Booking::findOrFail($payment->booking_id);

            if($booking->payment_status === 'completed')
            {
                $payment->amount_paid = $payment->amount;
                $payment->update();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            //
        });
    }

}
