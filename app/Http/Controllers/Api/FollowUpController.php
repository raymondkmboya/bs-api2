<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegistrationFollowUp;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FollowUpController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $followUps = RegistrationFollowUp::with(['student', 'createdBy'])
                ->orderBy('follow_up_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $followUps,
                'message' => 'Follow-ups retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve follow-ups: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get follow-ups for a specific student
     */
    public function getStudentFollowUps($studentId): JsonResponse
    {
        try {
            $followUps = RegistrationFollowUp::with(['createdBy'])
                ->where('student_id', $studentId)
                ->orderBy('follow_up_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $followUps,
                'message' => 'Follow-ups retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve follow-ups: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new follow-up
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'student_id' => 'required|exists:students,id',
                'follow_up_date' => 'required|date',
                'medium_used' => 'required|in:phone,email,sms,whatsapp,in_person,social_media',
                'message_content' => 'nullable|string|max:1000',
                'next_follow_up_date' => 'nullable|date|after_or_equal:follow_up_date',
                'status' => 'required|in:pending,contacted,interested,not_interested,enrolled,stop_follow_up',
                'notes' => 'nullable|string|max:500'
            ]);

            $followUp = RegistrationFollowUp::create([
                'student_id' => $validated['student_id'],
                'follow_up_date' => $validated['follow_up_date'],
                'medium_used' => $validated['medium_used'],
                'message_content' => $validated['message_content'],
                'next_follow_up_date' => $validated['next_follow_up_date'],
                'status' => $validated['status'],
                'notes' => $validated['notes'],
                'created_by' => auth()->id()
            ]);

            // Update student status if enrolled
            if ($validated['status'] === 'enrolled') {
                Student::find($validated['student_id'])->update(['status' => 'enrolled']);
            }

            return response()->json([
                'success' => true,
                'data' => $followUp->load(['createdBy']),
                'message' => 'Follow-up created successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create follow-up: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get students who need follow-up
     */
    public function getStudentsNeedingFollowUp(): JsonResponse
    {
        try {
            // Get students who are registered but not enrolled and have no recent follow-ups
            $students = Student::with(['latestFollowUp'])
                ->where('status', 'registered')
                ->whereDoesntHave('followUps', function($query) {
                    $query->where('follow_up_date', '>=', now()->subDays(7))
                          ->where('status', '!=', 'stop_follow_up');
                })
                ->orWhereHas('followUps', function($query) {
                    $query->where('next_follow_up_date', '<=', now())
                          ->where('status', '!=', 'stop_follow_up');
                })
                ->get();

            return response()->json([
                'success' => true,
                'data' => $students,
                'message' => 'Students needing follow-up retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve students: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get follow-up statistics
     */
    public function getFollowUpStats(): JsonResponse
    {
        try {
            $stats = [
                'total_registered' => Student::where('status', 'registered')->count(),
                'pending_follow_ups' => RegistrationFollowUp::pending()->count(),
                'due_today' => RegistrationFollowUp::where('next_follow_up_date', today())->count(),
                'overdue' => RegistrationFollowUp::where('next_follow_up_date', '<', today())->count(),
                'enrolled_this_month' => Student::where('status', 'enrolled')
                    ->whereMonth('updated_at', now()->month)
                    ->count(),
                'conversion_rate' => $this->calculateConversionRate()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Follow-up statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    private function calculateConversionRate(): float
    {
        $totalRegistered = Student::where('status', 'registered')->count();
        $totalEnrolled = Student::where('status', 'enrolled')->count();

        if ($totalRegistered === 0) return 0;

        return round(($totalEnrolled / $totalRegistered) * 100, 2);
    }
}
