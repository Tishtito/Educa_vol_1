(function () {
    const host = document.getElementById("sidebar-container");
    if (!host) {
        return;
    }

    const activeKey = host.getAttribute("data-active");

    fetch("components/sidebar.html")
        .then((res) => res.text())
        .then((html) => {
            host.innerHTML = html;
            if (!activeKey) {
                return;
            }
            const activeItem = host.querySelector(`[data-key="${activeKey}"]`);
            if (activeItem) {
                activeItem.classList.add("active");
            }
        })
        .catch((error) => {
            console.error("Failed to load sidebar", error);
        });
})();
