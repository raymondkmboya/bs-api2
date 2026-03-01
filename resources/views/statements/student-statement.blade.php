<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Fee Statement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .school-info {
            text-align: center;
            margin-bottom: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .summary {
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .summary-item {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
        }
        .summary-item.total {
            background: #e3f2fd;
            color: white;
        }
        .summary-item.paid {
            background: #10b981;
            color: white;
        }
        .summary-item.balance {
            background: {{ $balance > 0 ? '#ef4444' : '#22c55e' }};
            color: white;
        }
        .payments-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .payments-table th {
            background: #4f46e5;
            color: white;
            text-align: left;
            padding: 12px;
            font-weight: bold;
        }
        .payments-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }
        .payments-table tr:last-child td {
            border-bottom: none;
        }
        .fee-group {
            background: #f3f4f6;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .amount {
            font-weight: bold;
            color: #1f2937;
        }
        .amount.positive {
            color: #059669;
        }
        .amount.negative {
            color: #dc3545;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: #6c757d;
            font-size: 14px;
        }
        .print-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin: 20px auto;
            display: block;
        }
        @media print {
            body { margin: 0; }
            .print-btn { display: none; }
            .header { page-break-after: always; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>Student Fee Statement</h1>
        <p>British School Management System</p>
    </div>

    <!-- Student Information -->
    <div class="school-info">
        <h2>Student Information</h2>
        <p><strong>Name:</strong> {{ $student['first_name'] ?? '' }} {{ $student['last_name'] ?? '' }}</p>
        <p><strong>Student Number:</strong> {{ $student['student_number'] ?? '' }}</p>
        <p><strong>Class:</strong> {{ $student['class_level']['class_level_name'] ?? '' }} {{ $student['class_level_stream']['class_level_stream_name'] ?? '' }}</p>
    </div>

    <!-- Payment Summary -->
    <div class="summary">
        <h2>Payment Summary</h2>
        <div class="summary-grid">
            <div class="summary-item total">
                <h3>Total Amount</h3>
                <p class="amount">TZS {{ number_format($totalAmount, 0) }}</p>
            </div>
            <div class="summary-item paid">
                <h3>Total Paid</h3>
                <p class="amount">TZS {{ number_format($totalPaid, 0) }}</p>
            </div>
            <div class="summary-item balance">
                <h3>Balance</h3>
                <p class="amount">TZS {{ number_format($balance, 0) }}</p>
            </div>
        </div>
    </div>

    <!-- Payment Details -->
    <div class="payments-section">
        <h2>Payment Details</h2>
        <table class="payments-table">
            <thead>
                <tr>
                    <th>Invoice Number</th>
                    <th>Fee Group</th>
                    <th>Amount</th>
                    <th>Amount Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                <tr>
                    <td>{{ $payment['invoice_number'] ?? '' }}</td>
                    <td>
                        <span class="fee-group">{{ $payment['fee_structure']['fee_group']['fee_group_name'] ?? '' }}</span>
                    </td>
                    <td class="amount">TZS {{ number_format($payment['fee_structure']['amount'] ?? 0, 0) }}</td>
                    <td class="amount positive">TZS {{ number_format(array_sum(array_column($payment['transactions'], 'amount_paid')), 0) }}</td>
                    <td class="amount {{ $payment['fee_structure'] && ($payment['fee_structure']['amount'] - array_sum(array_column($payment['transactions'], 'amount_paid')) > 0 ? 'negative' : 'positive' }}">
                        TZS {{ number_format($payment['fee_structure'] ? $payment['fee_structure']['amount'] - array_sum(array_column($payment['transactions'], 'amount_paid')) : 0, 0) }}
                    </td>
                    <td>
                        @if($payment['status'] === 'paid')
                            <span style="color: #22c55e; font-weight: bold;">PAID</span>
                        @elseif($payment['status'] === 'partial')
                            <span style="color: #f59e0b; font-weight: bold;">PARTIAL</span>
                        @else
                            <span style="color: #ef4444; font-weight: bold;">UNPAID</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
        <p>© {{ date('Y') }} British School. All rights reserved.</p>
    </div>

    <!-- Print Button -->
    <button class="print-btn" onclick="window.print()">Print Statement</button>
</body>
</html>
