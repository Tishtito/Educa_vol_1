(async function () {
    const baseUrl = "../../backend/public/index.php";

    let subjectsData = [];
    let classesData = [];

    // Render subject-class assignment grid (instead of separate checkboxes)
    function renderAssignmentGrid(container, subjects, classes, idPrefix) {
        const html = `
            <div class="assignment-grid">
                <div class="assignment-header">
                    <div class="assignment-col">Subject</div>
                    <div class="assignment-col">Class</div>
                    <div class="assignment-col">Action</div>
                </div>
                <div class="assignment-rows" id="${idPrefix}-rows">
                    <!-- Assignment rows will be added here -->
                </div>
                <button type="button" class="btn btn-small" id="${idPrefix}-add-btn">+ Add Assignment</button>
            </div>
        `;
        container.innerHTML = html;
        
        const rowsContainer = container.querySelector(`#${idPrefix}-rows`);
        const addBtn = container.querySelector(`#${idPrefix}-add-btn`);
        
        addBtn.addEventListener('click', (e) => {
            e.preventDefault();
            addAssignmentRow(rowsContainer, subjects, classes, idPrefix);
        });
        
        return rowsContainer;
    }

    function addAssignmentRow(container, subjects, classes, idPrefix, subjectId = '', classId = '') {
        const rowId = `${idPrefix}-row-${Date.now()}`;
        const row = document.createElement('div');
        row.className = 'assignment-row';
        row.id = rowId;
        
        const subjectOptions = ['<option value="">-- Select Subject --</option>']
            .concat(subjects.map(s => `<option value="${s.subject_id}" ${s.subject_id == subjectId ? 'selected' : ''}>${s.name}</option>`))
            .join('');
        
        const classOptions = ['<option value="">-- Select Class --</option>']
            .concat(classes.map(c => `<option value="${c.class_id}" ${c.class_id == classId ? 'selected' : ''}>${c.class_name}</option>`))
            .join('');
        
        row.innerHTML = `
            <select name="assignments[subject_id][]" class="assignment-subject" required>${subjectOptions}</select>
            <select name="assignments[class_id][]" class="assignment-class" required>${classOptions}</select>
            <button type="button" class="btn btn-danger btn-small remove-assignment">Remove</button>
        `;
        
        const removeBtn = row.querySelector('.remove-assignment');
        removeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            row.remove();
        });
        
        container.appendChild(row);
    }

    function getAssignments(form) {
        const subjectInputs = form.querySelectorAll('input[name="assignments[subject_id][]"]');
        const classInputs = form.querySelectorAll('input[name="assignments[class_id][]"]');
        
        // If using selects instead
        const subjectSelects = form.querySelectorAll('select[name="assignments[subject_id][]"]');
        const classSelects = form.querySelectorAll('select[name="assignments[class_id][]"]');
        
        const assignments = [];
        const inputs = subjectSelects.length > 0 ? subjectSelects : subjectInputs;
        
        inputs.forEach((input, index) => {
            const subjectId = subjectSelects.length > 0 ? subjectSelects[index].value : input.value;
            const classId = classSelects.length > 0 ? classSelects[index].value : classInputs[index].value;
            
            if (subjectId && classId) {
                assignments.push({ subject_id: subjectId, class_id: classId });
            }
        });
        
        return assignments;
    }

    try {
        const [authRes, classesRes, subjectsRes] = await Promise.all([
            fetch(`${baseUrl}/auth/check`, { credentials: "include" }),
            fetch(`${baseUrl}/classes`, { credentials: "include" }),
            fetch(`${baseUrl}/subjects`, { credentials: "include" })
        ]);

        const auth = await authRes.json();
        if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
        }

        const classes = await classesRes.json();
        const subjects = await subjectsRes.json();

        const editClassSelect = document.getElementById("edit-teacher-class");
        const addClassSelect = document.getElementById("add-teacher-class");
        
        if (classes.success) {
            classesData = classes.data || [];
            const optionsHtml = ['<option value="">-- Select Class --</option>', '<option value="">Not class teacher</option>']
                .concat(classes.data.map(item => `<option value="${item.class_name}">${item.class_name}</option>`))
                .join('');
            editClassSelect.innerHTML = optionsHtml;
            addClassSelect.innerHTML = optionsHtml;
        }

        if (subjects.success) {
            subjectsData = subjects.data || [];
        }

        // Setup examiner assignment grids
        const editExaminerAssignments = renderAssignmentGrid(
            document.getElementById('edit-examiner-subjects'),
            subjectsData,
            classesData,
            'edit-examiner'
        );

        const addExaminerAssignments = renderAssignmentGrid(
            document.getElementById('add-examiner-subjects'),
            subjectsData,
            classesData,
            'add-examiner'
        );

        // ...existing teachers loading code...
        const teachersRes = await fetch(`${baseUrl}/teachers`, { credentials: "include" });
        const teachers = await teachersRes.json();
        document.getElementById('teachers-loading').style.display = 'none';
        if (teachers.success) {
            const tbody = document.getElementById("teachers-body");
            tbody.innerHTML = teachers.data.map((teacher) => `
                <tr>
                    <td><p>${teacher.name}</p></td>
                    <td><p>${teacher.class_assigned}</p></td>
                    <td><a href="#" class="edit-teacher" data-id="${teacher.id}" data-name="${teacher.name}" data-class="${teacher.class_assigned}"><span class='status process'>edit</span></a></td>
                    <td><a href="#" class="delete-teacher" data-id="${teacher.id}"><span class='status delete'>delete</span></a></td>
                </tr>
            `).join('');
        }

        // Examiners loading
        const examinersRes = await fetch(`${baseUrl}/examiners`, { credentials: "include" });
        const examiners = await examinersRes.json();
        document.getElementById('examiners-loading').style.display = 'none';
        if (examiners.success) {
            const tbody = document.getElementById("examiners-body");
            if (examiners.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4">No examiners found.</td></tr>';
            } else {
                tbody.innerHTML = examiners.data.map((examiner) => {
                    const assignments = examiner.assignments || [];
                    const assignmentText = assignments.length > 0 
                        ? assignments.map(a => `${a.subject_name} (${a.class_name})`).join(', ')
                        : 'No Assignments';
                    return `
                        <tr>
                            <td>${examiner.name}</td>
                            <td>${assignmentText}</td>
                            <td>
                                <a href="#" class="edit-examiner" data-id="${examiner.examiner_id}">
                                    <span class="status process">edit</span>
                                </a>
                            </td>
                            <td>
                                <a href="#" class="delete-examiner" data-id="${examiner.examiner_id}">
                                    <span class="status delete">delete</span>
                                </a>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        }

        // ...existing delete and reload functions...
        const deleteTeacher = async (teacherId) => {
            const formData = new FormData();
            formData.append('id', teacherId);
            const response = await fetch(`${baseUrl}/teachers/delete`, {
                method: 'POST',
                body: formData,
                credentials: 'include',
            });
            return await response.json();
        };

        const reloadTeachers = async () => {
            try {
                const res = await fetch(`${baseUrl}/teachers`, { credentials: "include" });
                const data = await res.json();
                if (data.success) {
                    const tbody = document.getElementById("teachers-body");
                    tbody.innerHTML = data.data.map((teacher) => `
                        <tr>
                            <td><p>${teacher.name}</p></td>
                            <td><p>${teacher.class_assigned}</p></td>
                            <td><a href="#" class="edit-teacher" data-id="${teacher.id}" data-name="${teacher.name}" data-class="${teacher.class_assigned}"><span class='status process'>edit</span></a></td>
                            <td><a href="#" class="delete-teacher" data-id="${teacher.id}"><span class='status delete'>delete</span></a></td>
                        </tr>
                    `).join('');
                    bindDeleteTeacherLinks();
                    bindEditTeacherLinks();
                }
            } catch (error) {
                console.error('Failed to reload teachers', error);
            }
        };

        const bindEditTeacherLinks = () => {
            document.querySelectorAll('.edit-teacher').forEach((link) => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    document.getElementById('edit-teacher-id').value = link.dataset.id;
                    document.getElementById('edit-teacher-name').value = link.dataset.name;
                    document.getElementById('edit-teacher-class').value = link.dataset.class;
                    document.getElementById('edit-teacher-password').value = '';
                    modal.classList.add('active');
                });
            });
        };

        const bindDeleteTeacherLinks = () => {
            document.querySelectorAll('.delete-teacher').forEach(function(link) {
                link.addEventListener('click', function(event) {
                    event.preventDefault();
                    var teacherId = this.getAttribute('data-id');
                    swal({
                        title: "Caution!",
                        text: "Are you sure you want to delete?",
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    }).then(async function(isConfirmed) {
                        if (isConfirmed) {
                            const result = await deleteTeacher(teacherId);
                            if (result.success) {
                                swal({
                                    title: 'Deleted',
                                    text: 'Teacher removed successfully.',
                                    icon: 'success',
                                    button: 'OK',
                                }).then(() => {
                                    reloadTeachers();
                                });
                            } else {
                                swal({
                                    title: 'Error',
                                    text: result.message || 'Failed to delete teacher.',
                                    icon: 'error',
                                    button: 'OK',
                                });
                            }
                        }
                    });
                });
            });
        };

        bindDeleteTeacherLinks();

        const deleteExaminer = async (examinerId) => {
            const formData = new FormData();
            formData.append('examiner_id', examinerId);
            const response = await fetch(`${baseUrl}/examiners/delete`, {
                method: 'POST',
                body: formData,
                credentials: 'include',
            });
            return await response.json();
        };

        const reloadExaminers = async () => {
            try {
                const res = await fetch(`${baseUrl}/examiners`, { credentials: "include" });
                const data = await res.json();
                if (data.success) {
                    const tbody = document.getElementById("examiners-body");
                    if (data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4">No examiners found.</td></tr>';
                    } else {
                        tbody.innerHTML = data.data.map((examiner) => {
                            const assignments = examiner.assignments || [];
                            const assignmentText = assignments.length > 0 
                                ? assignments.map(a => `${a.subject_name} (${a.class_name})`).join(', ')
                                : 'No Assignments';
                            return `
                                <tr>
                                    <td>${examiner.name}</td>
                                    <td>${assignmentText}</td>
                                    <td>
                                        <a href="#" class="edit-examiner" data-id="${examiner.examiner_id}">
                                            <span class="status process">edit</span>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="#" class="delete-examiner" data-id="${examiner.examiner_id}">
                                            <span class="status delete">delete</span>
                                        </a>
                                    </td>
                                </tr>
                            `;
                        }).join('');
                    }
                    bindDeleteExaminerLinks();
                    bindEditExaminerLinks();
                }
            } catch (error) {
                console.error('Failed to reload examiners', error);
            }
        };

        const bindEditExaminerLinks = () => {
            document.querySelectorAll('.edit-examiner').forEach((link) => {
                link.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const examinerId = link.dataset.id;
                    try {
                        const detailRes = await fetch(`${baseUrl}/examiners/detail?examiner_id=${examinerId}`, { credentials: "include" });
                        const detail = await detailRes.json();
                        if (!detail.success) {
                            swal({
                                title: 'Error',
                                text: detail.message || 'Failed to load examiner.',
                                icon: 'error',
                                button: 'OK',
                            });
                            return;
                        }

                        document.getElementById('edit-examiner-id').value = detail.data.examiner_id;
                        document.getElementById('edit-examiner-name').value = detail.data.name;
                        document.getElementById('edit-examiner-password').value = '';
                        
                        // Clear and repopulate assignments
                        const rowsContainer = document.getElementById('edit-examiner-rows');
                        rowsContainer.innerHTML = '';
                        detail.data.assignments.forEach(assignment => {
                            addAssignmentRow(rowsContainer, subjectsData, classesData, 'edit-examiner', assignment.subject_id, assignment.class_id);
                        });
                        
                        examinerModal.classList.add('active');
                    } catch (err) {
                        console.error('Failed to load examiner', err);
                    }
                });
            });
        };

        const bindDeleteExaminerLinks = () => {
            document.querySelectorAll('.delete-examiner').forEach(function(link) {
                link.addEventListener('click', function(event) {
                    event.preventDefault();
                    var examinerId = this.getAttribute('data-id');
                    swal({
                        title: "Caution!",
                        text: "Are you sure you want to delete?",
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    }).then(async function(isConfirmed) {
                        if (isConfirmed) {
                            const result = await deleteExaminer(examinerId);
                            if (result.success) {
                                swal({
                                    title: 'Deleted',
                                    text: 'Examiner removed successfully.',
                                    icon: 'success',
                                    button: 'OK',
                                }).then(() => {
                                    reloadExaminers();
                                });
                            } else {
                                swal({
                                    title: 'Error',
                                    text: result.message || 'Failed to delete examiner.',
                                    icon: 'error',
                                    button: 'OK',
                                });
                            }
                        }
                    });
                });
            });
        };

        bindDeleteExaminerLinks();

        // ...existing modal setup...
        const modal = document.getElementById('edit-teacher-modal');
        const closeBtn = document.getElementById('edit-teacher-close');
        const cancelBtn = document.getElementById('edit-teacher-cancel');
        const form = document.getElementById('edit-teacher-form');

        const addModal = document.getElementById('add-teacher-modal');
        const addOpen = document.getElementById('add-teacher-open');
        const addClose = document.getElementById('add-teacher-close');
        const addCancel = document.getElementById('add-teacher-cancel');
        const addForm = document.getElementById('add-teacher-form');

        const examinerModal = document.getElementById('edit-examiner-modal');
        const examinerClose = document.getElementById('edit-examiner-close');
        const examinerCancel = document.getElementById('edit-examiner-cancel');
        const examinerForm = document.getElementById('edit-examiner-form');

        const addExaminerModal = document.getElementById('add-examiner-modal');
        const addExaminerOpen = document.getElementById('add-examiner-open');
        const addExaminerClose = document.getElementById('add-examiner-close');
        const addExaminerCancel = document.getElementById('add-examiner-cancel');
        const addExaminerForm = document.getElementById('add-examiner-form');

        function closeModal() {
            modal.classList.remove('active');
            form.reset();
        }

        function closeAddModal() {
            addModal.classList.remove('active');
            addForm.reset();
        }

        function closeExaminerModal() {
            examinerModal.classList.remove('active');
            examinerForm.reset();
            const rowsContainer = document.getElementById('edit-examiner-rows');
            if (rowsContainer) rowsContainer.innerHTML = '';
        }

        function closeAddExaminerModal() {
            addExaminerModal.classList.remove('active');
            addExaminerForm.reset();
            const rowsContainer = document.getElementById('add-examiner-rows');
            if (rowsContainer) rowsContainer.innerHTML = '';
        }

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        addOpen.addEventListener('click', (e) => {
            e.preventDefault();
            addModal.classList.add('active');
        });
        addClose.addEventListener('click', closeAddModal);
        addCancel.addEventListener('click', closeAddModal);
        addModal.addEventListener('click', (e) => {
            if (e.target === addModal) closeAddModal();
        });

        examinerClose.addEventListener('click', closeExaminerModal);
        examinerCancel.addEventListener('click', closeExaminerModal);
        examinerModal.addEventListener('click', (e) => {
            if (e.target === examinerModal) closeExaminerModal();
        });

        addExaminerOpen.addEventListener('click', (e) => {
            e.preventDefault();
            addExaminerModal.classList.add('active');
        });
        addExaminerClose.addEventListener('click', closeAddExaminerModal);
        addExaminerCancel.addEventListener('click', closeAddExaminerModal);
        addExaminerModal.addEventListener('click', (e) => {
            if (e.target === addExaminerModal) closeAddExaminerModal();
        });

        // Bind edit teacher links
        bindEditTeacherLinks();

        // Bind edit examiner links
        bindEditExaminerLinks();

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const response = await fetch(`${baseUrl}/teachers/update`, {
                method: 'POST',
                body: formData,
                credentials: 'include',
            });
            const result = await response.json();
            if (result.success) {
                closeModal();
                await reloadTeachers();
                swal({
                    title: 'Success',
                    text: 'Teacher updated successfully.',
                    icon: 'success',
                    button: 'OK',
                });
            } else {
                swal({
                    title: 'Error',
                    text: result.message || 'Failed to update teacher.',
                    icon: 'error',
                    button: 'OK',
                });
            }
        });

        addForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(addForm);
            const response = await fetch(`${baseUrl}/teachers/create`, {
                method: 'POST',
                body: formData,
                credentials: 'include',
            });
            const result = await response.json();
            if (result.success) {
                closeAddModal();
                await reloadTeachers();
                swal({
                    title: 'Success',
                    text: 'Teacher created successfully.',
                    icon: 'success',
                    button: 'OK',
                });
            } else {
                swal({
                    title: 'Error',
                    text: result.message || 'Failed to create teacher.',
                    icon: 'error',
                    button: 'OK',
                });
            }
        });

        examinerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const assignments = getAssignments(examinerForm);
            
            if (assignments.length === 0) {
                swal({
                    title: 'Validation Error',
                    text: 'Please assign at least one subject to a class.',
                    icon: 'warning',
                    button: 'OK',
                });
                return;
            }

            const formData = new FormData(examinerForm);
            // Clear old assignments and add new ones
            formData.delete('assignments[subject_id][]');
            formData.delete('assignments[class_id][]');
            assignments.forEach(a => {
                formData.append('assignments[]', JSON.stringify(a));
            });

            const response = await fetch(`${baseUrl}/examiners/update`, {
                method: 'POST',
                body: formData,
                credentials: 'include',
            });
            const result = await response.json();
            if (result.success) {
                closeExaminerModal();
                await reloadExaminers();
                swal({
                    title: 'Success',
                    text: 'Examiner updated successfully.',
                    icon: 'success',
                    button: 'OK',
                });
            } else {
                swal({
                    title: 'Error',
                    text: result.message || 'Failed to update examiner.',
                    icon: 'error',
                    button: 'OK',
                });
            }
        });

        addExaminerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const assignments = getAssignments(addExaminerForm);
            
            if (assignments.length === 0) {
                swal({
                    title: 'Validation Error',
                    text: 'Please assign at least one subject to a class.',
                    icon: 'warning',
                    button: 'OK',
                });
                return;
            }

            const formData = new FormData(addExaminerForm);
            // Clear old assignments and add new ones
            formData.delete('assignments[subject_id][]');
            formData.delete('assignments[class_id][]');
            assignments.forEach(a => {
                formData.append('assignments[]', JSON.stringify(a));
            });

            const response = await fetch(`${baseUrl}/examiners/create`, {
                method: 'POST',
                body: formData,
                credentials: 'include',
            });
            const result = await response.json();
            if (result.success) {
                closeAddExaminerModal();
                await reloadExaminers();
                swal({
                    title: 'Success',
                    text: 'Examiner created successfully.',
                    icon: 'success',
                    button: 'OK',
                });
            } else {
                swal({
                    title: 'Error',
                    text: result.message || 'Failed to create examiner.',
                    icon: 'error',
                    button: 'OK',
                });
            }
        });
    } catch (error) {
        console.error("Users page load failed", error);
    }
})();