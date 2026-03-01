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
                // $student = Student::findOrFail($studentId);
                $payments = Payment::with([
                    'feeStructure.feeGroup',
                    'transactions',
                    'student',
                    'student.classLevel',
                    'student.classLevelStream'
                    ])
                // ->where('student_id', $studentId)
                ->orderBy('created_at', 'desc')
                ->get();

                return response()->json([
                    'success' => true,
                    'data' => $payments
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch payment history: ' . $e->getMessage()
                ], 500);
            }
        // try {
        //     $query = Payment::with(['student', 'feeStructure.feeGroup', 'transactions']);

        //     // Filter by student
        //     if ($request->has('student_id')) {
        //         $query->where('student_id', $request->student_id);
        //     }

        //     // Filter by academic year
        //     if ($request->has('academic_year')) {
        //         $query->where('academic_year', $request->academic_year);
        //     }

        //     // Filter by term
        //     if ($request->has('term')) {
        //         $query->where('term', $request->term);
        //     }

        //     // Filter by status
        //     if ($request->has('status')) {
        //         $query->where('status', $request->status);
        //     }

        //     // Filter by fee group
        //     if ($request->has('fee_group_id')) {
        //         $query->whereHas('feeStructure', function($q) use ($request) {
        //             $q->where('fee_group_id', $request->fee_group_id);
        //         });
        //     }

        //     // Search by student name or invoice number
        //     if ($request->has('search')) {
        //         $search = $request->search;
        //         $query->where(function ($q) use ($search) {
        //             $q->whereHas('student', function($studentQuery) use ($search) {
        //                 $studentQuery->where('first_name', 'like', "%{$search}%")
        //                           ->orWhere('last_name', 'like', "%{$search}%");
        //             })
        //             ->orWhere('invoice_number', 'like', "%{$search}%");
        //         });
        //     }

        //     // Order by latest first
        //     $query->orderBy('created_at', 'desc');

        //     // Pagination
        //     $perPage = $request->get('per_page', 20);
        //     $payments = $query->paginate($perPage);

        //     return response()->json([
        //         'success' => true,
        //         'data' => $payments->items(),
        //         'pagination' => [
        //             'current_page' => $payments->currentPage(),
        //             'per_page' => $payments->perPage(),
        //             'total' => $payments->total(),
        //             'last_page' => $payments->lastPage()
        //         ]
        //     ]);

        // } catch (\Exception $e) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Failed to fetch payments: ' . $e->getMessage()
        //     ], 500);
        // }
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
     * Add payment transaction or create payment invoice with transaction
     */
    public function addTransaction(Request $request): JsonResponse
    {
        try {
            // Validate base fields
            $validated = $request->validate([
                'payment_id' => 'nullable|exists:payments,id',
                'student_id' => 'required_without:payment_id|exists:students,id',
                'fee_structure_id' => 'required_without:payment_id|exists:fee_structures,id',
                'amount_paid' => 'required|numeric|min:0.01',
                'payment_method' => 'required|in:Cash,Bank Transfer,Selcom Pay,Bank Deposit,Cheque',
                'transaction_ref' => 'nullable|string|max:100',
                'transaction_date' => 'required_if:payment_method,Bank Transfer,Bank Deposit|date',
                'receipt_file' => 'required_if:payment_method,Bank Transfer,Bank Deposit|file|mimes:jpeg,png,jpg,pdf|max:2048'
            ]);

            // Handle file upload
            $receiptPath = null;
            if ($request->hasFile('receipt_file') && in_array($validated['payment_method'], ['Bank Transfer', 'Bank Deposit'])) {
                $file = $request->file('receipt_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $receiptPath = $file->storeAs('receipts', $filename, 'public');
            }

            // Generate reference number for cash payments
            $referenceNumber = $validated['transaction_ref'];
            if($validated['payment_method'] === 'Cash') {
                $paymentIdForRef = $validated['payment_id'] ?? 'NEW';
                $referenceNumber = 'REF-' . $paymentIdForRef . '-' . time();
            }

            // Use database transaction
            $result = DB::transaction(function () use ($validated, $receiptPath, $referenceNumber) {
                $paymentId = $validated['payment_id'] ?? null; // Handle nullable payment_id
                $existingPayment = null; // Initialize variable

                // If no payment_id, check for existing payment or create new one
                if (!$paymentId) {
                    // Check for existing payment with same student and fee structure
                    $existingPayment = Payment::where('student_id', $validated['student_id'])
                        ->where('fee_structure_id', $validated['fee_structure_id'])
                        ->first();

                    if ($existingPayment) {
                        // Use existing payment ID
                        $paymentId = $existingPayment->id;
                    } else {
                        // Create new payment invoice
                        $feeStructure = FeeStructure::findOrFail($validated['fee_structure_id']);

                        // Generate invoice number
                        $invoiceNumber = 'INV-' . $validated['student_id'] . '-' . date('Y-m-d') . '-' . rand(1000, 9999);

                        // Create payment invoice
                        $payment = Payment::create([
                            'student_id' => $validated['student_id'],
                            'fee_structure_id' => $validated['fee_structure_id'],
                            'invoice_number' => $invoiceNumber,
                            'due_date' => now()->addDays(30), // 30 days due date
                            'status' => 'partial',
                            'created_by' => auth('sanctum')->user()->id,
                        ]);

                        $paymentId = $payment->id;
                    }
                }

                // Create transaction
                $transaction = Transaction::create([
                    'payment_id' => $paymentId,
                    'transaction_number' => 'TXN-' . time(),
                    'amount_paid' => $validated['amount_paid'],
                    'payment_method' => $validated['payment_method'],
                    'transaction_ref' => $referenceNumber,
                    'transaction_date' => $validated['transaction_date'] ?? now(),
                    'receipt_file' => $receiptPath,
                    'status' => 'completed',
                    'created_by' => auth('sanctum')->user()->id,
                ]);

                // Check if payment is now fully paid
                $payment = Payment::find($paymentId);
                $totalPaid = $payment->transactions()->sum('amount_paid');
                $feeAmount = $payment->feeStructure->amount;

                if ($totalPaid >= $feeAmount) {
                    $payment->update(['status' => 'paid']);
                }

                return [
                    'transaction' => $transaction->load(['payment.student', 'payment.feeStructure.feeGroup']),
                    'payment_created' => !isset($paymentId) || ($paymentId && !$existingPayment),
                    'existing_payment_used' => isset($existingPayment) && $existingPayment
                ];
            });

            $message = $result['payment_created']
                ? 'Payment invoice created and transaction added successfully'
                : ($result['existing_payment_used']
                    ? 'Transaction added to existing payment invoice successfully'
                    : 'Payment transaction added successfully');

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'transaction' => $result['transaction'],
                    'payment_created' => $result['payment_created'],
                    'existing_payment_used' => $result['existing_payment_used']
                ]
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
                'message' => 'Failed to process transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate system receipt
     */
    public function generateReceipt($transactionId): JsonResponse
    {
        try {
            $transaction = Transaction::with(['payment.student', 'payment.feeStructure.feeGroup'])
                ->findOrFail($transactionId);

            $receiptHtml = view('receipts.payment-receipt', [
                'transaction' => $transaction,
                'student' => $transaction->payment->student,
                'feeGroup' => $transaction->payment->feeStructure->feeGroup ?? null
            ])->render();

            return response()->json([
                'success' => true,
                'data' => [
                    'receipt_html' => $receiptHtml
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate receipt: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve transaction
     */
    public function approveTransaction($transactionId): JsonResponse
    {
        try {
            $transaction = Transaction::findOrFail($transactionId);

            $transaction->verification_status = 'approved';
            $transaction->verified_by = auth('sanctum')->user()->id;
            $transaction->verified_at = now();
            $transaction->save();

            return response()->json([
                'success' => true,
                'message' => 'Transaction approved successfully',
                'data' => [
                    'transaction' => $transaction->load([
                        'payment.student',
                        'payment.feeStructure.feeGroup',
                        'verifiedBy',
                        'createdBy'
                    ])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject transaction
     */
    public function rejectTransaction(Request $request, $transactionId): JsonResponse
    {
        try {
            $request->validate([
                'rejection_reason' => 'required|string|max:255'
            ]);

            $transaction = Transaction::findOrFail($transactionId);

            $transaction->verification_status = 'rejected';
            $transaction->verified_by = auth('sanctum')->user()->id;
            $transaction->verified_at = now();
            $transaction->rejection_reason = $request->rejection_reason;
            $transaction->save();

            return response()->json([
                'success' => true,
                'message' => 'Transaction rejected successfully',
                'data' => [
                    'transaction' => $transaction->load([
                        'payment.student',
                        'payment.feeStructure.feeGroup',
                        'verifiedBy',
                        'createdBy'
                    ])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate income statement for bank use
     */
    public function generateIncomeStatement(Request $request)
    {
        try {
            $studentId = $request->get('student_id'); // null for all students
            $detailed = $request->get('detailed', false); // Flag for detailed report
            $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));

            // Build query
            $paymentsQuery = Payment::with([
                'student.classLevel',
                'student.classLevelStream',
                'feeStructure.feeGroup',
                'transactions' => function($query) use ($startDate, $endDate) {
                    $query->whereBetween('transaction_date', [$startDate, $endDate]);
                },
                'transactions.createdBy.staff',
                'transactions.verifiedBy.staff'
            ]);

            // Filter by student if specified
            if ($studentId) {
                $paymentsQuery->where('student_id', $studentId);
            }

            $payments = $paymentsQuery->get();

            // Calculate totals
            $totalAmount = 0;
            $totalPaid = 0;
            $totalBalance = 0;
            $paymentData = [];
            $allTransactions = [];

            foreach ($payments as $payment) {
                // Debug: Log payment structure
                \Log::info('Payment ID: ' . $payment->id);
                \Log::info('Fee Structure: ' . json_encode($payment->feeStructure));

                // Get fee structure amount properly
                $amount = 0;
                if ($payment->feeStructure) {
                    // Try amount field first (decimal:2 cast)
                    if (isset($payment->feeStructure->amount) && $payment->feeStructure->amount > 0) {
                        $amount = floatval($payment->feeStructure->amount);
                    }
                    // Fallback to initial_amount if amount is 0 or null
                    elseif (isset($payment->feeStructure->initial_amount) && $payment->feeStructure->initial_amount > 0) {
                        $amount = floatval($payment->feeStructure->initial_amount);
                    }
                }

                \Log::info('Calculated Amount: ' . $amount);

                // Calculate amounts by transaction status
                $approvedAmount = 0;
                $rejectedAmount = 0;
                $pendingAmount = 0;

                foreach ($payment->transactions as $transaction) {
                    $txnAmount = floatval($transaction->amount_paid);
                    switch ($transaction->verification_status) {
                        case 'approved':
                            $approvedAmount += $txnAmount;
                            break;
                        case 'rejected':
                            $rejectedAmount += $txnAmount;
                            break;
                        case 'pending':
                            $pendingAmount += $txnAmount;
                            break;
                    }

                    // Add to all transactions for detailed report
                    if ($detailed) {
                        // Debug: Log what we're getting
                        \Log::info('Transaction ID: ' . $transaction->id);
                        \Log::info('Created By: ' . json_encode($transaction->createdBy));
                        \Log::info('Verified By: ' . json_encode($transaction->verifiedBy));

                        // Safe access with multiple fallbacks
                        $createdBy = 'System';
                        if ($transaction->createdBy) {
                            if ($transaction->createdBy->staff) {
                                $createdBy = ($transaction->createdBy->staff->first_name ?? '') . ' ' . ($transaction->createdBy->staff->last_name ?? '');
                            } else {
                                $createdBy = $transaction->createdBy->name ?? 'System';
                            }
                        }

                        $verifiedBy = null;
                        if ($transaction->verifiedBy) {
                            if ($transaction->verifiedBy->staff) {
                                $verifiedBy = ($transaction->verifiedBy->staff->first_name ?? '') . ' ' . ($transaction->verifiedBy->staff->last_name ?? '');
                            } else {
                                $verifiedBy = $transaction->verifiedBy->name ?? null;
                            }
                        }

                        $allTransactions[] = [
                            'student_name' => ($payment->student->first_name ?? '') . ' ' . ($payment->student->last_name ?? ''),
                            'student_number' => $payment->student->student_number ?? '',
                            'class' => ($payment->student->classLevel->class_level_name ?? '') . ' ' . ($payment->student->classLevelStream->class_level_stream_name ?? ''),
                            'invoice_number' => $payment->invoice_number,
                            'fee_group' => $payment->feeStructure->feeGroup->fee_group_name ?? '',
                            'transaction_number' => $transaction->transaction_number,
                            'transaction_date' => $transaction->transaction_date,
                            'amount_paid' => $txnAmount,
                            'verification_status' => $transaction->verification_status,
                            'payment_method' => $transaction->payment_method,
                            'created_by' => $createdBy,
                            'verified_by' => $verifiedBy,
                            'rejection_reason' => $transaction->rejection_reason,
                            'created_at' => $transaction->created_at
                        ];
                    }
                }

                $amountPaid = $approvedAmount; // Only approved amounts count as paid
                $balance = $amount - $amountPaid;

                $totalAmount += $amount;
                $totalPaid += $amountPaid;
                $totalBalance += $balance;

                $paymentData[] = [
                    'student_name' => ($payment->student->first_name ?? '') . ' ' . ($payment->student->last_name ?? ''),
                    'student_number' => $payment->student->student_number ?? '',
                    'class' => ($payment->student->classLevel->class_level_name ?? '') . ' ' . ($payment->student->classLevelStream->class_level_stream_name ?? ''),
                    'invoice_number' => $payment->invoice_number,
                    'fee_group' => $payment->feeStructure->feeGroup->fee_group_name ?? '',
                    'amount' => $amount,
                    'approved_amount' => $approvedAmount,
                    'rejected_amount' => $rejectedAmount,
                    'pending_amount' => $pendingAmount,
                    'amount_paid' => $amountPaid,
                    'balance' => $balance,
                    'status' => $balance <= 0 ? 'paid' : ($amountPaid > 0 ? 'partial' : 'unpaid'),
                    'created_at' => $payment->created_at
                ];
            }

            $data = [
                'statement_type' => $studentId ? 'Student Income Statement' : 'All Students Income Statement',
                'detailed' => $detailed,
                'student' => $studentId ? $payments->first()->student : null,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'generated_on' => now()->format('Y-m-d H:i:s')
                ],
                'summary' => [
                    'total_students' => $studentId ? 1 : $payments->pluck('student_id')->unique()->count(),
                    'total_invoices' => $payments->count(),
                    'total_amount' => $totalAmount,
                    'total_paid' => $totalPaid,
                    'total_balance' => $totalBalance,
                    'collection_rate' => $totalAmount > 0 ? ($totalPaid / $totalAmount) * 100 : 0
                ],
                'payments' => $paymentData,
                'transactions' => $allTransactions
            ];

            // Return Blade view directly for bank printing
            if ($detailed) {
                return view('statements.detailed-income-statement', $data);
            } else {
                return view('statements.income-statement', $data);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate income statement: ' . $e->getMessage()
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

    public function studentPayments($studentId): JsonResponse
    {
        try {
                $student = Student::findOrFail($studentId);
                $payments = Payment::with([
                    'feeStructure.feeGroup',
                    'transactions',
                    'transactions.createdBy',
                    'transactions.verifiedBy',
                    ])
                ->where('student_id', $studentId)
                ->orderBy('created_at', 'desc')
                ->get();

                return response()->json([
                    'success' => true,
                    'data' => $payments
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch payment history: ' . $e->getMessage()
                ], 500);
            }
    }

    public function studentTransactions($studentId): JsonResponse
    {
        try {
                $student = Student::findOrFail($studentId);
                $payments = Transaction::with([
                    'payment',
                    'payment.feeStructure.feeGroup',
                    'createdBy',
                    'verifiedBy'
                    ])
                ->whereHas('payment', function($query) use ($studentId) {
                    $query->where('student_id', $studentId);
                })
                ->orderBy('created_at', 'desc')
                ->get();

                return response()->json([
                    'success' => true,
                    'data' => $payments
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch payment history: ' . $e->getMessage()
                ], 500);
            }
    }

    public function studentsTransactions(): JsonResponse
    {
        try {
                $payments = Transaction::with([
                    'payment',
                    'payment.feeStructure.feeGroup',
                    'payment.student',
                    'payment.student.classLevel',
                    'payment.student.classLevelStream',
                    'createdBy',
                    'verifiedBy'
                    ])
                ->orderBy('created_at', 'desc')
                ->get();

                return response()->json([
                    'success' => true,
                    'data' => $payments
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch payment history: ' . $e->getMessage()
                ], 500);
            }
    }

    public function getPaymentStats(): JsonResponse
    {
        // Use static method for total expected (returns value, not query)
        $expected = Payment::getTotalExpected();

        // Use scopes for filtering transactions (return query builders)
        $approved = (float) Transaction::approved()->sum('amount_paid');
        $rejected = (float) Transaction::rejected()->sum('amount_paid');
        $pendingApproval = (float) Transaction::pending()->sum('amount_paid');
        $completed = (float) Transaction::sum('amount_paid');
        $daily = (float) Transaction::daily()->sum('amount_paid');
        $balance = $expected - $completed;

        return response()->json([
            'success' => true,
            'data' => [
                'expected' => $expected,
                'approved' => $approved,
                'rejected' => $rejected,
                'pendingApproval' => $pendingApproval,
                'completed' => $completed,
                'daily' => $daily,
                'balance' => $balance,
                'collectionRate' => $expected > 0 ? round(($completed / $expected) * 100, 2) : 0
            ],
            'message' => 'Payment statistics retrieved successfully'
        ]);
    }

    /**
     * Generate student statement
     */
    public function generateStudentStatement(Request $request, $id)
    {
        try {
            $student = Student::with([
                'classLevel',
                'classLevelStream'
            ])->find($id);

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found with ID: ' . $id
                ], 404);
            }

            $payments = Payment::with([
                'feeStructure.feeGroup',
                'transactions' => function($query) {
                    $query->with(['createdBy', 'verifiedBy']);
                }
            ])
            ->where('student_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

            // Calculate totals
            $totalAmount = $payments->sum(function($payment) {
                return $payment->fee_structure ? $payment->fee_structure->amount : 0;
            });

            $totalPaid = $payments->sum(function($payment) {
                return $payment->transactions->sum(function($transaction) {
                    return $transaction->verification_status === 'approved' ? $transaction->amount_paid : 0;
                });
            });

            $balance = $totalAmount - $totalPaid;

            // Prepare data for Blade view
            $data = [
                'student' => [
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'student_number' => $student->student_number,
                    'class_level' => $student->classLevel ? $student->classLevel->class_level_name : 'N/A',
                    'class_level_stream' => $student->classLevelStream ? $student->classLevelStream->class_level_stream_name : 'N/A',
                ],
                'payments' => $payments->toArray(),
                'totalAmount' => $totalAmount,
                'totalPaid' => $totalPaid,
                'balance' => $balance,
                'generated_date' => date('Y-m-d H:i:s')
            ];

            // Return Blade view directly
            return view('statements.student-statement', $data);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate student statement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate statement PDF
     */
    private function generateStatementPDF($student, $payments, $totalAmount, $totalPaid, $balance)
    {
        $pdf = new \FPDF();
        $pdf->AddPage();

        // Header
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Student Fee Statement', 0, 1, 'C');
        $pdf->Ln(5);

        // Student Information
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, 'Name: ' . $student->first_name . ' ' . $student->last_name, 0, 1);
        $pdf->Cell(0, 8, 'Student Number: ' . $student->student_number, 0, 1);
        $pdf->Cell(0, 8, 'Class: ' . ($student->classLevel->class_level_name ?? '') . ' ' . ($student->classLevelStream->class_level_stream_name ?? ''), 0, 1);
        $pdf->Ln(10);

        // Summary
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Payment Summary', 0, 1);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, 'Total Amount: TZS ' . number_format($totalAmount, 0), 0, 1);
        $pdf->Cell(0, 8, 'Total Paid: TZS ' . number_format($totalPaid, 0), 0, 1);
        $pdf->Cell(0, 8, 'Balance: TZS ' . number_format($balance, 0), 0, 1);
        $pdf->Ln(10);

        // Payment Details
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Payment Details', 0, 1);
        $pdf->Ln(5);

        // Table headers
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(40, 8, 'Invoice', 1);
        $pdf->Cell(50, 8, 'Fee Group', 1);
        $pdf->Cell(30, 8, 'Amount', 1);
        $pdf->Cell(30, 8, 'Paid', 1);
        $pdf->Cell(30, 8, 'Balance', 1);
        $pdf->Ln();

        // Payment rows
        $pdf->SetFont('Arial', '', 10);
        foreach ($payments as $payment) {
            $amount = $payment->fee_structure ? $payment->fee_structure->amount : 0;
            $paid = $payment->transactions->sum(function($transaction) {
                return $transaction->verification_status === 'approved' ? $transaction->amount_paid : 0;
            });
            $paymentBalance = $amount - $paid;

            $pdf->Cell(40, 8, $payment->invoice_number, 1);
            $pdf->Cell(50, 8, $payment->fee_structure->fee_group->fee_group_name ?? '', 1);
            $pdf->Cell(30, 8, number_format($amount, 0), 1);
            $pdf->Cell(30, 8, number_format($paid, 0), 1);
            $pdf->Cell(30, 8, number_format($paymentBalance, 0), 1);
            $pdf->Ln();
        }

        // Footer
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 8, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');

        return $pdf;
    }

    /**
     * Get fee structure for all class level
     */

    public function getFeeStructure()
    {
        $feeStructures = FeeStructure::with(['feeGroup', 'classLevel'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $feeStructures
        ]);
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

    public function classLevelFeeStructure($classLevelId)
    {
        $feeStructures = FeeStructure::with([
            'feeGroup'
        ])->where('class_level_id', $classLevelId)->get();

        return response()->json([
            'success' => true,
            'data' => $feeStructures
        ]);
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

    // Fee Groups
    public function getFeeGroups(Request $request): JsonResponse
    {
        $query = FeeGroup::query();

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('fee_group_name', 'like', "%{$search}%");
        }

        // Order by name
        $query->orderBy('fee_group_name');

        // Pagination
        $perPage = $request->get('per_page', 10);
        $feeGroups = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $feeGroups->items(),
            'pagination' => [
                'current_page' => $feeGroups->currentPage(),
                'per_page' => $feeGroups->perPage(),
                'total' => $feeGroups->total(),
                'last_page' => $feeGroups->lastPage()
            ]
        ]);
    }

    public function generateControlNumber(Request $request)
    {
        $payment = Payment::find($request->paymentId);
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        // generate control number

        $invoiceData = [
            'payment_id' => $request->paymentId,
            'student_name' => $payment->student->first_name . ' ' . $payment->student->last_name,
            'student_number' => $payment->student->student_number,
            'amount' => $payment->feeStructure->amount,
            'type' => 'Fee',
        ];

        $response = AppHelper::instance()->sendNMBInvoice($invoiceData);

        if($response['status'] === 'success'){
            $controlNumber = 'SAS953' . str_pad($payment->paymentId, 4, '0', STR_PAD_LEFT);
            $payment->update(['control_number' => $controlNumber]);

            return response()->json([
                'success' => true,
                'message' => 'Control number generated successfully',
                'data' => $payment
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Control number not generated successfully',
                'data' => $payment
            ]);
        }
    }


}
