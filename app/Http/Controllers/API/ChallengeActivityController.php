<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Challenge;
use App\Models\ChallengeActivity;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;


class ChallengeActivityController extends Controller
{
    use ApiResponseTrait;

    

    #
    # Check Authentication in Constructor
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    

    # FN 01: Generate Challenge Activities based on Challenge frequency
    public function generateActivities(Challenge $challenge): JsonResponse
    {
        try {
            # Verify logged in user and requested user for Challenge
            if ($challenge->user_id !== Auth::id()) {
                return $this->errorResponse($this->getMessage('activity_unauthorized'), 403);
            }

            # First check if the challenge is active or not
            if (!$challenge->is_active) {
                return $this->errorResponse($this->getMessage('activity_unprocessable'), 422);            
            }

            # Transaction Started if everything is OK
            DB::beginTransaction();

            # Delete existing activities if any, of future
            ChallengeActivity::where('challenge_id', $challenge->id)
                ->where('activity_date', '>=', now()->toDateString())
                ->delete();

            # Generate new activities as per frequency
            $activities = $this->createActivitiesBasedOnFrequency($challenge);

            # Commit
            DB::commit();

            # Return Response
            return $this->successResponse(
                ['activities' => $activities],
                $this->getMessage('activity_created')           
            );

        } catch (\Exception $e) {
            # Rollback if error occured
            DB::rollBack();

            # Write Error Log
            Log::error('Failed to generate challenge activities: ' . $e->getMessage(), [
                'user_id'       => Auth::id(),
                'challenge_id'  => $challenge->id,
                'trace'         => $e->getTraceAsString()
            ]);

            # Return Response
            return $this->errorResponse($this->getMessage('activity_create_error'). $e->getMessage(), 500);
        }
    } # EOF




    # FN 01.1: Create activities based on challenge frequency
    private function createActivitiesBasedOnFrequency(Challenge $challenge): array
    {
        # Parsing dates as per Carbon Lib
        $startDate = Carbon::parse($challenge->start_date);
        $endDate = Carbon::parse($challenge->end_date);
        $activities = [];

        # Activity Creation as per Frequency
        switch ($challenge->frequency) {
            case 'daily':
                $period = CarbonPeriod::create($startDate, $endDate);
                foreach ($period as $date) {
                    $activities[] = $this->createActivity($challenge, $date);
                }
                break;

            case 'weekly':
                $period = CarbonPeriod::create($startDate, '1 week', $endDate);
                foreach ($period as $date) {
                    $activities[] = $this->createActivity($challenge, $date);
                }
                break;

            case 'monthly':
                $period = CarbonPeriod::create(
                    $startDate->startOfMonth(),
                    '1 month',
                    $endDate->endOfMonth()
                );
                foreach ($period as $date) {
                    if ($date >= $startDate && $date <= $endDate) {
                        $activities[] = $this->createActivity($challenge, $date);
                    }
                }
                break;
        }

        # Return Activity Array
        return $activities;
    } # EOF

    

    # FN 01.2: Create single activity
    private function createActivity(Challenge $challenge, Carbon $date): ChallengeActivity
    {
        return ChallengeActivity::create([
            'user_id'           => $challenge->user_id,
            'challenge_id'      => $challenge->id,
            'activity_date'     => $date->toDateString(),
            'status'            => 'pending' // Default status for upcomming challenges
        ]);
    } # EOF


    

    #
    # FN 02: Get activities list by challenge id
    public function getActivities(Challenge $challenge): JsonResponse
    {
        try {
            # Verify logged in user and requested user for Get List
            if ($challenge->user_id !== Auth::id()) {
                return $this->errorResponse($this->getMessage('activity_unauthorized'), 403);
            }

            # Get Challenge Activiy by challenge id
            $activities = ChallengeActivity::where('challenge_id', $challenge->id)
                ->orderBy('activity_date')
                ->get();

            # Return
            return $this->successResponse(
                ['activities' => $activities],
                $this->getMessage('activity_retrieved')                
            );

        } catch (\Exception $e) {
            # Write Error Log
            Log::error('Failed to fetch challenge activities: ' . $e->getMessage(), [
                'user_id'       => Auth::id(),
                'challenge_id'  => $challenge->id,
                'trace'         => $e->getTraceAsString()
            ]);

            # Return
            return $this->errorResponse($this->getMessage('activity_not_found'), 500);
        }
    } # EOF




