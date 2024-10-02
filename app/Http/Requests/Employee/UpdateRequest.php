<?php

namespace App\Http\Requests\Employee;

use App\Http\Requests\CoreRequest;

class UpdateRequest extends CoreRequest
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
            'email' => 'required|email:rfc,dns|unique:users,email,'.$this->route('employee'),
            'mobile' => 'required',
            'password'  => 'nullable|min:6',
            'calling_code' => 'required_with:mobile',
            'role_id' => 'required|exists:roles,id',
            'location' => 'required'
        ];
    }

}
