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
use App\Helpers\AppHelper;


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
            //validate sent data
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'middle_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:students,email',
                'phone' => 'required|string|max:20',
                'date_of_birth' => 'required|date',
                'gender' => 'required|in:male,female,other',
                'class_level_id' => 'required|numeric',
                'address' => 'required|string|max:500',
                'region' => 'required|string',
                'hear_from_source' => 'nullable|string|max:255',
                'parent_name' => 'required|string|max:255',
                'parent_phone' => 'required|string|max:20',
                'parent_email' => 'nullable|email|max:255',
                'relationship_to_student' => 'required|string|max:50',
                'previous_school' => 'nullable|string|max:255',
                'payment_method' => 'required|string|in:Cash,Selcom Pay,Mobile Money',
                'registration_data' => 'nullable|array',
                'selcom_phone' => 'nullable|string|max:20|required_if:payment_method,Selcom Pay'
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
                    'data' => []
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
                    'hear_from_source' => $validated['hear_from_source'] ?? null,
                    'parent_id' => $parentId,
                    'previous_school' => $validated['previous_school'] ?? null,
                    'registration_date' => now(),
                    'payment_method' => $validated['payment_method'],
                    'registration_data' => isset($validated['registration_data']) ? json_encode($validated['registration_data']) : null,
                ];

                $student = Student::create($studentData);

                // Check if registration fee structure exists
                $registrationFee = FeeStructure::whereHas('feeGroup', function($query) {
                        $query->where('fee_group_name', 'Registration Fee');
                    })
                    ->first();

                $tuitionFee = FeeStructure::whereHas('feeGroup', function($query) use ($validated) {
                        $query->where([
                            'fee_group_name' => 'Tuition Fee',
                            'class_level_id' => $validated['class_level_id'],
                            'admission_month' => now()->format('F') // Full month name (January, February, etc.)
                        ]);
                    })
                    ->first();

                if (!$tuitionFee)  {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tution fee structure not found. Please create registration fee structure first.',
                        'error' => 'Tuition fee structure is required for student registration'
                    ], 422);
                }

                if (!$registrationFee)  {
                    return response()->json([
                        'success' => false,
                        'message' => 'Registration fee structure not found. Please create registration fee structure first.',
                        'error' => 'Registration fee structure is required for student registration'
                    ], 422);
                }

                $registrationInoviceNumber = 'REG-' . $student->id . '-' . date('Y');

                $registrationFeeData = [
                    'student_id' => $student->id,
                    'fee_structure_id' => $registrationFee->id,
                    'invoice_number' => $registrationInoviceNumber,
                    'status' => 'paid',
                    'created_by' => auth('sanctum')->user()->id,
                ];

                // generate Tuition Fee invoice
                $tuitionFeeData = [
                    'student_id' => $student->id,
                    'fee_structure_id' => $tuitionFee->id,
                    'invoice_number' => 'FEE-' . $student->id . '-' . date('Y'),
                    'created_by' => auth('sanctum')->user()->id,
                ];

                $registrationPayment = Payment::create($registrationFeeData);
                $tuitionPayment = Payment::create($tuitionFeeData);

                // generate control number
                $nmbInvoiceData = [
                    'payment_id' => $tuitionPayment->id,
                    'student_name' => $student->first_name.' '.$student->last_name,
                    'student_number' => $student->student_number,
                    'amount' => $tuitionFee->amount,
                    'type' => 'Fee',
                ];

                \Log::info('Attempting to create NMB invoice', $nmbInvoiceData);

                $response = AppHelper::instance()->sendNMBInvoice($nmbInvoiceData);

                \Log::info('NMB invoice response', [
                    'response' => $response,
                    'success' => $response['success'] ?? false,
                    'description' => $response['description'] ?? 'No description'
                ]);

                if($response['success'] && $response['description'] == 'Success'){
                    Payment::where('id', $tuitionPayment->id)->update([
                        'control_number' => 'SAS953' . str_pad($tuitionPayment->id, 4, '0', STR_PAD_LEFT)
                    ]);

                    \Log::info('Control number updated successfully', [
                        'payment_id' => $tuitionPayment->id,
                        'control_number' => 'SAS953' . str_pad($tuitionPayment->id, 4, '0', STR_PAD_LEFT)
                    ]);
                } else {
                    \Log::error('NMB invoice creation failed', [
                        'response' => $response,
                        'error' => $response['error'] ?? 'Unknown error',
                        'student_id' => $student->id
                    ]);
                }

                // Send SMS notifications to parent and student
                try {
                    // SMS to Student
                    $studentMessage = "Dear {$student->first_name}, welcome to British School! Your registration is complete. Student ID: {$student->student_number}. Your tuition fee control number is: " . ($response['success'] ? 'SAS953' . str_pad($tuitionPayment->id, 4, '0', STR_PAD_LEFT) : 'Pending') . ". Please complete payment to secure your admission.";

                    $studentSMS = [
                        'message' => $studentMessage,
                        'recipient_id' => $student->id,
                        'phone' => $student->phone
                    ];

                    AppHelper::instance()->sendSMS($studentSMS);

                    \Log::info('SMS sent to student', [
                        'student_id' => $student->id,
                        'phone' => $student->phone,
                        'message' => $studentMessage
                    ]);

                    // SMS to Parent
                    $parentMessage = "Dear {$parent->parent_name}, your child {$student->first_name} {$student->last_name} has been successfully registered at British School. Student ID: {$student->student_number}. Tuition fee control number: " . ($response['success'] ? 'SAS953' . str_pad($tuitionPayment->id, 4, '0', STR_PAD_LEFT) : 'Pending') . ". Thank you for choosing us.";

                    $parentSMS = [
                        'message' => $parentMessage,
                        'recipient_id' => $parent->id,
                        'phone' => $parent->parent_phone
                    ];

                    AppHelper::instance()->sendSMS($parentSMS);

                    \Log::info('SMS sent to parent', [
                        'parent_id' => $parent->id,
                        'phone' => $parent->parent_phone,
                        'message' => $parentMessage
                    ]);

                } catch (\Exception $smsEx) {
                    \Log::error('SMS sending failed during registration', [
                        'student_id' => $student->id,
                        'parent_id' => $parent->id,
                        'error' => $smsEx->getMessage()
                    ]);
                }

                $payment_method = $validated['payment_method'];

                if($payment_method === 'Cash') {
                    // Create transaction for the payment
                    $transactionData = [
                        'payment_id' => $registrationPayment->id,
                        'transaction_number' => 'TXN-' . time(),
                        'payment_method' => $validated['payment_method'],
                        'transaction_date' => now(),
                        'reference_number' => 'REF-' . $student->id . '-' . time(),
                        'notes' => 'Registration fee payment',
                        'status' => 'completed',
                        'amount_paid' => $registrationFee->amount,
                        'created_by' => auth('sanctum')->user()->id,
                    ];

                    $transaction = Transaction::create($transactionData);

                    return response()->json([
                        'success' => true,
                        'message' => 'Student registered successfully with cash payment',
                        'data' => []
                    ]);

                }

                if($payment_method === 'Selcom Pay') {
                    $selcom_phone = $validated['selcom_phone'];
                    $data =  [
                        'invoice_number' => $registrationInoviceNumber,
                        // 'student_number' => $student->student_number,
                        'amount' => $registrationFee->amount,
                        'phone' => $selcom_phone,
                    ];

                    $response = AppHelper::instance()->processSelcomPay($data);

                    if ($response['result'] === 'SUCCESS') {
                        return response()->json([
                            'success' => true,
                            'message' => 'Please check your phone to enter PIN',
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

            // Handle FormData - decode JSON strings
            $studentData = json_decode($request->student_data, true);
            $firstParent = json_decode($request->first_parent, true);
            $secondParent = $request->second_parent ? json_decode($request->second_parent, true) : null;

            $validated = $request->validate([
                'admission_number' => 'nullable|string|max:50|unique:students,admission_number,' . $id,
                'admission_date' => 'required|date',
                'class_level_id' => 'required|numeric',
                'class_level_stream_id' => 'nullable|numeric',
                'payment_method' => 'required|string',
                'amount_paid' => 'required|numeric',
                'transaction_ref' => 'required|string|max:255|unique:transactions,transaction_ref',
                'selcom_phone' => 'nullable|string',
                'student_photo' => 'nullable'
            ]);

            // get the initial amount from fee structure check
            $feeStructure = FeeStructure::where('class_level_id', $validated['class_level_id'])->first();
            if (!$feeStructure) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fee structure not found for this class level'
                ], 422);
            }

            $initialAmount = $feeStructure->initial_amount;

            if ($validated['amount_paid'] < $initialAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Amount paid is less than the initial amount'
                ], 422);
            }

            // Additional validation for bank methods
            if($validated['payment_method'] === 'Bank Deposit' || $validated['payment_method'] === 'Bank Transfer'){
                if (!$request->transaction_ref || !$request->hasFile('transaction_receipt')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Transaction Reference or Payment Receipt is Missing'
                    ], 422);
                }
            }

            // Additional validation for mobile money
            if($validated['payment_method'] === 'Mpesa' || $validated['payment_method'] === 'Tigo Pesa' || $validated['payment_method'] === 'Airtel Money'){
                if (!$request->transaction_ref) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Transaction Reference is Missing for mobile money payment'
                    ], 422);
                }
            }

            return DB::transaction(function () use ($request, $id, $validated, $feeStructure, $student, $firstParent) {
                $invoice_number = 'FEE-' . $id . '-' . date('Y');

                // Handle photo upload if present
                $photoPath = null;
                if ($request->hasFile('photo')) {
                    $photoPath = $request->file('photo')->store('student_photos', 'public');
                }

                // Update student first
                $student->update([
                    'admission_number' => $validated['admission_number'],
                    'admission_date' => $validated['admission_date'],
                    'class_level_id' => $validated['class_level_id'],
                    'class_level_stream_id' => $validated['class_level_stream_id'],
                    'profile_image' => $photoPath,
                    'status' => 'admitted'
                ]);

                // Update parent information if needed
                if ($student->parent && $firstParent) {
                    $student->parent->update([
                        'parent_name' => $firstParent['name'],
                        'parent_email' => $firstParent['email'],
                        'parent_phone' => $firstParent['phone'],
                        'relationship_to_student' => $firstParent['relationship']
                    ]);
                }

                // Handle payment processing based on method
                if($validated['payment_method'] === 'Cash'){
                    // Create payment record for cash
                    $payment = Payment::create([
                        'student_id' => $id,
                        'invoice_number' => $invoice_number,
                        'fee_structure_id' => $feeStructure->id,
                        'amount' => $validated['amount_paid'],
                        'amount_paid' => $validated['amount_paid'],
                        'balance' => 0,
                        'payment_method' => 'Cash',
                        'payment_date' => now(),
                        'status' => 'paid',
                        'created_by' => auth('sanctum')->user()->id
                    ]);

                    // Create transaction record
                    Transaction::create([
                        'payment_id' => $payment->id,
                        'transaction_number' => 'TXN-' . time(),
                        'amount_paid' => $validated['amount_paid'],
                        'payment_method' => 'Cash',
                        'transaction_date' => now(),
                        'notes' => 'Registration fee payment - Cash',
                        'status' => 'completed',
                        'created_by' => auth('sanctum')->user()->id,
                    ]);

                } elseif($validated['payment_method'] === 'Bank Deposit' || $validated['payment_method'] === 'Bank Transfer'){

                    // Simple file upload using Laravel
                    $receiptPath = $request->file('transaction_receipt')->store('payment_receipts', 'public');

                    // Create payment record
                    $payment = Payment::create([
                        'student_id' => $id,
                        'invoice_number' => $invoice_number,
                        'fee_structure_id' => $feeStructure->id,
                        'amount' => $validated['amount_paid'],
                        'amount_paid' => $validated['amount_paid'],
                        'balance' => 0,
                        'payment_method' => $validated['payment_method'],
                        'payment_date' => now(),
                        'status' => 'paid',
                        'created_by' => auth('sanctum')->user()->id
                    ]);

                    // Create transaction for the payment
                    Transaction::create([
                        'payment_id' => $payment->id,
                        'transaction_number' => 'TXN-' . time(),
                        'amount_paid' => $validated['amount_paid'],
                        'payment_method' => $validated['payment_method'],
                        'transaction_date' => now(),
                        'transaction_ref' => $request->transaction_ref,
                        'transaction_reciept' => $receiptPath,
                        'notes' => 'Registration fee payment - ' . $validated['payment_method'],
                        'status' => 'completed',
                        'created_by' => auth('sanctum')->user()->id,
                    ]);

                } elseif($validated['payment_method'] === 'Mpesa' || $validated['payment_method'] === 'Tigo Pesa' || $validated['payment_method'] === 'Airtel Money'){

                    // Create payment record
                    $payment = Payment::create([
                        'student_id' => $id,
                        'invoice_number' => $invoice_number,
                        'fee_structure_id' => $feeStructure->id,
                        'amount' => $validated['amount_paid'],
                        'amount_paid' => $validated['amount_paid'],
                        'balance' => 0,
                        'payment_method' => $validated['payment_method'],
                        'payment_date' => now(),
                        'status' => 'paid',
                        'created_by' => auth('sanctum')->user()->id
                    ]);

                    // Create transaction for the payment
                    Transaction::create([
                        'payment_id' => $payment->id,
                        'transaction_number' => 'TXN-' . time(),
                        'amount_paid' => $validated['amount_paid'],
                        'payment_method' => $validated['payment_method'],
                        'transaction_date' => now(),
                        'transaction_ref' => $request->transaction_ref,
                        'notes' => 'Registration fee payment - ' . $validated['payment_method'],
                        'status' => 'completed',
                        'created_by' => auth('sanctum')->user()->id,
                    ]);

                } elseif($validated['payment_method'] === 'Selcom Pay'){

                    // Create payment record (pending until Selcom confirmation)
                    $payment = Payment::create([
                        'student_id' => $id,
                        'invoice_number' => $invoice_number,
                        'fee_structure_id' => $feeStructure->id,
                        'amount' => $validated['amount_paid'],
                        'amount_paid' => 0, // Will be updated after Selcom confirmation
                        'balance' => $validated['amount_paid'],
                        'payment_method' => 'Selcom Pay',
                        'payment_date' => now(),
                        'status' => 'pending',
                        'created_by' => auth('sanctum')->user()->id
                    ]);

                    // TODO: Implement Selcom Pay integration
                    // For now, just create the payment record
                }

                return response()->json([
                    'success' => true,
                    'data' => $student->fresh(['parent', 'classLevel', 'classLevelStream']),
                    'message' => 'Student admitted successfully'
                ]);
            });

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to admit student: ' . $e->getMessage()
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
        $student = Student::with([
            'parent',
            'classLevel',
            'classLevelStream',
            'subjects',
        ])->findOrFail($id);

        // Calculate fees information
        // $feesData = $this->calculateFeesData($student);

        // Add fees data to student response
        $studentArray = $student->toArray();
        // $studentArray['fees'] = $feesData;

        return response()->json([
            'success' => true,
            'data' => $studentArray
        ]);
    }

    /**
     * Calculate fees data for a student
     */
    private function calculateFeesData($student)
    {
        // Get total fees from fee structure based on class level
        $totalFees = $this->getTotalFeesForClassLevel($student->class_level_id);

        // Calculate total paid from transactions
        $totalPaid = $student->payments()
            ->join('transactions', 'transactions.payment_id', '=', 'payments.id')
            ->where('transactions.status', 'completed')
            ->sum('transactions.amount_paid');

        // Calculate balance
        $balance = $totalFees - $totalPaid;

        // Get payment history
        $paymentHistory = $student->payments()
            ->with(['transactions', 'feeStructure.feeGroup'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($payment) {
                $transaction = $payment->transactions->first(); // Get first transaction
                return [
                    'date' => $payment->created_at->format('Y-m-d'),
                    'description' => $payment->feeStructure->feeGroup->name ?? 'Payment',
                    'amount' => $payment->feeStructure->amount,
                    'method' => $transaction?->payment_method ?? $payment->status,
                    'transaction_ref' => $transaction?->transaction_ref ?? null,
                    'status' => $payment->status ?? 'completed'
                ];
            });

        // Get actual fee groups from database for this class level
        $feeGroups = FeeGroup::with('feeStructures')
            ->whereHas('feeStructures', function ($query) use ($student) {
                $query->where('class_level_id', $student->class_level_id);
            })
            ->get()
            ->map(function ($feeGroup) use ($totalPaid, $totalFees) {
                $groupTotal = $feeGroup->feeStructures
                    ->where('class_level_id', $student->class_level_id)
                    ->sum('amount');

                // Calculate paid proportion for this group
                $groupPaid = $totalFees > 0 ? ($groupTotal / $totalFees) * $totalPaid : 0;
                $groupBalance = $groupTotal - $groupPaid;

                return [
                    'name' => $feeGroup->name,
                    'description' => $feeGroup->description,
                    'amount' => $groupTotal,
                    'paid' => $groupPaid,
                    'balance' => $groupBalance
                ];
            });

        return [
            'total' => $totalFees,
            'paid' => $totalPaid,
            'balance' => $balance,
            'paymentHistory' => $paymentHistory,
            'feeGroups' => $feeGroups
        ];
    }

    /**
     * Get total fees for a class level from actual fee structure
     */
    private function getTotalFeesForClassLevel($classLevelId)
    {
        // Get actual fee structure from database
        $totalFees = FeeStructure::where('class_level_id', $classLevelId)->sum('amount');

        // If no fee structure found, use default values
        if ($totalFees == 0) {
            $defaultFees = [
                1 => 1500000, // Form 1
                2 => 1600000, // Form 2
                3 => 1700000, // Form 3
                4 => 1800000, // Form 4
                5 => 1900000, // Form 5
                6 => 2000000, // Form 6
            ];
            $totalFees = $defaultFees[$classLevelId] ?? 1500000;
        }

        return $totalFees;
    }

    public function update(Request $request, $id): JsonResponse
    {
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'student_number' => 'sometimes|string|max:255',
            'first_name' => 'sometimes|string|max:255',
            'middle_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255|unique:students,email,' . $id,
            'phone' => 'sometimes|string|max:20',
            'date_of_birth' => 'sometimes|date',
            'gender' => 'sometimes|in:male,female,other',
            'class_level_id' => 'sometimes|numeric|exists:class_levels,id',
            'class_level_stream_id' => 'sometimes|numeric|exists:class_level_streams,id',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'subjects' => 'sometimes|array',
            'subjects.*' => 'sometimes|numeric|exists:subjects,id',
            'parents' => 'sometimes|array',
            'parents.*.name' => 'required_with:parents|string|max:255',
            'parents.*.email' => 'nullable|email|max:255',
            'parents.*.phone' => 'required_with:parents.*.name|string|max:20',
            'parents.*.relationship' => 'required_with:parents.*.name|string|max:50',
            'status' => 'sometimes|in:active,inactive,graduated,transferred',
            'admission_date' => 'sometimes|date',
            'profile_image' => 'sometimes|string|max:255'
        ]);

        return DB::transaction(function () use ($student, $validated) {
            // Update basic student information
            $studentData = collect($validated)->except(['subjects', 'parents'])->toArray();
            
            // Convert null address fields to empty strings to satisfy database constraints
            $studentData['street'] = $studentData['street'] ?? '';
            $studentData['city'] = $studentData['city'] ?? '';
            $studentData['region'] = $studentData['region'] ?? '';
            
            $student->update($studentData);

            // Update subjects if provided
            if (isset($validated['subjects'])) {
                $student->subjects()->sync($validated['subjects']);
            }

            // Update parents if provided
            if (isset($validated['parents']) && is_array($validated['parents'])) {
                // Create new parent records and update student's parent_id
                foreach ($validated['parents'] as $index => $parentData) {
                    if (!empty($parentData['name'])) {
                        $parent = ParentGuardian::create([
                            'parent_name' => $parentData['name'],
                            'parent_email' => $parentData['email'] ?? null,
                            'parent_phone' => $parentData['phone'],
                            'relationship_to_student' => $parentData['relationship'],
                        ]);
                        
                        // Update student's parent_id for the first parent
                        if ($index === 0) {
                            $student->parent_id = $parent->id;
                            $student->save();
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Student updated successfully',
            ]);
        });
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
        $students = Student::with(['user', 'parent', 'classLevel', 'classLevelStream'])->registered()->get();

        return response()->json([
            'success' => true,
            'data' => $students,
            'message' => 'Registered students retrieved successfully'
        ]);
    }

    public function getRegistrationStats(): JsonResponse
    {
        $registered = Student::registered()->count();
        $followUps = Student::followedUp()->count();
        $todayDueFollowUps = Student::DueTodayFollowedUp()->count();
        $stoppedFollowUps = Student::StoppedFollowedUp()->count();
        $notFollowedUp = $registered - $followUps;


        $stats = [
            'registered' => $registered,
            'followUps' => $followUps,
            'todayDueFollowUps' => $todayDueFollowUps,
            'stoppedFollowUps' => $stoppedFollowUps,
            'notFollowedUp' => $notFollowedUp
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Registered students retrieved successfully'
        ]);
    }

    public function getAdmissionStats(): JsonResponse
    {
        $admitted = Student::admitted()->count();
        $enrolled = Student::enrolled()->count();
        $suspended = Student::suspended()->count();
        $inactive = Student::inactive()->count();
        $notEnrolled = $admitted - $enrolled;


        $stats = [
            'admitted' => $admitted,
            'enrolled' => $enrolled,
            'inactive' => $inactive,
            'notEnrolled' => $notEnrolled,
            'suspended' => $suspended
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => ''
        ]);
    }

    /**
     * Get admitted students
     */
    public function getAdmitted(): JsonResponse
    {
        $students = Student::with(['user', 'parent', 'classLevel', 'classLevelStream'])->admitted()->get();

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
        $students = Student::with(['user', 'parent', 'classLevel', 'classLevelStream'])->enrolled()->get();

        return response()->json([
            'success' => true,
            'data' => $students,
            'message' => 'Enrolled students retrieved successfully'
        ]);
    }

    /**
     * Print registration form
     */
    public function printRegistrationForm(Request $request, $id)
    {
        // For print form, we can use token-based auth or make it public
        // Option 1: Check for token in query parameter
        if ($request->has('token')) {
            // Validate token and authenticate user
            $token = $request->query('token');
            // You might need to implement token validation here
        }

        $student = Student::with(['user', 'parent', 'classLevel', 'classLevelStream'])->findOrFail($id);

        // Calculate initial fee based on class level
        $initialFee = $this->getInitialFeeByClassLevel($student->class_level_id);

        // Generate control number (you can implement your own logic)
        $controlNumber = 'REG-' . $student->id . '-' . date('Ym') . rand(1000, 9999);

        // Prepare data for the view
        $data = [
            'student' => [
                'student_number' => $student->student_number,
                'full_name' => strtoupper($student->first_name . ' ' . $student->middle_name . ' ' . $student->last_name),
                'email' => $student->email,
                'phone' => $student->phone,
                'date_of_birth' => $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '',
                'gender' => ucfirst($student->gender),
                'address' => $student->address,
                'region' => $student->region,
                'class_level' => $student->classLevel ? $student->classLevel->class_level_name : 'N/A',
                'registration_date' => $student->registration_date ? $student->registration_date->format('d/m/Y') : '',
                'status' => ucfirst(str_replace('_', ' ', $student->status))
            ],
            'parent' => [
                'name' => $student->parent ? $student->parent->parent_name : 'N/A',
                'email' => $student->parent ? $student->parent->parent_email : 'N/A',
                'phone' => $student->parent ? $student->parent->parent_phone : 'N/A',
                'relationship' => $student->parent ? $student->parent->relationship_to_student : 'N/A',
                'address' => $student->parent ? $student->parent->address : 'N/A',
                'city' => $student->parent ? $student->parent->city : 'N/A',
                'country' => $student->parent ? $student->parent->country : 'N/A'
            ],
            'payment' => [
                'control_number' => $controlNumber,
                'initial_amount' => number_format($initialFee, 0, '.', ','),
                'bank_name' => 'NMB Bank',
                'account_name' => 'THE BRITISH SCHOOL',
                'account_number' => '22210019865'
            ]
        ];

        // Return HTML view for printing
        return view('registration-form', $data);
    }

    /**
     * Get initial fee based on class level
     */
    private function getInitialFeeByClassLevel($classLevelId): int
    {
        // Define initial fees by class level ID
        $fees = [
            1 => 10000,  // Form 1
            2 => 10000,  // Form 2
            3 => 10000,  // Form 3
            4 => 10000,  // Form 4
            5 => 15000,  // Form 5
            6 => 15000,  // Form 6
        ];

        return $fees[$classLevelId] ?? 10000; // Default to 10000 if not found
    }

}
