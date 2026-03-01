<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class HumanResourceController extends Controller
{
    /**
     * Get all staff members
     */
    public function getStaffMembers(): JsonResponse
    {
        $staff = Staff::with('department')->get();

        return response()->json([
            'success' => true,
            'data' => $staff,
            'message' => 'Staff members retrieved successfully'
        ]);
    }

    /**
     * Get active staff members only
     */
    public function getActiveStaffMembers(): JsonResponse
    {
        $staff = Staff::active()->get();

        return response()->json([
            'success' => true,
            'data' => $staff,
            'message' => 'Active staff members retrieved successfully'
        ]);
    }

    /**
     * Get teachers only
     */
    public function getTeachers(): JsonResponse
    {
        $teachers = Staff::teachers()->active()->get();

        return response()->json([
            'success' => true,
            'data' => $teachers,
            'message' => 'Teachers retrieved successfully'
        ]);
    }

    /**
     * Get administrative staff only
     */
    public function getAdministrativeStaff(): JsonResponse
    {
        $adminStaff = Staff::administrative()->active()->get();

        return response()->json([
            'success' => true,
            'data' => $adminStaff,
            'message' => 'Administrative staff retrieved successfully'
        ]);
    }

    /**
     * Get staff by department
     */
    public function getStaffByDepartment($department): JsonResponse
    {
        $staff = Staff::byDepartment($department)->active()->get();

        return response()->json([
            'success' => true,
            'data' => $staff,
            'message' => "Staff from {$department} department retrieved successfully"
        ]);
    }

    /**
     * Get specific staff member
     */
    public function getStaffMember($id): JsonResponse
    {
        $staff = Staff::with(['classrooms', 'subjects', 'attendance', 'leaves'])->find($id);

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $staff,
            'message' => 'Staff member retrieved successfully'
        ]);
    }

    /**
     * Create new staff member
     */
    public function createStaffMember(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email|unique:staffs,email',
                'phone' => 'nullable|string|max:20',
                'department_id' => 'required|numeric',
                'position' => 'required|string|max:255',
                'employment_type' => 'required|in:full_time,part_time,contract,temporary',
                'hire_date' => 'nullable|date',
                'salary' => 'nullable|numeric|min:0',
                'status' => 'required|in:active,inactive,suspended,terminated',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:20',
                'qualifications' => 'nullable|array',
                'qualifications.*' => 'string|max:255',
                'experience_years' => 'nullable|integer|min:0',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:male,female,other',
                'nationality' => 'nullable|string|max:100',
                'passport_number' => 'nullable|string|max:50',
                'work_permit_number' => 'nullable|string|max:50',
                'bank_account' => 'nullable|string|max:50',
                'bank_name' => 'nullable|string|max:255',
                'tax_id' => 'nullable|string|max:50',
                'social_security_number' => 'nullable|string|max:50',
                'notes' => 'nullable|string|max:1000',
                'password' => 'required|string|min:8'
            ]);

            // Generate staff_id first
            $staffId = (new Staff())->generateStaffId();

            // Create User account first
            $userData = [
                'username' => $staffId, // Use staff_id as username
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
            ];

            $user = \App\Models\User::create($userData);

            // Create Staff record with user_id and generated staff_id
            $validated['user_id'] = $user->id;
            $validated['staff_id'] = $staffId;
            $validated['password'] = bcrypt($validated['password']);
            $staff = Staff::create($validated);

            return response()->json([
                'success' => true,
                'data' => $staff,
                'message' => 'Staff member created successfully'
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
                'message' => 'Failed to create staff member: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update staff member
     */
    public function updateStaffMember(Request $request, $id): JsonResponse
    {
        try {
            $staff = Staff::find($id);

            if (!$staff) {
                return response()->json([
                    'success' => false,
                    'message' => 'Staff member not found'
                ], 404);
            }

            $validated = $request->validate([
                'first_name' => 'sometimes|required|string|max:255',
                'last_name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:staffs,email,' . $id,
                'phone' => 'nullable|string|max:20',
                'staff_id' => 'sometimes|required|string|unique:staffs,staff_id,' . $id,
                'department_id' => 'sometimes|required|string|max:255',
                'position' => 'sometimes|required|string|max:255',
                'employment_type' => 'sometimes|required|in:full_time,part_time,contract,temporary',
                'hire_date' => 'sometimes|required|date',
                'salary' => 'nullable|numeric|min:0',
                'status' => 'sometimes|required|in:active,inactive,suspended,terminated',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:20',
                'qualifications' => 'nullable|array',
                'qualifications.*' => 'string|max:255',
                'experience_years' => 'nullable|integer|min:0',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:male,female,other',
                'nationality' => 'nullable|string|max:100',
                'passport_number' => 'nullable|string|max:50',
                'work_permit_number' => 'nullable|string|max:50',
                'bank_account' => 'nullable|string|max:50',
                'bank_name' => 'nullable|string|max:255',
                'tax_id' => 'nullable|string|max:50',
                'social_security_number' => 'nullable|string|max:50',
                'notes' => 'nullable|string|max:1000',
                'password' => 'nullable|string|min:8'
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            }

            $staff->update($validated);

            return response()->json([
                'success' => true,
                'data' => $staff,
                'message' => 'Staff member updated successfully'
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
     * Delete staff member
     */
    public function deleteStaffMember($id): JsonResponse
    {
        $staff = Staff::find($id);

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found'
            ], 404);
        }

        // Check if staff has assigned classrooms
        if ($staff->classrooms()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete staff member with assigned classrooms'
            ], 422);
        }

        $staff->delete();

        return response()->json([
            'success' => true,
            'message' => 'Staff member deleted successfully'
        ]);
    }

    /**
     * Get staff statistics
     */
    public function getStaffStatistics(): JsonResponse
    {
        $stats = [
            'total_staff' => Staff::count(),
            'active_staff' => Staff::active()->count(),
            'teachers' => Staff::teachers()->active()->count(),
            'administrative_staff' => Staff::administrative()->active()->count(),
            'by_department' => Staff::selectRaw('department, COUNT(*) as count')
                ->groupBy('department')
                ->get()
                ->pluck('count', 'department'),
            'by_employment_type' => Staff::selectRaw('employment_type, COUNT(*) as count')
                ->groupBy('employment_type')
                ->get()
                ->pluck('count', 'employment_type')
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Staff statistics retrieved successfully'
        ]);
    }

    // ==================== DEPARTMENT MANAGEMENT ====================

    /**
     * Get all departments
     */
    public function getDepartments(): JsonResponse
    {
        $departments = Department::with(['headOfDepartment', 'staff'])->get();

        return response()->json([
            'success' => true,
            'data' => $departments,
            'message' => 'Departments retrieved successfully'
        ]);
    }

    /**
     * Get active departments only
     */
    public function getActiveDepartments(): JsonResponse
    {
        $departments = Department::active()->with(['headOfDepartment', 'staff'])->get();

        return response()->json([
            'success' => true,
            'data' => $departments,
            'message' => 'Active departments retrieved successfully'
        ]);
    }

    /**
     * Get a specific department
     */
    public function getDepartment($id): JsonResponse
    {
        $department = Department::with(['headOfDepartment', 'staff'])->find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $department,
            'message' => 'Department retrieved successfully'
        ]);
    }

    /**
     * Create a new department
     */
    public function createDepartment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:departments,department_name',
                'hod_id' => 'nullable|exists:staffs,id',
                'description' => 'nullable|string|max:1000',
                'status' => 'required|in:active,inactive'
            ]);

            $department = Department::create($validated);

            return response()->json([
                'success' => true,
                'data' => $department->load(['headOfDepartment']),
                'message' => 'Department created successfully'
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
     * Update a department
     */
    public function updateDepartment(Request $request, $id): JsonResponse
    {
        try {
            $department = Department::find($id);

            if (!$department) {
                return response()->json([
                    'success' => false,
                    'message' => 'Department not found'
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:departments,department_name,' . $id,
                'hod_id' => 'nullable|exists:staffs,id',
                'description' => 'nullable|string|max:1000',
                'status' => 'required|in:active,inactive'
            ]);

            $department->update($validated);

            return response()->json([
                'success' => true,
                'data' => $department->load(['headOfDepartment']),
                'message' => 'Department updated successfully'
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
     * Delete a department
     */
    public function deleteDepartment($id): JsonResponse
    {
        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        // Check if department has staff members
        if ($department->staff()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete department with assigned staff members'
            ], 400);
        }

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully'
        ]);
    }

    /**
     * Get department statistics
     */
    public function getDepartmentStatistics(): JsonResponse
    {
        $totalDepartments = Department::count();
        $activeDepartments = Department::active()->count();
        $inactiveDepartments = Department::inactive()->count();
        $departmentsWithHod = Department::whereNotNull('hod_id')->count();

        $stats = [
            'total_departments' => $totalDepartments,
            'active_departments' => $activeDepartments,
            'inactive_departments' => $inactiveDepartments,
            'departments_with_hod' => $departmentsWithHod,
            'departments_without_hod' => $totalDepartments - $departmentsWithHod
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Department statistics retrieved successfully'
        ]);
    }
}
