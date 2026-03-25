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

// Paper marks out of (for component subjects)
let paper1EnglishOutOf = 0;
let paper2EnglishOutOf = 0;
let paper1KiswahiliOutOf = 0;
let paper2KiswahiliOutOf = 0;

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
const regularMarksOutOfSection = document.getElementById('regularMarksOutOfSection');
const paperMarksOutOfSection = document.getElementById('paperMarksOutOfSection');
const paper1OutOfInput = document.getElementById('paper1OutOf');
const paper2OutOfInput = document.getElementById('paper2OutOf');
const setPaperBtn = document.getElementById('setPaperBtn');

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
        currentSubjectName = data.subject_name;
    }
    
    // Show/hide appropriate marks-out-of section based on subject
    const isEnglish = data.subject_name === 'English';
    const isKiswahili = data.subject_name === 'Kiswahili';
    
    if (isEnglish || isKiswahili) {
        regularMarksOutOfSection.style.display = 'none';
        paperMarksOutOfSection.style.display = 'block';
        
        // Load saved paper marks out of values from localStorage
        const savedPaper1 = localStorage.getItem(`paper1OutOf_${data.subject_name}`);
        const savedPaper2 = localStorage.getItem(`paper2OutOf_${data.subject_name}`);
        
        if (savedPaper1) paper1OutOfInput.value = savedPaper1;
        if (savedPaper2) paper2OutOfInput.value = savedPaper2;
        
        // Load component marks-out-of from API
        loadComponentMarksOutOf(data.subject_name);
    } else {
        regularMarksOutOfSection.style.display = 'block';
        paperMarksOutOfSection.style.display = 'none';
        
        // Load saved regular marks out of value
        const savedMarksOutOf = localStorage.getItem('marksOutOf');
        if (savedMarksOutOf) {
            marksOutOfInput.value = savedMarksOutOf;
        }
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
// LOAD COMPONENT MARKS OUT OF
// ========================================
async function loadComponentMarksOutOf(subject) {
    try {
        const response = await fetch(`${API_BASE_URL}/subjects/marks-out-of?subject=${encodeURIComponent(subject)}`, {
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success && data.marks_out_of) {
            if (subject === 'English') {
                paper1EnglishOutOf = data.marks_out_of.Paper1;
                paper2EnglishOutOf = data.marks_out_of.Paper2;
            } else if (subject === 'Kiswahili') {
                paper1KiswahiliOutOf = data.marks_out_of.Paper1;
                paper2KiswahiliOutOf = data.marks_out_of.Paper2;
            }
        }
    } catch (error) {
        console.error('Error loading component marks out of:', error);
    }
}

// ========================================
// MODAL MANAGEMENT
// ========================================
const editMarksModal = new bootstrap.Modal(document.getElementById('editMarksModal'));
const editMarksForm = document.getElementById('editMarksForm');

window.openEditModal = function(studentId, studentClassId, studentName, currentMarks) {
    document.getElementById('modalStudentId').value = studentId;
    document.getElementById('modalStudentClassId').value = studentClassId;
    document.getElementById('modalStudentName').value = studentName;
    
    // Show/hide input groups based on current subject
    const isEnglish = currentSubjectName === 'English';
    const isKiswahili = currentSubjectName === 'Kiswahili';
    
    document.getElementById('regularSubjectInputGroup').style.display = isEnglish || isKiswahili ? 'none' : 'block';
    document.getElementById('englishInputGroup').style.display = isEnglish ? 'block' : 'none';
    document.getElementById('kiswahiliInputGroup').style.display = isKiswahili ? 'block' : 'none';
    
    if (isEnglish) {
        // Check if paper marks out of are set in input fields
        const paper1Value = paper1OutOfInput.value.trim();
        const paper2Value = paper2OutOfInput.value.trim();
        
        // Require explicit values from input fields - don't fall back to API/state values
        if (!paper1Value || !paper2Value) {
            swal({
                title: 'Warning!',
                text: 'Please set both Paper 1 and Paper 2 marks out of values first.',
                icon: 'warning',
                button: 'OK'
            });
            return;
        }
        
        const paper1OutOf = parseInt(paper1Value);
        const paper2OutOf = parseInt(paper2Value);
        
        if (isNaN(paper1OutOf) || paper1OutOf <= 0 || isNaN(paper2OutOf) || paper2OutOf <= 0) {
            swal({
                title: 'Error!',
                text: 'Paper marks must be valid numbers greater than 0.',
                icon: 'error',
                button: 'OK'
            });
            return;
        }
        
        document.getElementById('paper1EnglishMarks').value = '';
        document.getElementById('paper2EnglishMarks').value = '';
        document.getElementById('englishTotalPercentage').textContent = '0%';
        
        document.getElementById('paper1EnglishOutOf').textContent = paper1OutOf;
        document.getElementById('paper2EnglishOutOf').textContent = paper2OutOf;
        
        // Update state variables with validated values
        paper1EnglishOutOf = parseInt(paper1OutOf);
        paper2EnglishOutOf = parseInt(paper2OutOf);
    } else if (isKiswahili) {
        // Check if paper marks out of are set in input fields
        const paper1Value = paper1OutOfInput.value.trim();
        const paper2Value = paper2OutOfInput.value.trim();
        
        // Require explicit values from input fields - don't fall back to API/state values
        if (!paper1Value || !paper2Value) {
            swal({
                title: 'Warning!',
                text: 'Please set both Paper 1 and Paper 2 marks out of values first.',
                icon: 'warning',
                button: 'OK'
            });
            return;
        }
        
        const paper1OutOf = parseInt(paper1Value);
        const paper2OutOf = parseInt(paper2Value);
        
        if (isNaN(paper1OutOf) || paper1OutOf <= 0 || isNaN(paper2OutOf) || paper2OutOf <= 0) {
            swal({
                title: 'Error!',
                text: 'Paper marks must be valid numbers greater than 0.',
                icon: 'error',
                button: 'OK'
            });
            return;
        }
        
        document.getElementById('paper1KiswahiliMarks').value = '';
        document.getElementById('paper2KiswahiliMarks').value = '';
        document.getElementById('kiswahiliTotalPercentage').textContent = '0%';
        
        document.getElementById('paper1KiswahiliOutOf').textContent = paper1OutOf;
        document.getElementById('paper2KiswahiliOutOf').textContent = paper2OutOf;
        
        // Update state variables with validated values
        paper1KiswahiliOutOf = parseInt(paper1OutOf);
        paper2KiswahiliOutOf = parseInt(paper2OutOf);
    } else {
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
        document.getElementById('modalMarksInput').value = currentMarks === 0 ? '' : currentMarks;
        document.getElementById('modalMarksOutOf').textContent = marksOutOf;
    }
    
    editMarksModal.show();
};

editMarksForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const studentId = parseInt(document.getElementById('modalStudentId').value);
    const studentClassId = parseInt(document.getElementById('modalStudentClassId').value);
    
    const isEnglish = currentSubjectName === 'English';
    const isKiswahili = currentSubjectName === 'Kiswahili';
    
    let marks = 0;
    let marksOutOf = 100;
    
    if (isEnglish) {
        const paper1 = parseInt(document.getElementById('paper1EnglishMarks').value) || 0;
        const paper2 = parseInt(document.getElementById('paper2EnglishMarks').value) || 0;
        
        if (paper1 < 0 || paper2 < 0) {
            swal({
                title: 'Error!',
                text: 'Please enter valid marks.',
                icon: 'error',
                button: 'OK'
            });
            return;
        }
        
        if (paper1 > paper1EnglishOutOf || paper2 > paper2EnglishOutOf) {
            swal({
                title: 'Error!',
                text: `Paper 1 cannot exceed ${paper1EnglishOutOf}, Paper 2 cannot exceed ${paper2EnglishOutOf}.`,
                icon: 'error',
                button: 'OK'
            });
            return;
        }
        
        await updatePaperMarks(studentId, studentClassId, 'English', paper1, paper1EnglishOutOf, 'Paper1');
        await updatePaperMarks(studentId, studentClassId, 'English', paper2, paper2EnglishOutOf, 'Paper2');
        
        const totalMarks = paper1 + paper2;
        const totalOutOf = paper1EnglishOutOf + paper2EnglishOutOf;
        const percentage = totalOutOf > 0 ? Math.round((totalMarks / totalOutOf) * 100) : 0;
        await updatePaperMarks(studentId, studentClassId, 'English', percentage, 100, 'Total');
        
        // Update table display in real-time
        document.getElementById(`marks-${studentId}`).textContent = percentage + '%';
        
        editMarksModal.hide();
        swal({
            title: 'Success!',
            text: `Marks updated successfully (Paper1: ${paper1}/${paper1EnglishOutOf}, Paper2: ${paper2}/${paper2EnglishOutOf} = ${percentage}%)`,
            icon: 'success',
            button: 'OK'
        });
        return;
        
    } else if (isKiswahili) {
        const paper1 = parseInt(document.getElementById('paper1KiswahiliMarks').value) || 0;
        const paper2 = parseInt(document.getElementById('paper2KiswahiliMarks').value) || 0;
        
        if (paper1 < 0 || paper2 < 0) {
            swal({
                title: 'Error!',
                text: 'Please enter valid marks.',
                icon: 'error',
                button: 'OK'
            });
            return;
        }
        
        if (paper1 > paper1KiswahiliOutOf || paper2 > paper2KiswahiliOutOf) {
            swal({
                title: 'Error!',
                text: `Paper 1 cannot exceed ${paper1KiswahiliOutOf}, Paper 2 cannot exceed ${paper2KiswahiliOutOf}.`,
                icon: 'error',
                button: 'OK'
            });
            return;
        }
        
        await updatePaperMarks(studentId, studentClassId, 'Kiswahili', paper1, paper1KiswahiliOutOf, 'Paper1');
        await updatePaperMarks(studentId, studentClassId, 'Kiswahili', paper2, paper2KiswahiliOutOf, 'Paper2');
        
        const totalMarks = paper1 + paper2;
        const totalOutOf = paper1KiswahiliOutOf + paper2KiswahiliOutOf;
        const percentage = totalOutOf > 0 ? Math.round((totalMarks / totalOutOf) * 100) : 0;
        await updatePaperMarks(studentId, studentClassId, 'Kiswahili', percentage, 100, 'Total');
        
        // Update table display in real-time
        document.getElementById(`marks-${studentId}`).textContent = percentage + '%';
        
        editMarksModal.hide();
        swal({
            title: 'Success!',
            text: `Marks updated successfully (Paper1: ${paper1}/${paper1KiswahiliOutOf}, Paper2: ${paper2}/${paper2KiswahiliOutOf} = ${percentage}%)`,
            icon: 'success',
            button: 'OK'
        });
        return;
        
    } else {
        marks = parseInt(document.getElementById('modalMarksInput').value);
        
        if (isNaN(marks) || marks < 0) {
            swal({
                title: 'Error!',
                text: 'Please enter a valid mark.',
                icon: 'error',
                button: 'OK'
            });
            return;
        }
        
        const marksOutOfValue = marksOutOfInput.value || 100;
        if (marks > marksOutOfValue) {
            swal({
                title: 'Error!',
                text: `Marks cannot exceed ${marksOutOfValue}.`,
                icon: 'error',
                button: 'OK'
            });
            return;
        }
        
        await updateMarks(studentId, studentClassId, subjectId, marks);
    }
    
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
// UPDATE PAPER MARKS (for component subjects)
// ========================================
async function updatePaperMarks(studentId, studentClassId, subject, marks, marksOutOf, paperType) {
    try {
        const response = await fetch(`${API_BASE_URL}/subjects/students/marks`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                student_id: studentId,
                student_class_id: studentClassId,
                subject: subject,
                marks: marks,
                marks_out_of: marksOutOf,
                paper_type: paperType
            })
        });

        const data = await response.json();
        
        if (!data.success) {
            console.error('Error updating paper marks:', data.message);
        }
    } catch (error) {
        console.error('Error updating paper marks:', error);
    }
}

