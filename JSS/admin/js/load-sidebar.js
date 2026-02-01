(function () {
    const host = document.getElementById("sidebar-container");
    if (!host) {
        return;
    }

    const activeKey = host.getAttribute("data-active");

    const basePath = "/Educa_vol_1/JSS/admin/Pages";

    fetch(`${basePath}/components/sidebar.html`)
        .then((res) => res.text())
        .then((html) => {
            host.innerHTML = html;
            if (!activeKey) {
                if (window.initSidebarBehavior) {
                    window.initSidebarBehavior();
                }
                return;
            }
            const activeItem = host.querySelector(`[data-key="${activeKey}"]`);
            if (activeItem) {
                activeItem.classList.add("active");
            }
            if (window.initSidebarBehavior) {
                window.initSidebarBehavior();
            }
        })
        .catch((error) => {
            console.error("Failed to load sidebar", error);
        });
})();
