// subjects.js - Subject marks management page logic

// ========================================
// TOKEN MANAGEMENT
// ========================================
function getSubjectTokenData(token) {
    const tokenMap = JSON.parse(sessionStorage.getItem('subjectTokenMap') || '{}');
    const data = tokenMap[token];
    
    if (!data) {
        console.error('Invalid token: ' + token);
        return null;
    }
    
    return data;
}

// ========================================
// COMPONENT LOADING
// ========================================
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

// ========================================
// API CONFIGURATION
// ========================================
const API_BASE_URL = '../backend/public/index.php';

// ========================================
// STATE
// ========================================
let subjectId = null;
let classId = null;
let currentSubjectName = null;
let currentClassName = null;
let currentExamId = null;

// ========================================
// DOM ELEMENTS
// ========================================
const loading = document.getElementById('loading');
const studentsContainer = document.getElementById('studentsContainer');
const noDataMessage = document.getElementById('noDataMessage');
const errorMessage = document.getElementById('errorMessage');
const successMessage = document.getElementById('successMessage');
const studentTableBody = document.getElementById('studentTableBody');
const marksOutOfInput = document.getElementById('marksOutOf');
const setMarksBtn = document.getElementById('setMarksBtn');

// ========================================
// INITIALIZATION
// ========================================
document.addEventListener('DOMContentLoaded', async () => {
    await Promise.all([
        loadComponentPromise('header', 'headerContainer'),
        loadComponentPromise('sidebar', 'sidebarContainer'),
        loadComponentPromise('bottom-navigator', 'bottomNavContainer'),
        loadComponentPromise('footer', 'footerContainer'),
        loadExaminerData()
    ]);

    await checkAuth();
    
    // Load marksOutOf from localStorage if it exists
    const savedMarksOutOf = localStorage.getItem('marksOutOf');
    if (savedMarksOutOf) {
        marksOutOfInput.value = savedMarksOutOf;
    }
    
    // Get token from URL, or fall back to direct IDs for backward compatibility
    const params = new URLSearchParams(window.location.search);
    const token = params.get('token');
    let directSubjectId = params.get('subject_id');
    let directClassId = params.get('class_id');

    if (token) {
        // Retrieve actual IDs from token
        const tokenData = getSubjectTokenData(token);
        if (!tokenData) {
            showError('Invalid or expired link. Please go back and select again.');
            return;
        }
        subjectId = tokenData.subject_id;
        classId = tokenData.class_id;
    } else {
        subjectId = directSubjectId;
        classId = directClassId;
    }

    if (!subjectId || !classId) {
        showError('Missing subject or class ID. Please go back and select again.');
        return;
    }

    await loadStudents(subjectId, classId);
});

// ========================================
// AUTHENTICATION
// ========================================
async function checkAuth() {
    try {
        const response = await fetch(`${API_BASE_URL}/auth/check`, { credentials: 'include' });
        const data = await response.json();

        if (data.success) {
            document.querySelector('.name') && (document.querySelector('.name').textContent = data.name || 'User');
        } else {
            window.location.href = '../index.php';
        }
    } catch (error) {
        console.error('Auth check failed:', error);
        window.location.href = '../index.php';
    }
}

// ========================================
// LOAD STUDENTS
// ========================================
async function loadStudents(subjectId, classId) {
    try {
        showLoading(true);
        hideMessages();

        const url = `${API_BASE_URL}/subjects/students?subject_id=${subjectId}&class_id=${classId}`;

        const response = await fetch(url, { credentials: 'include' });

        const data = await response.json();

        if (!response.ok) {
            showError(data.message || 'Failed to load students.');
            return;
        }

        if (data.success && data.students && data.students.length > 0) {
            displayStudents(data);
            studentsContainer.style.display = 'block';
            noDataMessage.style.display = 'none';
        } else {
            noDataMessage.style.display = 'block';
            studentsContainer.style.display = 'none';
            showError('No students found for this subject and class.');
        }
    } catch (error) {
        console.error('Error loading students:', error);
        showError('Failed to load students: ' + error.message);
        noDataMessage.style.display = 'block';
        studentsContainer.style.display = 'none';
    } finally {
        showLoading(false);
    }
}

// ========================================
// DISPLAY STUDENTS
// ========================================
function displayStudents(data) {
    studentTableBody.innerHTML = '';

    // Update heading with subject name
    const heading = document.querySelector('.heading');
    if (heading && data.subject_name) {
        heading.textContent = `Subject Marks Management - ${data.subject_name}`;
    }

    data.students.forEach(student => {
        const row = document.createElement('tr');
        const marks = student.marks ?? '-';
        const marksDisplay = marks === '-' ? '-' : marks + '%';

        row.innerHTML = `
            <td>${student.name}</td>
            <td id="marks-${student.student_id}">${marksDisplay}</td>
            <td>
                <button class="action-btn edit-student-btn" 
                    data-student-id="${student.student_id}"
                    data-student-class-id="${student.student_class_id}"
                    data-student-name="${student.name}"
                    data-marks="${marks === '-' ? 0 : marks}">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </td>
        `;

        studentTableBody.appendChild(row);
    });

    // Add event delegation for edit buttons
    studentTableBody.addEventListener('click', (e) => {
        const button = e.target.closest('.edit-student-btn');
        if (button) {
            openEditModal(
                parseInt(button.dataset.studentId),
                parseInt(button.dataset.studentClassId),
                button.dataset.studentName,
                parseInt(button.dataset.marks)
            );
        }
    });
}

