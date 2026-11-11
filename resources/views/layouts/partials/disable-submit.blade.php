<script>
    document.addEventListener('submit', function (event) {
        if (event.defaultPrevented) {
            return;
        }
        event.target.querySelectorAll('button[type=submit], input[type=submit]').forEach(function (button) {
            setTimeout(function () { button.disabled = true; }, 0);
        });
    });
</script>
