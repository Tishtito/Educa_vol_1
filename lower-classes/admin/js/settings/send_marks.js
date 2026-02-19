/**
 * SendMarksManager - Handle SMS sending of student marks to parents
 * Features: Exam/class selection, dynamic table rendering, SMS message customization
 */
const SendMarksManager = (function () {
	const BASE_URL = 'backend/public/index.php';
	let smsExamName = '';
	let smsExamSelect, smsClassSelect, smsLoadBtn, smsSendAllBtn, smsResultsTable;

	// DOM element caching
	function cacheDOMElements() {
		smsExamSelect = document.getElementById('smsExamSelect');
		smsClassSelect = document.getElementById('smsClassSelect');
		smsLoadBtn = document.getElementById('smsLoadBtn');
		smsSendAllBtn = document.getElementById('smsSendAllBtn');
		smsResultsTable = document.getElementById('smsResultsTable');
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
	function showLoadingState(message = 'Loading...') {
		if (!smsResultsTable) return;
		const tbody = smsResultsTable.querySelector('tbody');
		if (tbody) {
			tbody.innerHTML = `<tr><td colspan="7">${escapeHtml(message)}</td></tr>`;
		}
	}

	// Show error state
	function showErrorState(message) {
		if (!smsResultsTable) return;
		const tbody = smsResultsTable.querySelector('tbody');
		if (tbody) {
			tbody.innerHTML = `<tr><td colspan="7"><span style="color: #ef4444;">${escapeHtml(message)}</span></td></tr>`;
		}
	}

	// Show empty state
	function showEmptyState(message) {
		if (!smsResultsTable) return;
		const tbody = smsResultsTable.querySelector('tbody');
		if (tbody) {
			tbody.innerHTML = `<tr><td colspan="7">${escapeHtml(message)}</td></tr>`;
		}
	}

	// Populate select elements
	function populateSelect(selectEl, rows, valueKey, labelKey) {
		if (!selectEl) return;
		selectEl.innerHTML = '<option value="">-- Select --</option>';
		const options = rows.map(row => 
			`<option value="${escapeHtml(String(row[valueKey]))}">${escapeHtml(String(row[labelKey]))}</option>`
		).join('');
		selectEl.innerHTML += options;
	}

	// Load filter options (exams and classes)
	async function loadSmsFilters() {
		try {
			const [examsRes, classesRes] = await Promise.all([
				fetch(`${BASE_URL}/settings/exams`, { credentials: 'include' }),
				fetch(`${BASE_URL}/settings/classes`, { credentials: 'include' })
			]);

			const examsJson = await examsRes.json();
			const classesJson = await classesRes.json();

			if (examsJson.success && examsJson.data) {
				populateSelect(smsExamSelect, examsJson.data, 'exam_id', 'exam_name');
			}
			if (classesJson.success && classesJson.data) {
				populateSelect(smsClassSelect, classesJson.data, 'class_name', 'class_name');
			}
		} catch (error) {
			console.error('Failed to load SMS filters', error);
		}
	}

	// Build SMS message from student data
	function buildSmsMessage(student, examName) {
		const total = student.total_marks ?? 0;
		const position = student.position ?? '-';
		const subjects = [
			['Math', student.Math],
			['Eng', student.English],
			['Kis', student.Kiswahili],
			['Env', student.Enviromental],
			['Creat', student.Creative],
			['Rel', student.Religious]
		]
			.filter(([, score]) => score !== null && score !== undefined)
			.map(([label, score]) => `${label}:${score}`)
			.join(' ');

		return `${student.student_name} ${examName}: ${subjects} | Total:${total} Pos:${position}`;
	}

	// Render table rows with batch rendering
	function renderSmsRows(rows) {
		if (!smsResultsTable) return;
		const tbody = smsResultsTable.querySelector('tbody');
		if (!tbody) return;

		if (!rows.length) {
			showEmptyState('No results found for the selected exam/class.');
			return;
		}

		const tableRows = rows.map(row => {
			const autoMessage = buildSmsMessage(row, smsExamName);
			const subjectsJson = escapeHtml(JSON.stringify({
				Math: row.Math ?? null,
				English: row.English ?? null,
				Kiswahili: row.Kiswahili ?? null,
				Enviromental: row.Enviromental ?? null,
				Creative: row.Creative ?? null,
				Religious: row.Religious ?? null,
			}));

			return `
				<tr data-student-id="${escapeHtml(String(row.student_id))}" 
					data-exam-id="${escapeHtml(String(row.exam_id))}" 
					data-class-name="${escapeHtml(String(row.class))}"
					data-auto-message="${escapeHtml(autoMessage)}"
					data-message-edited="0"
					data-total-marks="${escapeHtml(String(row.total_marks ?? 0))}"
					data-position="${escapeHtml(String(row.position ?? '-'))}"
					data-subjects='${subjectsJson}'>
					<td>${escapeHtml(row.student_name)}</td>
					<td>${escapeHtml(String(row.total_marks ?? '-'))}</td>
					<td>${escapeHtml(String(row.position ?? '-'))}</td>
					<td><span class="sms-status pending" title="Not sent"><i class='bx bx-time'></i></span></td>
					<td><input type="text" placeholder="+2567xxxxxxx" value=""></td>
					<td><textarea rows="2">${escapeHtml(autoMessage)}</textarea></td>
					<td>
						<div class="sms-actions">
							<button type="button" class="sms-btn sms-btn-primary sms-send-btn">Send</button>
						</div>
					</td>
				</tr>
			`;
		}).join('');

		tbody.innerHTML = tableRows;
	}

	// Send SMS for a single row
	async function sendSmsRow(row) {
		const phoneInput = row.querySelector('input');
		const messageInput = row.querySelector('textarea');
		const phone = phoneInput ? phoneInput.value.trim() : '';
		let message = messageInput ? messageInput.value.trim() : '';

		// Refresh message if not edited
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

			if (messageInput) {
				messageInput.value = freshMessage;
			}
			message = freshMessage;
		}

		// Validate
		if (!phone) {
			swal('Error', 'Phone number is required.', 'error');
			return false;
		}

		if (!message) {
			swal('Error', 'Message is required.', 'error');
			return false;
		}

		// Prepare payload
		const payload = {
			student_id: Number(row.dataset.studentId || 0),
			exam_id: Number(row.dataset.examId || 0),
			class_name: row.dataset.className || '',
			phone: phone,
			message: message,
		};

		try {
			const res = await fetch(`${BASE_URL}/settings/sms/send`, {
				method: 'POST',
				credentials: 'include',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(payload),
			});
			const data = await res.json();

			const statusEl = row.querySelector('.sms-status');
			if (!data.success) {
				if (statusEl) {
					statusEl.classList.remove('sent');
					statusEl.classList.add('failed');
					statusEl.innerHTML = "<i class='bx bx-x'></i>";
					statusEl.title = 'Failed';
				}
				swal('Error', data.message || 'Failed to send SMS.', 'error');
				return false;
			}

			if (statusEl) {
				statusEl.classList.remove('failed');
				statusEl.classList.add('sent');
				statusEl.innerHTML = "<i class='bx bx-check'></i>";
				statusEl.title = 'Sent';
			}
			return true;
		} catch (error) {
			console.error('Failed to send SMS', error);
			const statusEl = row.querySelector('.sms-status');
			if (statusEl) {
				statusEl.classList.remove('sent');
				statusEl.classList.add('failed');
				statusEl.innerHTML = "<i class='bx bx-x'></i>";
				statusEl.title = 'Failed';
			}
			swal('Error', 'Failed to send SMS.', 'error');
			return false;
		}
	}

	// Attach event listeners
	function attachEventListeners() {
		// Load results button
		if (smsLoadBtn) {
			smsLoadBtn.addEventListener('click', async () => {
				const examId = smsExamSelect ? smsExamSelect.value : '';
				const className = smsClassSelect ? smsClassSelect.value : '';

				if (!examId || !className) {
					swal('Error', 'Please select an exam and class.', 'error');
					return;
				}

				showLoadingState('Loading results...');

				try {
					const res = await fetch(
						`${BASE_URL}/settings/sms/results?exam_id=${encodeURIComponent(examId)}&class=${encodeURIComponent(className)}`,
						{ credentials: 'include' }
					);
					const data = await res.json();

					if (!data.success) {
						showErrorState(data.message || 'Failed to load results.');
						return;
					}

					smsExamName = data.exam?.exam_name || 'Exam';
					renderSmsRows(data.data || []);
				} catch (error) {
					console.error('Failed to load SMS results', error);
					showErrorState('Failed to load results.');
				}
			});
		}

		// Event delegation for send buttons and textarea changes
		if (smsResultsTable) {
			smsResultsTable.addEventListener('click', async event => {
				const sendBtn = event.target.closest('.sms-send-btn');
				if (!sendBtn) return;

				const row = sendBtn.closest('tr');
				if (!row) return;

				const ok = await sendSmsRow(row);
				if (ok) {
					swal('Success', 'SMS sent successfully.', 'success');
				}
			});

			smsResultsTable.addEventListener('input', event => {
				const textarea = event.target.closest('textarea');
				if (!textarea) return;

				const row = textarea.closest('tr');
				if (!row) return;

				row.dataset.messageEdited = '1';
			});
		}

		// Send all button
		if (smsSendAllBtn) {
			smsSendAllBtn.addEventListener('click', async () => {
				if (!smsResultsTable) return;

				const rows = Array.from(smsResultsTable.querySelectorAll('tbody tr'))
					.filter(row => row.dataset.studentId);

				if (!rows.length) {
					swal('Error', 'No results to send.', 'error');
					return;
				}

				for (const row of rows) {
					const sent = await sendSmsRow(row);
					if (!sent) {
						return;
					}
				}

				swal('Success', 'All SMS sent successfully.', 'success');
			});
		}
	}

	// Initialize
	async function init() {
		cacheDOMElements();
		attachEventListeners();
		await loadSmsFilters();
	}

	return {
		init
	};
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
	SendMarksManager.init();
});
