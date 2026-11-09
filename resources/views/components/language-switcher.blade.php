<form method="POST" action="{{ route('locale.update') }}" class="language-switcher">
    @csrf
    <label class="visually-hidden" for="language-select">{{ __('ui.language') }}</label>
    <select id="language-select" name="locale" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="id" @selected(app()->getLocale() === 'id')>{{ __('ui.indonesian') }}</option>
        <option value="en" @selected(app()->getLocale() === 'en')>{{ __('ui.english') }}</option>
    </select>
</form>
