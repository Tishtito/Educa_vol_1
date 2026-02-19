
// send_marks.js - optimized per frontend guidelines
(async function () {
	const baseUrl = "../../backend/public/index.php";
	const smsExamSelect = document.getElementById('smsExamSelect');
	const smsClassSelect = document.getElementById('smsClassSelect');
	const smsLoadBtn = document.getElementById('smsLoadBtn');
	const smsSendAllBtn = document.getElementById('smsSendAllBtn');
	const smsResultsTable = document.getElementById('smsResultsTable');
	let smsExamName = '';

	const renderTableMessage = (message) => {
		if (!smsResultsTable) return;
		const tbody = smsResultsTable.querySelector('tbody');
		if (!tbody) return;
		tbody.innerHTML = `<tr><td colspan="7">${message}</td></tr>`;
	};

	const populateSelect = (selectEl, rows, valueKey, labelKey) => {
		if (!selectEl) return;
		selectEl.innerHTML = '<option value="">-- Select --</option>';
		rows.forEach(row => {
			const option = document.createElement('option');
			option.value = row[valueKey];
			option.textContent = row[labelKey];
			selectEl.appendChild(option);
		});
	};

	const loadSmsFilters = async () => {
		try {
			const [examsRes, classesRes] = await Promise.all([
				fetch(`${baseUrl}/settings/exams`, { credentials: 'include' }),
				fetch(`${baseUrl}/settings/classes`, { credentials: 'include' })
			]);
			const examsJson = await examsRes.json();
			const classesJson = await classesRes.json();
			if (examsJson.success) {
				populateSelect(smsExamSelect, examsJson.data, 'exam_id', 'exam_name');
			}
			if (classesJson.success) {
				populateSelect(smsClassSelect, classesJson.data, 'class_name', 'class_name');
			}
		} catch (error) {
			// Optionally log error
		}
	};

	const buildSmsMessage = (student, examName) => {
		const total = student.total_marks ?? 0;
		const position = student.position ?? '-';
		const subjects = [
			['Eng', student.English],
			['Math', student.Math],
			['Kis', student.Kiswahili],
			['Tech', student.Technical],
			['Agri', student.Agriculture],
			['Creat', student.Creative],
			['Rel', student.Religious],
			['SST', student.SST],
			['Sci', student.Science]
		]
			.filter(([, score]) => score !== null && score !== undefined)
			.map(([label, score]) => `${label}:${score}`)
			.join(' ');
		return `${student.student_name} ${examName}: ${subjects} | Total:${total} Pos:${position}`;
	};

	const renderSmsRows = (rows) => {
		if (!smsResultsTable) return;
		const tbody = smsResultsTable.querySelector('tbody');
		if (!tbody) return;
		if (!rows.length) {
			renderTableMessage('No results found for the selected exam/class.');
			return;
		}
		// Batch DOM update
		tbody.innerHTML = rows.map(row => {
			const autoMessage = buildSmsMessage(row, smsExamName);
			return `
				<tr data-student-id="${row.student_id}" data-exam-id="${row.exam_id}" data-class-name="${row.class}" data-auto-message="${autoMessage}" data-message-edited="0" data-total-marks="${row.total_marks ?? 0}" data-position="${row.position ?? '-'}" data-subjects='${JSON.stringify({
					English: row.English ?? null,
					Math: row.Math ?? null,
					Kiswahili: row.Kiswahili ?? null,
					Technical: row.Technical ?? null,
					Agriculture: row.Agriculture ?? null,
					Creative: row.Creative ?? null,
					Religious: row.Religious ?? null,
					SST: row.SST ?? null,
					Science: row.Science ?? null,
				})}'>
					<td>${row.student_name}</td>
					<td>${row.total_marks ?? '-'}</td>
					<td>${row.position ?? '-'}</td>
					<td><span class="sms-status pending" title="Not sent"><i class='bx bx-time'></i></span></td>
					<td><input type="text" placeholder="+2567xxxxxxx" value=""></td>
					<td><textarea rows="2">${autoMessage}</textarea></td>
					<td>
						<div class="sms-actions">
							<button type="button" class="sms-btn sms-btn-primary sms-send-btn">Send</button>
						</div>
					</td>
				</tr>
			`;
		}).join('');
	};

	const sendSmsRow = async (row) => {
		const phoneInput = row.querySelector('input');
		const messageInput = row.querySelector('textarea');
		const phone = phoneInput ? phoneInput.value.trim() : '';
		let message = messageInput ? messageInput.value.trim() : '';
		if (row.dataset.messageEdited !== '1') {
			let subjects = {};
			try {
				subjects = JSON.parse(row.dataset.subjects || '{}');
			} catch (e) {
				subjects = {};
			}
			const freshMessage = buildSmsMessage({
				student_name: row.children[0]?.textContent?.trim() || '',
				total_marks: row.dataset.totalMarks ?? 0,
				position: row.dataset.position ?? '-',
				...subjects,
			}, smsExamName);
			if (messageInput) messageInput.value = freshMessage;
			message = freshMessage;
		}
		if (!phone) {
			swal('Error', 'Phone number is required.', 'error');
			return false;
		}
		if (!message) {
			swal('Error', 'Message is required.', 'error');
			return false;
		}
		const payload = {
			student_id: Number(row.dataset.studentId || 0),
			exam_id: Number(row.dataset.examId || 0),
			class_name: row.dataset.className || '',
			phone,
			message,
		};
		try {
			const res = await fetch(`${baseUrl}/settings/sms/send`, {
				method: 'POST',
				credentials: 'include',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(payload),
			});
			const data = await res.json();
			const status = row.querySelector('.sms-status');
			if (!data.success) {
				if (status) {
					status.classList.remove('sent');
					status.classList.add('failed');
					status.innerHTML = "<i class='bx bx-x'></i>";
					status.title = 'Failed';
				}
				swal('Error', data.message || 'Failed to send SMS.', 'error');
				return false;
			}
			if (status) {
				status.classList.remove('failed');
				status.classList.add('sent');
				status.innerHTML = "<i class='bx bx-check'></i>";
				status.title = 'Sent';
			}
			return true;
		} catch (error) {
			const status = row.querySelector('.sms-status');
			if (status) {
				status.classList.remove('sent');
				status.classList.add('failed');
				status.innerHTML = "<i class='bx bx-x'></i>";
				status.title = 'Failed';
			}
			swal('Error', 'Failed to send SMS.', 'error');
			return false;
		}
	};

	if (smsLoadBtn) {
		smsLoadBtn.addEventListener('click', async () => {
			const examId = smsExamSelect ? smsExamSelect.value : '';
			const className = smsClassSelect ? smsClassSelect.value : '';
			if (!examId || !className) {
				swal('Error', 'Please select an exam and class.', 'error');
				return;
			}
			renderTableMessage('Loading results...');
			try {
				const res = await fetch(`${baseUrl}/settings/sms/results?exam_id=${encodeURIComponent(examId)}&class=${encodeURIComponent(className)}`, {
					credentials: 'include',
				});
				const data = await res.json();
				if (!data.success) {
					renderTableMessage(data.message || 'Failed to load results.');
					return;
				}
				smsExamName = data.exam?.exam_name || 'Exam';
				renderSmsRows(data.data || []);
			} catch (error) {
				renderTableMessage('Failed to load results.');
			}
		});
	}

	if (smsResultsTable) {
		smsResultsTable.addEventListener('click', async (event) => {
			const button = event.target.closest('.sms-send-btn');
			if (!button) return;
			const row = button.closest('tr');
			if (!row) return;
			const ok = await sendSmsRow(row);
			if (ok) swal('Success', 'SMS sent successfully.', 'success');
		});
		smsResultsTable.addEventListener('input', (event) => {
			const textarea = event.target.closest('textarea');
			if (!textarea) return;
			const row = textarea.closest('tr');
			if (!row) return;
			row.dataset.messageEdited = '1';
		});
	}

	if (smsSendAllBtn) {
		smsSendAllBtn.addEventListener('click', async () => {
			if (!smsResultsTable) return;
			const rows = Array.from(smsResultsTable.querySelectorAll('tbody tr')).filter(row => row.dataset.studentId);
			if (!rows.length) {
				swal('Error', 'No results to send.', 'error');
				return;
			}
			for (const row of rows) {
				const sent = await sendSmsRow(row);
				if (!sent) return;
			}
			swal('Success', 'All SMS sent successfully.', 'success');
		});
	}

	await loadSmsFilters();
})();
