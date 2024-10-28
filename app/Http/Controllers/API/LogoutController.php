<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LogoutController extends Controller
{
    use ApiResponseTrait;

    #
    # FN 01 : Logout User
    public function logout(Request $request)
    {
        try {
            $user = Auth::user();

            # Revoke token
            if ($user) {
                $user->currentAccessToken()->delete();
            }

            # Return Response
            return $this->successResponse(
                [],
                $this->getMessage('logout_success')           
            );

        } catch (\Exception $e) {
            # Write Error Log
            Log::error('Failed to Logout: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'date' => Carbon::today()->toDateString(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        # Return Response
        return $this->errorResponse($this->getMessage('general_error'). $e->getMessage(), 500);
    } # EOF
}
