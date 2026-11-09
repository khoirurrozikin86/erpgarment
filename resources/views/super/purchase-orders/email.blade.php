<!doctype html>
<html lang="id">
<body style="font-family:Arial,sans-serif;color:#202938;line-height:1.6">
    <p>Yth. {{ $purchaseOrder->supplier?->contact_person ?: $purchaseOrder->supplier?->name ?: 'Supplier' }},</p>

    <p>
        @if ($company?->name)
            {{ $company->name }} mengirimkan Purchase Order <strong>{{ $purchaseOrder->number }}</strong>.
        @else
            Kami mengirimkan Purchase Order <strong>{{ $purchaseOrder->number }}</strong>.
        @endif
        Dokumen PO terlampir pada email ini.
    </p>

    <p>Mohon konfirmasi penerimaan pesanan dan perkiraan waktu pengiriman.</p>
    <p>Terima kasih.</p>

    <p style="color:#667085">
        {{ $company?->name ?: 'Tim Purchasing' }}<br>
        @if ($company?->phone){{ $company->phone }}<br>@endif
        @if ($company?->email){{ $company->email }}@endif
    </p>
</body>
</html>
