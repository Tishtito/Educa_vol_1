// student_exam_breakdown.js - optimized per frontend guidelines
(async function () {
    const baseUrl = "../../backend/public/index.php";
    const params = new URLSearchParams(window.location.search);
    const resultId = params.get('result_id') || '';

    const body = document.getElementById('subjects-body');

    if (!resultId || !/^\d+$/.test(resultId)) {
        if (body) body.innerHTML = '<tr><td colspan="2" style="text-align:center;">Invalid result ID.</td></tr>';
        return;
    }

    if (body) body.innerHTML = '<tr><td colspan="2" style="text-align:center;">Loading...</td></tr>';

    try {
        const [authRes, detailRes] = await Promise.all([
            fetch(`${baseUrl}/auth/check`, { credentials: "include" }),
            fetch(`${baseUrl}/students/result-detail?result_id=${encodeURIComponent(resultId)}`, { credentials: "include" })
        ]);
        const auth = await authRes.json();
        if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
        }

        const detail = await detailRes.json();
        if (!detail.success) {
            if (body) body.innerHTML = '<tr><td colspan="2" style="text-align:center;">Exam result not found.</td></tr>';
            return;
        }

        const data = detail.data;
        document.getElementById('student-name').textContent = data.student_name ?? '-';
        document.getElementById('student-class').textContent = data.student_class ?? '-';
        document.getElementById('exam-name').textContent = data.exam_name ?? 'N/A';
        document.getElementById('breadcrumb-exam').textContent = data.exam_name ?? 'Exam';

        const createdAt = data.created_at ? new Date(data.created_at) : null;
        const dateText = createdAt ? createdAt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
        document.getElementById('exam-date').textContent = dateText;

        const subjects = [
            { key: 'Math', label: 'Math' },
                { key: 'English', label: 'English' },
                { key: 'Kiswahili', label: 'Kiswahili' },
                { key: 'Technical', label: 'Pre-Technical' },
                { key: 'Agriculture', label: 'Agriculture' },
                { key: 'Creative', label: 'Creative Arts' },
                { key: 'Religious', label: 'Religious Activities' },
                { key: 'SST', label: 'Social Studies' },
                { key: 'Science', label: 'Science' }
        ];

        if (body) {
            const rows = subjects
                .map((subject) => {
                    const value = data[subject.key];
                    if (value === null || value === undefined) return '';
                    return `
                        <tr>
                            <td>${subject.label}</td>
                            <td>${value}</td>
                        </tr>
                    `;
                })
                .filter(Boolean);

            body.innerHTML = rows.length
                ? rows.join('')
                : '<tr><td colspan="2" style="text-align:center;">No subject marks found.</td></tr>';
        }

        document.getElementById('total-marks').textContent = data.total_marks ?? '-';
        document.getElementById('position').textContent = data.position ?? '-';
        document.getElementById('stream-position').textContent = data.stream_position ?? '-';
    } catch (error) {
        if (body) body.innerHTML = '<tr><td colspan="2" style="text-align:center;">Failed to load exam breakdown.</td></tr>';
    }
})();
