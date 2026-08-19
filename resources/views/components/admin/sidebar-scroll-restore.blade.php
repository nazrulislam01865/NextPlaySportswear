<script>
    (() => {
        try {
            const nav = document.querySelector('[data-admin-sidebar-nav]');
            const savedScroll = Number(sessionStorage.getItem('nextplay.admin.sidebar.scrollTop') || 0);
            if (nav && savedScroll > 0) nav.scrollTop = savedScroll;
        } catch (_) {}
    })();
</script>
