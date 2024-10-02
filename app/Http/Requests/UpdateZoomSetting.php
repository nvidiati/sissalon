<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateZoomSetting extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */

    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */

    public function rules()
    {
        return [
            'enable_zoom' => 'required',
            'zoom_api_key' => 'required_if:enable_zoom,active',
            'zoom_secret_key' => 'required_if:enable_zoom,active',
        ];
    }

}
