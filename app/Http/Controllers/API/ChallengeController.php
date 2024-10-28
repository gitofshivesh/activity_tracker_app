<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Challenge;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;

class ChallengeController extends Controller
{
    use ApiResponseTrait;
    
    # 
    # Frequency types for challenges    
    private const FREQUENCY_TYPES = ['daily', 'weekly', 'monthly'];
    
    

    #
    # Check Authentication in Constructor
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }



    #
    # FN 01: Get Latest Challenges List
    public function challengesList(): JsonResponse
    {        
        try {
            # Fetching Challenges
            $challenges = Challenge::where('user_id', Auth::id())
                ->with('user')
                ->with('activities')
                ->where('is_active', true)
                ->latest()
                ->get();

            # Return Response            
            $data = ['challenges' => $challenges];
            return $this->successResponse($data, $this->getMessage('challenges_retrived'));
        } catch (\Exception $e) {
            # Write Error Log
            Log::error('Failed to fetch challenges: ' . $e->getMessage(), [
                'user_id'   => Auth::id(),
                'trace'     => $e->getTraceAsString()
            ]);
            
            # Return Response            
            return $this->errorResponse($this->getMessage('challenges_list_error'), 500);
        }
    } # EOF



    #
    # FN 02: Addition of New Challenge
    public function challangeAdd(Request $request): JsonResponse
    {
        try {
            # Validation of Request Nodes
            $validator = validator($request->all(), [
                'title'         => 'required|string|max:255',
                'description'   => 'required|string',
                'start_date'    => 'required|date|after_or_equal:today',
                'end_date'      => 'required|date|after:start_date',
                'frequency'     => 'required|string|in:' . implode(',', self::FREQUENCY_TYPES),
            ]);

            # If Validation Fails, Return with Errors
            if ($validator->fails()) {                
                return $this->validationErrorResponse($validator->errors()->toArray());
            }            

            # Challenge Creation
            $challenge = Challenge::create([
                'challenge_title'       => $request->title,
                'user_id'               => Auth::id(),
                'challenge_description' => $request->description,
                'start_date'            => $request->start_date,
                'end_date'              => $request->end_date,
                'frequency'             => $request->frequency,
                'is_active'             => true
            ]);

            # Return Response           
            $data = ['challenge' => $challenge];
            return $this->successResponse($data, $this->getMessage('challenges_create_success'), 201);
        } catch (\Exception $e) {
            # Write Error Log
            Log::error('Failed to create challenge: ' . $e->getMessage(), [
                'user_id'       => Auth::id(),
                'request_data'  => $request->all(),
                'trace'         => $e->getTraceAsString()
            ]);

            # Return Response
            return $this->errorResponse(
                $this->getMessage('challenges_create_error'),
                500
            );
        }
    } # EOF



    #
    # FN 03: Challenge List with Filter using Frequency Type
    public function challengesListFilter($filter): JsonResponse
    {
        try {
            # Getting List on behalf of provided filters
            $challenge = Challenge::where('user_id', Auth::id())
                ->with('user')
                ->with('user')
                ->with('activities')
                ->where('frequency', $filter)
                ->where('is_active', true)              
                ->get();
            
            # Return Response            
            $data = ['challenge' => $challenge];
            return $this->successResponse(
                $data,
                $this->getMessage('challenges_retrived')
            );
        } catch (\Exception $e) {
            # Write Error Log
            Log::error('Failed to fetch challenge: ' . $e->getMessage(), [
                'user_id'       => Auth::id(),
                'challenge_id'  => $filter,
                'trace'         => $e->getTraceAsString()
            ]);

            # Return Response
            return $this->errorResponse(
                $this->getMessage('challenges_not_found'),
                404
            );
        }
    } # EOF



    #
    # FN 04: Challenge Delete
    public function challengeDelete($id): JsonResponse
    {
        try {
            # Get Challenge if Exist
            $challenge = Challenge::where('user_id', Auth::id())->findOrFail($id);

            # Update Challenge for Soft delete
            $challenge->update([
                'is_active' =>  false
            ]);

            # Return Response            
            return $this->successResponse(
                [],
                $this->getMessage('challenges_deleted')
            );

        } catch (\Exception $e) {
            # Write Error Log
            Log::error('Failed to delete challenge: ' . $e->getMessage(), [
                'user_id'       => Auth::id(),
                'challenge_id'  => $id,
                'trace'         => $e->getTraceAsString()
            ]);

            # Return Response
            return $this->errorResponse(
                $this->getMessage('challenges_not_found'),
                404
            );
        }
    } # EOF

    
}