    #
    # FN 03: Update activity status
    public function updateStatus(Request $request, ChallengeActivity $activity): JsonResponse
    {
        try {
            # Check logged in user and requested user for Update
            if ($activity->user_id !== Auth::id()) {
                return $this->errorResponse($this->getMessage('activity_unauthorized'), 403);
            }

            # Validate Request
            $validator = validator($request->all(), [
                'status' => 'required|in:completed,missed'
            ]);

            # Validator Fail Check
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            # Update Status of Activity
            $activity->update(['status' => $request->status]);

            # Return
            return $this->successResponse(
                ['activity' => $activity],
                $this->getMessage('activity_updated')              
            );

        } catch (\Exception $e) {
            # Write Error Log
            Log::error('Failed to update activity status: ' . $e->getMessage(), [
                'user_id'       => Auth::id(),
                'activity_id'   => $activity->id,
                'trace'         => $e->getTraceAsString()
            ]);

            # Return
            return $this->errorResponse($this->getMessage('activity_updated_failed'), 500);
        }
    } # EOF

    

    # FN 04: Get User's progress
    public function getProgress(Challenge $challenge): JsonResponse
    {
        try {
            # Check logged in user and requested user for Update
            if ($challenge->user_id !== Auth::id()) {
                return $this->errorResponse($this->getMessage('activity_unauthorized'), 403);
            }

            # Get count status wise and for all status for challenge id
            $stats = ChallengeActivity::where('challenge_id', $challenge->id)
                        ->selectRaw('
                            COUNT(status) as total_activities,
                            COALESCE(SUM(CASE WHEN status = \'missed\' THEN 1 ELSE 0 END), 0) as missed_activities,
                            COALESCE(SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END), 0) as completed_activities,
                            COALESCE(SUM(CASE WHEN status = \'pending\' THEN 1 ELSE 0 END), 0) as pending_activities
                        ')
                        ->first();

            # Binding results in keys
            $progress = [
                'total_activities'          => $stats->total_activities,
                'completed_activities'      => $stats->completed_activities,
                'missed_activities'         => $stats->missed_activities,
                'completion_rate'           => $stats->total_activities > 0 
                    ? round(($stats->completed_activities / $stats->total_activities) * 100, 2)
                    : 0
            ];

            # Return
            return $this->successResponse(
                ['progress' => $progress],
                $this->getMessage('activity_progress_retrived')                
            );

        } catch (\Exception $e) {
            # Write Error Log
            Log::error('Failed to fetch challenge progress: ' . $e->getMessage(), [
                'user_id'       => Auth::id(),
                'challenge_id'  => $challenge->id,
                'trace'         => $e->getTraceAsString()
            ]);

            # Return
            return $this->errorResponse($this->getMessage('activity_not_found'), 500);
        }
    } # EOF

    


    # FN 05: Get List of pending activities for current day
    public function getListPendingActivities(): JsonResponse
    {
        try {
            # Getting current date
            $today = Carbon::today()->toDateString();
            
            # Getting pending activities for current user
            $pendingActivities = ChallengeActivity::with(['challenge' => function($query) {
                $query->select('id', 'challenge_title as title', 'challenge_description as description');
            }])
            ->whereHas('challenge', function($query) {
                $query->where('is_active', true);
            })
            ->where('user_id', Auth::id())
            ->where('activity_date', $today)
            ->where('status', 'pending')
            ->get();
            
            # Binding Summary
            $summary = [
                'total_pending'     => $pendingActivities->count(),
                'date'              => $today,
                'activities'        => $pendingActivities->map(function ($activity) {
                    return [
                        'id'                    => $activity->id,
                        'challenge_title'       => $activity->challenge->title,
                        'challenge_description' => $activity->challenge->description,
                        'activity_date'         => $activity->activity_date,
                        'status'                => $activity->status
                    ];
                })
            ];

            # Return
            return $this->successResponse(
                ['pending_summary' => $summary],
                $this->getMessage('activity_pending_retrived')                
            );

        } catch (\Exception $e) {
            # Write Error Log
            Log::error('Failed to fetch pending activities: ' . $e->getMessage(), [
                'user_id'   => Auth::id(),
                'date'      => Carbon::today()->toDateString(),
                'trace'     => $e->getTraceAsString()
            ]);

            # Return
            return $this->errorResponse($this->getMessage('activity_not_found'). $e->getMessage(), 500);
        }
    } # EOF

    # FN 06: Get activities count by status
    public function getActivityStats(): JsonResponse
    {
        try {
            # Getting current date
            $today = Carbon::today()->toDateString();

            # Getting Total Challenges
            $totalChallenges = Challenge::where('user_id', Auth::id())
                                ->where('is_active', true)
                                ->count();
            
            # Getting Stats
            $stats = ChallengeActivity::whereHas('challenge', function($query) {
                $query->where('is_active', true);
            })
                ->where('user_id', Auth::id())
                ->selectRaw('
                    COUNT(status) as total_activities,
                    COALESCE(SUM(CASE WHEN status = \'missed\' THEN 1 ELSE 0 END), 0) as missed_activities,
                    COALESCE(SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END), 0) as completed_activities,
                    COALESCE(SUM(CASE WHEN status = \'pending\' THEN 1 ELSE 0 END), 0) as pending_activities
                ')
                ->first();

            # Binding Summary
            $summary = [
                'date'                      => $today,
                'total_active_challenges'   => $totalChallenges,                
                'activities_summary'        => [
                        'total_activities'      => $stats->total_activities,
                        'pending_activities'    => $stats->pending_activities,
                        'completed_activities'  => $stats->completed_activities,
                        'completion_rate'       => $stats->total_activities > 0 
                            ? round(($stats->completed_activities / $stats->total_activities) * 100, 2)
                            : 0
                ]
                
            ];

            # Return
            return $this->successResponse(                
                ['activity_stats' => $summary],
                $this->getMessage('activity_statistics_retrieved')
            );

        } catch (\Exception $e) {
            # Write Error Log
            Log::error('Failed to fetch activity statistics: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'date' => Carbon::today()->toDateString(),
                'trace' => $e->getTraceAsString()
            ]);            

            # Return
            return $this->errorResponse($this->getMessage('activity_not_found'), 500);
        }
    } # EOF



    # FN 07: Get pending activities count of current date
    public function getActivitiesPendingCount(): JsonResponse
    {
        try {
            # Getting current date
            $today = Carbon::today()->toDateString();
            
            # Getting Stats
            $stats = ChallengeActivity::where('user_id', Auth::id())
                ->where('activity_date', $today)
                ->selectRaw('                    
                    COALESCE(SUM(CASE WHEN status = \'pending\' THEN 1 ELSE 0 END), 0) as pending_activities
                ')
                ->first();

            # Binding Summary
            $summary = [
                'date'                  => $today,                
                'pending_activities'    => $stats->pending_activities
            ];

            # Return
            return $this->successResponse(                
                ['activity_stats' => $summary],
                $this->getMessage('activity_statistics_retrieved')
            );

        } catch (\Exception $e) {
            # Write Error Log
            Log::error('Failed to fetch activity statistics: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'date' => Carbon::today()->toDateString(),
                'trace' => $e->getTraceAsString()
            ]);
            // dd($e->getMessage());

            # Return
            return $this->errorResponse($this->getMessage('activity_not_found'), 500);
        }
    } # EOF
    

    
}
