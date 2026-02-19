// students_finished_by_year.js - optimized per frontend guidelines
(async function () {
    const baseUrl = "../../backend/public/index.php";
    const params = new URLSearchParams(window.location.search);
    const year = params.get('year') || '';
    const tbody = document.getElementById('students-body');

    if (!year.match(/^\d{4}$/)) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Invalid year supplied.</td></tr>';
        return;
    }

    const yearLabel = document.getElementById('year-label');
    const pageTitle = document.getElementById('page-title');
    if (yearLabel) yearLabel.textContent = year;
    if (pageTitle) pageTitle.textContent = `Finished Students - ${year}`;

    if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Loading...</td></tr>';

    try {
        const [authRes, listRes] = await Promise.all([
            fetch(`${baseUrl}/auth/check`, { credentials: "include" }),
            fetch(`${baseUrl}/students/finished-by-year?year=${encodeURIComponent(year)}`, { credentials: "include" })
        ]);
        const auth = await authRes.json();
        if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
        }
        const list = await listRes.json();
        if (!list.success || !Array.isArray(list.data) || list.data.length === 0) {
            if (tbody) tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;">No students finished in ${year}.</td></tr>`;
            return;
        }
        if (tbody) {
            const rows = list.data.map((student, index) => {
                const finishedAt = student.finished_at ? new Date(student.finished_at) : null;
                const finishedOn = finishedAt ? finishedAt.toLocaleDateString('en-GB', { year: 'numeric' }) : '-';
                return `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${student.name}</td>
                        <td>${student.class}</td>
                        <td>${finishedOn}</td>
                        <td><a href="student_all_results.html?student_id=${student.student_id}"><span class="status delete">All Results</span></a></td>
                    </tr>
                `;
            });
            tbody.innerHTML = rows.join('');
        }
    } catch (error) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Failed to load finished students.</td></tr>';
    }
})();
