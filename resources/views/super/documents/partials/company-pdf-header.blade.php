<table class="company-header">
    <tr>
        @if ($companyLogoDataUri)
            <td class="company-logo-cell"><img src="{{ $companyLogoDataUri }}" alt="Logo {{ $company?->name }}"></td>
        @endif
        <td class="company-info">
            <div class="company-name">{{ $company?->name ?? 'Perusahaan' }}</div>
            @if ($company?->address)
                <div>{{ $company->address }}</div>
            @endif
            @php
                $companyCityLine = collect([$company?->city, $company?->province, $company?->postal_code])->filter()->implode(', ');
            @endphp
            @if ($companyCityLine)
                <div>{{ $companyCityLine }}</div>
            @endif
            <div class="company-contact">
                @if ($company?->phone)
                    <span>Telp: {{ $company->phone }}</span>
                @endif
                @if ($company?->email)
                    <span>{{ $company->phone ? ' | ' : '' }}{{ $company->email }}</span>
                @endif
                @if ($company?->tax_number)
                    <span>{{ $company->phone || $company->email ? ' | ' : '' }}NPWP: {{ $company->tax_number }}</span>
                @endif
            </div>
        </td>
    </tr>
</table>
