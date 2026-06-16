
// settings.js - optimized per frontend guidelines
(function () {
	const baseUrl = "../../backend/public/index.php";
	const createExamBtn = document.getElementById('createExamBtn');
	const createExamModal = document.getElementById('createExamModal');
	const closeExamModal = document.getElementById('closeExamModal');
	const cancelExam = document.getElementById('cancelExam');
	const createExamForm = document.getElementById('createExamForm');

	// Modal handling
	const toggleModal = (show) => {
		if (!createExamModal) return;
		createExamModal.classList.toggle('active', show);
		createExamModal.setAttribute('aria-hidden', show ? 'false' : 'true');
	};

	// Modal open/close
	if (createExamBtn) {
		createExamBtn.addEventListener('click', (event) => {
			event.preventDefault();
			toggleModal(true);
		});
	}
	if (closeExamModal) {
		closeExamModal.addEventListener('click', () => toggleModal(false));
	}
	if (cancelExam) {
		cancelExam.addEventListener('click', () => toggleModal(false));
	}
	if (createExamModal) {
		createExamModal.addEventListener('click', (event) => {
			if (event.target === createExamModal) toggleModal(false);
		});
	}

	// Auth check
	(async () => {
		try {
			const authRes = await fetch(`${baseUrl}/auth/check`, { credentials: "include" });
			const auth = await authRes.json();
			if (!auth.authenticated) {
				window.location.replace("../login.html");
			}
		} catch (error) {
			// Optionally log error
		}
	})();

	// Create exam form
	if (createExamForm) {
		createExamForm.addEventListener('submit', async (event) => {
			event.preventDefault();
			const payload = {
				name: createExamForm.name.value.trim(),
				exam_type: createExamForm.exam_type.value,
				term: createExamForm.term.value,
			};
			if (!payload.name || !payload.exam_type || !payload.term) {
				swal('Error', 'All fields are required!', 'error');
				return;
			}
			try {
				const res = await fetch(`${baseUrl}/settings/exams`, {
					method: 'POST',
					credentials: 'include',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify(payload),
				});
				const data = await res.json();
				if (!data.success) {
					swal('Error', data.message || 'Failed to create exam.', 'error');
					return;
				}
				toggleModal(false);
				swal('Success', 'Exam created successfully!', 'success');
				createExamForm.reset();
			} catch (error) {
				swal('Error', 'Failed to create exam.', 'error');
			}
		});
	}
})();
