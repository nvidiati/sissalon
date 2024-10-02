<?php

namespace App\Http\Requests\Employee;

use App\Http\Requests\CoreRequest;

class StoreRequest extends CoreRequest
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
            'name' => 'required',
            'email' => 'required|email:rfc,dns|unique:users,email',
            'password' => 'required|min:6',
            'mobile' => 'required|numeric',
            'calling_code' => 'required_with:mobile',
            'role_id' => 'required|exists:roles,id',
            'location' => 'required',
            'image' => 'mimes:jpeg,png,jpg',
        ];
    }

    public function messages()
    {
        return [
            'role_id.exists' => __('app.role').' '.__('errors.fieldRequired'),
        ];
    }

}
