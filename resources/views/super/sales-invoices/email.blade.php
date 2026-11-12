<!doctype html>
<html lang="{{ app()->getLocale() }}"><body style="font-family:Arial,sans-serif;color:#202938;line-height:1.6">
    <p>{{ __('ui.modules.dear_customer', ['customer' => $invoice->customer?->contact_person ?: $invoice->customer?->name ?: __('ui.modules.customer_general')]) }}</p>
    <p>{{ $company?->name ? __('ui.modules.invoice_sent_intro', ['company' => $company->name, 'number' => $invoice->number]) : __('ui.modules.invoice_sent_intro_no_company', ['number' => $invoice->number]) }} {{ __('ui.modules.invoice_attached') }}</p>
    <p>{{ __('ui.modules.invoice_total_due') }}: <strong>Rp {{ number_format((float)$invoice->total_amount, 2, ',', '.') }}</strong><br>{{ __('ui.modules.invoice_due_date') }}: <strong>{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</strong></p>
    <p>{{ __('ui.common.thank_you') }}</p>
    <p style="color:#667085">{{ $company?->name ?: __('ui.modules.sales_team') }}<br>@if($company?->phone){{ $company->phone }}<br>@endif @if($company?->email){{ $company->email }}@endif</p>
</body></html>
