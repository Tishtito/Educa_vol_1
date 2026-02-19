
// reports.js - optimized per frontend guidelines
(async function () {
	const baseUrl = "../../backend/public/index.php";

	function renderList(containerId, exams, emptyText, linkBuilder) {
		const container = document.getElementById(containerId);
		if (!container) return;
		container.innerHTML = '';
		if (!exams || exams.length === 0) {
			const li = document.createElement('li');
			li.className = 'no-task';
			li.textContent = emptyText;
			container.appendChild(li);
			return;
		}
		// Batch DOM update
		const items = exams.map((exam) => {
			const url = linkBuilder(exam);
			return `
				<a href="${url}">
					<li class="completed">
						<div class="task-title">
							<i class='bx bx-book'></i>
							<p>${exam.exam_name}<span></span></p>
						</div>
						<i class='bx bx-dots-vertical-rounded'></i>
					</li>
				</a>
			`;
		});
		container.innerHTML = items.join('');
	}

	// Show loading indicators
	['weekly-list', 'opener-list', 'midterm-list', 'endterm-list'].forEach(id => {
		const el = document.getElementById(id);
		if (el) {
			el.innerHTML = '<li class="no-task">Loading...</li>';
		}
	});

	try {
		// Parallel fetch: auth and all exam types
		const [authRes, weeklyRes, openerRes, midtermRes, endtermRes] = await Promise.all([
			fetch(`${baseUrl}/auth/check`, { credentials: "include" }),
			fetch(`${baseUrl}/reports/exams?type=Weekly`, { credentials: "include" }),
			fetch(`${baseUrl}/reports/exams?type=Opener`, { credentials: "include" }),
			fetch(`${baseUrl}/reports/exams?type=Mid-Term`, { credentials: "include" }),
			fetch(`${baseUrl}/reports/exams?type=End-Term`, { credentials: "include" })
		]);
		const auth = await authRes.json();
		if (!auth.authenticated) {
			window.location.replace("../login.html");
			return;
		}
		const [weekly, opener, midterm, endterm] = await Promise.all([
			weeklyRes.json(), openerRes.json(), midtermRes.json(), endtermRes.json()
		]);

		renderList('weekly-list', weekly.success ? weekly.data : [], 'No Weekly Exams Found',
			(exam) => `report_select.html?exam_id=${encodeURIComponent(exam.exam_id)}&exam_type=${encodeURIComponent(exam.exam_type)}&token=${encodeURIComponent(exam.token)}`);

		renderList('opener-list', opener.success ? opener.data : [], 'No Opener Exams Found',
			(exam) => `report_select.html?exam_id=${encodeURIComponent(exam.exam_id)}&exam_type=${encodeURIComponent(exam.exam_type)}&token=${encodeURIComponent(exam.token)}`);

		renderList('midterm-list', midterm.success ? midterm.data : [], 'No Mid-Term Exams Found',
			(exam) => `report_select.html?exam_id=${encodeURIComponent(exam.exam_id)}&exam_type=${encodeURIComponent(exam.exam_type)}&token=${encodeURIComponent(exam.token)}`);

		renderList('endterm-list', endterm.success ? endterm.data : [], 'No End of Term Exams Found',
			(exam) => `report_select.html?exam_id=${encodeURIComponent(exam.exam_id)}&exam_type=${encodeURIComponent(exam.exam_type)}&token=${encodeURIComponent(exam.token)}`);
	} catch (error) {
		// Show error in all lists
		['weekly-list', 'opener-list', 'midterm-list', 'endterm-list'].forEach(id => {
			const el = document.getElementById(id);
			if (el) {
				el.innerHTML = '<li class="no-task">Failed to load reports.</li>';
			}
		});
		// Optionally log error
		// console.error('Failed to load reports', error);
	}
})();
