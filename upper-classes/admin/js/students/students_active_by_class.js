// students_active_by_class.js - optimized per frontend guidelines
(async function () {
    const baseUrl = "../../backend/public/index.php";
    const params = new URLSearchParams(window.location.search);
    const className = params.get('class') || '';

    const tbody = document.getElementById('students-body');
    const classNameEl = document.getElementById('class-name');
    const tableTitle = document.getElementById('table-title');
    const modal = document.getElementById('student-profile-modal');
    const closeBtn = document.getElementById('profile-close');
    const closeBtn2 = document.getElementById('profile-close-btn');

    if (!className) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Invalid class selected.</td></tr>';
        return;
    }

    if (classNameEl) classNameEl.textContent = className;
    if (tableTitle) tableTitle.textContent = `Active Students in ${className}`;
    if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Loading...</td></tr>';

    const closeProfile = () => {
        if (modal) modal.classList.remove('active');
    };

    if (closeBtn) closeBtn.addEventListener('click', closeProfile);
    if (closeBtn2) closeBtn2.addEventListener('click', closeProfile);
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeProfile();
        });
    }

    try {
        const [authRes, listRes] = await Promise.all([
            fetch(`${baseUrl}/auth/check`, { credentials: "include" }),
            fetch(`${baseUrl}/students/active-by-class?class=${encodeURIComponent(className)}`, { credentials: "include" })
        ]);
        const auth = await authRes.json();
        if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
        }
        const list = await listRes.json();
        if (!list.success || !Array.isArray(list.data) || list.data.length === 0) {
            if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No active students found in this class.</td></tr>';
            return;
        }

        if (tbody) {
            const rows = list.data.map((student, index) => {
                const createdAt = student.created_at ? new Date(student.created_at) : null;
                const joinedOn = createdAt ? createdAt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
                return `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${student.name}</td>
                        <td>${student.class}</td>
                        <td>${joinedOn}</td>
                        <td>
                            <a href="#" class="view-student" data-id="${student.student_id}">
                                <span class="status process">View</span>
                            </a>
                            <button class="edit-student-btn" data-id="${student.student_id}" data-name="${student.name}" style="margin-left: 10px; padding: 5px 10px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                <i class="fas fa-pencil"></i> Edit
                            </button>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = rows.join('');
        }

        if (tbody) {
            tbody.addEventListener('click', async (event) => {
                const link = event.target.closest('.view-student');
                if (!link) return;
                event.preventDefault();
                const studentId = link.dataset.id;
                try {
                    const profileRes = await fetch(`${baseUrl}/students/profile?student_id=${encodeURIComponent(studentId)}`, { credentials: "include" });
                    const profile = await profileRes.json();
                    if (!profile.success) return;

                    const info = profile.data.student;
                    const lastExamDate = profile.data.last_exam_date ? new Date(profile.data.last_exam_date) : null;
                    const joinedAt = info.created_at ? new Date(info.created_at) : null;
                    const updatedAt = info.updated_at ? new Date(info.updated_at) : null;

                    document.getElementById('profile-title').textContent = `Student Profile - ${info.name}`;
                    document.getElementById('profile-name').textContent = info.name ?? '-';
                    document.getElementById('profile-class').textContent = info.class ?? '-';
                    document.getElementById('profile-status').textContent = info.status ?? '-';
                    document.getElementById('profile-joined').textContent = joinedAt ? joinedAt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
                    document.getElementById('profile-updated').textContent = updatedAt ? updatedAt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
                    document.getElementById('profile-results').textContent = profile.data.results_count ?? 0;
                    document.getElementById('profile-last-exam').textContent = profile.data.last_exam_name ?? '-';
                    document.getElementById('profile-last-exam-date').textContent = lastExamDate ? lastExamDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';

                    if (modal) modal.classList.add('active');
                } catch (err) {
                    // Optionally log error
                }
            });

            // Event delegation for edit button
            tbody.addEventListener('click', (event) => {
                const editBtn = event.target.closest('.edit-student-btn');
                if (!editBtn) return;
                event.preventDefault();
                const studentId = editBtn.dataset.id;
                const currentName = editBtn.dataset.name;
                openEditNameModal(studentId, currentName);
            });
        }

        // Edit name modal functions
        function openEditNameModal(studentId, currentName) {
            let editModal = document.getElementById('edit-name-modal');
            
            if (!editModal) {
                editModal = document.createElement('div');
                editModal.id = 'edit-name-modal';
                editModal.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    display: none;
                    align-items: center;
                    justify-content: center;
                    z-index: 1000;
                `;
                editModal.innerHTML = `
                    <div style="background: white; padding: 30px; border-radius: 8px; width: 90%; max-width: 400px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                        <h2 style="margin-top: 0; margin-bottom: 20px;">Edit Student Name</h2>
                        <form id="edit-name-form">
                            <div style="margin-bottom: 15px;">
                                <label for="edit-name-input" style="display: block; margin-bottom: 8px; font-weight: 500;">Student Name:</label>
                                <input 
                                    type="text" 
                                    id="edit-name-input" 
                                    required 
                                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 14px;"
                                />
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" style="flex: 1; padding: 10px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 500;">Save</button>
                                <button type="button" id="edit-close-btn" style="flex: 1; padding: 10px; background-color: #666; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 500;">Cancel</button>
                            </div>
                        </form>
                    </div>
                `;
                document.body.appendChild(editModal);
            }
            
            document.getElementById('edit-name-input').value = currentName;
            editModal.style.display = 'flex';
            
            document.getElementById('edit-close-btn').onclick = () => {
                editModal.style.display = 'none';
            };
            
            editModal.onclick = (e) => {
                if (e.target === editModal) {
                    editModal.style.display = 'none';
                }
            };
            
            const form = document.getElementById('edit-name-form');
            form.onsubmit = async (e) => {
                e.preventDefault();
                const newName = document.getElementById('edit-name-input').value.trim();
                
                if (!newName) {
                    alert('Please enter a student name');
                    return;
                }
                
                if (newName === currentName) {
                    editModal.style.display = 'none';
                    return;
                }
                
                await updateStudentName(studentId, newName);
                editModal.style.display = 'none';
            };
        }

        async function updateStudentName(studentId, newName) {
            try {
                const response = await fetch(`${baseUrl}/students/update-name`, {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        student_id: studentId,
                        name: newName
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const editBtn = document.querySelector(`[data-id="${studentId}"]`);
                    if (editBtn) {
                        const row = editBtn.closest('tr');
                        if (row) {
                            row.cells[1].textContent = newName;
                            editBtn.dataset.name = newName;
                        }
                    }
                    
                    const messageEl = document.createElement('div');
                    messageEl.style.cssText = `
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background-color: #4CAF50;
                        color: white;
                        padding: 15px 20px;
                        border-radius: 4px;
                        z-index: 2000;
                    `;
                    messageEl.textContent = 'Student name updated successfully';
                    document.body.appendChild(messageEl);
                    
                    setTimeout(() => messageEl.remove(), 3000);
                } else {
                    alert('Error: ' + (result.message || 'Failed to update student name'));
                }
            } catch (error) {
                alert('Error updating student name: ' + error.message);
            }
        }
    } catch (error) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Failed to load active students.</td></tr>';
    }
})();
