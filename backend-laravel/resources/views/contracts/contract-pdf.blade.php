<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contract - {{ $contract->contract_name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #334155;
            line-height: 1.6;
            margin: 0;
            padding: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 5px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-weight: bold;
            font-size: 14px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
            margin-bottom: 15px;
            color: #1e3a8a;
        }
        .details-grid {
            width: 100%;
            margin-bottom: 20px;
        }
        .details-grid td {
            padding: 8px 0;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            width: 35%;
            color: #64748b;
        }
        .content {
            white-space: pre-wrap;
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .footer {
            margin-top: 50px;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }
        .signature-section {
            margin-top: 60px;
            width: 100%;
        }
        .signature-box {
            width: 45%;
            display: inline-block;
            vertical-align: top;
        }
        .signature-line {
            border-top: 1px solid #334155;
            margin-top: 50px;
            padding-top: 10px;
        }
        .verified-badge {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $companyName }}</div>
        <div class="title">Employment Agreement</div>
    </div>

    <div class="section">
        <div class="section-title">Contract Information</div>
        <table class="details-grid">
            <tr>
                <td class="label">Contract ID:</td>
                <td>#{{ $contract->id }}</td>
            </tr>
            <tr>
                <td class="label">Contract Name:</td>
                <td>{{ $contract->contract_name }}</td>
            </tr>
            <tr>
                <td class="label">Contract Type:</td>
                <td>{{ $contract->contractType->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Status:</td>
                <td style="text-transform: capitalize;">{{ $contract->status }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Employee Details</div>
        <table class="details-grid">
            <tr>
                <td class="label">Employee Name:</td>
                <td>{{ $employee->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Department:</td>
                <td>{{ $employee->department->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Designation:</td>
                <td>{{ $employee->designation->name ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Term & Duration</div>
        <table class="details-grid">
            <tr>
                <td class="label">Start Date:</td>
                <td>{{ $contract->start_date }}</td>
            </tr>
            <tr>
                <td class="label">End Date:</td>
                <td>{{ $contract->end_date ?? 'Indefinite' }}</td>
            </tr>
        </table>
    </div>

    @if($contract->notes)
    <div class="section">
        <div class="section-title">Notes / Additional Terms</div>
        <div class="content">
            {{ $contract->notes }}
        </div>
    </div>
    @endif

    <div class="signature-section">
        <div class="signature-box" style="margin-right: 5%;">
            <div class="signature-line">
                <strong>Employer Signature</strong><br>
                {{ $companyName }} Representative
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <strong>Employee Signature</strong><br>
                {{ $employee->name ?? 'N/A' }}
            </div>
            @if($contract->status === 'signed')
            <div class="verified-badge">
                ✓ Digitally Signed via Mejj platform
            </div>
            <div style="font-size: 9px; color: #94a3b8; margin-top: 5px;">
                Timestamp: {{ $contract->signed_at }}<br>
                IP Address: {{ $contract->signed_ip }}<br>
                Verification: {{ $contract->verification_code }}
            </div>
            @endif
        </div>
    </div>

    <div class="footer">
        This document was automatically generated by the HR platform. Reference code: {{ $contract->verification_code ?? 'UNAVAIL' }}
    </div>
</body>
</html>
