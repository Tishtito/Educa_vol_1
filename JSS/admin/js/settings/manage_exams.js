
// manage_exams.js - optimized per frontend guidelines
(async function () {
            const baseUrl = "../../backend/public/index.php";
            const tableBody = document.querySelector('tbody');
            const createExamBtn = document.getElementById('createExamBtn');
            const createExamModal = document.getElementById('createExamModal');
            const closeExamModal = document.getElementById('closeExamModal');
            const cancelExam = document.getElementById('cancelExam');
            const createExamForm = document.getElementById('createExamForm');
            
            // Edit modal elements
            const editExamModal = document.getElementById('editExamModal');
            const closeEditModal = document.getElementById('closeEditModal');
            const cancelEdit = document.getElementById('cancelEdit');
            const editExamForm = document.getElementById('editExamForm');
            const editExamName = document.getElementById('editExamName');
            const editExamStatus = document.getElementById('editExamStatus');
            let currentEditExamId = null;

            const toggleModal = (modal, show) => {
                if (!modal) return;
                modal.classList.toggle('active', show);
                modal.setAttribute('aria-hidden', show ? 'false' : 'true');
            };

            const bindActionLinks = () => {
                // Edit links
                document.querySelectorAll('.edit-link').forEach((link) => {
                    link.addEventListener('click', (event) => {
                        event.preventDefault();
                        const examId = link.getAttribute('data-id');
                        const examName = link.getAttribute('data-name');
                        const examStatus = link.getAttribute('data-status');
                        
                        currentEditExamId = examId;
                        editExamName.value = examName;
                        editExamStatus.value = examStatus;
                        toggleModal(editExamModal, true);
                    });
                });

                // Delete links
                document.querySelectorAll('.delete-link').forEach((link) => {
                    link.addEventListener('click', async (event) => {
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
                            console.error('Failed to delete exam', error);
                            swal('Error', 'Failed to delete exam.', 'error');
                        }
                    });
                });
            };

            const loadExams = async () => {
                try {
                    const authRes = await fetch(`${baseUrl}/auth/check`, { credentials: "include" });
                    const auth = await authRes.json();
                    if (!auth.authenticated) {
                        window.location.replace("../login.html");
                        return;
                    }

                    const res = await fetch(`${baseUrl}/settings/exams`, { credentials: 'include' });
                    const data = await res.json();
                    if (!data.success || !Array.isArray(data.data)) {
                        tableBody.innerHTML = '<tr><td colspan="3" style="text-align:center;">No exams found.</td></tr>';
                        return;
                    }

                    if (data.data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="3" style="text-align:center;">No exams found.</td></tr>';
                        return;
                    }

                    tableBody.innerHTML = '';
                    data.data.forEach((row) => {
                        const statusColors = {
                            'Scheduled': '#3b82f6',
                            'Completed': '#10b981',
                            'Cancelled': '#ef4444'
                        };
                        const statusBg = statusColors[row.status] || '#6b7280';
                        
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td><p>${row.exam_name}</p></td>
                            <td><span style="background-color: ${statusBg}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">${row.status}</span></td>
                            <td>
                                <a href="#" class="edit-link" data-id="${row.exam_id}" data-name="${row.exam_name}" data-status="${row.status}"><span class="status pending">edit</span></a>
                                <a href="#" class="delete-link" data-id="${row.exam_id}"><span class="status delete">delete</span></a>
                            </td>
                        `;
                        tableBody.appendChild(tr);
                    });
                    bindActionLinks();
                } catch (error) {
                    console.error('Failed to load exams', error);
                    tableBody.innerHTML = '<tr><td colspan="3" style="text-align:center;">Failed to load exams.</td></tr>';
                }
            };

            if (createExamBtn) {
                createExamBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    toggleModal(createExamModal, true);
                });
            }

            if (closeExamModal) {
                closeExamModal.addEventListener('click', () => toggleModal(createExamModal, false));
            }

            if (cancelExam) {
                cancelExam.addEventListener('click', () => toggleModal(createExamModal, false));
            }

            if (closeEditModal) {
                closeEditModal.addEventListener('click', () => toggleModal(editExamModal, false));
            }

            if (cancelEdit) {
                cancelEdit.addEventListener('click', () => toggleModal(editExamModal, false));
            }

            if (createExamModal) {
                createExamModal.addEventListener('click', (event) => {
                    if (event.target === createExamModal) {
                        toggleModal(createExamModal, false);
                    }
                });
            }

            if (editExamModal) {
                editExamModal.addEventListener('click', (event) => {
                    if (event.target === editExamModal) {
                        toggleModal(editExamModal, false);
                    }
                });
            }

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

                        toggleModal(createExamModal, false);
                        createExamForm.reset();
                        await loadExams();
                        swal('Success', 'Exam created successfully!', 'success');
                    } catch (error) {
                        console.error('Failed to create exam', error);
                        swal('Error', 'Failed to create exam.', 'error');
                    }
                });
            }

            if (editExamForm) {
                editExamForm.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const payload = {
                        exam_id: Number(currentEditExamId),
                        status: editExamStatus.value,
                    };

                    if (!payload.status) {
                        swal('Error', 'Please select a status!', 'error');
                        return;
                    }

                    try {
                        const res = await fetch(`${baseUrl}/settings/exams/update`, {
                            method: 'POST',
                            credentials: 'include',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload),
                        });
                        const data = await res.json();
                        if (!data.success) {
                            swal('Error', data.message || 'Failed to update exam.', 'error');
                            return;
                        }

                        toggleModal(editExamModal, false);
                        await loadExams();
                        swal('Success', 'Exam status updated successfully!', 'success');
                    } catch (error) {
                        console.error('Failed to update exam', error);
                        swal('Error', 'Failed to update exam.', 'error');
                    }
                });
            }

            await loadExams();
        })();