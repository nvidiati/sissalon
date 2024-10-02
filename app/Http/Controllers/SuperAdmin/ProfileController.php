<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helper\Files;
use App\Helper\Reply;
use App\User;
use App\Http\Controllers\SuperAdminBaseController;
use App\Http\Requests\Setting\ProfileSetting;

class ProfileController extends SuperAdminBaseController
{

    public function __construct()
    {
        parent::__construct();
        view()->share('pageTitle', __('menu.profile'));
    }

    public function store(ProfileSetting $request)
    {
        $user = User::find($this->user->id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->rtl = $request->rtl;

        if($request->password != ''){
            $user->password = $request->password;
        }

        if ($request->has('mobile')) {
            if ($user->mobile !== $request->mobile || $user->calling_code !== $request->calling_code) {
                $user->mobile_verified = 0;
            }

            $user->mobile = $request->mobile;
            $user->calling_code = $request->calling_code;
        }

        if ($request->hasFile('image')) {
            $user->image = Files::upload($request->image, 'avatar');
        }

        if ($request->image_delete == 'yes') {
            Files::deleteFile($user->image, 'avatar');
            $user->image = null;
        }

        $user->save();

        return Reply::redirect(route('superadmin.settings.index'), __('messages.updatedSuccessfully'));
    }

}
