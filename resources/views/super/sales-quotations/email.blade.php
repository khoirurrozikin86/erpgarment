<!doctype html>
<html lang="id"><body style="font-family:Arial,sans-serif;color:#202938;line-height:1.6">
    <p>Yth. {{ $quotation->customer?->contact_person ?: $quotation->customer?->name ?: 'Customer' }},</p>
    <p>
        @if ($company?->name)
            {{ $company->name }} mengirimkan penawaran harga <strong>{{ $quotation->number }}</strong>.
        @else
            Kami mengirimkan penawaran harga <strong>{{ $quotation->number }}</strong>.
        @endif
        Dokumen quotation terlampir pada email ini.
    </p>
    <p>Penawaran berlaku sampai {{ $quotation->valid_until?->format('d/m/Y') ?? 'waktu yang disepakati' }}. Silakan hubungi kami jika ada pertanyaan atau perubahan.</p>
    <p>Terima kasih.</p>
    <p style="color:#667085">{{ $company?->name ?: 'Tim Sales' }}<br>@if($company?->phone){{ $company->phone }}<br>@endif @if($company?->email){{ $company->email }}@endif</p>
</body></html>
