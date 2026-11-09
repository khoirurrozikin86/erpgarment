<!doctype html>
<html lang="id"><body style="font-family:Arial,sans-serif;color:#202938;line-height:1.6">
    <p>Yth. {{ $invoice->customer?->contact_person ?: $invoice->customer?->name ?: 'Customer' }},</p>
    <p>
        @if($company?->name){{ $company->name }} mengirimkan Sales Invoice <strong>{{ $invoice->number }}</strong>.@else Kami mengirimkan Sales Invoice <strong>{{ $invoice->number }}</strong>.@endif
        Invoice terlampir pada email ini.
    </p>
    <p>Total tagihan: <strong>Rp {{ number_format((float)$invoice->total_amount, 2, ',', '.') }}</strong><br>Jatuh tempo: <strong>{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</strong></p>
    <p>Terima kasih.</p>
    <p style="color:#667085">{{ $company?->name ?: 'Tim Sales' }}<br>@if($company?->phone){{ $company->phone }}<br>@endif @if($company?->email){{ $company->email }}@endif</p>
</body></html>
