/**
 * StudentsHomeManager - Display students summary with counts
 * Features: Load and display active/finished student counts
 */
const StudentsHomeManager = (function () {
	const BASE_URL = '../../backend/public/index.php';
	let activeCount, finishedCount;

	// DOM element caching
	function cacheDOMElements() {
		activeCount = document.getElementById('active-count');
		finishedCount = document.getElementById('finished-count');
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

	// Update counts
	function updateCounts(data) {
		const active = escapeHtml(String(data.active ?? 0));
		const finished = escapeHtml(String(data.finished ?? 0));

		if (activeCount) activeCount.textContent = active;
		if (finishedCount) finishedCount.textContent = finished;
	}

	// Load student summary
	async function loadStudentsSummary() {
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

			// Fetch summary
			const summaryRes = await fetch(`${BASE_URL}/students/summary`, { 
				credentials: 'include' 
			});
			const summary = await summaryRes.json();

			if (!summary.success || !summary.data) {
				return;
			}

			updateCounts(summary.data);
		} catch (error) {
			console.error('Failed to load students summary', error);
		}
	}

	// Initialize
	async function init() {
		cacheDOMElements();
		await loadStudentsSummary();
	}

	return {
		init
	};
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
	StudentsHomeManager.init();
});