// ========================================
// PERCENTAGE CALCULATION
// ========================================
function calculateEnglishTotal() {
    const paper1 = parseInt(document.getElementById('paper1EnglishMarks').value) || 0;
    const paper2 = parseInt(document.getElementById('paper2EnglishMarks').value) || 0;
    const totalOutOf = paper1EnglishOutOf + paper2EnglishOutOf;
    const totalMarks = paper1 + paper2;
    const percentage = totalOutOf > 0 ? Math.round((totalMarks / totalOutOf) * 100) : 0;
    document.getElementById('englishTotalPercentage').textContent = percentage + '%';
}

function calculateKiswahiliTotal() {
    const paper1 = parseInt(document.getElementById('paper1KiswahiliMarks').value) || 0;
    const paper2 = parseInt(document.getElementById('paper2KiswahiliMarks').value) || 0;
    const totalOutOf = paper1KiswahiliOutOf + paper2KiswahiliOutOf;
    const totalMarks = paper1 + paper2;
    const percentage = totalOutOf > 0 ? Math.round((totalMarks / totalOutOf) * 100) : 0;
    document.getElementById('kiswahiliTotalPercentage').textContent = percentage + '%';
}

// Add event listeners for real-time calculation
document.addEventListener('DOMContentLoaded', () => {
    const paper1EnglishInput = document.getElementById('paper1EnglishMarks');
    const paper2EnglishInput = document.getElementById('paper2EnglishMarks');
    const paper1KiswahiliInput = document.getElementById('paper1KiswahiliMarks');
    const paper2KiswahiliInput = document.getElementById('paper2KiswahiliMarks');
    
    if (paper1EnglishInput) paper1EnglishInput.addEventListener('input', calculateEnglishTotal);
    if (paper2EnglishInput) paper2EnglishInput.addEventListener('input', calculateEnglishTotal);
    if (paper1KiswahiliInput) paper1KiswahiliInput.addEventListener('input', calculateKiswahiliTotal);
    if (paper2KiswahiliInput) paper2KiswahiliInput.addEventListener('input', calculateKiswahiliTotal);
});

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
// SET PAPER MARKS OUT OF
// ========================================
setPaperBtn.addEventListener('click', () => {
    const paper1OutOf = paper1OutOfInput.value;
    const paper2OutOf = paper2OutOfInput.value;

    if (!paper1OutOf || !paper2OutOf) {
        swal({
            title: 'Error!',
            text: 'Please enter values for both Paper 1 and Paper 2.',
            icon: 'error',
            button: 'OK'
        });
        return;
    }

    // Store in localStorage
    localStorage.setItem(`paper1OutOf_${currentSubjectName}`, paper1OutOf);
    localStorage.setItem(`paper2OutOf_${currentSubjectName}`, paper2OutOf);
    
    // Update state variables
    if (currentSubjectName === 'English') {
        paper1EnglishOutOf = parseInt(paper1OutOf);
        paper2EnglishOutOf = parseInt(paper2OutOf);
    } else if (currentSubjectName === 'Kiswahili') {
        paper1KiswahiliOutOf = parseInt(paper1OutOf);
        paper2KiswahiliOutOf = parseInt(paper2OutOf);
    }
    
    swal({
        title: 'Success!',
        text: `Paper marks set: Paper 1 = ${paper1OutOf}, Paper 2 = ${paper2OutOf}`,
        icon: 'success',
        button: 'OK'
    });
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
