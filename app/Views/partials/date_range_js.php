<script>
    // Keep "end date" from being earlier than "start date" in any form marked data-range-form.
    document.querySelectorAll('[data-range-form]').forEach(function (form) {
        const from = form.querySelector('input[name="from"]');
        const to = form.querySelector('input[name="to"]');
        if (!from || !to) { return; }
        function sync() {
            to.min = from.value || '';
            from.max = to.value || to.max || '';
            to.setCustomValidity(from.value && to.value && to.value < from.value ? 'The end date must be on or after the start date.' : '');
        }
        from.addEventListener('change', sync);
        to.addEventListener('change', sync);
        sync();
    });
</script>
