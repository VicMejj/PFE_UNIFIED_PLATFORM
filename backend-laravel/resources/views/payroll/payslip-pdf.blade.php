<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip #{{ $paySlip->id }} — {{ $periodLabel }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #fff;
            line-height: 1.5;
        }

        /* ── Page layout ── */
        .page { padding: 40px 48px; max-width: 820px; margin: 0 auto; }

        /* ── Header ── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 28px;
        }
        .company-name {
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: -0.5px;
        }
        .company-meta { font-size: 10px; color: #64748b; margin-top: 4px; line-height: 1.6; }

        .payslip-badge {
            text-align: right;
        }
        .payslip-badge .label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #2563eb;
        }
        .payslip-badge .ref {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
        }
        .payslip-badge .period {
            display: inline-block;
            margin-top: 6px;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            border-radius: 20px;
            padding: 3px 12px;
            font-size: 10px;
            font-weight: 600;
        }

        /* ── Employee info grid ── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            margin-bottom: 28px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }
        .info-section { padding: 16px 20px; }
        .info-section:first-child { border-right: 1px solid #e2e8f0; background: #f8fafc; }
        .info-section h3 {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #94a3b8;
            margin-bottom: 10px;
        }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 6px; }
        .info-label { color: #64748b; font-size: 10.5px; }
        .info-value { font-weight: 600; color: #1e293b; font-size: 10.5px; }

        /* ── Salary table ── */
        .salary-section { margin-bottom: 28px; }
        .salary-section h3 {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #94a3b8;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead th {
            background: #1e293b;
            color: #f1f5f9;
            padding: 9px 14px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        thead th:last-child { text-align: right; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:hover { background: #f1f5f9; }
        tbody td {
            padding: 9px 14px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
            color: #334155;
        }
        tbody td:last-child { text-align: right; font-weight: 500; }

        .positive { color: #16a34a; }
        .negative { color: #dc2626; }

        /* ── Summary row ── */
        .summary-row {
            background: #eff6ff !important;
            border-top: 2px solid #2563eb !important;
        }
        .summary-row td {
            font-weight: 700 !important;
            color: #1e3a8a !important;
            font-size: 12px !important;
        }

        /* ── Net payable box ── */
        .net-box {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: #fff;
            border-radius: 12px;
            padding: 20px 28px;
            margin-bottom: 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .net-box .net-label { font-size: 13px; font-weight: 500; opacity: 0.85; }
        .net-box .net-amount { font-size: 28px; font-weight: 800; letter-spacing: -0.5px; }
        .net-box .net-period { font-size: 10px; opacity: 0.7; margin-top: 2px; }
        .net-box .net-status {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
        }

        /* ── Footer ── */
        .footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
            margin-top: 8px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .footer-note { font-size: 9px; color: #94a3b8; line-height: 1.7; max-width: 60%; }
        .signature-box { text-align: center; width: 180px; }
        .signature-line {
            border-top: 1px solid #cbd5e1;
            margin-top: 40px;
            padding-top: 6px;
            font-size: 9px;
            color: #94a3b8;
        }

        /* ── Watermark for draft ── */
        @if($isDraft)
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 96px;
            font-weight: 900;
            color: rgba(203, 213, 225, 0.35);
            pointer-events: none;
            text-transform: uppercase;
            letter-spacing: 8px;
            white-space: nowrap;
        }
        @endif
    </style>
</head>
<body>
    @if($isDraft)
    <div class="watermark">DRAFT</div>
    @endif

    <div class="page">
        <!-- ── Header ── -->
        <div class="header">
            <div>
                <div class="company-name">{{ $companyName }}</div>
                <div class="company-meta">
                    Payroll &amp; Compensation Department<br>
                    Generated: {{ now()->format('d M Y, H:i') }}
                </div>
            </div>
            <div class="payslip-badge">
                <div class="label">Payslip</div>
                <div class="ref">#{{ str_pad($paySlip->id, 5, '0', STR_PAD_LEFT) }}</div>
                <div class="period">{{ $periodLabel }}</div>
            </div>
        </div>

        <!-- ── Employee & Payroll Info ── -->
        <div class="info-grid">
            <div class="info-section">
                <h3>Employee Details</h3>
                <div class="info-row">
                    <span class="info-label">Full Name</span>
                    <span class="info-value">{{ $employee->name ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Employee ID</span>
                    <span class="info-value">{{ $employee->employee_id ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value">{{ $employee->email ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Department</span>
                    <span class="info-value">{{ $employee->department?->name ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Designation</span>
                    <span class="info-value">{{ $employee->designation?->name ?? '—' }}</span>
                </div>
            </div>
            <div class="info-section">
                <h3>Payroll Information</h3>
                <div class="info-row">
                    <span class="info-label">Pay Period</span>
                    <span class="info-value">{{ $periodLabel }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Date</span>
                    <span class="info-value">{{ $paySlip->payment_date ? $paySlip->payment_date->format('d M Y') : '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Salary Type</span>
                    <span class="info-value">{{ ucfirst($employee->salary_type ?? 'Monthly') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="info-value" style="text-transform: capitalize;">{{ $paySlip->status ?? 'draft' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Reference No.</span>
                    <span class="info-value">#{{ str_pad($paySlip->id, 5, '0', STR_PAD_LEFT) }}</span>
                </div>
            </div>
        </div>

        <!-- ── Salary Breakdown ── -->
        <div class="salary-section">
            <h3>Salary Breakdown</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width:50%">Description</th>
                        <th>Type</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Basic Salary</td>
                        <td><span class="positive">Earnings</span></td>
                        <td class="positive">{{ $currency }} {{ number_format($paySlip->basic_salary ?? 0, 2) }}</td>
                    </tr>
                    @if($paySlip->gross_salary && $paySlip->gross_salary != $paySlip->basic_salary)
                    <tr>
                        <td>Gross Salary (including allowances)</td>
                        <td><span class="positive">Earnings</span></td>
                        <td class="positive">{{ $currency }} {{ number_format($paySlip->gross_salary, 2) }}</td>
                    </tr>
                    @endif
                    @if($paySlip->deductions > 0)
                    <tr>
                        <td>Deductions (tax, insurance, etc.)</td>
                        <td><span class="negative">Deduction</span></td>
                        <td class="negative">- {{ $currency }} {{ number_format($paySlip->deductions, 2) }}</td>
                    </tr>
                    @endif
                    @if($paySlip->notes)
                    <tr>
                        <td colspan="3" style="color:#64748b; font-style:italic; font-size:10px;">
                            Note: {{ $paySlip->notes }}
                        </td>
                    </tr>
                    @endif
                    <tr class="summary-row">
                        <td><strong>NET PAYABLE</strong></td>
                        <td></td>
                        <td><strong>{{ $currency }} {{ number_format($paySlip->net_payable ?? 0, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ── Net Amount Highlight ── -->
        <div class="net-box">
            <div>
                <div class="net-label">Total Net Pay for {{ $periodLabel }}</div>
                <div class="net-amount">{{ $currency }} {{ number_format($paySlip->net_payable ?? 0, 2) }}</div>
                <div class="net-period">Employee: {{ $employee->name ?? 'N/A' }}</div>
            </div>
            <div class="net-status">{{ $paySlip->status ?? 'draft' }}</div>
        </div>

        <!-- ── Footer ── -->
        <div class="footer">
            <div class="footer-note">
                This payslip is a confidential document generated by the HR &amp; Payroll system.<br>
                Please contact your HR department for any discrepancies.<br>
                Generated on {{ now()->format('d M Y') }} · Payslip ID: #{{ str_pad($paySlip->id, 5, '0', STR_PAD_LEFT) }}
            </div>
            <div class="signature-box">
                <div class="signature-line">Authorized Signatory</div>
            </div>
        </div>
    </div>
</body>
</html>
