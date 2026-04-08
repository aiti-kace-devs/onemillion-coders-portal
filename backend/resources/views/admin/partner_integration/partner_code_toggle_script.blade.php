{{-- Toggles custom partner code field when "Other" is selected. --}}
<script>
(function () {
    var OTHER = @json(\App\Http\Requests\PartnerIntegrationRequest::PARTNER_CODE_OTHER);
    function sync() {
        var p = document.getElementById('partner-code-preset');
        var wrap = document.getElementById('partner-code-manual-wrapper');
        if (!p || !wrap) return;
        var other = p.value === OTHER;
        wrap.classList.toggle('d-none', !other);
        var manual = document.getElementById('partner-code-manual');
        if (manual) { manual.required = other; }
    }
    document.addEventListener('change', function (e) {
        if (e.target && e.target.id === 'partner-code-preset') sync();
    });
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', sync);
    } else {
        sync();
    }
})();
</script>
