<!doctype html>
<html lang="{{ app()->getLocale() }}"><body style="font-family:Arial,sans-serif;color:#202938;line-height:1.6">
    <p>{{ __('ui.modules.dear_customer', ['customer' => $quotation->customer?->contact_person ?: $quotation->customer?->name ?: __('ui.modules.customer_general')]) }}</p>
    <p>{{ $company?->name ? __('ui.modules.quotation_sent_intro', ['company' => $company->name, 'number' => $quotation->number]) : __('ui.modules.quotation_sent_intro_no_company', ['number' => $quotation->number]) }} {{ __('ui.modules.quotation_attached') }}</p>
    <p>{{ __('ui.modules.quotation_valid_until', ['date' => $quotation->valid_until?->format('d/m/Y') ?? __('ui.modules.validity_undefined')]) }}</p>
    <p>{{ __('ui.common.thank_you') }}</p>
    <p style="color:#667085">{{ $company?->name ?: __('ui.modules.sales_team') }}<br>@if($company?->phone){{ $company->phone }}<br>@endif @if($company?->email){{ $company->email }}@endif</p>
</body></html>
