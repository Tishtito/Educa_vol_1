
// report_select.js - optimized per frontend guidelines
(async function () {
	const baseUrl = "../../backend/public/index.php";
	const params = new URLSearchParams(window.location.search);
	const examId = params.get('exam_id') || '';
	const examType = params.get('exam_type') || '';
	const examToken = params.get('token') || '';
	const labelEl = document.getElementById('report-label');
	const gradesList = document.getElementById('grades-list');

	// Table and label mapping
	const tableMap = {
		'Mid-Term': 'report_table.html',
		'End-Term': 'report_table.html',
		'Opener': 'report_table.html',
		'Weekly': 'report_table.html'
	};
	const labelMap = {
		'Mid-Term': 'Mid-Term',
		'End-Term': 'End of Term',
		'Opener': 'Opener',
		'Weekly': 'Weekly'
	};
	const tableTarget = tableMap[examType] || 'report_table.html';
	const labelText = labelMap[examType] || 'Report';
	if (labelEl) labelEl.textContent = labelText;

	// Show loading indicator
	if (gradesList) {
		gradesList.innerHTML = '<li class="no-task">Loading grades...</li>';
	}

	if (!examId || !/^\d+$/.test(examId) || !examToken) {
		if (gradesList) gradesList.innerHTML = '<li class="no-task">Invalid or missing exam ID.</li>';
		return;
	}

	try {
		// Parallel fetch: auth and grades
		const [authRes, gradesRes] = await Promise.all([
			fetch(`${baseUrl}/auth/check`, { credentials: "include" }),
			fetch(`${baseUrl}/reports/grades?exam_id=${encodeURIComponent(examId)}&token=${encodeURIComponent(examToken)}`, { credentials: "include" })
		]);
		const auth = await authRes.json();
		if (!auth.authenticated) {
			window.location.replace("../login.html");
			return;
		}
		const grades = await gradesRes.json();
		if (!gradesList) return;
		gradesList.innerHTML = '';

		if (!grades.success || !Array.isArray(grades.data) || grades.data.length === 0) {
			gradesList.innerHTML = '<li class="no-task">No grades found.</li>';
			return;
		}

		// Batch DOM update
		const items = grades.data.map((grade) => {
			const url = `${tableTarget}?exam_id=${encodeURIComponent(examId)}&grade=${encodeURIComponent(grade.grade)}&exam_type=${encodeURIComponent(examType)}&token=${encodeURIComponent(grade.token)}&exam_token=${encodeURIComponent(examToken)}`;
			return `
				<a href="${url}">
					<li>
						<i class='bx bx-show-alt'></i>
						<span class="info-2">
							<p>${grade.grade}</p>
						</span>
					</li>
				</a>
			`;
		});
		gradesList.innerHTML = items.join('');
	} catch (error) {
		if (gradesList) gradesList.innerHTML = '<li class="no-task">Failed to load grades.</li>';
		// Optionally log error
		// console.error('Failed to load grades', error);
	}
})();
