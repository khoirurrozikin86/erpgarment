<table class="company-header">
    <tr>
        @if ($companyLogoDataUri)
            <td class="company-logo-cell"><img src="{{ $companyLogoDataUri }}" alt="Logo {{ $company?->name }}"></td>
        @endif
        <td class="company-info">
            <div class="company-name">{{ $company?->name ?? 'Perusahaan' }}</div>
        </td>
        <td class="company-details">
            @if ($company?->address)
                <div class="company-address">{{ \Illuminate\Support\Str::squish($company->address) }}</div>
            @endif
            @php
                $companyCityLine = collect([$company?->city, $company?->province, $company?->postal_code])->filter()->implode(', ');
            @endphp
            @if ($companyCityLine)
                <div class="company-address">{{ $companyCityLine }}</div>
            @endif
            <div class="company-contact">
                @if ($company?->phone)
                    <span>Telp: {{ $company->phone }}</span>
                @endif
                @if ($company?->email)
                    <span>{{ $company->phone ? ' | ' : '' }}{{ $company->email }}</span>
                @endif
            </div>
        </td>
    </tr>
</table>
