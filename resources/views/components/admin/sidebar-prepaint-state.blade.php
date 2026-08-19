<script>
    (() => {
        try {
            const savedWidth = Number(localStorage.getItem('nextplay.admin.sidebar.width'));
            if (Number.isFinite(savedWidth) && savedWidth > 0) {
                const width = Math.min(380, Math.max(220, Math.round(savedWidth)));
                document.documentElement.style.setProperty('--admin-sidebar-width', `${width}px`);
            }
        } catch (_) {}
    })();
</script>
