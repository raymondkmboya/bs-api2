<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\ClassLevel;
use App\Models\ClassLevelStream;
use App\Models\Compass;
use App\Models\Subject;
use App\Models\Staff;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicController extends Controller
{
    /**
     * Display a listing of classrooms.
     */
    public function getClassRooms(): JsonResponse
    {
        $classrooms = Classroom::all();
        return response()->json([
            'success' => true,
            'data' => $classrooms,
            'message' => 'Classrooms retrieved successfully'
        ]);
    }

    /**
     * Store a newly created classroom in storage.
     */
    public function storeClassRoom(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'room_number' => 'required|string|unique:classrooms,room_number',
                'capacity' => 'required|integer|min:1|max:100',
                'building' => 'nullable|string|max:255',
                'floor' => 'nullable|string|max:100',
                'classroom_type' => 'required|in:classroom,lecture,lab,computer,art,music,general',
                'status' => 'required|in:available,occupied,maintenance,under_construction',
                'description' => 'nullable|string',
                'facilities' => 'nullable|array',
                'facilities.*' => 'string|max:100'
            ]);

            $classroom = Classroom::create($validated);

            return response()->json([
                'success' => true,
                'data' => $classroom,
                'message' => 'Classroom created successfully'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create classroom',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified classroom.
     */
    public function getClassRoom(string $id): JsonResponse
    {
        try {
            $classroom = Classroom::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $classroom,
                'message' => 'Classroom retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Classroom not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified classroom in storage.
     */
    public function updateClassRoom(Request $request, string $id): JsonResponse
    {
        try {
            $classroom = Classroom::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'room_number' => 'sometimes|required|string|unique:classrooms,room_number,' . $id,
                'capacity' => 'sometimes|required|integer|min:1|max:100',
                'building' => 'nullable|string|max:255',
                'floor' => 'nullable|string|max:100',
                'classroom_type' => 'sometimes|required|in:lecture,lab,computer,art,music,general',
                'status' => 'sometimes|required|in:active,inactive,maintenance',
                'description' => 'nullable|string',
                'facilities' => 'nullable|array',
                'facilities.*' => 'string|max:100'
            ]);

            $classroom->update($validated);

            return response()->json([
                'success' => true,
                'data' => $classroom,
                'message' => 'Classroom updated successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update classroom',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified classroom from storage.
     */
    public function destroyClassRoom(string $id): JsonResponse
    {
        try {
            $classroom = Classroom::findOrFail($id);
            $classroom->delete();

            return response()->json([
                'success' => true,
                'message' => 'Classroom deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete classroom',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get classrooms by type.
     */
    public function getByType(string $type): JsonResponse
    {
        try {
            if (!in_array($type, ['lecture', 'lab', 'computer', 'art', 'music', 'general'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid classroom type'
                ], 400);
            }

            $classrooms = Classroom::where('classroom_type', $type)->get();

            return response()->json([
                'success' => true,
                'data' => $classrooms,
                'message' => "Classrooms of type '{$type}' retrieved successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve classrooms',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available classrooms (active status).
     */
    public function getAvailable(): JsonResponse
    {
        try {
            $classrooms = Classroom::where('status', 'active')->get();

            return response()->json([
                'success' => true,
                'data' => $classrooms,
                'message' => 'Available classrooms retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve available classrooms',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ==================== CLASS LEVELS ====================

    /**
     * Display a listing of class levels.
     */
    public function getClassLevels(): JsonResponse
    {
        $classLevels = ClassLevel::all();
        return response()->json([
            'success' => true,
            'data' => $classLevels,
            'message' => 'Class levels retrieved successfully'
        ]);
    }

    /**
     * Store a newly created class level in storage.
     */
    public function storeClassLevel(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:class_levels,name',
                'description' => 'nullable|string',
                'level_number' => 'required|integer|min:1|max:12',
                'status' => 'required|in:active,inactive'
            ]);

            $classLevel = ClassLevel::create($validated);

            return response()->json([
                'success' => true,
                'data' => $classLevel,
                'message' => 'Class level created successfully'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Display the specified class level.
     */
    public function getClassLevel(string $id): JsonResponse
    {
        try {
            $classLevel = ClassLevel::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $classLevel,
                'message' => 'Class level retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Class level not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified class level in storage.
     */
    public function updateClassLevel(Request $request, string $id): JsonResponse
    {
        try {
            $classLevel = ClassLevel::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:class_levels,name,' . $id,
                'description' => 'nullable|string',
                'level_number' => 'sometimes|required|integer|min:1|max:12',
                'status' => 'sometimes|required|in:active,inactive'
            ]);

            $classLevel->update($validated);

            return response()->json([
                'success' => true,
                'data' => $classLevel,
                'message' => 'Class level updated successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Remove the specified class level from storage.
     */
    public function destroyClassLevel(string $id): JsonResponse
    {
        try {
            $classLevel = ClassLevel::findOrFail($id);
            $classLevel->delete();

            return response()->json([
                'success' => true,
                'message' => 'Class level deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete class level'
            ], 500);
        }
    }

    // ==================== CLASS LEVEL STREAMS ====================

    /**
     * Get streams for a specific class level.
     */
    public function getStreamsByClassLevel(string $classLevelId): JsonResponse
    {
        try {
            $classLevelStreams = ClassLevelStream::where('class_level_id', $classLevelId)->get();

            return response()->json([
                'success' => true,
                'data' => $classLevelStreams,
                'message' => 'Streams retrieved successfully for class level'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve streams',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of class level streams.
     */
    public function getClassLevelStreams(): JsonResponse
    {
        $classLevelStreams = ClassLevelStream::all();
        return response()->json([
            'success' => true,
            'data' => $classLevelStreams,
            'message' => 'Class level streams retrieved successfully'
        ]);
    }

    /**
     * Store a newly created class level stream in storage.
     */
    public function storeClassLevelStream(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:class_level_streams,name',
                'class_level_id' => 'required|exists:class_levels,id',
                'description' => 'nullable|string',
                'capacity' => 'required|integer|min:1|max:100',
                'status' => 'required|in:active,inactive'
            ]);

            $classLevelStream = ClassLevelStream::create($validated);

            return response()->json([
                'success' => true,
                'data' => $classLevelStream,
                'message' => 'Class level stream created successfully'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Display the specified class level stream.
     */
    public function getClassLevelStream(string $id): JsonResponse
    {
        try {
            $classLevelStream = ClassLevelStream::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $classLevelStream,
                'message' => 'Class level stream retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Class level stream not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified class level stream in storage.
     */
    public function updateClassLevelStream(Request $request, string $id): JsonResponse
    {
        try {
            $classLevelStream = ClassLevelStream::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:class_level_streams,name,' . $id,
                'class_level_id' => 'sometimes|required|exists:class_levels,id',
                'description' => 'nullable|string',
                'capacity' => 'sometimes|required|integer|min:1|max:100',
                'status' => 'sometimes|required|in:active,inactive'
            ]);

            $classLevelStream->update($validated);

            return response()->json([
                'success' => true,
                'data' => $classLevelStream,
                'message' => 'Class level stream updated successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Remove the specified class level stream from storage.
     */
    public function destroyClassLevelStream(string $id): JsonResponse
    {
        try {
            $classLevelStream = ClassLevelStream::findOrFail($id);
            $classLevelStream->delete();

            return response()->json([
                'success' => true,
                'message' => 'Class level stream deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete class level stream'
            ], 500);
        }
    }

    // ==================== COMPASS ====================

    /**
     * Display a listing of compass entries.
     */
    public function getCompass(): JsonResponse
    {
        $compass = Compass::all();
        return response()->json([
            'success' => true,
            'data' => $compass,
            'message' => 'Compass entries retrieved successfully'
        ]);
    }

    /**
     * Store a newly created compass entry in storage.
     */
    public function storeCompass(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:compass,name',
                'description' => 'nullable|string',
                'building' => 'required|string|max:255',
                'floor' => 'nullable|string|max:100',
                'supervisor' => 'nullable|string|max:255',
                'capacity' => 'required|integer|min:1|max:500',
                'status' => 'required|in:active,inactive,maintenance'
            ]);

            $compass = Compass::create($validated);

            return response()->json([
                'success' => true,
                'data' => $compass,
                'message' => 'Compass entry created successfully'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Display the specified compass entry.
     */
    public function getCompassEntry(string $id): JsonResponse
    {
        try {
            $compass = Compass::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $compass,
                'message' => 'Compass entry retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Compass entry not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified compass entry in storage.
     */
    public function updateCompass(Request $request, string $id): JsonResponse
    {
        try {
            $compass = Compass::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:compass,name,' . $id,
                'description' => 'nullable|string',
                'building' => 'sometimes|required|string|max:255',
                'floor' => 'nullable|string|max:100',
                'supervisor' => 'nullable|string|max:255',
                'capacity' => 'sometimes|required|integer|min:1|max:500',
                'status' => 'sometimes|required|in:active,inactive,maintenance'
            ]);

            $compass->update($validated);

            return response()->json([
                'success' => true,
                'data' => $compass,
                'message' => 'Compass entry updated successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Remove the specified compass entry from storage.
     */
    public function destroyCompass(string $id): JsonResponse
    {
        try {
            $compass = Compass::findOrFail($id);
            $compass->delete();

            return response()->json([
                'success' => true,
                'message' => 'Compass entry deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete compass entry'
            ], 500);
        }
    }

    // ==================== SUBJECT ====================

    /**
     * Get all subjects with optional filtering.
     */
    public function getAllSubjects(Request $request): JsonResponse
    {
        try {
            $query = Subject::with(['classLevel', 'subjectTeacher'])
                ->withCount('students');

            $subjects = $query->orderBy('subject_name')->get();

            return response()->json([
                'success' => true,
                'message' => 'Subjects retrieved successfully',
                'data' => $subjects
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve subjects: ' . $e->getMessage()
            ], 500);
        }
    }

    // In AcademicController
    public function getTeachersWithSubjects(): JsonResponse
    {
        try {
            $teachers = Staff::with('subjects')
                ->where('role', 'teacher')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $teachers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve teachers: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSubjectsByClassLevel(string $classLevelId): JsonResponse
    {
        $classLevelSubjects = Subject::where('class_level_id', $classLevelId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $classLevelSubjects
        ]);
    }

    /**
     * Get all exams
     */
    public function getExams(Request $request): JsonResponse
    {
        try {
            $query = Exam::with(['subject', 'classLevel', 'classStream', 'creator']);

            // Filter by class level if provided
            if ($request->has('class_level_id')) {
                $query->where('class_level_id', $request->class_level_id);
            }

            // Filter by stream if provided
            if ($request->has('stream_id')) {
                $query->where('class_level_stream_id', $request->stream_id);
            }

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by date range if provided
            if ($request->has('date_from')) {
                $query->whereDate('exam_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('exam_date', '<=', $request->date_to);
            }

            $exams = $query->orderBy('exam_date', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Exams retrieved successfully',
                'data' => $exams
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve exams: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new exam
     */
    public function createExam(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'exam_name' => 'required|string|max:255',
                'exam_date' => 'required|date|after:today',
                'exam_type' => 'required|in:midterm,final,quiz,assignment,practical',
                'subject_id' => 'required|exists:subjects,id',
                'duration' => 'required|integer|min:15|max:480', // 15 mins to 8 hours
                'status' => 'required|in:scheduled,ongoing,completed,cancelled',
                'class_level_id' => 'required|exists:class_levels,id',
                'class_level_stream_id' => 'nullable|exists:class_level_streams,id',
                'total_marks' => 'required|integer|min:1',
                'passing_marks' => 'required|integer|min:1|lte:total_marks',
                'instructions' => 'nullable|string|max:1000',
                'academic_year' => 'nullable|string|max:20',
                'semester' => 'nullable|string|max:20'
            ]);

            $exam = Exam::create([
                'exam_name' => $validated['exam_name'],
                'exam_date' => $validated['exam_date'],
                'exam_type' => $validated['exam_type'],
                'subject_id' => $validated['subject_id'],
                'duration' => $validated['duration'],
                'status' => $validated['status'],
                'class_level_id' => $validated['class_level_id'],
                'class_level_stream_id' => $validated['class_level_stream_id'] ?? null,
                'total_marks' => $validated['total_marks'],
                'passing_marks' => $validated['passing_marks'],
                'instructions' => $validated['instructions'] ?? null,
                'academic_year' => $validated['academic_year'] ?? date('Y'),
                'semester' => $validated['semester'] ?? '1',
                'created_by' => auth()->id()
            ]);

            // Load relationships for response
            $exam->load(['subject', 'classLevel', 'classStream', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Exam created successfully',
                'data' => $exam
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create exam: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific exam
     */
    public function getExam(string $id): JsonResponse
    {
        try {
            $exam = Exam::with(['subject', 'classLevel', 'classStream', 'creator', 'results'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Exam retrieved successfully',
                'data' => $exam
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve exam: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update an exam
     */
    public function updateExam(Request $request, string $id): JsonResponse
    {
        try {
            $exam = Exam::findOrFail($id);

            $validated = $request->validate([
                'exam_name' => 'sometimes|string|max:255',
                'exam_date' => 'sometimes|date',
                'exam_type' => 'sometimes|in:midterm,final,quiz,assignment,practical',
                'subject_id' => 'sometimes|exists:subjects,id',
                'duration' => 'sometimes|integer|min:15|max:480',
                'status' => 'sometimes|in:scheduled,ongoing,completed,cancelled',
                'class_level_id' => 'sometimes|exists:class_levels,id',
                'class_level_stream_id' => 'sometimes|exists:class_level_streams,id',
                'total_marks' => 'sometimes|integer|min:1',
                'passing_marks' => 'sometimes|integer|min:1|lte:total_marks',
                'instructions' => 'nullable|string|max:1000',
                'academic_year' => 'nullable|string|max:20',
                'semester' => 'nullable|string|max:20'
            ]);

            $exam->update($validated);

            // Load relationships for response
            $exam->load(['subject', 'classLevel', 'classStream', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Exam updated successfully',
                'data' => $exam
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update exam: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an exam
     */
    public function deleteExam(string $id): JsonResponse
    {
        try {
            $exam = Exam::findOrFail($id);

            // Check if exam has results before deleting
            if ($exam->results()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete exam with existing results'
                ], 422);
            }

            $exam->delete();

            return response()->json([
                'success' => true,
                'message' => 'Exam deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete exam: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get exam results
     */
    public function getExamResults(Request $request): JsonResponse
    {
        try {
            $query = ExamResult::with(['exam.subject', 'exam.classLevel', 'student']);

            // Filter by exam if provided
            if ($request->has('exam_id')) {
                $query->where('exam_id', $request->exam_id);
            }

            // Filter by student if provided
            if ($request->has('student_id')) {
                $query->where('student_id', $request->student_id);
            }

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by class level if provided
            if ($request->has('class_level_id')) {
                $query->whereHas('exam', function($q) use ($request) {
                    $q->where('class_level_id', $request->class_level_id);
                });
            }

            $results = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Exam results retrieved successfully',
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve exam results: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get results for a specific exam
     */
    public function getExamResultsByExam(string $examId): JsonResponse
    {
        try {
            $exam = Exam::with(['subject', 'classLevel', 'classStream'])->findOrFail($examId);

            $results = ExamResult::with(['student'])
                ->where('exam_id', $examId)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Exam results retrieved successfully',
                'data' => [
                    'exam' => $exam,
                    'results' => $results
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve exam results: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create exam results for an exam
     */
    public function createExamResults(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'exam_id' => 'required|exists:exams,id',
                'results' => 'required|array|min:1',
                'results.*.student_id' => 'required|exists:students,id',
                'results.*.marks_obtained' => 'required|numeric|min:0',
                'results.*.grade' => 'nullable|string|max:5',
                'results.*.remarks' => 'nullable|string|max:255',
                'results.*.status' => 'required|in:pass,fail,pending,absent'
            ]);

            $exam = Exam::findOrFail($validated['exam_id']);
            $createdResults = [];

            DB::transaction(function () use ($validated, &$createdResults) {
                foreach ($validated['results'] as $resultData) {
                    // Auto-calculate grade if not provided
                    if (!isset($resultData['grade'])) {
                        $exam = Exam::find($validated['exam_id']);
                        $percentage = ($resultData['marks_obtained'] / $exam->total_marks) * 100;

                        if ($percentage >= 80) $resultData['grade'] = 'A';
                        elseif ($percentage >= 70) $resultData['grade'] = 'B';
                        elseif ($percentage >= 60) $resultData['grade'] = 'C';
                        elseif ($percentage >= 50) $resultData['grade'] = 'D';
                        elseif ($percentage >= 40) $resultData['grade'] = 'E';
                        else $resultData['grade'] = 'F';
                    }

                    // Auto-generate remarks if not provided
                    if (!isset($resultData['remarks'])) {
                        $percentage = ($resultData['marks_obtained'] / $exam->total_marks) * 100;

                        if ($percentage >= 80) $resultData['remarks'] = 'Excellent Performance';
                        elseif ($percentage >= 70) $resultData['remarks'] = 'Very Good';
                        elseif ($percentage >= 60) $resultData['remarks'] = 'Good';
                        elseif ($percentage >= 50) $resultData['remarks'] = 'Satisfactory';
                        elseif ($percentage >= 40) $resultData['remarks'] = 'Needs Improvement';
                        else $resultData['remarks'] = 'Poor Performance';
                    }

                    $result = ExamResult::create([
                        'exam_id' => $validated['exam_id'],
                        'student_id' => $resultData['student_id'],
                        'marks_obtained' => $resultData['marks_obtained'],
                        'grade' => $resultData['grade'],
                        'remarks' => $resultData['remarks'],
                        'status' => $resultData['status'],
                        'submitted_by' => auth()->id(),
                        'submission_date' => now()
                    ]);

                    $createdResults[] = $result;
                }
            });

            // Load relationships for response
            $resultsWithRelations = ExamResult::with(['exam.subject', 'student'])
                ->whereIn('id', array_column($createdResults, 'id'))
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Exam results created successfully',
                'data' => $resultsWithRelations
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create exam results: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an exam result
     */
    public function updateExamResult(Request $request, string $id): JsonResponse
    {
        try {
            $result = ExamResult::findOrFail($id);

            $validated = $request->validate([
                'marks_obtained' => 'sometimes|required|numeric|min:0',
                'grade' => 'nullable|string|max:5',
                'remarks' => 'nullable|string|max:255',
                'status' => 'sometimes|required|in:pass,fail,pending,absent'
            ]);

            // Auto-calculate grade if marks changed but grade not provided
            if (isset($validated['marks_obtained']) && !isset($validated['grade'])) {
                $exam = $result->exam;
                $percentage = ($validated['marks_obtained'] / $exam->total_marks) * 100;

                if ($percentage >= 80) $validated['grade'] = 'A';
                elseif ($percentage >= 70) $validated['grade'] = 'B';
                elseif ($percentage >= 60) $validated['grade'] = 'C';
                elseif ($percentage >= 50) $validated['grade'] = 'D';
                elseif ($percentage >= 40) $validated['grade'] = 'E';
                else $validated['grade'] = 'F';
            }

            $result->update($validated);

            // Load relationships for response
            $result->load(['exam.subject', 'student']);

            return response()->json([
                'success' => true,
                'message' => 'Exam result updated successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update exam result: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an exam result
     */
    public function deleteExamResult(string $id): JsonResponse
    {
        try {
            $result = ExamResult::findOrFail($id);
            $result->delete();

            return response()->json([
                'success' => true,
                'message' => 'Exam result deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete exam result: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get exam statistics
     */
    public function getExamStatistics(string $examId): JsonResponse
    {
        try {
            $exam = Exam::findOrFail($examId);
            $results = ExamResult::where('exam_id', $examId)->get();

            $totalStudents = $results->count();
            $passedStudents = $results->where('status', 'pass')->count();
            $failedStudents = $results->where('status', 'fail')->count();
            $absentStudents = $results->where('status', 'absent')->count();
            $pendingStudents = $results->where('status', 'pending')->count();

            $averageMarks = $totalStudents > 0 ? $results->avg('marks_obtained') : 0;
            $highestMarks = $results->max('marks_obtained');
            $lowestMarks = $results->min('marks_obtained');

            $gradeDistribution = $results->groupBy('grade')->map(function($grade) {
                return $grade->count();
            });

            return response()->json([
                'success' => true,
                'message' => 'Exam statistics retrieved successfully',
                'data' => [
                    'exam' => $exam,
                    'statistics' => [
                        'total_students' => $totalStudents,
                        'passed_students' => $passedStudents,
                        'failed_students' => $failedStudents,
                        'absent_students' => $absentStudents,
                        'pending_students' => $pendingStudents,
                        'pass_rate' => $totalStudents > 0 ? ($passedStudents / $totalStudents) * 100 : 0,
                        'average_marks' => $averageMarks,
                        'highest_marks' => $highestMarks,
                        'lowest_marks' => $lowestMarks,
                        'grade_distribution' => $gradeDistribution
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve exam statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get timetable entries
     */
    public function getTimetable(Request $request): JsonResponse
    {
        try {
            $query = Timetable::with([
                'classStream.classLevel',
                'classStream',
                'subject',
                'teacher',
                'room'
            ]);

            // Filter by class level if provided
            if ($request->has('class_level_id')) {
                $query->where('class_level_id', $request->class_level_id);
            }

            // Filter by class stream if provided
            if ($request->has('class_level_stream_id')) {
                $query->where('class_level_stream_id', $request->class_level_stream_id);
            }

            // Filter by subject if provided
            if ($request->has('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }

            // Filter by teacher if provided
            if ($request->has('teacher_id')) {
                $query->where('teacher_id', $request->teacher_id);
            }

            // Filter by academic year if provided
            if ($request->has('academic_year')) {
                $query->where('academic_year', $request->academic_year);
            }

            // Filter by semester if provided
            if ($request->has('semester')) {
                $query->where('semester', $request->semester);
            }

            // Filter by status if provided
            // if ($request->has('status')) {
            //     $query->where('status', $request->status);
            // } else {
            //     // Default to active entries
            //     $query->where('status', 'active');
            // }

            $timetableEntries = $query->orderBy('day_of_week')
                                   ->orderBy('time_slot')
                                   ->get();

            return response()->json([
                'success' => true,
                'message' => 'Timetable entries retrieved successfully',
                'data' => $timetableEntries
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve timetable entries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get timetable for a specific class/stream
     */
    public function getClassTimetable(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'class_level_id' => 'required|exists:class_levels,id',
                'class_level_stream_id' => 'nullable|exists:class_level_streams,id',
                'academic_year' => 'nullable|string',
                'semester' => 'nullable|string|in:1,2,3'
            ]);

            $query = Timetable::with([
                'subject',
                'teacher',
                'room'
            ])
            ->where('class_level_id', $validated['class_level_id'])
            ->where('status', 'active');

            if (isset($validated['class_level_stream_id'])) {
                $query->where('class_level_stream_id', $validated['class_level_stream_id']);
            }

            if (isset($validated['academic_year'])) {
                $query->where('academic_year', $validated['academic_year']);
            } else {
                $query->where('academic_year', date('Y'));
            }

            if (isset($validated['semester'])) {
                $query->where('semester', $validated['semester']);
            }

            $timetableEntries = $query->orderBy('day_of_week')
                                   ->orderBy('time_slot')
                                   ->get();

            // Group by day and time slot for easier frontend consumption
            $groupedTimetable = [];
            foreach ($timetableEntries as $entry) {
                $groupedTimetable[] = [
                    'id' => $entry->id,
                    'day' => $entry->day_of_week,
                    'day_name' => $entry->day_name,
                    'time_slot' => $entry->time_slot,
                    'formatted_time' => $entry->formatted_time,
                    'subject' => $entry->subject,
                    'teacher' => $entry->teacher,
                    'room' => $entry->room,
                    'class_level_id' => $entry->class_level_id,
                    'class_level_stream_id' => $entry->class_level_stream_id
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Class timetable retrieved successfully',
                'data' => $groupedTimetable
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve class timetable: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new timetable entry
     */
    public function createTimetableEntry(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'class_level_id' => 'required|exists:class_levels,id',
                'class_level_stream_id' => 'nullable|exists:class_level_streams,id',
                'subject_id' => 'required|exists:subjects,id',
                'teacher_id' => 'required|exists:staff,id',
                'room_id' => 'required|exists:classrooms,id',
                'day_of_week' => 'required|integer|min:1|max:7',
                'time_slot' => 'required|string|format:H:i',
                'academic_year' => 'nullable|string',
                'semester' => 'nullable|string|in:1,2,3',
                'status' => 'sometimes|required|in:active,inactive,cancelled'
            ]);

            // Set defaults
            $validated['academic_year'] = $validated['academic_year'] ?? date('Y');
            $validated['semester'] = $validated['semester'] ?? '1';
            $validated['status'] = $validated['status'] ?? 'active';
            $validated['created_by'] = auth()->id();

            // Check for scheduling conflicts
            $timetableEntry = new Timetable();
            if ($timetableEntry->hasConflict(
                $validated['teacher_id'],
                $validated['room_id'],
                $validated['day_of_week'],
                $validated['time_slot']
            )) {
                return response()->json([
                    'success' => false,
                    'message' => 'Scheduling conflict detected. The teacher or room is already assigned at this time.'
                ], 422);
            }

            $timetableEntry = Timetable::create($validated);

            // Load relationships for response
            $timetableEntry->load(['classLevel', 'classStream', 'subject', 'teacher', 'room']);

            return response()->json([
                'success' => true,
                'message' => 'Timetable entry created successfully',
                'data' => $timetableEntry
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create timetable entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a timetable entry
     */
    public function updateTimetableEntry(Request $request, string $id): JsonResponse
    {
        try {
            $timetableEntry = Timetable::findOrFail($id);

            $validated = $request->validate([
                'class_level_id' => 'sometimes|required|exists:class_levels,id',
                'class_level_stream_id' => 'sometimes|nullable|exists:class_level_streams,id',
                'subject_id' => 'sometimes|required|exists:subjects,id',
                'teacher_id' => 'sometimes|required|exists:staff,id',
                'room_id' => 'sometimes|required|exists:classrooms,id',
                'day_of_week' => 'sometimes|required|integer|min:1|max:7',
                'time_slot' => 'sometimes|required|string|format:H:i',
                'academic_year' => 'sometimes|nullable|string',
                'semester' => 'sometimes|nullable|string|in:1,2,3',
                'status' => 'sometimes|required|in:active,inactive,cancelled'
            ]);

            // Check for scheduling conflicts (excluding current entry)
            if ($timetableEntry->hasConflict(
                $validated['teacher_id'] ?? $timetableEntry->teacher_id,
                $validated['room_id'] ?? $timetableEntry->room_id,
                $validated['day_of_week'] ?? $timetableEntry->day_of_week,
                $validated['time_slot'] ?? $timetableEntry->time_slot
            )) {
                return response()->json([
                    'success' => false,
                    'message' => 'Scheduling conflict detected. The teacher or room is already assigned at this time.'
                ], 422);
            }

            $timetableEntry->update($validated);

            // Load relationships for response
            $timetableEntry->load(['classLevel', 'classStream', 'subject', 'teacher', 'room']);

            return response()->json([
                'success' => true,
                'message' => 'Timetable entry updated successfully',
                'data' => $timetableEntry
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update timetable entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a timetable entry
     */
    public function deleteTimetableEntry(string $id): JsonResponse
    {
        try {
            $timetableEntry = Timetable::findOrFail($id);
            $timetableEntry->delete();

            return response()->json([
                'success' => true,
                'message' => 'Timetable entry deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete timetable entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get timetable statistics
     */
    public function getTimetableStatistics(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'class_level_id' => 'nullable|exists:class_levels,id',
                'academic_year' => 'nullable|string',
                'semester' => 'nullable|string|in:1,2,3'
            ]);

            $query = Timetable::where('status', 'active');

            if (isset($validated['class_level_id'])) {
                $query->where('class_level_id', $validated['class_level_id']);
            }

            if (isset($validated['academic_year'])) {
                $query->where('academic_year', $validated['academic_year']);
            } else {
                $query->where('academic_year', date('Y'));
            }

            if (isset($validated['semester'])) {
                $query->where('semester', $validated['semester']);
            }

            $totalEntries = $query->count();
            $entriesByDay = $query->get()->groupBy('day_of_week');
            $entriesBySubject = $query->get()->groupBy('subject_id');
            $entriesByTeacher = $query->get()->groupBy('teacher_id');

            return response()->json([
                'success' => true,
                'message' => 'Timetable statistics retrieved successfully',
                'data' => [
                    'total_entries' => $totalEntries,
                    'entries_by_day' => $entriesByDay->map(function($entries, $day) {
                        return [
                            'day' => $day,
                            'day_name' => Timetable::getDaysOfWeek()[$day] ?? 'Unknown',
                            'count' => $entries->count()
                        ];
                    })->values(),
                    'entries_by_subject' => $entriesBySubject->map(function($entries, $subjectId) {
                        $subject = Subject::find($subjectId);
                        return [
                            'subject_id' => $subjectId,
                            'subject_name' => $subject ? $subject->subject_name : 'Unknown',
                            'count' => $entries->count()
                        ];
                    })->values(),
                    'entries_by_teacher' => $entriesByTeacher->map(function($entries, $teacherId) {
                        $teacher = Staff::find($teacherId);
                        return [
                            'teacher_id' => $teacherId,
                            'teacher_name' => $teacher ? $teacher->first_name . ' ' . $teacher->last_name : 'Unknown',
                            'count' => $entries->count()
                        ];
                    })->values()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve timetable statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available options for timetable creation
     */
    public function getTimetableOptions(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Timetable options retrieved successfully',
                'data' => [
                    'time_slots' => Timetable::getAvailableTimeSlots(),
                    'days_of_week' => Timetable::getDaysOfWeek(),
                    'semesters' => Timetable::getSemesters(),
                    'statuses' => Timetable::getStatuses()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve timetable options: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk create timetable entries
     */
    public function bulkCreateTimetable(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'entries' => 'required|array|min:1',
                'entries.*.class_level_id' => 'required|exists:class_levels,id',
                'entries.*.class_level_stream_id' => 'nullable|exists:class_level_streams,id',
                'entries.*.subject_id' => 'required|exists:subjects,id',
                'entries.*.teacher_id' => 'required|exists:staff,id',
                'entries.*.room_id' => 'required|exists:classrooms,id',
                'entries.*.day_of_week' => 'required|integer|min:1|max:7',
                'entries.*.time_slot' => 'required|string|format:H:i',
                'academic_year' => 'nullable|string',
                'semester' => 'nullable|string|in:1,2,3'
            ]);

            $academicYear = $validated['academic_year'] ?? date('Y');
            $semester = $validated['semester'] ?? '1';
            $createdEntries = [];
            $conflicts = [];

            DB::transaction(function () use ($validated, $academicYear, $semester, &$createdEntries, &$conflicts) {
                foreach ($validated['entries'] as $index => $entryData) {
                    $entryData['academic_year'] = $academicYear;
                    $entryData['semester'] = $semester;
                    $entryData['status'] = 'active';
                    $entryData['created_by'] = auth()->id();

                    // Check for conflicts
                    $tempEntry = new Timetable();
                    if ($tempEntry->hasConflict(
                        $entryData['teacher_id'],
                        $entryData['room_id'],
                        $entryData['day_of_week'],
                        $entryData['time_slot']
                    )) {
                        $conflicts[] = [
                            'index' => $index,
                            'entry' => $entryData,
                            'message' => 'Scheduling conflict detected'
                        ];
                        continue;
                    }

                    $entry = Timetable::create($entryData);
                    $createdEntries[] = $entry;
                }
            });

            if (!empty($conflicts)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some entries have scheduling conflicts',
                    'data' => [
                        'conflicts' => $conflicts,
                        'created_count' => count($createdEntries)
                    ]
                ], 422);
            }

            // Load relationships for response
            $entriesWithRelations = Timetable::with(['classLevel', 'classStream', 'subject', 'teacher', 'room'])
                ->whereIn('id', array_column($createdEntries, 'id'))
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Timetable entries created successfully',
                'data' => $entriesWithRelations
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create timetable entries: ' . $e->getMessage()
            ], 500);
        }
    }
}
