<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    #
    # Check user is Authorized
    public function authorize(): bool
    {
        return true;
    }

    #
    # Validation Rules
    public function rules(): array
    {
        return [
            'mobile'    => 'required|string|min:10|max:15',
            // 'name'      => 'required|string|max:255',
            // 'email'     => 'required|email|max:255'
        ];
    }
}
