// students_active.js - optimized per frontend guidelines
(async function () {
    const baseUrl = "../../backend/public/index.php";
    const container = document.getElementById('active-classes');

    if (container) {
        container.innerHTML = '<li class="no-task">Loading...</li>';
    }

    try {
        const [authRes, listRes] = await Promise.all([
            fetch(`${baseUrl}/auth/check`, { credentials: "include" }),
            fetch(`${baseUrl}/students/active-classes`, { credentials: "include" })
        ]);
        const auth = await authRes.json();
        if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
        }
        const list = await listRes.json();
        if (list.success && container) {
            const items = (list.data || []).map((grade) => `
                <a href="students_active_by_class.html?class=${encodeURIComponent(grade)}">
                    <li>
                        <i class='bx bx-show-alt'></i>
                        <span class="info-2">
                            <p>${grade}</p>
                        </span>
                    </li>
                </a>
            `);
            container.innerHTML = items.length ? items.join('') : '<li class="no-task">No active classes found.</li>';
        }
    } catch (error) {
        if (container) container.innerHTML = '<li class="no-task">Failed to load active classes.</li>';
    }
})();
