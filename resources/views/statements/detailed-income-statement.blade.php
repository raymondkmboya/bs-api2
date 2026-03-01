<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $statement_type }} - British School</title>
    <style>
        /* General styling for browser view */
        body {
            background-color: #f5f5f5;
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }

        .statement-container {
            background-color: #fff;
            width: 210mm; /* A4 width */
            max-width: 210mm;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header p {
            margin: 5px 0;
            font-size: 14px;
        }

        .info-section {
            margin-bottom: 25px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            padding: 10px;
            border: 1px solid #ddd;
            background: #f9f9f9;
        }

        .info-item strong {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        .summary-box {
            background: #f0f0f0;
            border: 2px solid #000;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 15px;
        }

        .summary-item {
            text-align: center;
            padding: 15px;
            background: white;
            border: 1px solid #333;
        }

        .summary-item h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: bold;
        }

        .summary-item .amount {
            font-size: 18px;
            font-weight: bold;
            color: #000;
        }

        .payments-section {
            margin: 25px 0;
        }

        .payments-table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            border: 1px solid #000;
        }

        .payments-table th {
            background: #333;
            color: white;
            text-align: left;
            padding: 12px 8px;
            font-weight: bold;
            font-size: 12px;
            border: 1px solid #000;
        }

        .payments-table td {
            padding: 10px 8px;
            border: 1px solid #000;
            font-size: 11px;
            vertical-align: top;
        }

        .payments-table .amount {
            text-align: right;
            font-weight: bold;
        }

        .payments-table .status {
            text-align: center;
            font-weight: bold;
        }

        .status.paid { color: #22c55e; }
        .status.partial { color: #f59e0b; }
        .status.unpaid { color: #ef4444; }
        .status.approved { color: #22c55e; font-weight: bold; }
        .status.rejected { color: #ef4444; font-weight: bold; }
        .status.pending { color: #f59e0b; font-weight: bold; }

        .transactions-section {
            margin: 30px 0;
            page-break-inside: avoid;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            border: 1px solid #000;
            font-size: 10px;
        }

        .transactions-table th {
            background: #444;
            color: white;
            text-align: left;
            padding: 8px 6px;
            font-weight: bold;
            font-size: 10px;
            border: 1px solid #000;
        }

        .transactions-table td {
            padding: 8px 6px;
            border: 1px solid #000;
            font-size: 10px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #000;
            text-align: center;
            font-size: 12px;
            color: #666;
        }

        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: sans-serif;
            font-size: 14px;
        }

        /* PRINT SPECIFIC RULES */
        @media print {
            @page {
                size: A4 portrait;
                margin: 15mm;
            }
            body {
                background: none;
                padding: 0;
                margin: 0;
            }
            .statement-container {
                box-shadow: none;
                width: 100%;
                margin: 0;
                padding: 0;
            }
            .btn-print {
                display: none;
            }
            .payments-section {
                page-break-inside: auto;
            }
            .transactions-section {
                page-break-inside: auto;
            }
            .payments-table, .transactions-table {
                page-break-inside: auto;
            }
            .payments-table tr, .transactions-table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>

    <button class="btn-print" onclick="window.print()">Print Detailed Statement</button>

    <div class="statement-container">
        <!-- Header -->
        <div class="header">
            <h1>{{ $statement_type }}</h1>
            <p><strong>BRITISH SCHOOL</strong></p>
            <p>DAR ES SALAAM, TANZANIA</p>
            <p>DETAILED INCOME STATEMENT WITH TRANSACTIONS</p>
        </div>

        <!-- Period Information -->
        <div class="info-section">
            <div class="info-grid">
                <div class="info-item">
                    <strong>Period Start:</strong>
                    {{ \Carbon\Carbon::parse($period['start_date'])->format('d M Y') }}
                </div>
                <div class="info-item">
                    <strong>Period End:</strong>
                    {{ \Carbon\Carbon::parse($period['end_date'])->format('d M Y') }}
                </div>
                <div class="info-item">
                    <strong>Generated On:</strong>
                    {{ \Carbon\Carbon::parse($period['generated_on'])->format('d M Y H:i') }}
                </div>
                <div class="info-item">
                    <strong>Statement Type:</strong>
                    {{ $statement_type }}
                </div>
            </div>

            @if($student)
            <div class="info-item" style="grid-column: 1 / -1;">
                <strong>Student Information:</strong>
                {{ $student->first_name }} {{ $student->last_name }} ({{ $student->student_number }})
                - {{ $student->classLevel->class_level_name ?? '' }} {{ $student->classLevelStream->class_level_stream_name ?? '' }}
            </div>
            @endif
        </div>

        <!-- Summary -->
        <div class="summary-box">
            <h2>FINANCIAL SUMMARY</h2>
            <div class="summary-grid">
                <div class="summary-item">
                    <h3>Total Students</h3>
                    <div class="amount">{{ $summary['total_students'] }}</div>
                </div>
                <div class="summary-item">
                    <h3>Total Invoices</h3>
                    <div class="amount">{{ $summary['total_invoices'] }}</div>
                </div>
                <div class="summary-item">
                    <h3>Total Amount</h3>
                    <div class="amount">TZS {{ number_format($summary['total_amount'], 0) }}</div>
                </div>
                <div class="summary-item">
                    <h3>Total Paid</h3>
                    <div class="amount">TZS {{ number_format($summary['total_paid'], 0) }}</div>
                </div>
                <div class="summary-item">
                    <h3>Total Balance</h3>
                    <div class="amount">TZS {{ number_format($summary['total_balance'], 0) }}</div>
                </div>
                <div class="summary-item">
                    <h3>Collection Rate</h3>
                    <div class="amount">{{ number_format($summary['collection_rate'], 1) }}%</div>
                </div>
            </div>
        </div>

        <!-- Payment Summary -->
        <div class="payments-section">
            <h2>PAYMENT SUMMARY</h2>
            <table class="payments-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Student Number</th>
                        <th>Class</th>
                        <th>Invoice #</th>
                        <th>Fee Group</th>
                        <th>Total Amount</th>
                        <th>Approved</th>
                        <th>Rejected</th>
                        <th>Pending</th>
                        <th>Amount Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                    <tr>
                        <td>{{ $payment['student_name'] }}</td>
                        <td>{{ $payment['student_number'] }}</td>
                        <td>{{ $payment['class'] }}</td>
                        <td>{{ $payment['invoice_number'] }}</td>
                        <td>{{ $payment['fee_group'] }}</td>
                        <td class="amount">TZS {{ number_format($payment['amount'], 0) }}</td>
                        <td class="amount status paid">TZS {{ number_format($payment['approved_amount'], 0) }}</td>
                        <td class="amount status rejected">TZS {{ number_format($payment['rejected_amount'], 0) }}</td>
                        <td class="amount status pending">TZS {{ number_format($payment['pending_amount'], 0) }}</td>
                        <td class="amount">TZS {{ number_format($payment['amount_paid'], 0) }}</td>
                        <td class="amount">TZS {{ number_format($payment['balance'], 0) }}</td>
                        <td class="status {{ $payment['status'] }}">
                            {{ strtoupper($payment['status']) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Detailed Transactions -->
        @if($detailed && !empty($transactions))
        <div class="transactions-section">
            <h2>DETAILED PAYMENTS WITH TRANSACTIONS</h2>

            @foreach($payments as $payment)
            <div class="payment-group" style="margin-bottom: 30px; page-break-inside: avoid;">
                <!-- Payment Header -->
                <div class="payment-header" style="background: #f0f0f0; border: 1px solid #000; padding: 15px; margin-bottom: 10px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px;">
                        <div>
                            <strong>Student:</strong> {{ $payment['student_name'] }}<br>
                            <strong>Student #:</strong> {{ $payment['student_number'] }}<br>
                            <strong>Class:</strong> {{ $payment['class'] }}
                        </div>
                        <div>
                            <strong>Invoice #:</strong> {{ $payment['invoice_number'] }}<br>
                            <strong>Fee Group:</strong> {{ $payment['fee_group'] }}<br>
                            <strong>Status:</strong> <span class="status {{ $payment['status'] }}">{{ strtoupper($payment['status']) }}</span>
                        </div>
                        <div>
                            <strong>Total Amount:</strong> TZS {{ number_format($payment['amount'], 0) }}<br>
                            <strong>Amount Paid:</strong> TZS {{ number_format($payment['amount_paid'], 0) }}<br>
                            <strong>Balance:</strong> TZS {{ number_format($payment['balance'], 0) }}
                        </div>
                        <div>
                            <strong>Approved:</strong> <span class="status approved">TZS {{ number_format($payment['approved_amount'], 0) }}</span><br>
                            <strong>Rejected:</strong> <span class="status rejected">TZS {{ number_format($payment['rejected_amount'], 0) }}</span><br>
                            <strong>Pending:</strong> <span class="status pending">TZS {{ number_format($payment['pending_amount'], 0) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Transactions for this payment -->
                @php
                    $paymentTransactions = collect($transactions)->where('invoice_number', $payment['invoice_number'])->all();
                @endphp

                @if(!empty($paymentTransactions))
                <div class="transactions-for-payment" style="margin-left: 20px;">
                    <h4 style="margin-bottom: 10px; color: #333;">Transactions for Invoice #{{ $payment['invoice_number'] }}</h4>
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Transaction #</th>
                                <th>Date</th>
                                <th>Amount Paid</th>
                                <th>Status</th>
                                <th>Payment Method</th>
                                <th>Created By</th>
                                <th>Verified By</th>
                                <th>Rejection Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paymentTransactions as $transaction)
                            <tr>
                                <td>{{ $transaction['transaction_number'] }}</td>
                                <td>{{ \Carbon\Carbon::parse($transaction['transaction_date'])->format('d M Y') }}</td>
                                <td class="amount">TZS {{ number_format($transaction['amount_paid'], 0) }}</td>
                                <td class="status {{ $transaction['verification_status'] }}">
                                    {{ strtoupper($transaction['verification_status']) }}
                                </td>
                                <td>{{ $transaction['payment_method'] ?? '-' }}</td>
                                <td>{{ $transaction['created_by'] }}</td>
                                <td>{{ $transaction['verified_by'] ?? '-' }}</td>
                                <td>{{ $transaction['rejection_reason'] ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="no-transactions" style="margin-left: 20px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; color: #856404;">
                    <em>No transactions found for this payment in the selected period.</em>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p><strong>BRITISH SCHOOL - DETAILED INCOME STATEMENT</strong></p>
            <p>This statement includes all transactions within the specified period with detailed breakdown.</p>
            <p>Generated on: {{ $period['generated_on'] }}</p>
            <p>© {{ date('Y') }} British School. All rights reserved.</p>
        </div>
    </div>

</body>
</html>
