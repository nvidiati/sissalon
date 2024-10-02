<?php

namespace App\Http\Requests\Setting;

use App\Http\Requests\CoreRequest;
use Illuminate\Support\Arr;

class ProfileSetting extends CoreRequest
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
        $rules = [
            'name' => 'required',
            'email' => 'required|email:rfc,dns',
            'password'  => 'nullable|min:6',
        ];

        if ($this->has('mobile')) {
            $rules = Arr::add($rules, 'mobile', 'required|numeric');
            $rules = Arr::add($rules, 'calling_code', 'required');
        }

        return $rules;
    }

}
