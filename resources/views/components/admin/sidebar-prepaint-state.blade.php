<script>
    (() => {
        // Keep the admin shell visually stable while Alpine/Vite hydrate the new page.
        // Without this guard the sidebar transform/arrow transitions can briefly run
        // again on every normal Laravel navigation, which looks like a sidebar flash.
        document.documentElement.classList.add('admin-sidebar-prepaint');

        try {
            const savedWidth = Number(localStorage.getItem('nextplay.admin.sidebar.width'));
            if (Number.isFinite(savedWidth) && savedWidth > 0) {
                const width = Math.min(380, Math.max(200, Math.round(savedWidth)));
                document.documentElement.style.setProperty('--admin-sidebar-width', `${width}px`);
            }
        } catch (_) {}

        const revealSidebar = () => {
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    document.documentElement.classList.remove('admin-sidebar-prepaint');
                });
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', revealSidebar, { once: true });
        } else {
            revealSidebar();
        }
    })();
</script>
