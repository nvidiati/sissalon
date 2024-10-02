<?php

use App\Payment;
use App\Commission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('company_id')->unsigned()->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedInteger('currency_id')->unsigned()->nullable();
            $table->foreign('currency_id')->references('id')->on('currencies')->onUpdate('cascade')->onDelete('cascade');

            $table->double('total_amount');
            $table->double('commission_amount');
            $table->double('deposit_amount');
            $table->double('pending_amount');
            $table->string('gateway')->nullable();
            $table->enum('status', ['settled', 'pending'])->default('pending');
            $table->date('paid_on')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('commissions');
    }

}
