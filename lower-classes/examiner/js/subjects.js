// Subjects marks management page

// Global state
let currentSubject = null;
let currentClass = null;
let currentExamId = null;
let marksOutOf = 100;
let marksOutOfSet = false;
let editingStudent = {
    studentId: null,
    studentClassId: null,
    studentName: null
};

const apiBase = '../backend/public/index.php';

// Load components
async function loadComponentPromise(componentName, containerId) {
    try {
        const res = await fetch(`components/${componentName}.html`);
        if (!res.ok) throw new Error(`Failed to load ${componentName}`);
        const html = await res.text();
        document.getElementById(containerId).innerHTML = html;
        if (containerId === 'headerContainer') {
            document.dispatchEvent(new Event('headerLoaded'));
        }
    } catch (err) {
        console.error(`Error loading ${componentName} component:`, err);
    }
}

// Get subject from URL parameter
function getSubjectFromURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get('subject') || 'Math';
}

// Show error message
function showError(message) {
    const errorEl = document.getElementById('errorMessage');
    errorEl.innerHTML = `<div class="error-message">${message}</div>`;
}

// Clear error message
function clearError() {
    document.getElementById('errorMessage').innerHTML = '';
}

// Initialize page
async function initializePage() {
    try {
        // Check auth
        const authRes = await fetch(`${apiBase}/auth/check`, { credentials: 'include' });
        const authData = await authRes.json();
        
        if (!authData.success) {
            window.location.href = 'index.html';
            return;
        }

        // Check exam selection
        if (!authData.exam_id) {
            window.location.href = 'exam.html';
            return;
        }

        currentExamId = authData.exam_id;
        currentClass = authData.class_assigned;

        // Get subject from URL
        currentSubject = getSubjectFromURL();

        // Load examiner data
        await loadExaminerData();

        // Update page title
        document.title = `${currentSubject} - Marks`;
        document.getElementById('subjectTitle').textContent = `${currentSubject} Marks`;
        document.getElementById('marksOutOfSection').style.display = 'block';

        // Load students and marks
        await loadStudentsAndMarks();

    } catch (error) {
        console.error('Error initializing page:', error);
        showError('Failed to load page. Please refresh.');
    }
}

