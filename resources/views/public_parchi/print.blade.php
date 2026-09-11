<!DOCTYPE html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ووٹر پرچی - {{ $voter->name }}</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 10mm;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            margin: 0;
            padding: 10px;
            color: #000;
            background: #fff;
        }
        .slip-border {
            border: 2px solid #000;
            padding: 15px;
            border-radius: 8px;
            max-width: 550px;
            margin: 0 auto;
        }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .header-title { font-size: 20px; margin: 0 0 5px 0; }
        .sub-header { font-size: 12px; margin-bottom: 12px; border-bottom: 1px solid #000; padding-bottom: 6px; }
        .details-table { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 14px; }
        .details-table th, .details-table td { border: 1px solid #333; padding: 6px 10px; }
        .details-table th { background: #f0f0f0; width: 40%; text-align: right; }
        .highlight-box { display: flex; justify-content: space-around; margin: 10px 0; }
        .box { border: 1.5px solid #000; padding: 6px 12px; text-align: center; font-size: 15px; }
        .candidate-section { margin-top: 15px; border-top: 1px dashed #000; padding-top: 10px; font-size: 13px; }
        .candidate-grid { display: flex; justify-content: space-around; gap: 10px; margin-top: 8px; }
        .candidate-item { border: 1px solid #555; padding: 6px 10px; border-radius: 4px; text-align: center; flex: 1; }
        .footer-note { font-size: 11px; margin-top: 12px; text-align: center; border-top: 1px solid #aaa; padding-top: 6px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print text-center" style="margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 8px 16px; font-size: 14px; cursor: pointer; font-weight: bold;">🖨️ پرنٹ کریں (Print)</button>
        <button onclick="window.close()" style="padding: 8px 16px; font-size: 14px; cursor: pointer;">بند کریں (Close)</button>
    </div>

    <div class="slip-border">
        <div class="text-center">
            <h2 class="header-title">🇵🇰 انتخابی ووٹر پرچی</h2>
            <div class="sub-header">
                بلاک کوڈ: <strong>{{ $voter->blockCode ? $voter->blockCode->code : 'N/A' }}</strong> |
                یو سی: <strong>{{ $voter->uc ? $voter->uc->name : 'N/A' }}</strong> |
                تاریخ: {{ date('d-m-Y') }}
            </div>
        </div>

        <table class="details-table">
            <tr>
                <th>نام ووٹر:</th>
                <td class="fw-bold" style="font-size: 16px;">{{ $voter->name }}</td>
            </tr>
            <tr>
                <th>ولدیت / شوہر کا نام:</th>
                <td>{{ $voter->father_name ?: '—' }}</td>
            </tr>
            <tr>
                <th>قومی شناختی کارڈ نمبر:</th>
                <td class="fw-bold" style="font-family: monospace; font-size: 15px;" dir="ltr">
                    {{ \App\Models\Voter::formatCnic($voter->cnic) }}
                </td>
            </tr>
            <tr>
                <th>سلسلہ نمبر (Serial No):</th>
                <td class="fw-bold" style="font-size: 16px;">{{ $voter->silsala_no ?: '—' }}</td>
            </tr>
            <tr>
                <th>گھرانہ نمبر (Gharana No):</th>
                <td class="fw-bold">{{ $voter->gharana_no ?: '—' }}</td>
            </tr>
            <tr>
                <th>پولنگ اسٹیشن:</th>
                <td class="fw-bold">{{ $voter->pollingStation ? $voter->pollingStation->name : 'معلومات الیکشن ڈے پر دستیاب ہوں گی' }}</td>
            </tr>
            @if($voter->address)
            <tr>
                <th>پتہ / رہائش:</th>
                <td>{{ $voter->address }}</td>
            </tr>
            @endif
        </table>

        @if($candidates->isNotEmpty())
            <div class="candidate-section">
                <div class="text-center fw-bold">🗳️ ہمارا متفقہ انتخابی پینل:</div>
                <div class="candidate-grid">
                    @foreach($candidates as $c)
                        <div class="candidate-item">
                            <div class="fw-bold">{{ $c->name }}</div>
                            <small>{{ $c->party_name ?: 'امیدوار' }}</small>
                            <div style="margin-top: 4px; font-weight: bold; font-size: 14px;">
                                نشان: {{ $c->candidate_symbol ?: '—' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="footer-note">
            ووٹ ڈالنے کے لیے اصل شناختی کارڈ ہمراہ لانا لازمی ہے۔
        </div>
    </div>

</body>
</html>
