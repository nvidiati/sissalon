<?php

use App\Company;
use App\ZoomSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZoomSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('zoom_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onUpdate('cascade')->onDelete('cascade');
            $table->string('api_key', 50)->nullable();
            $table->string('secret_key', 50)->nullable();
            $table->string('purchase_code')->nullable();
            $table->string('meeting_app');
            $table->timestamp('supported_until')->nullable();
            $table->enum('enable_zoom', ['active', 'inactive']);
            $table->timestamps();
        });

        $companies = Company::all();

        foreach($companies as $company)
        {
            $zoomSettingCount = ZoomSetting::where('company_id', $company->id)->count();

            if($zoomSettingCount === 0)
            {
                $zoomSetting = new ZoomSetting();
                $zoomSetting->api_key = null;
                $zoomSetting->company_id = $company->id;
                $zoomSetting->secret_key = null;
                $zoomSetting->purchase_code = null;
                $zoomSetting->supported_until = null;
                $zoomSetting->meeting_app = 'in_app';
                $zoomSetting->enable_zoom = 'inactive';
                $zoomSetting->save();
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
        Schema::dropIfExists('zoom_settings');
    }

}
