<?php

use App\Deal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDealServiceTypeToDealsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->string('deal_service_type')->after('deal_type');
        });

        $deal_service_type = Deal::all();

        foreach( $deal_service_type as $data){
            $data->deal_service_type = 'offline';
            $data->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn('deal_service_type');
        });
    }

}