// Load students and their marks
async function loadStudentsAndMarks() {
    try {
        document.getElementById('loadingState').style.display = 'block';
        document.getElementById('tableContainer').style.display = 'none';
        clearError();

        // Fetch student marks from API
        const response = await fetch(
            `${apiBase}/subjects/marks?subject=${encodeURIComponent(currentSubject)}&class=${encodeURIComponent(currentClass)}&exam_id=${currentExamId}`,
            { credentials: 'include' }
        );

        if (!response.ok) {
            if (response.status === 401) {
                window.location.href = 'index.html';
                return;
            }
            throw new Error(`Failed to load marks: ${response.status}`);
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Failed to load marks');
        }

        // Store marks out of
        if (data.marks_out_of) {
            marksOutOf = data.marks_out_of;
            marksOutOfSet = true;
            document.getElementById('marksOutOf').value = marksOutOf;
        }

        // Populate table
        const tbody = document.getElementById('studentsTableBody');
        tbody.innerHTML = '';

        if (data.students && data.students.length > 0) {
            data.students.forEach(student => {
                const row = document.createElement('tr');
                const marks = student.marks !== null ? student.marks : '-';
                row.innerHTML = `
                    <td>${escapeHtml(student.student_name)}</td>
                    <td>${marks}</td>
                    <td>
                        <button 
                            class="option-btn edit-btn"
                            data-student-id="${student.student_id}"
                            data-student-class-id="${student.student_class_id}"
                            data-student-name="${student.student_name}"
                            data-marks="${student.marks || ''}"
                        >
                            Edit
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });

            // Add event delegation for edit buttons
            tbody.addEventListener('click', (e) => {
                if (e.target.classList.contains('edit-btn')) {
                    const button = e.target;
                    openEditModal(
                        button.dataset.studentId,
                        button.dataset.studentClassId,
                        button.dataset.studentName,
                        button.dataset.marks
                    );
                }
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align: center;">No students found in this class.</td></tr>';
        }

        document.getElementById('loadingState').style.display = 'none';
        document.getElementById('tableContainer').style.display = 'block';

    } catch (error) {
        console.error('Error loading students:', error);
        document.getElementById('loadingState').style.display = 'none';
        showError(error.message || 'Failed to load student marks');
    }
}

// Open edit modal
function openEditModal(studentId, studentClassId, studentName, currentMarks) {
    // Check if marks out of has been set
    if (!marksOutOfSet) {
        swal({
            title: 'Warning',
            text: 'Please set the maximum marks for this subject first in the Configure Marks section.',
            icon: 'warning',
            button: 'OK'
        });
        return;
    }

    editingStudent.studentId = studentId;
    editingStudent.studentClassId = studentClassId;
    editingStudent.studentName = studentName;
    
    document.getElementById('studentNameInModal').textContent = studentName;
    document.getElementById('studentMarks').value = currentMarks || '';
    document.getElementById('maxMarks').textContent = marksOutOf;
    document.getElementById('editMarksModal').classList.add('show');
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editMarksModal').classList.remove('show');
    editingStudent = { studentId: null, studentClassId: null, studentName: null };
}

// Save marks
async function saveMarks(event) {
    event.preventDefault();
    
    try {
        const marks = parseFloat(document.getElementById('studentMarks').value);

        if (marks < 0) {
            swal({
                title: 'Error',
                text: 'Marks cannot be negative',
                icon: 'error',
                button: 'OK'
            });
            return;
        }

        if (marks > marksOutOf) {
            swal({
                title: 'Error',
                text: `Marks cannot exceed ${marksOutOf}`,
                icon: 'error',
                button: 'OK'
            });
            return;
        }

        // Call API to save marks
        const response = await fetch(`${apiBase}/subjects/marks/update`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                student_class_id: editingStudent.studentClassId,
                subject: currentSubject,
                exam_id: currentExamId,
                marks: marks
            })
        });

        const result = await response.json();

        if (!result.success) {
            showError(result.message || 'Failed to save marks');
            return;
        }

        // Show success message
        swal({
            title: 'Success',
            text: `Marks saved for ${editingStudent.studentName}`,
            icon: 'success',
            button: 'OK'
        }).then(() => {
            closeEditModal();
            loadStudentsAndMarks();
        });

    } catch (error) {
        console.error('Error saving marks:', error);
        showError('Failed to save marks. Please try again.');
    }
}

// Set marks out of
async function setMarksOutOf() {
    try {
        const marksValue = parseInt(document.getElementById('marksOutOf').value);

        if (!marksValue || marksValue < 1) {
            showError('Please enter a valid number greater than 0');
            return;
        }

        // Call API to set marks out of
        const response = await fetch(`${apiBase}/subjects/marks-out-of`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                subject: currentSubject,
                exam_id: currentExamId,
                marks_out_of: marksValue
            })
        });

        const result = await response.json();

        if (!result.success) {
            showError(result.message || 'Failed to set marks out of');
            return;
        }

        marksOutOf = marksValue;
        marksOutOfSet = true;
        swal({
            title: 'Success',
            text: `Marks out of set to ${marksValue}`,
            icon: 'success',
            button: 'OK'
        });

    } catch (error) {
        console.error('Error setting marks out of:', error);
        showError('Failed to set marks out of. Please try again.');
    }
}

// Escape HTML special characters
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Close modal on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeEditModal();
    }
});

// Initialize on load
document.addEventListener('DOMContentLoaded', async () => {
    await Promise.all([
        loadComponentPromise('header', 'headerContainer'),
        loadComponentPromise('sidebar', 'sidebarContainer'),
        loadComponentPromise('bottom-navigator', 'bottomNavContainer'),
        loadComponentPromise('footer', 'footerContainer')
    ]);
    await initializePage();
});
