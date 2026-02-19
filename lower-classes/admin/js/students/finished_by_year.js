/**
 * FinishedByYearManager - Display finished students for a specific year
 * Features: Year-based student filtering, results listing
 */
const FinishedByYearManager = (function () {
	const BASE_URL = '../../backend/public/index.php';
	let studentsBody, pageTitle, yearLabel;

	// Extract URL parameters
	function extractURLParameters() {
		const params = new URLSearchParams(window.location.search);
		return {
			year: params.get('year') || ''
		};
	}

	// DOM element caching
	function cacheDOMElements() {
		studentsBody = document.getElementById('students-body');
		pageTitle = document.getElementById('page-title');
		yearLabel = document.getElementById('year-label');
	}

	// XSS Protection
	function escapeHtml(text) {
		const htmlEscapeMap = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#39;'
		};
		return (text || '').replace(/[&<>"']/g, char => htmlEscapeMap[char]);
	}

	// Show loading state
	function showLoadingState() {
		if (studentsBody) {
			studentsBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Loading...</td></tr>';
		}
	}

	// Show error state
	function showErrorState(message) {
		if (studentsBody) {
			studentsBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color: #ef4444;">${escapeHtml(message)}</td></tr>`;
		}
	}

	// Show empty state
	function showEmptyState(year) {
		if (studentsBody) {
			studentsBody.innerHTML = `<tr><td colspan="5" style="text-align:center;">No students finished in ${escapeHtml(year)}.</td></tr>`;
		}
	}

	// Format date
	function formatDate(dateString) {
		if (!dateString) return '-';
		try {
			const date = new Date(dateString);
			return date.toLocaleDateString('en-GB', { year: 'numeric' });
		} catch (e) {
			return '-';
		}
	}

	// Load finished students for year
	async function loadFinishedStudents(year) {
		try {
			// Auth check
			const authRes = await fetch(`${BASE_URL}/auth/check`, { 
				credentials: 'include' 
			});
			const auth = await authRes.json();
			if (!auth.authenticated) {
				window.location.replace('../login.html');
				return;
			}

			// Fetch students
			const listRes = await fetch(
				`${BASE_URL}/students/finished-by-year?year=${encodeURIComponent(year)}`, 
				{ credentials: 'include' }
			);
			const list = await listRes.json();

			if (!list.success || !list.data || list.data.length === 0) {
				showEmptyState(year);
				return;
			}

			renderStudentsTable(list.data);
		} catch (error) {
			console.error('Failed to load finished students', error);
			showErrorState('Failed to load students. Please try again.');
		}
	}

	// Render students table with batch rendering
	function renderStudentsTable(students) {
		if (!studentsBody) return;

		const tableRows = students.map((student, index) => {
			const name = escapeHtml(student.name);
			const studentClass = escapeHtml(student.class);
			const finishedOn = formatDate(student.finished_at);
			const studentId = escapeHtml(String(student.student_id));

			return `
				<tr>
					<td>${index + 1}</td>
					<td>${name}</td>
					<td>${studentClass}</td>
					<td>${finishedOn}</td>
					<td><a href="student_all_results.html?student_id=${studentId}"><span class="status delete">All Results</span></a></td>
				</tr>
			`;
		}).join('');

		studentsBody.innerHTML = tableRows;
	}

	// Initialize
	async function init() {
		cacheDOMElements();

		const { year } = extractURLParameters();

		// Validate year
		if (!year || !year.match(/^\d{4}$/)) {
			showErrorState('Invalid year supplied.');
			return;
		}

		// Update header
		if (yearLabel) yearLabel.textContent = year;
		if (pageTitle) pageTitle.textContent = `Finished Students – ${year}`;

		showLoadingState();
		await loadFinishedStudents(year);
	}

	return {
		init
	};
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
	FinishedByYearManager.init();
});
