<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentAttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class StudentAttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = StudentAttendanceRecord::with('student');

        // Filter by date
        if ($request->has('date')) {
            $query->byDate($request->date);
        }

        // Filter by level
        if ($request->has('level')) {
            $query->byLevel($request->level);
        }

        // Filter by stream
        if ($request->has('stream')) {
            $query->byStream($request->stream);
        }

        // Filter by class
        if ($request->has('class')) {
            $query->byClass($request->class);
        }

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'present') {
                $query->present();
            } elseif ($request->status === 'late') {
                $query->late();
            } elseif ($request->status === 'absent') {
                $query->absent();
            }
        }

        // Search by student name or ID
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('student_name', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('class', 'like', "%{$search}%");
            });
        }

        // Order by scan time descending
        $query->orderBy('scan_time', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 20);
        $attendance = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $attendance->items(),
            'pagination' => [
                'current_page' => $attendance->currentPage(),
                'per_page' => $attendance->perPage(),
                'total' => $attendance->total(),
                'last_page' => $attendance->lastPage()
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|string|max:50',
            'student_name' => 'required|string|max:255',
            'level' => 'required|string|max:50',
            'stream' => 'required|string|max:50',
            'class' => 'required|string|max:50',
            'scan_time' => 'required|date',
            'status' => 'required|in:present,late,absent,half_day,excused',
            'scan_method' => 'required|string|max:50',
            'device' => 'required|string|max:50',
            'attendance_type' => 'required|string|max:50'
        ]);

        $attendance = StudentAttendanceRecord::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Attendance record created successfully',
            'data' => $attendance
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $attendance = StudentAttendanceRecord::with('student')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $attendance
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $attendance = StudentAttendanceRecord::findOrFail($id);

        $validated = $request->validate([
            'student_id' => 'sometimes|string|max:50',
            'student_name' => 'sometimes|string|max:255',
            'level' => 'sometimes|string|max:50',
            'stream' => 'sometimes|string|max:50',
            'class' => 'sometimes|string|max:50',
            'scan_time' => 'sometimes|date',
            'status' => 'sometimes|in:present,late,absent,half_day,excused',
            'scan_method' => 'sometimes|string|max:50',
            'device' => 'sometimes|string|max:50',
            'attendance_type' => 'sometimes|string|max:50'
        ]);

        $attendance->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Attendance record updated successfully',
            'data' => $attendance
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $attendance = StudentAttendanceRecord::findOrFail($id);
        $attendance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attendance record deleted successfully'
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $query = StudentAttendanceRecord::query();

        // Filter by date if provided
        if ($request->has('date')) {
            $query->byDate($request->date);
        }

        $total = $query->count();
        $present = $query->clone()->present()->count();
        $late = $query->clone()->late()->count();
        $absent = $query->clone()->absent()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
                'late_rate' => $total > 0 ? round(($late / $total) * 100, 1) : 0
            ]
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $query = StudentAttendanceRecord::query();

        // Apply same filters as index method
        if ($request->has('date')) {
            $query->byDate($request->date);
        }
        if ($request->has('level')) {
            $query->byLevel($request->level);
        }
        if ($request->has('stream')) {
            $query->byStream($request->stream);
        }

        $records = $query->orderBy('scan_time', 'desc')->get();

        $csvData = [];
        $csvData[] = ['Student ID', 'Name', 'Level', 'Stream', 'Class', 'Scan Time', 'Status', 'Method', 'Device'];

        foreach ($records as $record) {
            $csvData[] = [
                $record->student_id,
                $record->student_name,
                $record->level,
                $record->stream,
                $record->class,
                $record->formatted_scan_time,
                $record->status,
                $record->scan_method,
                $record->device
            ];
        }

        $filename = 'attendance_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        return response()->json([
            'success' => true,
            'data' => $csvData,
            'filename' => $filename
        ]);
    }
}
