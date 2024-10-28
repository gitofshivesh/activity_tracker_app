<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;


trait ApiResponseTrait {


    # ==========================================================================================
    # Response Messages
    protected static $messages = [
        'already_registered_mobile'         => 'Mobile number already registered',
        'otp_sent'                          => 'OTP sent successfully',
        'otp_failed_to_sent'                => 'Failed to send OTP',
        'invalid_expired_otp'               => 'Invalid or expired OTP',
        'logged_in_success'                 => 'Logged in successfully',
        'otp_verification_failed'           => 'Failed to verify OTP',

        'challenges_retrived'               => 'Challenges retrieved successfully',
        'challenges_create_success'         => 'Challenge created successfully',
        'challenges_create_error'           => 'Failed to create challenge',
        'challenges_not_found'              => 'Challenge not found',
        'challenges_list_error'             => 'Failed to fetch challenges',
        'challenges_deleted'                => 'Challenge deleted successfully',

        'activity_created'                  => 'Challenge activities created successfully',
        'activity_updated'                  => 'Challenge activity updated successfully',
        'activity_updated_failed'           => 'Failed to update activity',
        'activity_create_error'             => 'Failed to create challenge activities',
        'activity_not_found'                => 'Challenge activity not found',
        'activity_unauthorized'             => 'You are not authorized to access this activity',
        'activity_unprocessable'            => 'Cannot create activity for inactive challenge',
        'activity_retrieved'                => 'Activities retrieved successfully',
        'activity_progress_retrived'        => 'Progress retrieved successfully',
        'activity_pending_retrived'         => 'Pending activities retrieved successfully',
        'activity_statistics_retrieved'     =>  'Activity statistics retrieved successfully',

        'logout_success'                    =>  'Logged out successfully',

        'general_success'                   => 'Operation completed successfully',
        'general_error'                     => 'Something went wrong'
    ];




    # ==========================================================================================
    # Retrive messages from array
    protected function getMessage(string $key): string
    {
        return static::$messages[$key] ?? static::$messages['general_error'];
    } #./

    


    # ==========================================================================================
    # Response for Success
    protected function successResponse($data = [], string $message = '', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message ?: $this->getMessage('general_success'),
            'data' => $data
        ], $status);
    } #./

    


    # ==========================================================================================
    # Response for Error
    protected function errorResponse(string $message, int $status = 500, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    } #./



    # ==========================================================================================
    # Response for Validation Errors
    protected function validationErrorResponse(array $errors, string $message = ''): JsonResponse
    {
        return $this->errorResponse(
            $message ?: $this->getMessage('validation_error'),
            422,
            $errors
        );
    } #./

}   