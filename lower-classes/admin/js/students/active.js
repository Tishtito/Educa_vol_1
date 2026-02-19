/**
 * ActiveManager - Display list of classes with active students
 * Features: Load active classes and provide navigation links
 */
const ActiveManager = (function () {
	const BASE_URL = '../../backend/public/index.php';
	let activeClassesContainer;

	// DOM element caching
	function cacheDOMElements() {
		activeClassesContainer = document.getElementById('active-classes');
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
		if (activeClassesContainer) {
			activeClassesContainer.innerHTML = '<li style="grid-column: span 3; text-align: center; padding: 20px;">Loading active classes...</li>';
		}
	}

	// Show error state
	function showErrorState(message) {
		if (activeClassesContainer) {
			activeClassesContainer.innerHTML = `<li style="grid-column: span 3; text-align: center; padding: 20px; color: #ef4444;">${escapeHtml(message)}</li>`;
		}
	}

	// Show empty state
	function showEmptyState() {
		if (activeClassesContainer) {
			activeClassesContainer.innerHTML = '<li style="grid-column: span 3; text-align: center; padding: 20px;">No active classes found.</li>';
		}
	}

	// Render active classes with batch rendering
	function renderActiveClasses(classes) {
		if (!activeClassesContainer) return;

		if (!classes || classes.length === 0) {
			showEmptyState();
			return;
		}

		// Batch render class cards
		const classCards = classes.map(grade => {
			const escapedClass = escapeHtml(String(grade));
			const href = `students_active_by_class.html?class=${encodeURIComponent(grade)}`;
			return `
				<a href="${escapeHtml(href)}">
					<li>
						<i class='bx bx-show-alt'></i>
						<span class="info-2">
							<p>${escapedClass}</p>
						</span>
					</li>
				</a>
			`;
		}).join('');

		activeClassesContainer.innerHTML = classCards;
	}

	// Load active classes
	async function loadActiveClasses() {
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

			// Fetch active classes
			const listRes = await fetch(`${BASE_URL}/students/active-classes`, { 
				credentials: 'include' 
			});
			const list = await listRes.json();

			if (!list.success || !list.data) {
				showErrorState('Failed to load active classes.');
				return;
			}

			renderActiveClasses(list.data);
		} catch (error) {
			console.error('Failed to load active classes', error);
			showErrorState('Failed to load active classes. Please try again.');
		}
	}

	// Initialize
	async function init() {
		cacheDOMElements();
		showLoadingState();
		await loadActiveClasses();
	}

	return {
		init
	};
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
	ActiveManager.init();
});
