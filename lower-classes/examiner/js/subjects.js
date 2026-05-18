// Subjects marks management page

// Global state
let currentSubject = null;
let currentClass = null;
let currentExamId = null;
let marksOutOf = 100;
let marksOutOfSet = false;
let isCombinedSubject = false;
let combinedSubjectConfig = null;
let marksOutOfMap = {}; // Store individual marks out of for each subject
let editingStudent = {
    studentId: null,
    studentClassId: null,
    studentName: null
};

// Configuration for combined subjects
const COMBINED_SUBJECTS = {
    'GRM': {
        label: 'English (RDG + GRM)',
        field1: 'RDG',
        field2: 'GRM',
        field1Label: 'RDG Marks',
        field2Label: 'GRM Marks',
        totalLabel: 'English Total',
        totalColumn: 'English'
    },
    'LUG': {
        label: 'Kiswahili (LUG + KUS)',
        field1: 'LUG',
        field2: 'KUS',
        field1Label: 'LUG Marks',
        field2Label: 'KUS Marks',
        totalLabel: 'Kiswahili Total',
        totalColumn: 'Kiswahili'
    }
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
        
        // Check if it's a combined subject
        if (COMBINED_SUBJECTS[currentSubject]) {
            isCombinedSubject = true;
            combinedSubjectConfig = COMBINED_SUBJECTS[currentSubject];
        }

        // Load examiner data
        await loadExaminerData();

        // Update page title
        const pageLabel = isCombinedSubject ? combinedSubjectConfig.label : `${currentSubject} Marks`;
        document.title = pageLabel;
        document.getElementById('subjectTitle').textContent = pageLabel;
        
        if (isCombinedSubject) {
            // Show combined subject marks configuration
            document.getElementById('marksOutOfSection').style.display = 'none';
            
            // Show the correct marks section based on subject
            if (currentSubject === 'GRM') {
                document.getElementById('paperMarksOutOfSectionEnglish').style.display = 'block';
                document.getElementById('paperMarksOutOfSectionKiswahili').style.display = 'none';
            } else if (currentSubject === 'LUG') {
                document.getElementById('paperMarksOutOfSectionEnglish').style.display = 'none';
                document.getElementById('paperMarksOutOfSectionKiswahili').style.display = 'block';
            }
        } else {
            // Show regular subject marks configuration
            document.getElementById('marksOutOfSection').style.display = 'block';
            document.getElementById('paperMarksOutOfSectionEnglish').style.display = 'none';
            document.getElementById('paperMarksOutOfSectionKiswahili').style.display = 'none';
        }

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

        // Fetch student marks from API (no subject filter - get all subjects)
        const response = await fetch(
            `${apiBase}/subjects/marks?class=${encodeURIComponent(currentClass)}&exam_id=${currentExamId}`,
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

        // Store marks out of map
        if (data.marks_out_of && typeof data.marks_out_of === 'object') {
            marksOutOfMap = data.marks_out_of;
            
            // For combined subjects, check if both components are set
            if (isCombinedSubject) {
                if (currentSubject === 'GRM') {
                    if (marksOutOfMap['GRM'] && marksOutOfMap['RDG']) {
                        marksOutOfSet = true;
                    }
                } else if (currentSubject === 'LUG') {
                    if (marksOutOfMap['LUG'] && marksOutOfMap['KUS']) {
                        marksOutOfSet = true;
                    }
                }
            } else {
                // For regular subjects
                if (marksOutOfMap[currentSubject]) {
                    marksOutOf = marksOutOfMap[currentSubject];
                    marksOutOfSet = true;
                    document.getElementById('marksOutOf').value = marksOutOf;
                }
            }
        }

        // Populate table
        const tableHead = document.getElementById('studentsTableHead');
        const tbody = document.getElementById('studentsTableBody');
        tbody.innerHTML = '';

        if (isCombinedSubject) {
            tableHead.innerHTML = `
                <tr>
                    <th>Student Name</th>
                    <th>${combinedSubjectConfig.field1Label}</th>
                    <th>${combinedSubjectConfig.field2Label}</th>
                    <th>${combinedSubjectConfig.totalLabel}</th>
                    <th>Action</th>
                </tr>
            `;
        } else {
            tableHead.innerHTML = `
                <tr>
                    <th>Student Name</th>
                    <th>Current Marks (%)</th>
                    <th>Action</th>
                </tr>
            `;
        }

        if (data.students && data.students.length > 0) {
            data.students.forEach(student => {
                const row = document.createElement('tr');

                if (isCombinedSubject) {
                    const field1Key = `${combinedSubjectConfig.field1.toLowerCase()}_marks`;
                    const field2Key = `${combinedSubjectConfig.field2.toLowerCase()}_marks`;
                    const totalKey = `${combinedSubjectConfig.totalColumn.toLowerCase()}_marks`;

                    const field1Marks = student[field1Key] !== null ? parseFloat(student[field1Key]).toFixed(2) : '-';
                    const field2Marks = student[field2Key] !== null ? parseFloat(student[field2Key]).toFixed(2) : '-';
                    const totalMarks = student[totalKey] !== null ? parseFloat(student[totalKey]).toFixed(2) : '-';

                    row.innerHTML = `
                        <td>${escapeHtml(student.student_name)}</td>
                        <td>${field1Marks}</td>
                        <td>${field2Marks}</td>
                        <td><strong>${totalMarks}</strong></td>
                        <td>
                            <button 
                                class="option-btn edit-btn"
                                data-student-id="${student.student_id}"
                                data-student-class-id="${student.student_class_id}"
                                data-student-name="${student.student_name}"
                                data-field1-marks="${student[field1Key] || ''}"
                                data-field2-marks="${student[field2Key] || ''}"
                            >
                                Edit
                            </button>
                        </td>
                    `;
                } else {
                    const marksKey = `${currentSubject.toLowerCase()}_marks`;
                    const marks = student[marksKey] !== null ? parseFloat(student[marksKey]).toFixed(2) : '-';
                    row.innerHTML = `
                        <td>${escapeHtml(student.student_name)}</td>
                        <td>${marks}</td>
                        <td>
                            <button 
                                class="option-btn edit-btn"
                                data-student-id="${student.student_id}"
                                data-student-class-id="${student.student_class_id}"
                                data-student-name="${student.student_name}"
                                data-marks="${student[marksKey] || ''}"
                            >
                                Edit
                            </button>
                        </td>
                    `;
                }

                tbody.appendChild(row);
            });

            tbody.onclick = (e) => {
                if (e.target.classList.contains('edit-btn')) {
                    const button = e.target;
                    if (isCombinedSubject) {
                        openEditModal(
                            button.dataset.studentId,
                            button.dataset.studentClassId,
                            button.dataset.studentName,
                            null,
                            button.dataset.field1Marks,
                            button.dataset.field2Marks
                        );
                    } else {
                        openEditModal(
                            button.dataset.studentId,
                            button.dataset.studentClassId,
                            button.dataset.studentName,
                            button.dataset.marks
                        );
                    }
                }
            };
        } else {
            const colspan = isCombinedSubject ? 5 : 3;
            tbody.innerHTML = `<tr><td colspan="${colspan}" style="text-align: center;">No students found in this class.</td></tr>`;
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
function openEditModal(studentId, studentClassId, studentName, currentMarks, field1Marks, field2Marks) {
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

    if (isCombinedSubject) {
        document.getElementById('studentMarksGroup').style.display = 'none';
        document.getElementById('studentRdgMarksGroup').style.display = 'block';
        document.getElementById('studentGrmMarksGroup').style.display = 'block';
        document.getElementById('studentMarks').removeAttribute('required');
        document.getElementById('studentRdgMarks').setAttribute('required', 'required');
        document.getElementById('studentGrmMarks').setAttribute('required', 'required');
        
        // Get individual max marks for each component
        let field1MaxMarks = 100;
        let field2MaxMarks = 100;
        
        if (currentSubject === 'GRM') {
            field1MaxMarks = marksOutOfMap['RDG'] || 100;  // RDG is field1
            field2MaxMarks = marksOutOfMap['GRM'] || 100;  // GRM is field2
        } else if (currentSubject === 'LUG') {
            field1MaxMarks = marksOutOfMap['LUG'] || 100;  // LUG is field1
            field2MaxMarks = marksOutOfMap['KUS'] || 100;  // KUS is field2
        }
        
        // Update max marks display
        document.getElementById('maxMarksRdg').textContent = field1MaxMarks;
        document.getElementById('maxMarksGrm').textContent = field2MaxMarks;
        
        // Update labels dynamically
        document.querySelector('#studentRdgMarksGroup label').innerHTML = 
            `${combinedSubjectConfig.field1Label} (out of <span id="maxMarksRdg">${field1MaxMarks}</span>):`;
        document.querySelector('#studentGrmMarksGroup label').innerHTML = 
            `${combinedSubjectConfig.field2Label} (out of <span id="maxMarksGrm">${field2MaxMarks}</span>):`;
        
        document.getElementById('studentRdgMarks').value = field1Marks || '';
        document.getElementById('studentGrmMarks').value = field2Marks || '';
        document.getElementById('studentRdgMarks').placeholder = `Enter ${combinedSubjectConfig.field1} marks`;
        document.getElementById('studentGrmMarks').placeholder = `Enter ${combinedSubjectConfig.field2} marks`;
        
        // Store in data attributes for later validation
        document.getElementById('studentRdgMarks').dataset.maxMarks = field1MaxMarks;
        document.getElementById('studentGrmMarks').dataset.maxMarks = field2MaxMarks;
    } else {
        document.getElementById('studentMarksGroup').style.display = 'block';
        document.getElementById('studentRdgMarksGroup').style.display = 'none';
        document.getElementById('studentGrmMarksGroup').style.display = 'none';
        document.getElementById('studentMarks').setAttribute('required', 'required');
        document.getElementById('studentRdgMarks').removeAttribute('required');
        document.getElementById('studentGrmMarks').removeAttribute('required');
        
        const maxMarks = marksOutOfMap[currentSubject] || 100;
        document.getElementById('maxMarks').textContent = maxMarks;
        document.getElementById('studentMarks').value = currentMarks || '';
        document.getElementById('studentMarks').dataset.maxMarks = maxMarks;
    }

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
        let body = {
            student_class_id: editingStudent.studentClassId,
            subject: currentSubject,
            exam_id: currentExamId
        };

        if (isCombinedSubject) {
            const field1Marks = parseFloat(document.getElementById('studentRdgMarks').value);
            const field2Marks = parseFloat(document.getElementById('studentGrmMarks').value);
            
            const field1MaxMarks = parseFloat(document.getElementById('studentRdgMarks').dataset.maxMarks) || 100;
            const field2MaxMarks = parseFloat(document.getElementById('studentGrmMarks').dataset.maxMarks) || 100;

            if (isNaN(field1Marks) || isNaN(field2Marks)) {
                swal({
                    title: 'Error',
                    text: `Please enter both ${combinedSubjectConfig.field1} and ${combinedSubjectConfig.field2} marks`,
                    icon: 'error',
                    button: 'OK'
                });
                return;
            }

            if (field1Marks < 0 || field2Marks < 0) {
                swal({
                    title: 'Error',
                    text: 'Marks cannot be negative',
                    icon: 'error',
                    button: 'OK'
                });
                return;
            }

            if (field1Marks > field1MaxMarks) {
                swal({
                    title: 'Error',
                    text: `${combinedSubjectConfig.field1} marks cannot exceed ${field1MaxMarks}`,
                    icon: 'error',
                    button: 'OK'
                });
                return;
            }

            if (field2Marks > field2MaxMarks) {
                swal({
                    title: 'Error',
                    text: `${combinedSubjectConfig.field2} marks cannot exceed ${field2MaxMarks}`,
                    icon: 'error',
                    button: 'OK'
                });
                return;
            }

            if (currentSubject === 'GRM') {
                body.rdg_marks = field1Marks;
                body.grm_marks = field2Marks;
            } else if (currentSubject === 'LUG') {
                body.lug_marks = field1Marks;
                body.kus_marks = field2Marks;
            }
        } else {
            const marks = parseFloat(document.getElementById('studentMarks').value);
            const maxMarks = parseFloat(document.getElementById('studentMarks').dataset.maxMarks) || 100;

            if (isNaN(marks)) {
                swal({
                    title: 'Error',
                    text: 'Please enter valid marks',
                    icon: 'error',
                    button: 'OK'
                });
                return;
            }

            if (marks < 0) {
                swal({
                    title: 'Error',
                    text: 'Marks cannot be negative',
                    icon: 'error',
                    button: 'OK'
                });
                return;
            }

            if (marks > maxMarks) {
                swal({
                    title: 'Error',
                    text: `Marks cannot exceed ${maxMarks}`,
                    icon: 'error',
                    button: 'OK'
                });
                return;
            }

            body.marks = marks;
        }

        const response = await fetch(`${apiBase}/subjects/marks/update`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(body)
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
        marksOutOfMap[currentSubject] = marksValue;
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

// Set marks out of for English (GRM and RDG)
async function setPaperMarksOutOfEnglish() {
    try {
        const grmValue = parseInt(document.getElementById('paper1OutOfEnglish').value);
        const rdgValue = parseInt(document.getElementById('paper2OutOfEnglish').value);

        if (!grmValue || !rdgValue || grmValue < 1 || rdgValue < 1) {
            showError('Please enter valid numbers greater than 0 for both GRM and RDG');
            return;
        }

        // Set marks for GRM
        const grmResponse = await fetch(`${apiBase}/subjects/marks-out-of`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                subject: 'GRM',
                exam_id: currentExamId,
                marks_out_of: grmValue
            })
        });

        const grmResult = await grmResponse.json();
        if (!grmResult.success) {
            showError(grmResult.message || 'Failed to set GRM marks out of');
            return;
        }

        // Set marks for RDG
        const rdgResponse = await fetch(`${apiBase}/subjects/marks-out-of`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                subject: 'RDG',
                exam_id: currentExamId,
                marks_out_of: rdgValue
            })
        });

        const rdgResult = await rdgResponse.json();
        if (!rdgResult.success) {
            showError(rdgResult.message || 'Failed to set RDG marks out of');
            return;
        }

        // Update local values in map
        marksOutOfMap['GRM'] = grmValue;
        marksOutOfMap['RDG'] = rdgValue;
        marksOutOfSet = true;

        swal({
            title: 'Success',
            text: `GRM marks set to ${grmValue} and RDG marks set to ${rdgValue}`,
            icon: 'success',
            button: 'OK'
        });

    } catch (error) {
        console.error('Error setting English marks out of:', error);
        showError('Failed to set marks out of. Please try again.');
    }
}