// ========================================
// MODAL MANAGEMENT
// ========================================
const editMarksModal = new bootstrap.Modal(document.getElementById('editMarksModal'));
const editMarksForm = document.getElementById('editMarksForm');

window.openEditModal = function(studentId, studentClassId, studentName, currentMarks) {
    // Check if marks out of is set
    const marksOutOf = marksOutOfInput.value;
    if (!marksOutOf) {
        swal({
            title: 'Warning!',
            text: 'Please set the "Marks Out Of" value first.',
            icon: 'warning',
            button: 'OK'
        });
        return;
    }
    
    document.getElementById('modalStudentId').value = studentId;
    document.getElementById('modalStudentClassId').value = studentClassId;
    document.getElementById('modalStudentName').value = studentName;
    document.getElementById('modalMarksInput').value = currentMarks === 0 ? '' : currentMarks;
    document.getElementById('modalMarksOutOf').textContent = marksOutOf;
    editMarksModal.show();
};

editMarksForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const studentId = parseInt(document.getElementById('modalStudentId').value);
    const studentClassId = parseInt(document.getElementById('modalStudentClassId').value);
    const marks = parseInt(document.getElementById('modalMarksInput').value);

    if (isNaN(marks) || marks < 0) {
        swal({
            title: 'Error!',
            text: 'Please enter a valid mark.',
            icon: 'error',
            button: 'OK'
        });
        return;
    }

    const marksOutOf = marksOutOfInput.value || 100;
    if (marks > marksOutOf) {
        swal({
            title: 'Error!',
            text: `Marks cannot exceed ${marksOutOf}.`,
            icon: 'error',
            button: 'OK'
        });
        return;
    }

    await updateMarks(studentId, studentClassId, subjectId, marks);
    editMarksModal.hide();
});

// ========================================
// UPDATE MARKS
// ========================================
async function updateMarks(studentId, studentClassId, subjectId, marks) {
    try {
        const marksOutOf = marksOutOfInput.value || 100;
        
        const response = await fetch(`${API_BASE_URL}/subjects/students/marks`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                student_id: studentId,
                student_class_id: studentClassId,
                subject_id: parseInt(subjectId),
                marks: marks,
                marks_out_of: parseInt(marksOutOf)
            })
        });

        const data = await response.json();

        if (data.success) {
            // Update display with percentage
            const percentage = Math.round((marks / marksOutOf) * 100);
            document.getElementById(`marks-${studentId}`).textContent = percentage + '%';

            // Show success message
            swal({
                title: 'Success!',
                text: 'Marks updated successfully (' + marks + '/' + marksOutOf + ' = ' + percentage + '%)',
                icon: 'success',
                button: 'OK'
            });
        } else {
            swal({
                title: 'Error!',
                text: data.message || 'Failed to update marks.',
                icon: 'error',
                button: 'OK'
            });
        }
    } catch (error) {
        console.error('Error updating marks:', error);
        swal({
            title: 'Error!',
            text: 'Failed to update marks: ' + error.message,
            icon: 'error',
            button: 'OK'
        });
    }
}

// ========================================
// SET MARKS OUT OF
// ========================================
setMarksBtn.addEventListener('click', () => {
    const marksOutOf = marksOutOfInput.value;

    if (!marksOutOf) {
        swal({
            title: 'Error!',
            text: 'Please enter marks out of value.',
            icon: 'error',
            button: 'OK'
        });
        return;
    }

    // Store in localStorage for reference
    localStorage.setItem('marksOutOf', marksOutOf);
    swal({
        title: 'Success!',
        text: `Marks out of set to ${marksOutOf}`,
        icon: 'success',
        button: 'OK'
    });
    // Keep the value in the input field
});

// ========================================
// UI UTILITIES
// ========================================
function showLoading(show) {
    loading.classList.toggle('active', show);
}

function showError(message) {
    errorMessage.textContent = message;
    errorMessage.classList.add('show');
    setTimeout(() => errorMessage.classList.remove('show'), 5000);
}

function showSuccess(message) {
    successMessage.textContent = message;
    successMessage.classList.add('show');
    setTimeout(() => successMessage.classList.remove('show'), 3000);
}

function hideMessages() {
    errorMessage.classList.remove('show');
    successMessage.classList.remove('show');
}
