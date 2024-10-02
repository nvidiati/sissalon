<?php

namespace App\Http\Controllers\Admin;

use App\Helper\Reply;
use App\Http\Controllers\AdminBaseController;
use App\Http\Requests\UpdateZoomSetting;
use App\Http\Controllers\SuperAdminBaseController;
use App\ZoomSetting;

class ZoomSettingController extends AdminBaseController
{
    /**
     * Update the specified resource in storage.
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function update (UpdateZoomSetting $request, $id)
    {
        $zoomSetting = ZoomSetting::findOrFail($id);
        $zoomSetting->company_id = company()->id;
        $zoomSetting->api_key = $request->zoom_api_key;
        $zoomSetting->secret_key = $request->zoom_secret_key;
        $zoomSetting->meeting_app = $request->meeting_app;

        if($request->enable_zoom ?? false)
        {
            $zoomSetting->enable_zoom = $request->enable_zoom;
        }
        else
        {
            $zoomSetting->enable_zoom = 'inactive';
        }

        $zoomSetting->update();

        return Reply::success(__('messages.updatedSuccessfully'));
    }

}
