<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\ClassLevel;
use App\Models\ClassLevelStream;
use App\Models\Compass;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
}
