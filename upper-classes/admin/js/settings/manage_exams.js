
// manage_exams.js - optimized per frontend guidelines
(async function () {
    const baseUrl = "../../backend/public/index.php";
    const tableBody = document.querySelector('tbody');
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

    // Event delegation for delete
    const bindDeleteLinks = () => {
        tableBody.addEventListener('click', async (event) => {
            const link = event.target.closest('.delete-link');
            if (!link) return;
            event.preventDefault();
            const examId = link.getAttribute('data-id');
            const confirm = await swal({
                title: 'Caution!',
                text: 'Are you sure you want to delete?',
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            });
            if (!confirm) return;
            try {
                const res = await fetch(`${baseUrl}/settings/exams/delete`, {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ exam_id: Number(examId) })
                });
                const data = await res.json();
                if (!data.success) {
                    swal('Error', data.message || 'Failed to delete exam.', 'error');
                    return;
                }
                await loadExams();
                swal('Deleted', 'Exam removed successfully.', 'success');
            } catch (error) {
                swal('Error', 'Failed to delete exam.', 'error');
            }
        });
    };

    // Load exams
    const loadExams = async () => {
        if (tableBody) tableBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">Loading...</td></tr>';
        try {
            // Parallel fetch: auth and exams
            const [authRes, res] = await Promise.all([
                fetch(`${baseUrl}/auth/check`, { credentials: "include" }),
                fetch(`${baseUrl}/settings/exams`, { credentials: 'include' })
            ]);
            const auth = await authRes.json();
            if (!auth.authenticated) {
                window.location.replace("../login.html");
                return;
            }
            const data = await res.json();
            if (!data.success || !Array.isArray(data.data) || data.data.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">No exams found.</td></tr>';
                return;
            }
            // Batch DOM update
            const rows = data.data.map(row => `
                <tr>
                    <td><p>${row.exam_name}</p></td>
                    <td><a href="#" class="delete-link" data-id="${row.exam_id}"><span class="status delete">delete</span></a></td>
                </tr>
            `);
            tableBody.innerHTML = rows.join('');
        } catch (error) {
            tableBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">Failed to load exams.</td></tr>';
        }
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
                createExamForm.reset();
                await loadExams();
                swal('Success', 'Exam created successfully!', 'success');
            } catch (error) {
                swal('Error', 'Failed to create exam.', 'error');
            }
        });
    }

    bindDeleteLinks();
    await loadExams();
})();