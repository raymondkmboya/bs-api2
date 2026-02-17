<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Models\ParentGuardian;
use App\Models\FeeStructure;
use App\Models\FeeGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\SelcomService;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Student::query();

        // Filter by level
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        // Filter by stream
        if ($request->has('stream')) {
            $query->where('stream', $request->stream);
        }

        // Filter by class
        if ($request->has('class')) {
            $query->where('class', $request->class);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by name, student ID, or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Order by last name, then first name
        $query->orderBy('last_name')->orderBy('first_name');

        // Pagination
        $perPage = $request->get('per_page', 20);
        $students = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $students->items(),
            'pagination' => [
                'current_page' => $students->currentPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
                'last_page' => $students->lastPage()
            ]
        ]);
    }

    /**
     * Register new student (Stage 1)
     */
    public function register(Request $request, SelcomService $selcom): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'middle_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:students,email',
                'phone' => 'required|string|max:20',
                'date_of_birth' => 'required|date',
                'gender' => 'required|in:male,female,other',
                'class_level_id' => 'required|numeric',
                'address' => 'required|string|max:500',
                'region' => 'required|string',
                'parent_name' => 'required|string|max:255',
                'parent_phone' => 'required|string|max:20',
                'parent_email' => 'required|email|max:255',
                'relationship_to_student' => 'required|string|max:50',
                'previous_school' => 'nullable|string|max:255',
                'registration_fee' => 'required|numeric|min:10000|max:10000',
                'payment_method' => 'required|string|in:Cash,Selcom Pay,Bank Transfer,Mobile Money,Cheque',
                'registration_data' => 'nullable|array',
                'selcom_phone' => 'nullable|string|max:20'
            ]);

            // Check if student is already registered by comparing names and date of birth
            $existingStudent = Student::where('first_name', $validated['first_name'])
                ->where('middle_name', $validated['middle_name'])
                ->where('last_name', $validated['last_name'])
                ->where('date_of_birth', $validated['date_of_birth'])
                ->first();

            if ($existingStudent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is already registered',
                    'data' => [
                        'student_number' => $existingStudent->student_number,
                        'status' => $existingStudent->status,
                    ]
                ], 422);
            }

            // Use database transaction to ensure all or nothing
            return DB::transaction(function () use ($validated, $selcom) {

                // Create Parent record (parent information is required)
                $parentData = [
                    'parent_name' => $validated['parent_name'],
                    'parent_phone' => $validated['parent_phone'],
                    'parent_email' => $validated['parent_email'],
                    'relationship_to_student' => $validated['relationship_to_student'],
                ];

                $parent = ParentGuardian::create($parentData);
                $parentId = $parent->id;

                // Create Student record
                $studentData = [
                    'first_name' => $validated['first_name'],
                    'middle_name' => $validated['middle_name'],
                    'last_name' => $validated['last_name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'date_of_birth' => $validated['date_of_birth'],
                    'gender' => $validated['gender'],
                    'class_level_id' => $validated['class_level_id'],
                    'address' => $validated['address'],
                    'region' => $validated['region'],
                    'parent_id' => $parentId,
                    'previous_school' => $validated['previous_school'] ?? null,
                    'status' => 'registered',
                    'registration_date' => now(),
                    'registration_fee' => $validated['registration_fee'],
                    'payment_method' => $validated['payment_method'],
                    'registration_data' => isset($validated['registration_data']) ? json_encode($validated['registration_data']) : null,
                ];

                $student = Student::create($studentData);

                // Check if registration fee structure exists
                $feeStructure = FeeStructure::whereHas('feeGroup', function($query) {
                        $query->where('fee_group_name', 'Registration Fee');
                    })
                    ->first();

                if (!$feeStructure) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Registration fee structure not found. Please create registration fee structure first.',
                        'error' => 'Registration fee structure is required for student registration'
                    ], 422);
                }

                // Create payment record for registration fee

                $payment_method = $validated['payment_method'];

                if($payment_method === 'Cash') {
                    // $payment_method = 'Cash';
                     $paymentData = [
                        'student_id' => $student->id,
                        'fee_structure_id' => $feeStructure->id,
                        'invoice_number' => 'REG-' . $student->id . '-' . date('Y'),
                        'due_date' => now(),
                        'status' => 'paid',
                        'academic_year' => date('Y'),
                        'created_by' => auth('sanctum')->user()->id,
                    ];

                    $payment = Payment::create($paymentData);

                    // Create transaction for the payment
                    $transactionData = [
                        'payment_id' => $payment->id,
                        'transaction_number' => 'TXN-' . time(),
                        'amount_paid' => $validated['registration_fee'],
                        'payment_method' => $validated['payment_method'],
                        'transaction_date' => now(),
                        'reference_number' => 'REF-' . $student->id . '-' . time(),
                        'notes' => 'Registration fee payment',
                        'status' => 'completed',
                        'recorded_by' => auth('sanctum')->user()->id,
                    ];

                    $transaction = Transaction::create($transactionData);

                    return response()->json([
                        'success' => true,
                        'message' => 'Student registered successfully with cash payment',
                        'data' => [
                            'student' => $student,
                            'payment' => $payment,
                            'transaction' => $transaction
                        ]
                    ]);

                } elseif($payment_method === 'Selcom Pay') {
                    // $payment_method = 'Cash';
                    $selcom_phone = $validated['selcom_phone'];

                    $invoice_number = 'REG-' . $student->id . '-' . date('Y');

                     $paymentData = [
                        'student_id' => $student->id,
                        'fee_structure_id' => $feeStructure->id,
                        'invoice_number' => $invoice_number,
                        'due_date' => now(),
                        'status' => 'paid',
                        'academic_year' => date('Y'),
                        'created_by' => auth('sanctum')->user()->id,
                    ];

                    $payment = Payment::create($paymentData);

                    $response = $selcom->initiateStkPush(
                        $invoice_number,
                        $selcom_phone,
                        $validated['registration_fee']
                    );

                    if ($response['result'] === 'SUCCESS') {
                        return response()->json([
                            'success' => true,
                            'message' => 'Please check your phone to enter PIN',
                            'order_id' => $invoice_number
                        ]);
                    }else{
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to initiate Selcom Pay',
                            'error' => $response['message']
                        ], 500);
                    }
                }

            });

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Transaction automatically rolls back on any exception
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admit student (Stage 2)
     */
    public function admit(Request $request, $id): JsonResponse
    {
        try {
            $student = Student::findOrFail($id);

            $validated = $request->validate([
                'admission_number' => 'required|string|max:50|unique:students,admission_number,' . $id,
                'admission_date' => 'required|date',
                'level' => 'required|string|max:50',
                'stream' => 'nullable|string|max:50',
                'class' => 'required|string|max:50',
                'admission_data' => 'nullable|array'
            ]);

            $student->update([
                'status' => 'admitted',
                'admission_number' => $validated['admission_number'],
                'admission_date' => $validated['admission_date'],
                'level' => $validated['level'],
                'stream' => $validated['stream'],
                'class' => $validated['class'],
                'admission_data' => $validated['admission_data'] ?? []
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Student admitted successfully',
                'data' => $student
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
                'message' => 'Admission failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enroll student (Stage 3)
     */
    public function enroll(Request $request, $id): JsonResponse
    {
        try {
            $student = Student::findOrFail($id);

            $validated = $request->validate([
                'enrollment_date' => 'required|date',
                'stream' => 'required|string|max:50',
                'class' => 'required|string|max:50',
                'enrollment_data' => 'nullable|array'
            ]);

            $student->update([
                'status' => 'enrolled',
                'enrollment_date' => $validated['enrollment_date'],
                'stream' => $validated['stream'],
                'class' => $validated['class'],
                'enrollment_data' => $validated['enrollment_data'] ?? []
            ]);

            // Update user account with proper username
            if ($student->student_id) {
                $student->user->update(['username' => $student->student_id]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Student enrolled successfully',
                'data' => $student
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
                'message' => 'Enrollment failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        $student = Student::with('attendanceRecords')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $student
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'student_id' => 'sometimes|string|max:50|unique:students,student_id,' . $id,
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:students,email,' . $id,
            'phone' => 'sometimes|string|max:20',
            'date_of_birth' => 'sometimes|date',
            'gender' => 'sometimes|in:male,female,other',
            'level' => 'sometimes|string|max:50',
            'stream' => 'sometimes|string|max:50',
            'class' => 'sometimes|string|max:50',
            'address' => 'sometimes|string|max:500',
            'parent_name' => 'sometimes|string|max:255',
            'parent_phone' => 'sometimes|string|max:20',
            'status' => 'sometimes|in:active,inactive,graduated,transferred',
            'admission_date' => 'sometimes|date',
            'profile_image' => 'sometimes|string|max:255'
        ]);

        $student->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Student updated successfully',
            'data' => $student
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $student = Student::findOrFail($id);
        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student deleted successfully'
        ]);
    }

    /**
     * Get registered students
     */
    public function getRegistered(): JsonResponse
    {
        $students = Student::with(['user', 'parent'])->registered()->get();

        return response()->json([
            'success' => true,
            'data' => $students,
            'message' => 'Registered students retrieved successfully'
        ]);
    }

    /**
     * Get admitted students
     */
    public function getAdmitted(): JsonResponse
    {
        $students = Student::with(['user', 'parent'])->admitted()->get();

        return response()->json([
            'success' => true,
            'data' => $students,
            'message' => 'Admitted students retrieved successfully'
        ]);
    }

    /**
     * Get enrolled students
     */
    public function getEnrolled(): JsonResponse
    {
        $students = Student::with(['user', 'parent'])->enrolled()->get();

        return response()->json([
            'success' => true,
            'data' => $students,
            'message' => 'Enrolled students retrieved successfully'
        ]);
    }
}
