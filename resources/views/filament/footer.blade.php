<footer class="aho-footer">
    <div class="aho-footer__inner">
        <div class="aho-footer__content">
            <div class="aho-footer__title">{{ __('aho.brand.app_name') }}</div>
            <div class="aho-footer__subtitle">{{ __('aho.footer.subtitle') }}</div>
        </div>

        <div class="aho-footer__meta">
            <span>{{ __('aho.footer.organization') }}</span>
            <span>{{ __('aho.footer.copyright', ['year' => now()->year]) }}</span>
        </div>
    </div>
</footer>
