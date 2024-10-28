<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Carbon\Carbon;
use App\Models\OtpTrail;
use App\Models\UserProfile;
use App\Traits\ApiResponse;
use App\Traits\ApiResponseTrait;

class RegisterController extends Controller
{
    use ApiResponseTrait;

    // public function send(Request $request)
    // {
    //     return response()->json([
    //         "hello"
    //     ]);
    // }

    #
    # FN 01: Send OTP
    public function sendOtp(SendOtpRequest $request)
    {
        try {
            DB::beginTransaction();

            # Check if mobile already registered and verified
            // $existingUser = User::where('mobile', $request->mobile)
            //                    ->whereNotNull('mobile_verified_at')
            //                    ->first();
                               

            // if ($existingUser) {                
            //     return $this->errorResponse($this->getMessage('already_registered_mobile'), 422);
            // }

            # Generate OTP
            // $otp        = mt_rand(100000, 999999);
            $otp        = '123456'; // Default OTP till SMS Gateway is not implemented.
            $expiryTime = Carbon::now()->addMinutes(10);

            # Create or update user
            $user = User::firstOrCreate(
                ['mobile'       => $request->mobile],
                [
                    'name'      => !empty($request->name) ? $request->name : $request->mobile,
                    'email'     => !empty($request->email) ? $request->email : '',
                    'is_active' => true
                ]
            );

            # Save OTP trail
            OtpTrail::create([
                'user_id'       => $user->id,
                'mobile'        => $request->mobile,
                'otp'           => $otp,
                'otp_expire_at' => $expiryTime,
                'is_verified'   => false
            ]);

            DB::commit();

            ## =====================================================================
            ## SEND OTP MESSAGE API CAN BE IMPLEMENTED HERE.
            ## =====================================================================

            
            # Return Response            
            $data = [
                'user_id'       => $user->id,
                'otp'           => $otp,
                'expires_at'    => $expiryTime
            ];
            return $this->successResponse($data, $this->getMessage('otp_sent'));
        } catch (\Exception $e) {
            # Rollback Transaction if Fail
            DB::rollBack();

            # Return Response                       
            return $this->errorResponse($this->getMessage('otp_failed_to_sent'), 500);
        }
    } # EOF




    #
    # FN 02: Verify OTP and Complete New Registration or Login Process
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {    
        try {
            DB::beginTransaction();

            # Setup for Default Profile Image of User
            $baseUrl                = url('/');
            $userProfileImage       = env('PUBLIC_USER_PROFILE_IMAGE_PATH');
            $userProfileImagePath   = $baseUrl.$userProfileImage;

            # Check User id Exist or Not
            $user = User::findOrFail($request->user_id);
            
            # Get Latest OTP from Trail
            $otpTrail = OtpTrail::where('user_id', $user->id)
                               ->where('mobile', $user->mobile)
                               ->where('otp', $request->otp)
                               ->where('is_verified', false)
                               ->where('otp_expire_at', '>', Carbon::now())
                               ->latest()
                               ->first();

            # Check if OTP is not Available
            if (!$otpTrail) {
                # Return Response                
                return $this->errorResponse($this->getMessage('invalid_expired_otp'), 422);
            }

            # Update OTP as verified
            $otpTrail->update(['is_verified' => true]);

            
            # Update user's mobile is verified & Last Login of User
            $user->update([
                'mobile_verified_at'    => Carbon::now(),              
                'last_login_at'         => Carbon::now()              
            ]);

            # Create or Update User's Profile
            $userProfile = UserProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'profile_picture'           => $userProfileImagePath,
                    'bio'                       => "Achive The Goal",
                    'last_profile_updated_at'   => Carbon::now()
                ]
            );

            # Create Auth Token
            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            # Return Response with User's Data            
            $data = [
                    'token' => $token,
                    'user'  => $user,
                    'profile' => [                        
                        'profile_picture'           => $userProfile->profile_picture,
                        'bio'                       => $userProfile->bio,
                        'last_profile_updated_at'   => $userProfile->last_profile_updated_at
                    ]                  
                ];
            return $this->successResponse($data, $this->getMessage('logged_in_success'));
        } catch (\Exception $e) {
            # Rollback Transaction if Fail
            DB::rollBack();

            # Return Response            
            return $this->errorResponse($this->getMessage('otp_verification_failed'), 500);            
        }
    } # EOF

}
