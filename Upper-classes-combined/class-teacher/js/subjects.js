// Subject marks page script

// Load components
async function loadComponentPromise(componentPath, containerId) {
    try {
        const response = await fetch(componentPath);
        const html = await response.text();
        document.getElementById(containerId).innerHTML = html;
        
        // Trigger header re-initialization if this is the header
        if (containerId === 'headerContainer') {
            document.dispatchEvent(new Event('headerLoaded'));
        }
    } catch (error) {
        console.error(`Error loading ${componentPath}:`, error);
    }
}

// Initialize page
window.addEventListener('DOMContentLoaded', async () => {
    await Promise.all([
        loadComponentPromise('components/header.html', 'headerContainer'),
        loadComponentPromise('components/sidebar.html', 'sidebarContainer'),
        loadComponentPromise('components/bottom-navigator.html', 'bottomNavContainer'),
        loadComponentPromise('components/footer.html', 'footerContainer')
    ]);
    
    // Check if subject is passed as query parameter
    const params = new URLSearchParams(window.location.search);
    const subject = params.get('subject');
    
    if (subject) {
        const select = document.getElementById('subjectSelect');
        select.value = subject;
    }
    
    // Always load marks on page load (static display)
    await loadSubjectMarks();

    // Initialize search form listener
    initializeSearchForm();
});

/**
 * Initialize search form listener
 */
function initializeSearchForm() {
    const searchForm = document.querySelector('.search-form');
    const searchInput = searchForm ? searchForm.querySelector('input[type="text"]') : null;
    
    if (searchForm && searchInput) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const searchTerm = searchInput.value;
            searchStudents(searchTerm);
        });
    }
}

/**
 * Load subject marks
 */
async function loadSubjectMarks() {
    const subject = document.getElementById('subjectSelect').value;
    const url = subject 
        ? `../backend/public/index.php?route=/subjects/marks&subject=${encodeURIComponent(subject)}`
        : '../backend/public/index.php?route=/subjects/marks';

    try {
        const response = await fetch(url, {
            method: 'GET',
            credentials: 'include'
        });

        if (!response.ok) {
            if (response.status === 401) {
                window.location.href = 'index.html';
                return;
            }
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            displaySubjectMarks(data);
        } else {
            document.getElementById('tableContainer').innerHTML = 
                `<p style="text-align: center; color: red;">${escapeHtml(data.message)}</p>`;
        }
    } catch (error) {
        console.error('Error loading subject marks:', error);
        document.getElementById('tableContainer').innerHTML = 
            `<p style="text-align: center; color: red;">Error loading marks. Please refresh the page.</p>`;
    }
}

/**
 * Display subject marks table (responsive)
 */
function displaySubjectMarks(data) {
    const subject = data.subject || '';
    const students = data.students || [];

    // Build table with responsive wrapper
    let html = '<table class="content-table"><thead><tr>';
    html += '<th>Name</th>';
    html += '<th>Marks</th>';
    html += '</tr></thead><tbody>';

    if (students.length > 0) {
        students.forEach(student => {
            html += '<tr>';
            html += `<td>${escapeHtml(student.student_name || '')}</td>`;
            html += `<td>${escapeHtml(String(student.marks ?? '-'))}</td>`;
            html += '</tr>';
        });
    } else {
        html += '<tr><td colspan="2" style="text-align: center;">No students found</td></tr>';
    }

    html += '</tbody></table>';

    document.getElementById('tableContainer').innerHTML = html;
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Search students by name
 */
async function searchStudents(searchTerm) {
    const selectedSubject = document.getElementById('subjectSelect').value;

    if (!searchTerm.trim()) {
        // If search is empty, reload the subject marks
        await loadSubjectMarks();
        return;
    }

    // If no subject is selected, fetch all students
    if (!selectedSubject) {
        try {
            const response = await fetch(`../backend/public/index.php?route=/students&search=${encodeURIComponent(searchTerm)}`, {
                method: 'GET',
                credentials: 'include'
            });

            if (!response.ok) {
                if (response.status === 401) {
                    window.location.href = 'index.html';
                    return;
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success && data.students) {
                displaySearchResults(data.students, null);
            } else {
                document.getElementById('tableContainer').innerHTML = 
                    `<p style="text-align: center;">No students found matching "${escapeHtml(searchTerm)}"</p>`;
            }
        } catch (error) {
            console.error('Error searching students:', error);
            document.getElementById('tableContainer').innerHTML = 
                `<p style="text-align: center; color: red;">Error searching students. Please try again.</p>`;
        }
        return;
    }

    // If a subject is selected, get subject marks and filter by search term
    try {
        const response = await fetch(`../backend/public/index.php?route=/subjects/marks&subject=${encodeURIComponent(selectedSubject)}`, {
            method: 'GET',
            credentials: 'include'
        });

        if (!response.ok) {
            if (response.status === 401) {
                window.location.href = 'index.html';
                return;
            }
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success && data.students) {
            // Filter students by search term
            const filteredStudents = data.students.filter(student => 
                (student.student_name || '').toLowerCase().includes(searchTerm.toLowerCase())
            );
            displaySearchResults(filteredStudents, selectedSubject);
        } else {
            document.getElementById('tableContainer').innerHTML = 
                `<p style="text-align: center;">No students found matching "${escapeHtml(searchTerm)}"</p>`;
        }
    } catch (error) {
        console.error('Error searching students:', error);
        document.getElementById('tableContainer').innerHTML = 
            `<p style="text-align: center; color: red;">Error searching students. Please try again.</p>`;
    }
}

/**
 * Display search results in table format
 */
function displaySearchResults(students, subject) {
    // Build table
    let html = '<table class="content-table"><thead><tr>';
    html += '<th>Name</th>';
    
    // Only show Marks column if a subject was selected
    if (subject) {
        html += '<th>Marks</th>';
    } else {
        html += '<th>Class</th>';
        html += '<th>Status</th>';
    }
    
    html += '</tr></thead><tbody>';

    if (students.length > 0) {
        students.forEach(student => {
            html += '<tr>';
            
            if (subject) {
                // Subject-based search results
                html += `<td>${escapeHtml(student.student_name || '')}</td>`;
                html += `<td>${escapeHtml(String(student.marks ?? '-'))}</td>`;
            } else {
                // General student search results
                html += `<td>${escapeHtml(student.name || '')}</td>`;
                html += `<td>${escapeHtml(student.class || 'N/A')}</td>`;
                html += `<td>${escapeHtml(student.status || 'N/A')}</td>`;
            }
            
            html += '</tr>';
        });
    } else {
        const colSpan = subject ? 2 : 3;
        html += `<tr><td colspan="${colSpan}" style="text-align: center;">No students found</td></tr>`;
    }

    html += '</tbody></table>';

    document.getElementById('tableContainer').innerHTML = html;
}
