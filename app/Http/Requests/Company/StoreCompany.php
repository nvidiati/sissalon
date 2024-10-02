<?php

namespace App\Http\Requests\Company;

use App\Http\Requests\CoreRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompany extends CoreRequest
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
            'company_name'  => 'required',
            'company_email' => 'required|email:rfc,dns|unique:companies,company_email,'.request('company_email'),
            'logo'          => 'mimes:jpeg,jpg,png|max:10000', // max 10000kb
            'company_phone' => 'required|numeric',
            'address'       => 'required',
            'website'       => 'nullable|url',
            'name'          => 'required',
            'email'         => 'required|email:rfc,dns|unique:users,email,'.request('email'),
            'password'      => 'required|min:6|confirmed',
        ];
    }

    public function attributes()
    {
        return [
           'company_name'  => 'Business Name',
           'company_email' => 'Business Email',
           'company_phone' => 'Business Phone',
           'address'       => 'Address',
           'website'       => 'Business Website',
           'name'          => 'Employee Name',
           'email'         => 'Employee Email',
           'password'      => 'Password'
        ];
    }

}
