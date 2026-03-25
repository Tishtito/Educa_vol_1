
// move_class.js - optimized per frontend guidelines
(function () {
	const baseUrl = "../../backend/public/index.php";
	const classesEndpoint = `${baseUrl}/settings/classes`;
	const studentsEndpoint = `${baseUrl}/students/active-by-class`;
	const moveAllEndpoint = `${baseUrl}/settings/classes/move-all`;
	const moveStudentEndpoint = `${baseUrl}/settings/classes/move-student`;

	const fromClass = document.getElementById('fromClass');
	const targetClass = document.getElementById('targetClass');
	const studentsTable = document.getElementById('studentsTable');
	const moveAllForm = document.getElementById('moveAllForm');

	// Render students
	function renderStudents(students) {
		if (!students.length) {
			studentsTable.innerHTML = '<tr><td colspan="3" class="empty-state">No students found in this class.</td></tr>';
			return;
		}
		// Batch DOM update
		studentsTable.innerHTML = students.map((student) => `
			<tr>
				<td>${student.name}</td>
				<td>${student.class}</td>
				<td><button class="action-btn" data-id="${student.student_id}">Move</button></td>
			</tr>
		`).join('');
	}

	// Load students
	async function loadStudents(className) {
		if (!className) {
			studentsTable.innerHTML = '<tr><td colspan="3" class="empty-state">Select a class to load students.</td></tr>';
			return;
		}
		// Show loading indicator
		studentsTable.innerHTML = '<tr><td colspan="3" class="empty-state">Loading...</td></tr>';
		try {
			const response = await fetch(`${studentsEndpoint}?class=${encodeURIComponent(className)}`);
			const data = await response.json();
			if (!data.success) {
				throw new Error(data.message || 'Failed to load students');
			}
			renderStudents(data.data || []);
		} catch (error) {
			studentsTable.innerHTML = `<tr><td colspan="3" class="empty-state">${error.message}</td></tr>`;
		}
	}

	// Load classes
	async function loadClasses() {
		// Show loading indicator
		fromClass.innerHTML = '<option value="">Loading...</option>';
		targetClass.innerHTML = '<option value="">Loading...</option>';
		try {
			const response = await fetch(classesEndpoint);
			const data = await response.json();
			if (!data.success) {
				throw new Error(data.message || 'Failed to load classes');
			}
			const classes = data.data || [];
			fromClass.innerHTML = '<option value="">-- Select class --</option>';
			targetClass.innerHTML = '<option value="">-- Select class --</option>';
			// Batch DOM update
			const fromOptions = classes.map(cls => `<option value="${cls.class_name}">${cls.class_name}</option>`).join('');
			const targetOptions = classes.map(cls => `<option value="${cls.class_name}">${cls.class_name}</option>`).join('');
			fromClass.innerHTML += fromOptions;
			targetClass.innerHTML += targetOptions;
		} catch (error) {
			swal('Error', error.message, 'error');
		}
	}

	// Event delegation for class change
	if (fromClass) {
		fromClass.addEventListener('change', (event) => {
			loadStudents(event.target.value);
		});
	}

	// Move all form submit
	if (moveAllForm) {
		moveAllForm.addEventListener('submit', async (event) => {
			event.preventDefault();
			const fromValue = fromClass.value;
			const targetValue = targetClass.value;
			if (!fromValue || !targetValue) {
				swal('Missing data', 'Please select both classes.', 'warning');
				return;
			}
			if (fromValue === targetValue) {
				swal('Invalid selection', 'The destination class must be different.', 'warning');
				return;
			}
			try {
				const response = await fetch(moveAllEndpoint, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ from_class: fromValue, target_class: targetValue })
				});
				const data = await response.json();
				if (!data.success) {
					throw new Error(data.message || 'Failed to move class');
				}
				swal('Success', data.message || 'Class moved successfully.', 'success');
				loadStudents(fromValue);
			} catch (error) {
				swal('Error', error.message, 'error');
			}
		});
	}

	// Event delegation for move button
	if (studentsTable) {
		studentsTable.addEventListener('click', async (event) => {
			const button = event.target.closest('button[data-id]');
			if (!button) return;
			const studentId = button.getAttribute('data-id');
			const targetValue = targetClass.value;
			if (!targetValue) {
				swal('Select destination', 'Choose the target class first.', 'warning');
				return;
			}
			try {
				const response = await fetch(moveStudentEndpoint, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ student_id: studentId, target_class: targetValue })
				});
				const data = await response.json();
				if (!data.success) {
					throw new Error(data.message || 'Failed to move student');
				}
				swal('Success', data.message || 'Student moved successfully.', 'success');
				loadStudents(fromClass.value);
			} catch (error) {
				swal('Error', error.message, 'error');
			}
		});
	}

	loadClasses();
})();
