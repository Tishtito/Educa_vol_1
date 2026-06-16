// profile.js - Examiner profile page logic

// ========================================
// COMPONENT LOADING
// ========================================
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
        console.error(`Error loading component:`, error);
    }
}

// ========================================
// PROFILE DATA LOADING
// ========================================
/**
 * Load examiner profile data from API
 */
async function loadProfile() {
    try {
        const response = await fetch('../backend/public/index.php/profile', { 
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
            displayProfile(data.data);
        } else {
            document.getElementById('profileContainer').innerHTML = 
                `<p class="error-message">${escapeHtml(data.message)}</p>`;
        }
    } catch (error) {
        console.error('Error loading profile:', error);
        document.getElementById('profileContainer').innerHTML = 
            `<p class="error-message">Error loading profile. Please refresh the page.</p>`;
    }
}

// ========================================
// DISPLAY PROFILE
// ========================================
/**
 * Display examiner profile information
 */
function displayProfile(data) {
    const examinerName = data.name || 'N/A';
    const username = data.username || 'N/A';
    const classAssigned = data.class_assigned || 'N/A';
    const totalStudents = data.total_students || 0;

    const formattedClass = classAssigned.replace(/_/g, ' ').toUpperCase();

    let html = `
        <div class="user">
            <img src="../photos/user1.png" alt="Profile Photo">
            <div>
                <h3>${escapeHtml(examinerName)}</h3>
                <p>Examiner</p>
                <a href="#" class="inline-btn">Update Profile</a>
            </div>
        </div>

        <div class="box-container">
            <div class="box">
                <div class="flex">
                    <i class="fas fa-user"></i>
                    <div>
                        <span style="color:white">Username</span>
                        <p>${escapeHtml(username)}</p>
                    </div>
                </div>
            </div>

            <div class="box">
                <div class="flex">
                    <i class="fas fa-chalkboard-user"></i>
                    <div>
                        <span style="color:white">Class Assigned</span>
                        <p>${escapeHtml(formattedClass)}</p>
                    </div>
                </div>
            </div>

            <div class="box">
                <div class="flex">
                    <i class="fas fa-users"></i>
                    <div>
                        <span class="title" style="color:white">${totalStudents}</span>
                        <p>Total Students</p>
                    </div>
                </div>
                <a href="exam.html" class="inline-btn">View Exams</a>
            </div>
        </div>
    `;

    document.getElementById('profileContainer').innerHTML = html;
}

// ========================================
// UTILITIES
// ========================================
/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ========================================
// INITIALIZATION
// ========================================
window.addEventListener('DOMContentLoaded', async () => {
    await Promise.all([
        loadComponentPromise('components/header.html', 'headerContainer'),
        loadComponentPromise('components/sidebar.html', 'sidebarContainer'),
        loadComponentPromise('components/bottom-navigator.html', 'bottomNavContainer'),
        loadComponentPromise('components/footer.html', 'footerContainer'),
        loadExaminerData()
    ]);

    // Load profile data
    await loadProfile();
});
