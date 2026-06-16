
// classes.js - optimized per frontend guidelines
(async function () {
	const baseUrl = "../../backend/public/index.php";
	const tableBody = document.querySelector('tbody');
	const addClassBtn = document.getElementById('addClassBtn');
	const addClassModal = document.getElementById('addClassModal');
	const closeClassModal = document.getElementById('closeClassModal');
	const cancelClass = document.getElementById('cancelClass');
	const addClassForm = document.getElementById('addClassForm');

	// Modal handling
	const toggleModal = (show) => {
		if (!addClassModal) return;
		addClassModal.classList.toggle('active', show);
		addClassModal.setAttribute('aria-hidden', show ? 'false' : 'true');
	};

	// Event delegation for delete
	const bindDeleteLinks = () => {
		tableBody.addEventListener('click', async (event) => {
			const link = event.target.closest('.delete-link');
			if (!link) return;
			event.preventDefault();
			const classId = link.getAttribute('data-id');
			const confirm = await swal({
				title: 'Caution!',
				text: 'Are you sure you want to delete?',
				icon: 'warning',
				buttons: true,
				dangerMode: true,
			});
			if (!confirm) return;
			try {
				const res = await fetch(`${baseUrl}/settings/classes/delete`, {
					method: 'POST',
					credentials: 'include',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ class_id: Number(classId) })
				});
				const data = await res.json();
				if (!data.success) {
					swal('Error', data.message || 'Failed to delete class.', 'error');
					return;
				}
				await loadClasses();
				swal('Deleted', 'Class removed successfully.', 'success');
			} catch (error) {
				// Optionally log error
				swal('Error', 'Failed to delete class.', 'error');
			}
		});
	};

	// Load classes
	const loadClasses = async () => {
		if (tableBody) tableBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">Loading...</td></tr>';
		try {
			// Parallel fetch: auth and classes
			const [authRes, res] = await Promise.all([
				fetch(`${baseUrl}/auth/check`, { credentials: "include" }),
				fetch(`${baseUrl}/settings/classes`, { credentials: 'include' })
			]);
			const auth = await authRes.json();
			if (!auth.authenticated) {
				window.location.replace("../login.html");
				return;
			}
			const data = await res.json();
			if (!data.success || !Array.isArray(data.data) || data.data.length === 0) {
				tableBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">No classes found.</td></tr>';
				return;
			}
			// Batch DOM update
			const rows = data.data.map(row => `
				<tr>
					<td><p>${row.class_name}</p></td>
					<td><a href="#" class="delete-link" data-id="${row.class_id}"><span class="status delete">delete</span></a></td>
				</tr>
			`);
			tableBody.innerHTML = rows.join('');
		} catch (error) {
			tableBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">Failed to load classes.</td></tr>';
		}
	};

	// Modal open/close
	if (addClassBtn) {
		addClassBtn.addEventListener('click', (event) => {
			event.preventDefault();
			toggleModal(true);
		});
	}
	if (closeClassModal) {
		closeClassModal.addEventListener('click', () => toggleModal(false));
	}
	if (cancelClass) {
		cancelClass.addEventListener('click', () => toggleModal(false));
	}
	if (addClassModal) {
		addClassModal.addEventListener('click', (event) => {
			if (event.target === addClassModal) toggleModal(false);
		});
	}

	// Add class form
	if (addClassForm) {
		addClassForm.addEventListener('submit', async (event) => {
			event.preventDefault();
			const payload = {
				class_name: addClassForm.class_name.value.trim(),
				grade: Number(addClassForm.grade.value),
			};
			if (!payload.class_name || !payload.grade) {
				swal('Error', 'All fields are required!', 'error');
				return;
			}
			try {
				const res = await fetch(`${baseUrl}/settings/classes`, {
					method: 'POST',
					credentials: 'include',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify(payload),
				});
				const data = await res.json();
				if (!data.success) {
					swal('Error', data.message || 'Failed to create class.', 'error');
					return;
				}
				toggleModal(false);
				addClassForm.reset();
				await loadClasses();
				swal('Success', 'Class created successfully!', 'success');
			} catch (error) {
				swal('Error', 'Failed to create class.', 'error');
			}
		});
	}

	bindDeleteLinks();
	await loadClasses();
})();
