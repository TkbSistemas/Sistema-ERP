<script src="assets/js/app.js"></script>
<script>
(() => {
    const toggleButton = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('.main_sidebar');
    const content = document.querySelector('.content-area');
    if (!toggleButton || !sidebar || !content || toggleButton.dataset.sidebarReady === '1') {
        return;
    }

    toggleButton.dataset.sidebarReady = '1';
    toggleButton.addEventListener('click', () => {
        const collapsed = sidebar.classList.toggle('collapsed');
        content.classList.toggle('collapsed', collapsed);
        const icon = toggleButton.querySelector('i');
        if (icon) {
            icon.className = collapsed ? 'fa-solid fa-bars' : 'fa-solid fa-xmark';
        }
        toggleButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    });
})();
</script>
