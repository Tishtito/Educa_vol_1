// Subjects marks management page

// Global state
let currentSubject = null;
let currentClass = null;
let currentExamId = null;
let marksOutOf = 100;
let marksOutOfSet = false;
let grammaMarksOutOf = 100;
let compoMarksOutOf = 100;
let englishMarksOutOfSet = false;
let lughaMarksOutOf = 100;
let inshaMarksOutOf = 100;
let kiswahiliMarksOutOfSet = false;
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

        // Show appropriate marks out of section
        const singleForm = document.getElementById('singleMarksOutOfForm');
        const englishForm = document.getElementById('englishMarksOutOfForm');
        const kiswahiliForm = document.getElementById('kiswahiliMarksOutOfForm');
        
        if (currentSubject === 'English') {
            singleForm.style.display = 'none';
            englishForm.style.display = 'block';
            kiswahiliForm.style.display = 'none';
        } else if (currentSubject === 'Kiswahili') {
            singleForm.style.display = 'none';
            englishForm.style.display = 'none';
            kiswahiliForm.style.display = 'block';
        } else {
            singleForm.style.display = 'block';
            englishForm.style.display = 'none';
            kiswahiliForm.style.display = 'none';
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

        // Store marks out of based on subject type
        if (currentSubject === 'English') {
            // Load Gramma and Compo marks out of
            const grammaRes = await fetch(
                `${apiBase}/subjects/marks-out-of?subject=Gramma&exam_id=${currentExamId}`,
                { credentials: 'include' }
            );
            const grammaData = await grammaRes.json();
            if (grammaData.success && grammaData.marks_out_of) {
                grammaMarksOutOf = grammaData.marks_out_of;
                document.getElementById('grammaMarksOutOf').value = grammaMarksOutOf;
            }

            const compoRes = await fetch(
                `${apiBase}/subjects/marks-out-of?subject=Compo&exam_id=${currentExamId}`,
                { credentials: 'include' }
            );
            const compoData = await compoRes.json();
            if (compoData.success && compoData.marks_out_of) {
                compoMarksOutOf = compoData.marks_out_of;
                document.getElementById('compoMarksOutOf').value = compoMarksOutOf;
            }

            // Set flag if either component has been configured (not at default 100)
            if (grammaMarksOutOf !== 100 || compoMarksOutOf !== 100) {
                englishMarksOutOfSet = true;
            }
        } else if (currentSubject === 'Kiswahili') {
            // Load Lugha and Insha marks out of
            const lughaRes = await fetch(
                `${apiBase}/subjects/marks-out-of?subject=Lugha&exam_id=${currentExamId}`,
                { credentials: 'include' }
            );
            const lughaData = await lughaRes.json();
            if (lughaData.success && lughaData.marks_out_of) {
                lughaMarksOutOf = lughaData.marks_out_of;
                document.getElementById('lughaMarksOutOf').value = lughaMarksOutOf;
            }

            const inshaRes = await fetch(
                `${apiBase}/subjects/marks-out-of?subject=Insha&exam_id=${currentExamId}`,
                { credentials: 'include' }
            );
            const inshaData = await inshaRes.json();
            if (inshaData.success && inshaData.marks_out_of) {
                inshaMarksOutOf = inshaData.marks_out_of;
                document.getElementById('inshaMarksOutOf').value = inshaMarksOutOf;
            }

            // Set flag if either component has been configured (not at default 100)
            if (lughaMarksOutOf !== 100 || inshaMarksOutOf !== 100) {
                kiswahiliMarksOutOfSet = true;
            }
        } else {
            // Load marks out of for regular subjects
            if (data.marks_out_of) {
                marksOutOf = data.marks_out_of;
                marksOutOfSet = true;
                document.getElementById('marksOutOf').value = marksOutOf;
            }
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
    // Check if marks out of has been set (based on subject type)
    if (currentSubject === 'English') {
        if (!englishMarksOutOfSet) {
            swal({
                title: 'Warning',
                text: 'Please set the maximum marks for Gramma and/or Compo in the Configure Marks section.',
                icon: 'warning',
                button: 'OK'
            });
            return;
        }
    } else if (currentSubject === 'Kiswahili') {
        if (!kiswahiliMarksOutOfSet) {
            swal({
                title: 'Warning',
                text: 'Please set the maximum marks for Lugha and/or Insha in the Configure Marks section.',
                icon: 'warning',
                button: 'OK'
            });
            return;
        }
    } else {
        if (!marksOutOfSet) {
            swal({
                title: 'Warning',
                text: 'Please set the maximum marks for this subject first in the Configure Marks section.',
                icon: 'warning',
                button: 'OK'
            });
            return;
        }
    }

    editingStudent.studentId = studentId;
    editingStudent.studentClassId = studentClassId;
    editingStudent.studentName = studentName;
    
    document.getElementById('studentNameInModal').textContent = studentName;
    document.getElementById('maxMarks').textContent = marksOutOf;

    // Show appropriate input fields based on subject
    const singleInputGroup = document.getElementById('singleInputGroup');
    const englishInputGroup = document.getElementById('englishInputGroup');
    const kiswahiliInputGroup = document.getElementById('kiswahiliInputGroup');

    // Hide all groups first
    singleInputGroup.style.display = 'none';
    englishInputGroup.style.display = 'none';
    kiswahiliInputGroup.style.display = 'none';

    if (currentSubject === 'English') {
        englishInputGroup.style.display = 'block';
        document.getElementById('maxGramma').textContent = grammaMarksOutOf || 100;
        document.getElementById('maxCompo').textContent = compoMarksOutOf || 100;
        document.getElementById('grammaMarks').value = '';
        document.getElementById('compoMarks').value = '';
        document.getElementById('englishTotalDisplay').textContent = '-';
        
        // Add event listeners for calculated display
        document.getElementById('grammaMarks').addEventListener('input', calculateEnglishTotal);
        document.getElementById('compoMarks').addEventListener('input', calculateEnglishTotal);
    } else if (currentSubject === 'Kiswahili') {
        kiswahiliInputGroup.style.display = 'block';
        document.getElementById('maxLugha').textContent = lughaMarksOutOf;
        document.getElementById('maxInsha').textContent = inshaMarksOutOf;
        document.getElementById('lughaMarks').value = '';
        document.getElementById('inshaMarks').value = '';
        document.getElementById('kiswahiliTotalDisplay').textContent = '-';
        
        // Add event listeners for calculated display
        document.getElementById('lughaMarks').addEventListener('input', calculateKiswahiliTotal);
        document.getElementById('inshaMarks').addEventListener('input', calculateKiswahiliTotal);
    } else {
        singleInputGroup.style.display = 'block';
        document.getElementById('studentMarks').value = currentMarks || '';
    }

    document.getElementById('editMarksModal').classList.add('show');
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editMarksModal').classList.remove('show');
    editingStudent = { studentId: null, studentClassId: null, studentName: null };
}

// Calculate English total (Gramma + Compo)
function calculateEnglishTotal() {
    const grammaVal = parseFloat(document.getElementById('grammaMarks').value) || 0;
    const compoVal = parseFloat(document.getElementById('compoMarks').value) || 0;
    
    if (grammaVal === 0 && compoVal === 0) {
        document.getElementById('englishTotalDisplay').textContent = '-';
        return;
    }

    const total = grammaVal + compoVal;
    const maxTotal = grammaMarksOutOf + compoMarksOutOf;
    const percentage = (total / maxTotal) * 100;
    document.getElementById('englishTotalDisplay').textContent = percentage.toFixed(2);
}

// Calculate Kiswahili total (Lugha + Insha)
function calculateKiswahiliTotal() {
    const lughaVal = parseFloat(document.getElementById('lughaMarks').value) || 0;
    const inshaVal = parseFloat(document.getElementById('inshaMarks').value) || 0;
    
    if (lughaVal === 0 && inshaVal === 0) {
        document.getElementById('kiswahiliTotalDisplay').textContent = '-';
        return;
    }

    const total = lughaVal + inshaVal;
    const maxTotal = lughaMarksOutOf + inshaMarksOutOf;
    const percentage = (total / maxTotal) * 100;
    document.getElementById('kiswahiliTotalDisplay').textContent = percentage.toFixed(2);
}

// Save marks
async function saveMarks(event) {
    event.preventDefault();
    console.log('Save marks called for subject:', currentSubject);
    
    try {
        let marksToSave = [];

        if (currentSubject === 'English') {
            // Handle English = Gramma + Compo
            const grammaVal = parseFloat(document.getElementById('grammaMarks').value);
            const compoVal = parseFloat(document.getElementById('compoMarks').value);
            
            console.log('Gramma value:', grammaVal, 'Compo value:', compoVal);

            if (isNaN(grammaVal) || isNaN(compoVal)) {
                swal({
                    title: 'Error',
                    text: 'Please enter valid marks for both Gramma and Compo',
                    icon: 'error',
                    button: 'OK'
                });
                return;
            }

            if (grammaVal < 0 || compoVal < 0) {
                swal({
                    title: 'Error',
                    text: 'Marks cannot be negative',
                    icon: 'error',
                    button: 'OK'
                });
                return;
            }

            if (grammaVal > grammaMarksOutOf) {
                swal({
                    title: 'Error',
                    text: `Gramma marks cannot exceed ${grammaMarksOutOf}`,
                    icon: 'error',
                    button: 'OK'
                });
                return;
            }

            if (compoVal > compoMarksOutOf) {
                swal({
                    title: 'Error',
                    text: `Compo marks cannot exceed ${compoMarksOutOf}`,
                    icon: 'error',
                    button: 'OK'
                });
                return;
            }

            // Calculate percentage for English (sum of both / max of both)
            const englishSum = grammaVal + compoVal;
            const englishPercentage = (englishSum / (grammaMarksOutOf + compoMarksOutOf)) * 100;

            // Save Gramma, Compo, and English
            marksToSave = [
                { subject: 'Gramma', marks: grammaVal },
                { subject: 'Compo', marks: compoVal },
                { subject: 'English', marks: englishPercentage }
            ];

        } else if (currentSubject === 'Kiswahili') {
            // Handle Kiswahili = Lugha + Insha
            const lughaVal = parseFloat(document.getElementById('lughaMarks').value);
            const inshaVal = parseFloat(document.getElementById('inshaMarks').value);
            
            console.log('Lugha value:', lughaVal, 'Insha value:', inshaVal);

            if (isNaN(lughaVal) || isNaN(inshaVal)) {
                swal({
                    title: 'Error',
                    text: 'Please enter valid marks for both Lugha and Insha',
                    icon: 'error',
                    button: 'OK'
                });
                return;
            }

            if (lughaVal < 0 || inshaVal < 0) {
                swal({
                    title: 'Error',
                    text: 'Marks cannot be negative',
                    icon: 'error',
                    button: 'OK'
                });
                return;
            }

            if (lughaVal > lughaMarksOutOf) {
                swal({
                    title: 'Error',
                    text: `Lugha marks cannot exceed ${lughaMarksOutOf}`,
                    icon: 'error',
                    button: 'OK'
                });
                return;
            }

            if (inshaVal > inshaMarksOutOf) {
                swal({
                    title: 'Error',
                    text: `Insha marks cannot exceed ${inshaMarksOutOf}`,
                    icon: 'error',
                    button: 'OK'
                });
                return;
            }

            // Calculate percentage for Kiswahili (sum of both / max of both)
            const kiswahiliSum = lughaVal + inshaVal;
            const kiswahiliPercentage = (kiswahiliSum / (lughaMarksOutOf + inshaMarksOutOf)) * 100;

            // Save Lugha, Insha, and Kiswahili
            marksToSave = [
                { subject: 'Lugha', marks: lughaVal },
                { subject: 'Insha', marks: inshaVal },
                { subject: 'Kiswahili', marks: kiswahiliPercentage }
            ];

        } else {
            // Handle regular subjects
            const marks = parseFloat(document.getElementById('studentMarks').value);

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

            if (marks > marksOutOf) {
                swal({
                    title: 'Error',
                    text: `Marks cannot exceed ${marksOutOf}`,
                    icon: 'error',
                    button: 'OK'
                });
                return;
            }

            marksToSave = [{ subject: currentSubject, marks: marks }];
        }

        // Save all marks via API
        for (const markData of marksToSave) {
            console.log('Saving mark:', markData);
            const response = await fetch(`${apiBase}/subjects/marks/update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify({
                    student_class_id: editingStudent.studentClassId,
                    subject: markData.subject,
                    exam_id: currentExamId,
                    marks: markData.marks
                })
            });

            const result = await response.json();
            console.log('API response for', markData.subject, ':', result);

            if (!result.success) {
                showError(result.message || `Failed to save ${markData.subject} marks`);
                return;
            }
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

// Set English marks out of (Gramma + Compo)
async function setEnglishMarksOutOf() {
    try {
        const grammaValue = parseInt(document.getElementById('grammaMarksOutOf').value);
        const compoValue = parseInt(document.getElementById('compoMarksOutOf').value);

        if (!grammaValue || grammaValue < 1 || !compoValue || compoValue < 1) {
            showError('Please enter valid numbers greater than 0 for both Gramma and Compo');
            return;
        }

        // Set marks out of for Gramma
        await setSubjectMarksOutOf('Gramma', grammaValue);
        // Set marks out of for Compo
        await setSubjectMarksOutOf('Compo', compoValue);

        grammaMarksOutOf = grammaValue;
        compoMarksOutOf = compoValue;
        englishMarksOutOfSet = true;

        swal({
            title: 'Success',
            text: `Marks set - Gramma: ${grammaValue}, Compo: ${compoValue}`,
            icon: 'success',
            button: 'OK'
        });

    } catch (error) {
        console.error('Error setting marks out of:', error);
        showError('Failed to set marks out of. Please try again.');
    }
}

// Set Kiswahili marks out of (Lugha + Insha)
async function setKiswahiliMarksOutOf() {
    try {
        const lughaValue = parseInt(document.getElementById('lughaMarksOutOf').value);
        const inshaValue = parseInt(document.getElementById('inshaMarksOutOf').value);

        if (!lughaValue || lughaValue < 1 || !inshaValue || inshaValue < 1) {
            showError('Please enter valid numbers greater than 0 for both Lugha and Insha');
            return;
        }

        // Set marks out of for Lugha
        await setSubjectMarksOutOf('Lugha', lughaValue);
        // Set marks out of for Insha
        await setSubjectMarksOutOf('Insha', inshaValue);

        lughaMarksOutOf = lughaValue;
        inshaMarksOutOf = inshaValue;
        kiswahiliMarksOutOfSet = true;

        swal({
            title: 'Success',
            text: `Marks set - Lugha: ${lughaValue}, Insha: ${inshaValue}`,
            icon: 'success',
            button: 'OK'
        });

    } catch (error) {
        console.error('Error setting marks out of:', error);
        showError('Failed to set marks out of. Please try again.');
    }
}

// Helper function to set marks out of for a subject
async function setSubjectMarksOutOf(subject, marksValue) {
    const response = await fetch(`${apiBase}/subjects/marks-out-of`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify({
            subject: subject,
            exam_id: currentExamId,
            marks_out_of: marksValue
        })
    });

    const result = await response.json();
    if (!result.success) {
        throw new Error(result.message || `Failed to set marks out of for ${subject}`);
    }
}

// Set marks out of for regular subjects
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
