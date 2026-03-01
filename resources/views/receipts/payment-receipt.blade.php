<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Receipt - {{ $transaction->transaction_number }}</title>
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

        .receipt-container {
            background-color: #fff;
            width: 80mm;
            padding: 10mm 4mm; /* Standard padding for 80mm roll */
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            color: #000;
        }

        .line-dashed {
            border-bottom: 1px dashed #000;
            margin: 8px 0;
            width: 100%;
        }

        .total-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .total-table td {
            font-weight: bold;
            font-size: 16px;
        }

        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: sans-serif;
        }

        /* PRINT SPECIFIC RULES - This fixes the "Endless" and "Blank" issues */
        @media print {
            @page {
                size: 80mm auto; /* 'auto' height stops the printer when content ends */
                margin: 0;
            }
            body {
                background: none;
                padding: 0;
                margin: 0;
                width: 80mm;
            }
            .receipt-container {
                box-shadow: none;
                width: 72mm; /* Actual printable area for POS-80 */
                margin: 0;
                padding: 2mm;
            }
            .btn-print {
                display: none; /* Hide button on the actual receipt */
            }
        }
    </style>
</head>
<body>

    <button class="btn-print" onclick="window.print()">Print Receipt</button>

    <div class="receipt-container">
        <div style="text-align: center; font-weight: bold; font-size: 16px; line-height: 1.2;">
            *** BRITISH SCHOOL ***<br>
            DAR ES SALAAM, TANZANIA<br>
            --------------------------------
        </div>

        <div style="margin: 10px 0; font-size: 13px;">
            <div>RECEIPT: {{ $transaction->transaction_number }}</div>
            <div>DATE: {{ \Carbon\Carbon::parse($transaction->created_at)->format('d/m/Y H:i') }}</div>

            <div class="line-dashed"></div>

            <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 2px;">
                STUDENT: {{ ($student->first_name ?? 'N/A') . ' ' . ($student->last_name ?? '') }}
            </div>
            <div>ID: {{ $student->student_number ?? 'N/A' }}</div>
            <div>FEE: {{ $feeGroup->fee_group_name ?? 'N/A' }}</div>
        </div>

        <div class="line-dashed"></div>

        <table class="total-table">
            <tr>
                <td align="left">TOTAL PAID:</td>
                <td align="right">TZS {{ number_format($transaction->amount_paid, 0) }}</td>
            </tr>
        </table>

        <div class="line-dashed"></div>

        <div style="text-align: center; margin-top: 15px;">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ $transaction->transaction_number }}"
                 style="width: 40mm; height: 40mm; image-rendering: pixelated;" />
            <br>
            <span style="font-size: 11px;">SCAN TO VERIFY RECORD</span>
        </div>

        <div style="text-align: center; font-size: 11px; margin-top: 20px; line-height: 1.4;">
            *** END OF LEGAL RECEIPT ***<br>
            Thank you for your payment!<br>
            <br>
            . </div>
    </div>

</body>
</html>
