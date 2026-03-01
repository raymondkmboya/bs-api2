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
            .payments-table {
                page-break-inside: auto;
            }
            .payments-table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>

    <button class="btn-print" onclick="window.print()">Print Income Statement</button>

    <div class="statement-container">
        <!-- Header -->
        <div class="header">
            <h1>{{ $statement_type }}</h1>
            <p><strong>BRITISH SCHOOL</strong></p>
            <p>DAR ES SALAAM, TANZANIA</p>
            <p>BANK INCOME STATEMENT</p>
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

        <!-- Payment Details -->
        <h2>DETAILED PAYMENT RECORDS</h2>
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

        <!-- Footer -->
        <div class="footer">
            <p><strong>BRITISH SCHOOL - BANK INCOME STATEMENT</strong></p>
            <p>This statement includes only approved transactions within the specified period.</p>
            <p>Generated on: {{ $period['generated_on'] }}</p>
            <p>© {{ date('Y') }} British School. All rights reserved.</p>
        </div>
    </div>

</body>
</html>
