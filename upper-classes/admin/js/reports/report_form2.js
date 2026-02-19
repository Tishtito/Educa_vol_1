(async function () {
    const baseUrl = "../../backend/public/index.php";
    const params = new URLSearchParams(window.location.search);
    const studentId = params.get('student_id') || '';
    const examId = params.get('exam_id') || '';
    const token = params.get('token') || '';

    function getPerformanceLevel(score, levels) {
        if (score === null || score === undefined) {
            return '-';
        }
        for (const level of levels) {
            if (score >= level.min_marks && score <= level.max_marks) {
                return level.pl;
            }
        }
        return 'UNKNOWN';
    }

    if (!studentId || !examId || !token) {
        return;
    }

    // Loading indicator
    const loading = document.createElement("div");
    loading.id = "loading-indicator";
    loading.style = "position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(255,255,255,0.7);z-index:9999;display:flex;align-items:center;justify-content:center;font-size:2em;";
    loading.textContent = "Loading...";
    document.body.appendChild(loading);

    try {
        const [authRes] = await Promise.all([
            fetch(`${baseUrl}/auth/check`, { credentials: "include" })
        ]);
        const auth = await authRes.json();
        if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
        }

        const dataRes = await fetch(`${baseUrl}/reports/report-combined?student_id=${encodeURIComponent(studentId)}&exam_id=${encodeURIComponent(examId)}&token=${encodeURIComponent(token)}`, { credentials: "include" });
        const payload = await dataRes.json();
        if (!payload.success) {
            return;
        }

        const { student, tutor, term, exam_year, opener_results, mid_results, levels, subjects } = payload.data;
        document.getElementById('student-name').textContent = student.name || '-';
        document.getElementById('student-class').textContent = student.class || '-';
        document.getElementById('tutor-name').textContent = tutor || 'Not Assigned';
        document.getElementById('term-label').textContent = term || '-';
        document.getElementById('exam-year').textContent = exam_year || '-';

        const body = document.getElementById('subjects-body');
        let totalOpener = 0;
        let totalMid = 0;
        const rows = Object.keys(subjects || {}).map((key, index) => {
            const subjectName = subjects[key];
            const openerVal = opener_results ? opener_results[key] : null;
            const midVal = mid_results ? mid_results[key] : null;
            if (openerVal !== null && openerVal !== undefined) totalOpener += Number(openerVal);
            if (midVal !== null && midVal !== undefined) totalMid += Number(midVal);
            return `<tr>
                <td class="no">${index + 1}</td>
                <td class="text-left"><h3>${subjectName}</h3></td>
                <td class="unit">${openerVal ?? '-'}</td>
                <td class="total">${getPerformanceLevel(openerVal, levels)}</td>
                <td class="unit">${midVal ?? '-'}</td>
                <td class="total">${getPerformanceLevel(midVal, levels)}</td>
            </tr>`;
        });
        body.innerHTML = rows.join("");
        document.getElementById('total-opener').textContent = totalOpener;
        document.getElementById('total-mid').textContent = totalMid;
    } catch (error) {
        console.error('Failed to load report form', error);
    } finally {
        // Remove loading indicator
        if (loading && loading.parentNode) loading.parentNode.removeChild(loading);
    }
})();

document.getElementById('printInvoice').addEventListener('click', () => {
    window.print();
});
