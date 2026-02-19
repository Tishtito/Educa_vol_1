/**
 * FinishedManager - Display list of years with finished students
 * Features: Load finished year groups and provide navigation links
 */
const FinishedManager = (function () {
	const BASE_URL = '../../backend/public/index.php';
	let finishedYearsContainer;

	// DOM element caching
	function cacheDOMElements() {
		finishedYearsContainer = document.getElementById('finished-years');
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
		if (finishedYearsContainer) {
			finishedYearsContainer.innerHTML = '<li style="grid-column: span 3; text-align: center; padding: 20px;">Loading finished years...</li>';
		}
	}

	// Show error state
	function showErrorState(message) {
		if (finishedYearsContainer) {
			finishedYearsContainer.innerHTML = `<li style="grid-column: span 3; text-align: center; padding: 20px; color: #ef4444;">${escapeHtml(message)}</li>`;
		}
	}

	// Show empty state
	function showEmptyState() {
		if (finishedYearsContainer) {
			finishedYearsContainer.innerHTML = '<li style="grid-column: span 3; text-align: center; padding: 20px;">No finished students found.</li>';
		}
	}

	// Render finished years with batch rendering
	function renderFinishedYears(years) {
		if (!finishedYearsContainer) return;

		if (!years || years.length === 0) {
			showEmptyState();
			return;
		}

		// Batch render year cards
		const yearCards = years.map(year => {
			const escapedYear = escapeHtml(String(year));
			const href = `students_finished_by_year.html?year=${encodeURIComponent(year)}`;
			return `
				<a href="${escapeHtml(href)}">
					<li>
						<i class='bx bx-calendar-check'></i>
						<span class="info-2">
							<p>${escapedYear}</p>
						</span>
					</li>
				</a>
			`;
		}).join('');

		finishedYearsContainer.innerHTML = yearCards;
	}

	// Load finished years
	async function loadFinishedYears() {
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

			// Fetch finished years
			const listRes = await fetch(`${BASE_URL}/students/finished-years`, { 
				credentials: 'include' 
			});
			const list = await listRes.json();

			if (!list.success || !list.data) {
				showErrorState('Failed to load finished years.');
				return;
			}

			renderFinishedYears(list.data);
		} catch (error) {
			console.error('Failed to load finished years', error);
			showErrorState('Failed to load finished years. Please try again.');
		}
	}

	// Initialize
	async function init() {
		cacheDOMElements();
		showLoadingState();
		await loadFinishedYears();
	}

	return {
		init
	};
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
	FinishedManager.init();
});
