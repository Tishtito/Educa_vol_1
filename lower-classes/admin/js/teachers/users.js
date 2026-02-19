/**
 * UsersManager - Manage teachers and examiners (CRUD operations)
 * Features: Add/edit/delete teachers and examiners with modal forms, class assignment
 */
const UsersManager = (function () {
	const BASE_URL = '../../backend/public/index.php';
	let classesData = [];
	let subjectsData = [];

	// DOM elements
	let teachersBody, examinersBody;
	let editTeacherModal, editTeacherForm, editTeacherClose, editTeacherCancel;
	let addTeacherModal, addTeacherForm, addTeacherOpen, addTeacherClose, addTeacherCancel;
	let editExaminerModal, editExaminerForm, editExaminerClose, editExaminerCancel;
	let addExaminerModal, addExaminerForm, addExaminerOpen, addExaminerClose, addExaminerCancel;
	let editTeacherClassSelect, addTeacherClassSelect, editExaminerClassSelect, addExaminerClassSelect;

	// DOM element caching
	function cacheDOMElements() {
		teachersBody = document.getElementById('teachers-body');
		examinersBody = document.getElementById('examiners-body');
		editTeacherModal = document.getElementById('edit-teacher-modal');
		editTeacherForm = document.getElementById('edit-teacher-form');
		editTeacherClose = document.getElementById('edit-teacher-close');
		editTeacherCancel = document.getElementById('edit-teacher-cancel');
		addTeacherModal = document.getElementById('add-teacher-modal');
		addTeacherForm = document.getElementById('add-teacher-form');
		addTeacherOpen = document.getElementById('add-teacher-open');
		addTeacherClose = document.getElementById('add-teacher-close');
		addTeacherCancel = document.getElementById('add-teacher-cancel');
		editExaminerModal = document.getElementById('edit-examiner-modal');
		editExaminerForm = document.getElementById('edit-examiner-form');
		editExaminerClose = document.getElementById('edit-examiner-close');
		editExaminerCancel = document.getElementById('edit-examiner-cancel');
		addExaminerModal = document.getElementById('add-examiner-modal');
		addExaminerForm = document.getElementById('add-examiner-form');
		addExaminerOpen = document.getElementById('add-examiner-open');
		addExaminerClose = document.getElementById('add-examiner-close');
		addExaminerCancel = document.getElementById('add-examiner-cancel');
		editTeacherClassSelect = document.getElementById('edit-teacher-class');
		addTeacherClassSelect = document.getElementById('add-teacher-class');
		editExaminerClassSelect = document.getElementById('edit-examiner-class');
		addExaminerClassSelect = document.getElementById('add-examiner-class');
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

	// Modal management
	function toggleModal(modal, show) {
		if (!modal) return;
		if (show) {
			modal.classList.add('active');
		} else {
			modal.classList.remove('active');
		}
	}

	// Populate class options
	function populateClassSelects() {
		const optionsHtml = [
			'<option value="">-- Select Class --</option>',
			'<option value="">Not class teacher</option>'
		]
			.concat(classesData.map(item => `<option value="${escapeHtml(item.class_name)}">${escapeHtml(item.class_name)}</option>`))
			.join('');

		if (editTeacherClassSelect) editTeacherClassSelect.innerHTML = optionsHtml;
		if (addTeacherClassSelect) addTeacherClassSelect.innerHTML = optionsHtml;
		if (editExaminerClassSelect) editExaminerClassSelect.innerHTML = optionsHtml;
		if (addExaminerClassSelect) addExaminerClassSelect.innerHTML = optionsHtml;
	}

	// Show loading state
	function showLoadingState(tableBody) {
		if (!tableBody) return;
		tableBody.innerHTML = `
			<tr>
				<td colspan="4" style="text-align: center; padding: 40px;">
					<div style="display: inline-block;">
						<div style="
							border: 4px solid #f3f3f3;
							border-top: 4px solid #3498db;
							border-radius: 50%;
							width: 40px;
							height: 40px;
							animation: spin 1s linear infinite;
							margin: 0 auto;">
						</div>
						<p style="margin-top: 10px; color: #666;">Loading...</p>
					</div>
				</td>
			</tr>
		`;
	}

	// Load initial data
	async function loadInitialData() {
		try {
			// Show loading indicators
			showLoadingState(teachersBody);
			showLoadingState(examinersBody);

			// Auth check
			const authRes = await fetch(`${BASE_URL}/auth/check`, { 
				credentials: 'include' 
			});
			const auth = await authRes.json();
			if (!auth.authenticated) {
				window.location.replace('../login.html');
				return;
			}

			// Load classes and subjects in parallel
			const [classesRes, subjectsRes] = await Promise.all([
				fetch(`${BASE_URL}/classes`, { credentials: 'include' }),
				fetch(`${BASE_URL}/subjects`, { credentials: 'include' })
			]);

			const classes = await classesRes.json();
			const subjects = await subjectsRes.json();

			if (classes.success) {
				classesData = classes.data || [];
				populateClassSelects();
			}

			if (subjects.success) {
				subjectsData = subjects.data || [];
			}

			// Load teachers and examiners in parallel
			const [teachersRes, examinersRes] = await Promise.all([
				fetch(`${BASE_URL}/teachers`, { credentials: 'include' }),
				fetch(`${BASE_URL}/examiners`, { credentials: 'include' })
			]);

			const teachers = await teachersRes.json();
			const examiners = await examinersRes.json();

			if (teachers.success) {
				renderTeachersTable(teachers.data || []);
			} else {
				showErrorState(teachersBody, 'Failed to load teachers');
			}

			if (examiners.success) {
				renderExaminersTable(examiners.data || []);
			} else {
				showErrorState(examinersBody, 'Failed to load examiners');
			}
		} catch (error) {
			console.error('Failed to load users data', error);
			showErrorState(teachersBody, 'Error loading teachers');
			showErrorState(examinersBody, 'Error loading examiners');
		}
	}

	// Show error state
	function showErrorState(tableBody, message) {
		if (!tableBody) return;
		tableBody.innerHTML = `
			<tr>
				<td colspan="4" style="text-align: center; padding: 20px; color: #e74c3c;">
					${escapeHtml(message)}
				</td>
			</tr>
		`;
	}

	// Render teachers table with batch rendering
	function renderTeachersTable(teachers) {
		if (!teachersBody) return;

		if (teachers.length === 0) {
			teachersBody.innerHTML = '<tr><td colspan="4">No teachers found.</td></tr>';
			return;
		}

		const tableRows = teachers.map(teacher => {
			const name = escapeHtml(teacher.name);
			const classAssigned = escapeHtml(teacher.class_assigned || '-');
			const teacherId = escapeHtml(String(teacher.id));

			return `
				<tr>
					<td><p>${name}</p></td>
					<td><p>${classAssigned}</p></td>
					<td><a href="#" class="edit-teacher" data-id="${teacherId}" data-name="${escapeHtml(teacher.name)}" data-class="${classAssigned}"><span class='status process'>edit</span></a></td>
					<td><a href="#" class="delete-teacher" data-id="${teacherId}"><span class='status delete'>delete</span></a></td>
				</tr>
			`;
		}).join('');

		teachersBody.innerHTML = tableRows;
	}

	// Render examiners table with batch rendering
	function renderExaminersTable(examiners) {
		if (!examinersBody) return;

		if (!examiners || examiners.length === 0) {
			examinersBody.innerHTML = '<tr><td colspan="4">No examiners found.</td></tr>';
			return;
		}

		const tableRows = examiners.map(examiner => {
			const name = escapeHtml(examiner.name);
			const classAssigned = escapeHtml(examiner.class_assigned || 'No Class Assigned');
			const examinerId = escapeHtml(String(examiner.examiner_id || examiner.id));

			return `
				<tr>
					<td><p>${name}</p></td>
					<td><p>${classAssigned}</p></td>
					<td><a href="#" class="edit-examiner" data-id="${examinerId}" data-name="${escapeHtml(examiner.name)}" data-class="${classAssigned}"><span class='status process'>edit</span></a></td>
					<td><a href="#" class="delete-examiner" data-id="${examinerId}"><span class='status delete'>delete</span></a></td>
				</tr>
			`;
		}).join('');

		examinersBody.innerHTML = tableRows;
	}

	// Setup modal listeners
	function setupModalListeners() {
		// Edit teacher modal
		if (editTeacherClose) {
			editTeacherClose.addEventListener('click', () => {
				toggleModal(editTeacherModal, false);
				if (editTeacherForm) editTeacherForm.reset();
			});
		}
		if (editTeacherCancel) {
			editTeacherCancel.addEventListener('click', () => {
				toggleModal(editTeacherModal, false);
				if (editTeacherForm) editTeacherForm.reset();
			});
		}
		if (editTeacherModal) {
			editTeacherModal.addEventListener('click', event => {
				if (event.target === editTeacherModal) {
					toggleModal(editTeacherModal, false);
					if (editTeacherForm) editTeacherForm.reset();
				}
			});
		}

		// Add teacher modal
		if (addTeacherOpen) {
			addTeacherOpen.addEventListener('click', event => {
				event.preventDefault();
				toggleModal(addTeacherModal, true);
			});
		}
		if (addTeacherClose) {
			addTeacherClose.addEventListener('click', () => {
				toggleModal(addTeacherModal, false);
				if (addTeacherForm) addTeacherForm.reset();
			});
		}
		if (addTeacherCancel) {
			addTeacherCancel.addEventListener('click', () => {
				toggleModal(addTeacherModal, false);
				if (addTeacherForm) addTeacherForm.reset();
			});
		}
		if (addTeacherModal) {
			addTeacherModal.addEventListener('click', event => {
				if (event.target === addTeacherModal) {
					toggleModal(addTeacherModal, false);
					if (addTeacherForm) addTeacherForm.reset();
				}
			});
		}

		// Edit examiner modal
		if (editExaminerClose) {
			editExaminerClose.addEventListener('click', () => {
				toggleModal(editExaminerModal, false);
				if (editExaminerForm) editExaminerForm.reset();
			});
		}
		if (editExaminerCancel) {
			editExaminerCancel.addEventListener('click', () => {
				toggleModal(editExaminerModal, false);
				if (editExaminerForm) editExaminerForm.reset();
			});
		}
		if (editExaminerModal) {
			editExaminerModal.addEventListener('click', event => {
				if (event.target === editExaminerModal) {
					toggleModal(editExaminerModal, false);
					if (editExaminerForm) editExaminerForm.reset();
				}
			});
		}

		// Add examiner modal
		if (addExaminerOpen) {
			addExaminerOpen.addEventListener('click', event => {
				event.preventDefault();
				toggleModal(addExaminerModal, true);
			});
		}
		if (addExaminerClose) {
			addExaminerClose.addEventListener('click', () => {
				toggleModal(addExaminerModal, false);
				if (addExaminerForm) addExaminerForm.reset();
			});
		}
		if (addExaminerCancel) {
			addExaminerCancel.addEventListener('click', () => {
				toggleModal(addExaminerModal, false);
				if (addExaminerForm) addExaminerForm.reset();
			});
		}
		if (addExaminerModal) {
			addExaminerModal.addEventListener('click', event => {
				if (event.target === addExaminerModal) {
					toggleModal(addExaminerModal, false);
					if (addExaminerForm) addExaminerForm.reset();
				}
			});
		}
	}

	// Setup table event delegation
	function setupTableListeners() {
		// Teachers table
		if (teachersBody) {
			teachersBody.addEventListener('click', async event => {
				const editLink = event.target.closest('.edit-teacher');
				const deleteLink = event.target.closest('.delete-teacher');

				if (editLink) {
					event.preventDefault();
					const teacherId = editLink.dataset.id;
					const teacherName = editLink.dataset.name;
					const teacherClass = editLink.dataset.class;

					document.getElementById('edit-teacher-id').value = teacherId;
					document.getElementById('edit-teacher-name').value = teacherName;
					document.getElementById('edit-teacher-class').value = teacherClass === '-' ? '' : teacherClass;
					document.getElementById('edit-teacher-password').value = '';

					toggleModal(editTeacherModal, true);
				}

				if (deleteLink) {
					event.preventDefault();
					const teacherId = deleteLink.dataset.id;
					handleDeleteTeacher(teacherId);
				}
			});
		}

		// Examiners table
		if (examinersBody) {
			examinersBody.addEventListener('click', async event => {
				const editLink = event.target.closest('.edit-examiner');
				const deleteLink = event.target.closest('.delete-examiner');

				if (editLink) {
					event.preventDefault();
					const examinerId = editLink.dataset.id;
					await loadExaminerDetail(examinerId);
				}

				if (deleteLink) {
					event.preventDefault();
					const examinerId = deleteLink.dataset.id;
					handleDeleteExaminer(examinerId);
				}
			});
		}
	}

	// Load examiner detail for editing
	async function loadExaminerDetail(examinerId) {
		try {
			const detailRes = await fetch(`${BASE_URL}/examiners/detail?examiner_id=${encodeURIComponent(examinerId)}`, {
				credentials: 'include'
			});
			const detail = await detailRes.json();

			if (!detail.success) {
				swal('Error', 'Failed to load examiner details.', 'error');
				return;
			}

			document.getElementById('edit-examiner-id').value = detail.data.examiner_id;
			document.getElementById('edit-examiner-name').value = detail.data.name;
			document.getElementById('edit-examiner-class').value = detail.data.class_assigned || '';
			document.getElementById('edit-examiner-password').value = '';

			toggleModal(editExaminerModal, true);
		} catch (error) {
			console.error('Failed to load examiner', error);
			swal('Error', 'Failed to load examiner details.', 'error');
		}
	}

	// Handle delete teacher
	function handleDeleteTeacher(teacherId) {
		swal({
			title: 'Caution!',
			text: 'Are you sure you want to delete?',
			icon: 'warning',
			buttons: true,
			dangerMode: true,
		}).then(async isConfirmed => {
			if (!isConfirmed) return;

			try {
				const response = await fetch(`${BASE_URL}/teachers/delete`, {
					method: 'POST',
					credentials: 'include',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ id: teacherId })
				});
				const result = await response.json();

				if (result.success) {
					swal('Success', 'Teacher deleted successfully.', 'success').then(() => {
						window.location.reload();
					});
				} else {
					swal('Error', result.message || 'Failed to delete teacher.', 'error');
				}
			} catch (error) {
				console.error('Failed to delete teacher', error);
				swal('Error', 'Failed to delete teacher.', 'error');
			}
		});
	}

	// Handle delete examiner
	function handleDeleteExaminer(examinerId) {
		swal({
			title: 'Caution!',
			text: 'Are you sure you want to delete?',
			icon: 'warning',
			buttons: true,
			dangerMode: true,
		}).then(async isConfirmed => {
			if (!isConfirmed) return;

			try {
				const response = await fetch(`${BASE_URL}/examiners/delete`, {
					method: 'POST',
					credentials: 'include',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ examiner_id: examinerId })
				});
				const result = await response.json();

				if (result.success) {
					swal('Success', 'Examiner deleted successfully.', 'success').then(() => {
						window.location.reload();
					});
				} else {
					swal('Error', result.message || 'Failed to delete examiner.', 'error');
				}
			} catch (error) {
				console.error('Failed to delete examiner', error);
				swal('Error', 'Failed to delete examiner.', 'error');
			}
		});
	}

	// Setup form submissions
	function setupFormListeners() {
		// Edit teacher form
		if (editTeacherForm) {
			editTeacherForm.addEventListener('submit', async event => {
				event.preventDefault();
				const formData = new FormData(editTeacherForm);

				try {
					const response = await fetch(`${BASE_URL}/teachers/update`, {
						method: 'POST',
						body: formData,
						credentials: 'include',
					});
					const result = await response.json();

					if (result.success) {
						swal('Success', 'Teacher updated successfully.', 'success').then(() => {
							toggleModal(editTeacherModal, false);
							editTeacherForm.reset();
							window.location.reload();
						});
					} else {
						swal('Error', result.message || 'Failed to update teacher.', 'error');
					}
				} catch (error) {
					console.error('Failed to update teacher', error);
					swal('Error', 'Failed to update teacher.', 'error');
				}
			});
		}

		// Add teacher form
		if (addTeacherForm) {
			addTeacherForm.addEventListener('submit', async event => {
				event.preventDefault();
				const formData = new FormData(addTeacherForm);

				try {
					const response = await fetch(`${BASE_URL}/teachers/create`, {
						method: 'POST',
						body: formData,
						credentials: 'include',
					});
					const result = await response.json();

					if (result.success) {
						swal('Success', 'Teacher created successfully.', 'success').then(() => {
							toggleModal(addTeacherModal, false);
							addTeacherForm.reset();
							window.location.reload();
						});
					} else {
						swal('Error', result.message || 'Failed to create teacher.', 'error');
					}
				} catch (error) {
					console.error('Failed to create teacher', error);
					swal('Error', 'Failed to create teacher.', 'error');
				}
			});
		}

		// Edit examiner form
		if (editExaminerForm) {
			editExaminerForm.addEventListener('submit', async event => {
				event.preventDefault();
				const formData = new FormData(editExaminerForm);

				try {
					const response = await fetch(`${BASE_URL}/examiners/update`, {
						method: 'POST',
						body: formData,
						credentials: 'include',
					});
					const result = await response.json();

					if (result.success) {
						swal('Success', 'Examiner updated successfully.', 'success').then(() => {
							toggleModal(editExaminerModal, false);
							editExaminerForm.reset();
							window.location.reload();
						});
					} else {
						swal('Error', result.message || 'Failed to update examiner.', 'error');
					}
				} catch (error) {
					console.error('Failed to update examiner', error);
					swal('Error', 'Failed to update examiner.', 'error');
				}
			});
		}

		// Add examiner form
		if (addExaminerForm) {
			addExaminerForm.addEventListener('submit', async event => {
				event.preventDefault();
				const formData = new FormData(addExaminerForm);

				try {
					const response = await fetch(`${BASE_URL}/examiners/create`, {
						method: 'POST',
						body: formData,
						credentials: 'include',
					});
					const result = await response.json();

					if (result.success) {
						swal('Success', 'Examiner created successfully.', 'success').then(() => {
							toggleModal(addExaminerModal, false);
							addExaminerForm.reset();
							window.location.reload();
						});
					} else {
						swal('Error', result.message || 'Failed to create examiner.', 'error');
					}
				} catch (error) {
					console.error('Failed to create examiner', error);
					swal('Error', 'Failed to create examiner.', 'error');
				}
			});
		}
	}

	// Initialize
	async function init() {
		cacheDOMElements();
		setupModalListeners();
		setupTableListeners();
		setupFormListeners();
		await loadInitialData();
	}

	return {
		init
	};
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
	UsersManager.init();
});
