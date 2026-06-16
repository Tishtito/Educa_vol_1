/**
 * ActiveClassManager - Display active students in a specific class with profile modal
 * Features: Student list filtering by class, profile modal view with detailed information
 */
const ActiveClassManager = (function () {
	const BASE_URL = '../../backend/public/index.php';
	let className = '';
	let studentsBody, classNameEl, tableTitle, profileModal, profileCloseBtn, profileCloseBtn2;
	let profileTitle, profileName, profileClass, profileStatus, profileJoined;
	let profileUpdated, profileResults, profileLastExam, profileLastExamDate;

	// Extract URL parameters
	function extractURLParameters() {
		const params = new URLSearchParams(window.location.search);
		return {
			className: params.get('class') || ''
		};
	}

	// DOM element caching
	function cacheDOMElements() {
		studentsBody = document.getElementById('students-body');
		classNameEl = document.getElementById('class-name');
		tableTitle = document.getElementById('table-title');
		profileModal = document.getElementById('student-profile-modal');
		profileCloseBtn = document.getElementById('profile-close');
		profileCloseBtn2 = document.getElementById('profile-close-btn');
		profileTitle = document.getElementById('profile-title');
		profileName = document.getElementById('profile-name');
		profileClass = document.getElementById('profile-class');
		profileStatus = document.getElementById('profile-status');
		profileJoined = document.getElementById('profile-joined');
		profileUpdated = document.getElementById('profile-updated');
		profileResults = document.getElementById('profile-results');
		profileLastExam = document.getElementById('profile-last-exam');
		profileLastExamDate = document.getElementById('profile-last-exam-date');
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
	function showEmptyState() {
		if (studentsBody) {
			studentsBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No active students found in this class.</td></tr>';
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

	// Toggle modal visibility
	function toggleModal(show) {
		if (!profileModal) return;
		if (show) {
			profileModal.classList.add('active');
		} else {
			profileModal.classList.remove('active');
		}
	}

	// Setup modal event listeners
	function setupModalListeners() {
		if (profileCloseBtn) {
			profileCloseBtn.addEventListener('click', () => toggleModal(false));
		}
		if (profileCloseBtn2) {
			profileCloseBtn2.addEventListener('click', () => toggleModal(false));
		}
		if (profileModal) {
			profileModal.addEventListener('click', event => {
				if (event.target === profileModal) {
					toggleModal(false);
				}
			});
		}
	}

	// Load students for class
	async function loadStudentsByClass() {
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

			// Fetch students list
			const listRes = await fetch(
				`${BASE_URL}/students/active-by-class?class=${encodeURIComponent(className)}`, 
				{ credentials: 'include' }
			);
			const list = await listRes.json();

			if (!list.success || !list.data || list.data.length === 0) {
				showEmptyState();
				return;
			}

			renderStudentsTable(list.data);
		} catch (error) {
			console.error('Failed to load active students', error);
			showErrorState('Failed to load students. Please try again.');
		}
	}

	// Render students table with batch rendering
	function renderStudentsTable(students) {
		if (!studentsBody) return;

		const tableRows = students.map((student, index) => {
			const name = escapeHtml(student.name);
			const studentClass = escapeHtml(student.class);
			const joinedOn = formatDate(student.created_at);
			const studentId = escapeHtml(String(student.student_id));

			return `
				<tr>
					<td>${index + 1}</td>
					<td>${name}</td>
					<td>${studentClass}</td>
					<td>${joinedOn}</td>
					<td>
						<a href="#" class="view-student" data-id="${studentId}">
							<span class="status process">View</span>
						</a>
						<button class="edit-student-btn" data-id="${studentId}" data-name="${student.name}" style="margin-left: 10px; padding: 5px 10px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
							<i class="fas fa-pencil"></i> Edit
						</button>
					</td>
				</tr>
			`;
		}).join('');

		studentsBody.innerHTML = tableRows;
	}

	// Load and display student profile
	async function loadStudentProfile(studentId) {
		try {
			const profileRes = await fetch(
				`${BASE_URL}/students/profile?student_id=${encodeURIComponent(studentId)}`, 
				{ credentials: 'include' }
			);
			const profile = await profileRes.json();

			if (!profile.success || !profile.data) {
				console.error('Failed to load profile');
				return;
			}

			populateProfileModal(profile.data);
			toggleModal(true);
		} catch (error) {
			console.error('Failed to load student profile', error);
		}
	}

	// Populate profile modal with data
	function populateProfileModal(data) {
		const student = data.student || {};
		const name = escapeHtml(student.name || '-');
		const studentClass = escapeHtml(student.class || '-');
		const status = escapeHtml(student.status || '-');
		const resultsCount = escapeHtml(String(data.results_count ?? 0));
		const lastExamName = escapeHtml(data.last_exam_name || '-');

		const joinedDate = formatDate(student.created_at);
		const updatedDate = formatDate(student.updated_at);
		const lastExamDate = formatDate(data.last_exam_date);

		if (profileTitle) profileTitle.textContent = `Student Profile - ${name}`;
		if (profileName) profileName.textContent = name;
		if (profileClass) profileClass.textContent = studentClass;
		if (profileStatus) profileStatus.textContent = status;
		if (profileJoined) profileJoined.textContent = joinedDate;
		if (profileUpdated) profileUpdated.textContent = updatedDate;
		if (profileResults) profileResults.textContent = resultsCount;
		if (profileLastExam) profileLastExam.textContent = lastExamName;
		if (profileLastExamDate) profileLastExamDate.textContent = lastExamDate;
	}

	// Attach event listeners
	function attachEventListeners() {
		// Event delegation for view-student links
		if (studentsBody) {
			studentsBody.addEventListener('click', async event => {
				const link = event.target.closest('.view-student');
				if (!link) return;
				event.preventDefault();

				const studentId = link.dataset.id;
				if (!studentId) return;

				await loadStudentProfile(studentId);
			});
		}

		// Event delegation for edit-student buttons
		if (studentsBody) {
			studentsBody.addEventListener('click', event => {
				const editBtn = event.target.closest('.edit-student-btn');
				if (!editBtn) return;
				event.preventDefault();

				const studentId = editBtn.dataset.id;
				const currentName = editBtn.dataset.name;
				if (!studentId) return;

				openEditNameModal(studentId, currentName);
			});
		}
	}

	// Open edit name modal
	function openEditNameModal(studentId, currentName) {
		let editModal = document.getElementById('edit-name-modal');
		
		if (!editModal) {
			// Create modal if it doesn't exist
			editModal = document.createElement('div');
			editModal.id = 'edit-name-modal';
			editModal.style.cssText = `
				position: fixed;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				background: rgba(0, 0, 0, 0.5);
				display: none;
				align-items: center;
				justify-content: center;
				z-index: 1000;
			`;
			editModal.innerHTML = `
				<div style="background: white; padding: 30px; border-radius: 8px; width: 90%; max-width: 400px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
					<h2 style="margin-top: 0; margin-bottom: 20px;">Edit Student Name</h2>
					<form id="edit-name-form">
						<div style="margin-bottom: 15px;">
							<label for="edit-name-input" style="display: block; margin-bottom: 8px; font-weight: 500;">Student Name:</label>
							<input 
								type="text" 
								id="edit-name-input" 
								required 
								style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 14px;"
							/>
						</div>
						<div style="display: flex; gap: 10px;">
							<button type="submit" style="flex: 1; padding: 10px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 500;">Save</button>
							<button type="button" id="edit-close-btn" style="flex: 1; padding: 10px; background-color: #666; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 500;">Cancel</button>
						</div>
					</form>
				</div>
			`;
			document.body.appendChild(editModal);
		}
		
		document.getElementById('edit-name-input').value = currentName;
		editModal.style.display = 'flex';
		
		// Close button handler
		document.getElementById('edit-close-btn').onclick = () => {
			editModal.style.display = 'none';
		};
		
		// Click outside modal to close
		editModal.onclick = (e) => {
			if (e.target === editModal) {
				editModal.style.display = 'none';
			}
		};
		
		// Form submission
		const form = document.getElementById('edit-name-form');
		form.onsubmit = async (e) => {
			e.preventDefault();
			const newName = document.getElementById('edit-name-input').value.trim();
			
			if (!newName) {
				alert('Please enter a student name');
				return;
			}
			
			if (newName === currentName) {
				editModal.style.display = 'none';
				return;
			}
			
			await updateStudentName(studentId, newName);
			editModal.style.display = 'none';
		};
	}

	// Update student name via API
	async function updateStudentName(studentId, newName) {
		try {
			const response = await fetch(`${BASE_URL}/students/update-name`, {
				method: 'POST',
				credentials: 'include',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					student_id: studentId,
					name: newName
				})
			});
			
			const result = await response.json();
			
			if (result.success) {
				// Update the table cell with the new name
				const editBtn = document.querySelector(`[data-id="${studentId}"]`);
				if (editBtn) {
					const row = editBtn.closest('tr');
					if (row) {
						row.cells[1].textContent = newName;
						editBtn.dataset.name = newName;
					}
				}
				
				// Show success message
				const messageEl = document.createElement('div');
				messageEl.style.cssText = `
					position: fixed;
					top: 20px;
					right: 20px;
					background-color: #4CAF50;
					color: white;
					padding: 15px 20px;
					border-radius: 4px;
					z-index: 2000;
				`;
				messageEl.textContent = 'Student name updated successfully';
				document.body.appendChild(messageEl);
				
				setTimeout(() => messageEl.remove(), 3000);
			} else {
				alert('Error: ' + (result.message || 'Failed to update student name'));
			}
		} catch (error) {
			alert('Error updating student name: ' + error.message);
		}
	}

	// Initialize
	async function init() {
		cacheDOMElements();

		const { className: paramClassName } = extractURLParameters();

		// Validate class name
		if (!paramClassName) {
			showErrorState('Invalid class selected.');
			return;
		}

		className = paramClassName;

		// Update header and title
		if (classNameEl) classNameEl.textContent = className;
		if (tableTitle) tableTitle.textContent = `Active Students in ${className}`;

		// Setup listeners
		setupModalListeners();
		attachEventListeners();

		// Load data
		showLoadingState();
		await loadStudentsByClass();
	}

	return {
		init
	};
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
	ActiveClassManager.init();
});