// Set marks out of for Kiswahili (LUG and KUS)
async function setPaperMarksOutOfKiswahili() {
    try {
        const lugValue = parseInt(document.getElementById('paper1OutOfKiswahili').value);
        const kusValue = parseInt(document.getElementById('paper2OutOfKiswahili').value);

        if (!lugValue || !kusValue || lugValue < 1 || kusValue < 1) {
            showError('Please enter valid numbers greater than 0 for both LUG and KUS');
            return;
        }

        // Set marks for LUG
        const lugResponse = await fetch(`${apiBase}/subjects/marks-out-of`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                subject: 'LUG',
                exam_id: currentExamId,
                marks_out_of: lugValue
            })
        });

        const lugResult = await lugResponse.json();
        if (!lugResult.success) {
            showError(lugResult.message || 'Failed to set LUG marks out of');
            return;
        }

        // Set marks for KUS
        const kusResponse = await fetch(`${apiBase}/subjects/marks-out-of`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                subject: 'KUS',
                exam_id: currentExamId,
                marks_out_of: kusValue
            })
        });

        const kusResult = await kusResponse.json();
        if (!kusResult.success) {
            showError(kusResult.message || 'Failed to set KUS marks out of');
            return;
        }

        // Update local values in map
        marksOutOfMap['LUG'] = lugValue;
        marksOutOfMap['KUS'] = kusValue;
        marksOutOfSet = true;

        swal({
            title: 'Success',
            text: `LUG marks set to ${lugValue} and KUS marks set to ${kusValue}`,
            icon: 'success',
            button: 'OK'
        });

    } catch (error) {
        console.error('Error setting Kiswahili marks out of:', error);
        showError('Failed to set marks out of. Please try again.');
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', async () => {
    await Promise.all([
        loadComponentPromise('header', 'headerContainer'),
        loadComponentPromise('sidebar', 'sidebarContainer'),
        loadComponentPromise('bottom-navigator', 'bottomNavContainer'),
        loadComponentPromise('footer', 'footerContainer')
    ]);
    await initializePage();

    // Add event listeners for Set Both buttons
    const setPaperBtnEnglish = document.getElementById('setPaperBtnEnglish');
    if (setPaperBtnEnglish) {
        setPaperBtnEnglish.addEventListener('click', setPaperMarksOutOfEnglish);
    }

    const setPaperBtnKiswahili = document.getElementById('setPaperBtnKiswahili');
    if (setPaperBtnKiswahili) {
        setPaperBtnKiswahili.addEventListener('click', setPaperMarksOutOfKiswahili);
    }
});
