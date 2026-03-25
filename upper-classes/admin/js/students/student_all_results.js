// student_all_results.js - optimized per frontend guidelines
(async function () {
    const baseUrl = "../../backend/public/index.php";
    const params = new URLSearchParams(window.location.search);
    const studentId = params.get('student_id') || '';
    const tbody = document.getElementById('results-body');

    if (!studentId || !/^\d+$/.test(studentId)) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Invalid student ID.</td></tr>';
        return;
    }

    if (tbody) tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Loading...</td></tr>';

    try {
        const [authRes, studentRes, resultsRes] = await Promise.all([
            fetch(`${baseUrl}/auth/check`, { credentials: "include" }),
            fetch(`${baseUrl}/students/detail?student_id=${encodeURIComponent(studentId)}`, { credentials: "include" }),
            fetch(`${baseUrl}/students/results?student_id=${encodeURIComponent(studentId)}`, { credentials: "include" })
        ]);
        const auth = await authRes.json();
        if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
        }

        const student = await studentRes.json();
        if (student.success) {
            document.getElementById('student-name').textContent = student.data.name;
            document.getElementById('student-name-card').textContent = student.data.name;
            document.getElementById('student-class').textContent = student.data.class;
            document.getElementById('page-title').textContent = `Exam Results - ${student.data.name}`;
        }

        const results = await resultsRes.json();
        if (!results.success || !Array.isArray(results.data) || results.data.length === 0) {
            if (tbody) tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">No exam results found for this student.</td></tr>';
            return;
        }

        if (tbody) {
            const rows = results.data.map((row, index) => {
                const createdAt = row.created_at ? new Date(row.created_at) : null;
                const dateText = createdAt ? createdAt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
                return `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${row.exam_name || 'N/A'}</td>
                        <td>${row.total_marks ?? '-'}</td>
                        <td>${row.position ?? '-'}</td>
                        <td>${row.stream_position ?? '-'}</td>
                        <td>${dateText}</td>
                        <td><a href="student_exam_breakdown.html?result_id=${row.result_id}"><span class='status process'>view</span></a></td>
                    </tr>
                `;
            });
            tbody.innerHTML = rows.join('');
        }
    } catch (error) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Failed to load results.</td></tr>';
    }
})();
