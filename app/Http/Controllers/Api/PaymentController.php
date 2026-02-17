<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\FeeStructure;
use App\Models\FeeGroup;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    /**
     * Get all payments with filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Payment::with(['student', 'feeStructure.feeGroup', 'transactions']);

            // Filter by student
            if ($request->has('student_id')) {
                $query->where('student_id', $request->student_id);
            }

            // Filter by academic year
            if ($request->has('academic_year')) {
                $query->where('academic_year', $request->academic_year);
            }

            // Filter by term
            if ($request->has('term')) {
                $query->where('term', $request->term);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by fee group
            if ($request->has('fee_group_id')) {
                $query->whereHas('feeStructure', function($q) use ($request) {
                    $q->where('fee_group_id', $request->fee_group_id);
                });
            }

            // Search by student name or invoice number
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('student', function($studentQuery) use ($search) {
                        $studentQuery->where('first_name', 'like', "%{$search}%")
                                  ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhere('invoice_number', 'like', "%{$search}%");
                });
            }

            // Order by latest first
            $query->orderBy('created_at', 'desc');

            // Pagination
            $perPage = $request->get('per_page', 20);
            $payments = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $payments->items(),
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                    'last_page' => $payments->lastPage()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single payment details
     */
    public function show($id): JsonResponse
    {
        try {
            $payment = Payment::with([
                'student',
                'feeStructure.feeGroup',
                'transactions'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $payment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create new payment invoice
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'student_id' => 'required|exists:students,id',
                'fee_structure_id' => 'required|exists:fee_structures,id',
                'invoice_number' => 'required|string|max:50|unique:payments,invoice_number',
                'due_date' => 'required|date',
                'academic_year' => 'required|string|max:20',
                'term' => 'required|string|max:20'
            ]);

            // Use database transaction
            return DB::transaction(function () use ($validated) {
                $payment = Payment::create([
                    'student_id' => $validated['student_id'],
                    'fee_structure_id' => $validated['fee_structure_id'],
                    'invoice_number' => $validated['invoice_number'],
                    'due_date' => $validated['due_date'],
                    'status' => 'pending',
                    'academic_year' => $validated['academic_year'],
                    'term' => $validated['term']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment invoice created successfully',
                    'data' => $payment->load(['student', 'feeStructure.feeGroup'])
                ], 201);
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
                'message' => 'Failed to create payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add payment transaction
     */
    public function addTransaction(Request $request, $paymentId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'transaction_number' => 'required|string|max:50',
                'amount_paid' => 'required|numeric|min:0.01',
                'payment_method' => 'required|in:cash,bank_transfer,mobile_money,cheque',
                'transaction_date' => 'required|date',
                'reference_number' => 'nullable|string|max:100',
                'notes' => 'nullable|string|max:500'
            ]);

            $payment = Payment::findOrFail($paymentId);

            // Use database transaction
            return DB::transaction(function () use ($validated, $payment) {
                $transaction = Transaction::create([
                    'payment_id' => $payment->id,
                    'transaction_number' => $validated['transaction_number'],
                    'amount_paid' => $validated['amount_paid'],
                    'payment_method' => $validated['payment_method'],
                    'transaction_date' => $validated['transaction_date'],
                    'reference_number' => $validated['reference_number'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'completed'
                ]);

                // Update payment status based on balance
                $paidAmount = $payment->paid_amount + $validated['amount_paid'];
                $totalAmount = $payment->total_amount;

                if ($paidAmount >= $totalAmount) {
                    $payment->update(['status' => 'paid']);
                } elseif ($paidAmount > 0) {
                    $payment->update(['status' => 'partial']);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Payment transaction added successfully',
                    'data' => [
                        'transaction' => $transaction->load(['payment.student', 'payment.feeStructure.feeGroup']),
                        'payment' => $payment->fresh(['feeStructure.feeGroup'])
                    ]
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
                'message' => 'Failed to add transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', now()->startOfMonth());
            $endDate = $request->get('end_date', now()->endOfMonth());

            // Total revenue by fee groups
            $revenueByGroups = Transaction::with(['payment.feeStructure.feeGroup'])
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->completed()
                ->get()
                ->groupBy('payment.feeStructure.fee_group.id')
                ->map(function ($transactions, $groupId) {
                    $firstTransaction = $transactions->first();
                    return [
                        'fee_group_id' => $groupId,
                        'fee_group_name' => $firstTransaction->feeGroup->fee_group_name ?? 'Unknown',
                        'total_amount' => $transactions->sum('amount_paid'),
                        'transaction_count' => $transactions->count()
                    ];
                })->values();

            // Payment status summary
            $paymentStatusSummary = Payment::selectRaw('
                    status,
                    COUNT(*) as count,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = "partial" THEN 1 ELSE 0 END) as partial_count,
                    SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_count
                ')
                ->when($request->has('academic_year'), function ($query) use ($request) {
                    $query->where('academic_year', $request->academic_year);
                })
                ->when($request->has('term'), function ($query) use ($request) {
                    $query->where('term', $request->term);
                })
                ->first();

            // Revenue by payment methods
            $revenueByMethods = Transaction::selectRaw('
                    payment_method,
                    SUM(amount_paid) as total_amount,
                    COUNT(*) as transaction_count
                ')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->completed()
                ->groupBy('payment_method')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'revenue_by_groups' => $revenueByGroups,
                    'payment_status_summary' => $paymentStatusSummary,
                    'revenue_by_methods' => $revenueByMethods,
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student payment history
     */
    public function studentPaymentHistory($studentId): JsonResponse
    {
        try {
            $student = Student::findOrFail($studentId);

            $payments = Payment::with(['feeStructure.feeGroup', 'transactions'])
                ->where('student_id', $studentId)
                ->orderBy('created_at', 'desc')
                ->get();

            // Calculate totals
            $totalFees = $payments->sum('total_amount');
            $totalPaid = $payments->sum('paid_amount');
            $totalBalance = $payments->sum('balance_amount');

            // Group by fee groups
            $paymentsByGroups = $payments->groupBy(function ($payment) {
                return $payment->feeStructure->fee_group_id;
            })->map(function ($groupPayments, $groupId) {
                $firstPayment = $groupPayments->first();
                return [
                    'fee_group_id' => $groupId,
                    'fee_group_name' => $firstPayment->feeStructure->feeGroup->fee_group_name,
                    'total_amount' => $groupPayments->sum('total_amount'),
                    'paid_amount' => $groupPayments->sum('paid_amount'),
                    'balance_amount' => $groupPayments->sum('balance_amount'),
                    'payments' => $groupPayments
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'student' => $student,
                    'summary' => [
                        'total_fees' => $totalFees,
                        'total_paid' => $totalPaid,
                        'total_balance' => $totalBalance
                    ],
                    'payments_by_groups' => $paymentsByGroups,
                    'all_payments' => $payments
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fee structure for class level
     */
    public function feeStructure(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'class_level_id' => 'required|exists:class_levels,id',
                'academic_year' => 'nullable|string|max:20'
            ]);

            $query = FeeStructure::with('feeGroup')
                ->where('class_level_id', $validated['class_level_id'])
                ->active();

            if ($request->has('academic_year')) {
                $query->where('academic_year', $request->academic_year);
            }

            $feeStructures = $query->get();

            // Group by fee groups
            $groupedFees = $feeStructures->groupBy('fee_group_id')
                ->map(function ($structures, $groupId) {
                    $firstStructure = $structures->first();
                    return [
                        'fee_group_id' => $groupId,
                        'fee_group_name' => $firstStructure->feeGroup->fee_group_name,
                        'fee_group_type' => $firstStructure->feeGroup->type,
                        'total_amount' => $structures->sum('amount'),
                        'fees' => $structures
                    ];
                })->values();

            return response()->json([
                'success' => true,
                'data' => $groupedFees
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
                'message' => 'Failed to fetch fee structure: ' . $e->getMessage()
            ], 500);
        }
    }

    public function handleSelcomCallback(Request $request)
    {
        $orderId = $request->order_id;
        $status = $request->result; // Expect 'SUCCESS'

        if ($status === 'SUCCESS') {
            $studentId = explode('-', $orderId)[2];
            $student = Student::find($studentId);

            if ($student && $student->status === 'pending_payment') {
                $student->update(['status' => 'registered']);

                // Create the Transaction record here for the client's audit trail
                Transaction::create([
                    'student_id' => $student->id,
                    'amount' => $student->registration_fee,
                    'reference' => $request->transid,
                    'status' => 'completed'
                ]);
            }
        }
        return response()->json(['result' => 'SUCCESS']);
    }

}
