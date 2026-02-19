
// editpoints.js - optimized per frontend guidelines
(async function () {
	const baseUrl = "../../backend/public/index.php";
	const gradeRows = document.getElementById('gradeRows');
	const form = document.getElementById('gradeForm');
	const cancelBtn = document.getElementById('cancelBtn');

	// Render grade rows
	const renderRows = (rows) => {
		if (!rows.length) {
			gradeRows.innerHTML = '<tr><td colspan="3" class="text-center">No grade boundaries found.</td></tr>';
			return;
		}
		// Batch DOM update
		const items = rows.map(row => `
			<tr>
				<td>
					<input type="text" class="settings-input" data-field="grade" data-id="${row.id}" value="${row.grade}" required>
				</td>
				<td>
					<input type="number" class="settings-input" data-field="min_marks" data-id="${row.id}" value="${row.min_marks}" required>
				</td>
				<td>
					<input type="number" class="settings-input" data-field="max_marks" data-id="${row.id}" value="${row.max_marks}" required>
				</td>
			</tr>
		`);
		gradeRows.innerHTML = items.join('');
	};

	// Show loading indicator
	if (gradeRows) gradeRows.innerHTML = '<tr><td colspan="3" class="text-center">Loading...</td></tr>';

	try {
		// Parallel fetch: auth and grade boundaries
		const [authRes, listRes] = await Promise.all([
			fetch(`${baseUrl}/auth/check`, { credentials: "include" }),
			fetch(`${baseUrl}/settings/point-boundaries`, { credentials: "include" })
		]);
		const auth = await authRes.json();
		if (!auth.authenticated) {
			window.location.replace("../login.html");
			return;
		}
		const list = await listRes.json();
		if (!list.success) {
			gradeRows.innerHTML = '<tr><td colspan="3" class="text-center">Failed to load grade boundaries.</td></tr>';
			return;
		}
		renderRows(list.data || []);
	} catch (error) {
		gradeRows.innerHTML = '<tr><td colspan="3" class="text-center">Failed to load grade boundaries.</td></tr>';
	}

	// Form submit
	if (form) {
		form.addEventListener('submit', async (event) => {
			event.preventDefault();
			const inputs = gradeRows.querySelectorAll('input[data-id]');
			const payload = {};
			inputs.forEach((input) => {
				const id = input.getAttribute('data-id');
				const field = input.getAttribute('data-field');
				if (!payload[id]) payload[id] = { id: Number(id) };
				payload[id][field] = input.value;
			});
			try {
				const saveRes = await fetch(`${baseUrl}/settings/point-boundaries`, {
					method: 'POST',
					credentials: 'include',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ grades: Object.values(payload) })
				});
				const save = await saveRes.json();
				if (!save.success) {
					swal('Update failed', save.message || 'Unable to update grades.', 'error');
					return;
				}
				swal({
					title: 'Saved',
					text: 'Grade boundaries updated successfully.',
					icon: 'success',
					button: 'OK',
				}).then(() => {
					window.location.href = 'settings/settings.html';
				});
			} catch (error) {
				swal('Update failed', 'Unable to update grades.', 'error');
			}
		});
	}

	// Cancel button
	if (cancelBtn) {
		cancelBtn.addEventListener('click', () => {
			window.location.href = 'settings/settings.html';
		});
	}
})();
