<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contract Assignment</title>
</head>
<body style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.6;">
    <h2 style="margin-bottom: 8px;">A contract is ready for your review</h2>
    <p>
        Hello {{ $contract->employee?->name ?? 'there' }},
    </p>
    <p>
        Your contract <strong>{{ $contract->contract_name }}</strong> is now available in the HR portal.
    </p>
    <p>
        <strong>Verification code:</strong> {{ $contract->verification_code }}<br>
        <strong>Review deadline:</strong> {{ optional($contract->signing_deadline)->format('M d, Y H:i') ?? 'Not set' }}<br>
        <strong>Contract period:</strong> {{ optional($contract->start_date)->format('M d, Y') ?? 'N/A' }}
        to {{ optional($contract->end_date)->format('M d, Y') ?? 'Open-ended' }}
    </p>
    <p>
        Sign in to the portal, open the contract review page, and enter your contract ID
        <strong>#{{ $contract->id }}</strong> with the verification code above.
    </p>
    <p>
        <a href="{{ $portalUrl }}" style="display: inline-block; background: #2563eb; color: #fff; padding: 10px 16px; text-decoration: none; border-radius: 6px;">
            Open Contract Review
        </a>
    </p>
    <p style="font-size: 12px; color: #64748b;">
        If you did not expect this message, please contact HR.
    </p>
</body>
</html>
