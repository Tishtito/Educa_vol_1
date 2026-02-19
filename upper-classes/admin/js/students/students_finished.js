// students_finished.js - optimized per frontend guidelines
(async function () {
    const baseUrl = "../../backend/public/index.php";
    const container = document.getElementById('finished-years');

    if (container) {
        container.innerHTML = '<li class="no-task">Loading...</li>';
    }

    try {
        const [authRes, listRes] = await Promise.all([
            fetch(`${baseUrl}/auth/check`, { credentials: "include" }),
            fetch(`${baseUrl}/students/finished-years`, { credentials: "include" })
        ]);
        const auth = await authRes.json();
        if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
        }
        const list = await listRes.json();
        if (list.success && container) {
            const items = (list.data || []).map((year) => `
                <a href="students_finished_by_year.html?year=${encodeURIComponent(year)}">
                    <li>
                        <i class='bx bx-calendar-check'></i>
                        <span class="info-2">
                            <p>${year}</p>
                        </span>
                    </li>
                </a>
            `);
            container.innerHTML = items.length ? items.join('') : '<li class="no-task">No finished years found.</li>';
        }
    } catch (error) {
        if (container) container.innerHTML = '<li class="no-task">Failed to load finished years.</li>';
    }
})();
