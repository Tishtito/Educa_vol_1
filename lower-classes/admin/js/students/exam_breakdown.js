/**
 * ExamBreakdownManager - Display detailed exam breakdown for a student result
 * Features: Subject-level marks display, result totals and positioning
 */
const ExamBreakdownManager = (function () {
	const BASE_URL = '../../backend/public/index.php';
	let subjectsBody, studentName, studentClass, examName, examDate, breadcrumbExam;
	let totalMarksEl, positionEl, streamPositionEl;

	// Exam subjects configuration
	const SUBJECTS = [
		{ key: 'Math', label: 'Math' },
		{ key: 'English', label: 'English' },
		{ key: 'Kiswahili', label: 'Kiswahili' },
		{ key: 'Enviromental', label: 'Enviromental' },
		{ key: 'Creative', label: 'Creative' },
		{ key: 'Religious', label: 'Religious' }
	];

	// Extract URL parameters
	function extractURLParameters() {
		const params = new URLSearchParams(window.location.search);
		return {
			resultId: params.get('result_id') || ''
		};
	}

	// DOM element caching
	function cacheDOMElements() {
		subjectsBody = document.getElementById('subjects-body');
		studentName = document.getElementById('student-name');
		studentClass = document.getElementById('student-class');
		examName = document.getElementById('exam-name');
		examDate = document.getElementById('exam-date');
		breadcrumbExam = document.getElementById('breadcrumb-exam');
		totalMarksEl = document.getElementById('total-marks');
		positionEl = document.getElementById('position');
		streamPositionEl = document.getElementById('stream-position');
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
		if (subjectsBody) {
			subjectsBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">Loading...</td></tr>';
		}
	}

	// Show error state
	function showErrorState(message) {
		if (subjectsBody) {
			subjectsBody.innerHTML = `<tr><td colspan="2" style="text-align:center; color: #ef4444;">${escapeHtml(message)}</td></tr>`;
		}
	}

	// Show empty state
	function showEmptyState() {
		if (subjectsBody) {
			subjectsBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">No subject marks found.</td></tr>';
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

	// Load exam breakdown detail
	async function loadExamBreakdown(resultId) {
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

			// Fetch result detail
			const detailRes = await fetch(
				`${BASE_URL}/students/result-detail?result_id=${encodeURIComponent(resultId)}`, 
				{ credentials: 'include' }
			);
			const detail = await detailRes.json();

			if (!detail.success || !detail.data) {
				showErrorState('Exam result not found.');
				return;
			}

			populateStudentInfo(detail.data);
			renderSubjectsTable(detail.data);
			updateSummaryFooter(detail.data);
		} catch (error) {
			console.error('Failed to load exam breakdown', error);
			showErrorState('Failed to load exam breakdown. Please try again.');
		}
	}

	// Populate student info card
	function populateStudentInfo(data) {
		const name = escapeHtml(data.student_name ?? '-');
		const className = escapeHtml(data.student_class ?? '-');
		const exam = escapeHtml(data.exam_name ?? 'N/A');
		const date = formatDate(data.created_at);

		if (studentName) studentName.textContent = name;
		if (studentClass) studentClass.textContent = className;
		if (examName) examName.textContent = exam;
		if (breadcrumbExam) breadcrumbExam.textContent = exam;
		if (examDate) examDate.textContent = date;
	}

	// Render subjects table with batch rendering
	function renderSubjectsTable(data) {
		if (!subjectsBody) return;

		// Filter subjects with actual marks
		const subjectsWithMarks = SUBJECTS.filter(subject => {
			const value = data[subject.key];
			return value !== null && value !== undefined;
		});

		if (subjectsWithMarks.length === 0) {
			showEmptyState();
			return;
		}

		// Batch render table rows
		const tableRows = subjectsWithMarks.map(subject => {
			const value = escapeHtml(String(data[subject.key]));
			return `
				<tr>
					<td>${escapeHtml(subject.label)}</td>
					<td>${value}</td>
				</tr>
			`;
		}).join('');

		subjectsBody.innerHTML = tableRows;
	}

	// Update summary footer
	function updateSummaryFooter(data) {
		const totalMarks = escapeHtml(String(data.total_marks ?? '-'));
		const position = escapeHtml(String(data.position ?? '-'));
		const streamPosition = escapeHtml(String(data.stream_position ?? '-'));

		if (totalMarksEl) totalMarksEl.textContent = totalMarks;
		if (positionEl) positionEl.textContent = position;
		if (streamPositionEl) streamPositionEl.textContent = streamPosition;
	}

	// Initialize
	async function init() {
		cacheDOMElements();

		const { resultId } = extractURLParameters();

		// Validate result ID
		if (!resultId || !resultId.match(/^\d+$/)) {
			showErrorState('Invalid result ID.');
			return;
		}

		showLoadingState();
		await loadExamBreakdown(resultId);
	}

	return {
		init
	};
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
	ExamBreakdownManager.init();
});
