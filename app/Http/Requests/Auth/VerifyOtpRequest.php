<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
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
            'user_id'   => 'required|exists:users,id',
            'otp'       => 'required|string|min:6|max:6'            
        ];
    }
}
