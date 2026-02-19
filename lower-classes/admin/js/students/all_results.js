/**
 * AllResultsManager - Display all exam results for a specific student
 * Features: Student detail display, exam results listing with formatting
 */
const AllResultsManager = (function () {
	const BASE_URL = '../../backend/public/index.php';
	let resultsBody, pageTitle, studentName, studentNameCard, studentClass;

	// Extract URL parameters
	function extractURLParameters() {
		const params = new URLSearchParams(window.location.search);
		return {
			studentId: params.get('student_id') || ''
		};
	}

	// DOM element caching
	function cacheDOMElements() {
		resultsBody = document.getElementById('results-body');
		pageTitle = document.getElementById('page-title');
		studentName = document.getElementById('student-name');
		studentNameCard = document.getElementById('student-name-card');
		studentClass = document.getElementById('student-class');
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
		if (resultsBody) {
			resultsBody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Loading...</td></tr>';
		}
	}

	// Show error state
	function showErrorState(message) {
		if (resultsBody) {
			resultsBody.innerHTML = `<tr><td colspan="7" style="text-align:center; color: #ef4444;">${escapeHtml(message)}</td></tr>`;
		}
	}

	// Show empty state
	function showEmptyState(message) {
		if (resultsBody) {
			resultsBody.innerHTML = `<tr><td colspan="7" style="text-align:center;">${escapeHtml(message)}</td></tr>`;
		}
	}

	// Format date
	function formatDate(dateString) {
		if (!dateString) return '-';
		try {
			const date = new Date(dateString);
			return date.toLocaleDateString('en-GB', { 
				day: '2-digit', 
				month: 'short', 
				year: 'numeric' 
			});
		} catch (e) {
			return '-';
		}
	}

	// Load student data and results
	async function loadStudentResults(studentId) {
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

			// Load student detail and results in parallel
			const [studentRes, resultsRes] = await Promise.all([
				fetch(`${BASE_URL}/students/detail?student_id=${encodeURIComponent(studentId)}`, { 
					credentials: 'include' 
				}),
				fetch(`${BASE_URL}/students/results?student_id=${encodeURIComponent(studentId)}`, { 
					credentials: 'include' 
				})
			]);

			const student = await studentRes.json();
			const results = await resultsRes.json();

			// Update student details
			if (student.success && student.data) {
				const name = escapeHtml(student.data.name || 'Unknown');
				const className = escapeHtml(student.data.class || 'N/A');
				
				if (studentName) studentName.textContent = name;
				if (studentNameCard) studentNameCard.textContent = name;
				if (studentClass) studentClass.textContent = className;
				if (pageTitle) pageTitle.textContent = `Exam Results - ${name}`;
			}

			// Render results
			if (!results.success || !results.data || results.data.length === 0) {
				showEmptyState('No exam results found for this student.');
				return;
			}

			renderResultsTable(results.data);
		} catch (error) {
			console.error('Failed to load student results', error);
			showErrorState('Failed to load results. Please try again.');
		}
	}

	// Render results table with batch rendering
	function renderResultsTable(data) {
		if (!resultsBody) return;

		const tableRows = data.map((row, index) => {
			const examName = escapeHtml(row.exam_name || 'N/A');
			const totalMarks = escapeHtml(String(row.total_marks ?? '-'));
			const position = escapeHtml(String(row.position ?? '-'));
			const streamPosition = escapeHtml(String(row.stream_position ?? '-'));
			const date = formatDate(row.created_at);
			const resultId = escapeHtml(String(row.result_id || ''));

			return `
				<tr>
					<td>${index + 1}</td>
					<td>${examName}</td>
					<td>${totalMarks}</td>
					<td>${position}</td>
					<td>${streamPosition}</td>
					<td>${date}</td>
					<td><a href="student_exam_breakdown.html?result_id=${resultId}"><span class='status process'>view</span></a></td>
				</tr>
			`;
		}).join('');

		resultsBody.innerHTML = tableRows;
	}

	// Initialize
	async function init() {
		cacheDOMElements();
		
		const { studentId } = extractURLParameters();

		// Validate student ID
		if (!studentId || !studentId.match(/^\d+$/)) {
			showErrorState('Invalid student ID.');
			return;
		}

		showLoadingState();
		await loadStudentResults(studentId);
	}

	return {
		init
	};
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
	AllResultsManager.init();
});
