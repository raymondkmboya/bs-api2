<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration Form - {{ $student['full_name'] }}</title>
    <style>
        @page { size: A4; margin: 1cm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; line-height: 1.4; }

        /* Header Layout */
        .header-table { width: 100%; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .logo-cell { width: 20%; text-align: center; }
        .info-cell { width: 80%; padding-left: 20px; }
        .school-name { font-size: 22px; font-weight: bold; margin: 0; color: #000; }
        .school-details { font-size: 11px; margin: 5px 0; }

        /* Section Styling matching the image */
        .section-title {
            background-color: #000;
            color: #fff;
            padding: 6px 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 15px;
            letter-spacing: 1px;
        }

        /* Form Grid */
        .row { display: flex; width: 100%; border-bottom: 1px solid #eee; padding: 8px 0; }
        .col { flex: 1; display: flex; align-items: baseline; }
        .label { font-weight: bold; margin-right: 8px; color: #555; white-space: nowrap; }
        .value { border-bottom: 1px dotted #999; flex-grow: 1; min-height: 15px; text-transform: uppercase; font-weight: 500; }

        /* Checkbox Styling */
        .check-container { display: flex; gap: 15px; margin: 10px 0; font-weight: bold; }
        .check-item { display: flex; align-items: center; gap: 5px; }
        .box { width: 12px; height: 12px; border: 1px solid #000; display: inline-block; }

        /* Footer / Office Use */
        .office-box { border: 2px solid #000; margin-top: 30px; padding: 15px; }
        .print-btn {
            position: fixed; top: 20px; right: 20px; background: #d32f2f; color: white;
            padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;
        }

        @media print {
            .print-btn { display: none; }
            body { background: none; }
            .section-title { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">PRINT REGISTRATION FORM</button>

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="https://academic.britishschool.sc.tz/storage/images/logo.png" width="100">
            </td>
            <td class="info-cell">
                <h1 class="school-name">THE BRITISH SCHOOL</h1>
                <p class="school-details">
                    <b>REGISTRATION FORM</b><br>
                    P.O Box 32526, Dar es Salaam | Tel: 0655 400 420 / 0755 400 420<br>
                    Email: info@britishschool.sc.tz | Location: Mwenge-ITV
                </p>
            </td>
        </tr>
    </table>

    <div class="content">
        <p style="font-style: italic; font-size: 11px; text-align: center;">
            Jaza fomu hii kwa usahihi baada ya kusoma na kuelewa yaliyoandikwa kwenye fomu ya maelekezo.
        </p>

        <div class="section-title">Student Information (Taarifa za Mwanafunzi)</div>

        <div class="row">
            <div class="col" style="flex: 2;">
                <span class="label">Student Name:</span>
                <span class="value">{{ $student['full_name'] }}</span>
            </div>
            <div class="col">
                <span class="label">ID#:</span>
                <span class="value">{{ $student['student_number'] ?? '................' }}</span>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <span class="label">Date of Birth:</span>
                <span class="value">{{ $student['date_of_birth'] }}</span>
            </div>
            <div class="col">
                <span class="label">Gender:</span>
                <span class="value">{{ $student['gender'] }}</span>
            </div>
            <div class="col">
                <span class="label">Region:</span>
                <span class="value">{{ $student['region'] }}</span>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <span class="label">Current Residence:</span>
                <span class="value">{{ $student['address'] }}</span>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <span class="label">Previous School:</span>
                <span class="value">{{ $student['previous_school'] ?? '................................' }}</span>
            </div>
        </div>

        <div class="section-title">Contact Information (Taarifa za Mlezi)</div>

        <div class="row">
            <div class="col" style="flex: 2;">
                <span class="label">Parent/Guardian:</span>
                <span class="value">{{ $parent['name'] }}</span>
            </div>
            <div class="col">
                <span class="label">Relationship:</span>
                <span class="value">{{ $parent['relationship'] }}</span>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <span class="label">Phone Number:</span>
                <span class="value">{{ $parent['phone'] }}</span>
            </div>
            <div class="col">
                <span class="label">Email Address:</span>
                <span class="value">{{ $parent['email'] }}</span>
            </div>
        </div>

        <div class="section-title">Program & Medical Information</div>

        <div class="check-container">
            <span class="label">Session Time:</span>
            <div class="check-item"><div class="box"></div> Morning</div>
            <div class="check-item"><div class="box"></div> Afternoon</div>
            <div class="check-item"><div class="box"></div> Evening</div>
        </div>

        <div class="row">
            <div class="col">
                <span class="label">Chronic Disease/Disability:</span>
                <span class="value">................................................................................................</span>
            </div>
        </div>

        <div class="section-title">Payment Information</div>
        <div class="row">
            <div class="col">
                <span class="label">Registration Fee:</span>
                <span class="value">TSh {{ $payment['initial_amount'] }}/=</span>
            </div>
            <div class="col">
                <span class="label">Control Number:</span>
                <span class="value">{{ $payment['control_number'] }}</span>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <span class="label">Bank:</span>
                <span class="value">{{ $payment['bank_name'] }}</span>
            </div>
            <div class="col">
                <span class="label">Account Name:</span>
                <span class="value">{{ $payment['account_name'] }}</span>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <span class="label">Account Number:</span>
                <span class="value">{{ $payment['account_number'] }}</span>
            </div>
        </div>

        <div class="office-box">
            <div style="font-weight: bold; margin-bottom: 10px; text-decoration: underline;">FOR OFFICE USE ONLY</div>
            <div class="row">
                <div class="col"><span class="label">Verified By:</span> <span class="value"></span></div>
                <div class="col"><span class="label">Signature:</span> <span class="value"></span></div>
                <div class="col"><span class="label">Date:</span> <span class="value"></span></div>
            </div>
            <div class="row" style="margin-top: 10px; border: none;">
                <div class="col">
                    <span class="label">Principal's Remarks:</span>
                    <span class="value" style="height: 40px; border-bottom: 1px solid #000;"></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.onafterprint = function() { window.close(); };
    </script>
</body>
</html>
