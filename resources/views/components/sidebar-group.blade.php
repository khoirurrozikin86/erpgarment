@props(['label', 'icon'])

<li class="nav-item">
    <details class="sidebar-group" name="sidebar-groups">
        <summary class="nav-link">
            <i class="link-icon" data-feather="{{ $icon }}"></i>
            <span class="link-title">{{ $label }}</span>
            <i class="link-arrow" data-feather="chevron-down"></i>
        </summary>
        <ul class="nav sub-menu">
            {{ $slot }}
        </ul>
    </details>
</li>
