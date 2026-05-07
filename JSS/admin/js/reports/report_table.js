
// report_table.js - optimized per frontend guidelines
(async function () {
	const baseUrl = "../../backend/public/index.php";
	const params = new URLSearchParams(window.location.search);
	const examId = params.get('exam_id') || '';
	const grade = params.get('grade') || '';
	const examType = params.get('exam_type') || '';
	const gradeToken = params.get('token') || '';
	const examToken = params.get('exam_token') || '';

	const labelMap = {
		'Mid-Term': 'Mid-Term',
		'End-Term': 'End of Term',
		'Opener': 'Opener',
		'Weekly': 'Weekly'
	};
	const reportLabel = labelMap[examType] || 'Report';
	const labelEl = document.getElementById('report-label');
	if (labelEl) labelEl.textContent = reportLabel;
	document.title = `Reports - ${reportLabel}`;

	const modeMap = {
		'Mid-Term': {
			viewPage: 'report_form1.html',
			printMode: 'download',
			includeExamType: false,
			reportFormPage: 'report_form1.html'
		},
		'End-Term': {
			viewPage: 'report_form.html',
			printMode: 'download',
			includeExamType: true,
			reportFormPage: 'report_form.html'
		},
		'Opener': {
			viewPage: 'report_form1.html',
			printMode: 'download',
			includeExamType: false,
			reportFormPage: 'report_form1.html'
		},
		'Weekly': {
			viewPage: 'report_form.html',
			printMode: 'download',
			includeExamType: false,
			reportFormPage: 'report_form.html'
		}
	};
	const mode = modeMap[examType] || modeMap['End-Term'];

	const tbody = document.getElementById('students-body');
	if (!examId || !grade || !gradeToken || !examToken) {
		if (tbody) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">Invalid class or exam selected.</td></tr>';
		return;
	}

	// Show loading indicator
	if (tbody) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">Loading...</td></tr>';

	try {
		// Parallel fetch: auth and students
		const [authRes, listRes] = await Promise.all([
			fetch(`${baseUrl}/auth/check`, { credentials: "include" }),
			fetch(`${baseUrl}/reports/students?exam_id=${encodeURIComponent(examId)}&grade=${encodeURIComponent(grade)}&token=${encodeURIComponent(gradeToken)}`, { credentials: "include" })
		]);
		const auth = await authRes.json();
		if (!auth.authenticated) {
			window.location.replace("../login.html");
			return;
		}
		const list = await listRes.json();
		if (!tbody) return;

		if (!list.success || !Array.isArray(list.data) || list.data.length === 0) {
			tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No students found.</td></tr>';
			return;
		}

		// Batch DOM update
		const rows = list.data.map((student, index) => {
			let viewHref = `${mode.viewPage}?student_id=${student.student_id}&exam_id=${examId}&token=${encodeURIComponent(examToken)}`;
			if (mode.includeExamType && examType) {
				viewHref += `&exam_type=${encodeURIComponent(examType)}`;
			}
			return `
				<tr>
					<td>${index + 1}</td>
					<td>${student.name}</td>
					<td>${student.class}</td>
					<td>
						<a href="${viewHref}" class="view-report">
							<span class="status process">View</span>
						</a>
					</td>
				</tr>
			`;
		});
		tbody.innerHTML = rows.join('');

		// Download/Print controls
		const downloadBtn = document.getElementById('downloadAll');
		const printBtn = document.getElementById('printAll');

		if (mode.printMode === 'download') {
				if (downloadBtn) {
				downloadBtn.style.display = 'inline-flex';
				const downloadUrl = `${baseUrl}/reports/download?grade=${encodeURIComponent(grade)}&exam_id=${encodeURIComponent(examId)}&exam_type=${encodeURIComponent(examType)}&token=${encodeURIComponent(gradeToken)}`;
				downloadBtn.addEventListener('click', async (e) => {
					e.preventDefault();
					if (downloadBtn.classList.contains('downloading')) return;

					const label = downloadBtn.querySelector('.download-label');
					downloadBtn.classList.add('downloading');
					label.textContent = 'Downloading...';

					try {
						const res = await fetch(downloadUrl, { credentials: 'include' });
						if (!res.ok) throw new Error('Download failed');
						const blob = await res.blob();
						const url = URL.createObjectURL(blob);
						const a = document.createElement('a');
						a.href = url;
						a.download = `${grade}_reports.pdf`;
						document.body.appendChild(a);
						a.click();
						a.remove();
						URL.revokeObjectURL(url);
					} catch (err) {
						console.error('Download failed', err);
						alert('Failed to download PDF. Please try again.');
					} finally {
						downloadBtn.classList.remove('downloading');
						label.textContent = 'Download All as PDF';
					}
				});
			}
			if (printBtn) {
				printBtn.style.display = 'none';
			}
		} else {
			if (downloadBtn) {
				downloadBtn.style.display = 'none';
			}
			if (printBtn) {
				printBtn.style.display = 'inline-block';
				// Remove previous event listeners by cloning
				const newPrintBtn = printBtn.cloneNode(true);
				printBtn.parentNode.replaceChild(newPrintBtn, printBtn);
				newPrintBtn.addEventListener('click', () => {
					const students = list.data || [];
					if (!students.length) {
						alert('No students to print!');
						return;
					}
					const printFrame = document.getElementById('printFrame');
					const frameDoc = printFrame.contentDocument || printFrame.contentWindow.document;
					let content = "<html><head><title>All Reports</title>";
					content += '<link rel="stylesheet" href="../../css/report.css">';
					content += "</head><body>";
					students.forEach(student => {
						let formHref = `${mode.reportFormPage}?student_id=${student.student_id}&exam_id=${examId}&token=${encodeURIComponent(examToken)}`;
						if (mode.includeExamType && examType) {
							formHref += `&exam_type=${encodeURIComponent(examType)}`;
						}
						content += `<iframe src="${formHref}" style="width:100%; height:1550px; border:0;"></iframe>`;
						content += '<div style="page-break-before: always;"></div>';
					});
					content += "</body></html>";
					frameDoc.open();
					frameDoc.write(content);
					frameDoc.close();
					setTimeout(() => {
						printFrame.contentWindow.print();
					}, 2000);
				});
			}
		}
	} catch (error) {
		if (tbody) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">Failed to load students.</td></tr>';
		// Optionally log error
		// console.error('Failed to load students', error);
	}
})();
