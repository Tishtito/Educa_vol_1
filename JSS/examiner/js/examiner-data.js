/**
 * Load examiner data from backend and populate names in header and sidebar
 * This function can be called on any page that has header and sidebar components
 */
let examinerDataGlobal = { name: 'Examiner' }; // Global examiner data store

async function loadExaminerData() {
    try {
        const response = await fetch('../backend/public/index.php/auth/check', { credentials: 'include' });
        
        if (!response.ok) {
            if (response.status === 401) {
                window.location.href = 'index.html';
                return;
            }
            throw new Error('Failed to load examiner data');
        }

        const data = await response.json();

        if (data.success) {
            const examinerName = data.name || 'Examiner';
            examinerDataGlobal.name = examinerName; // Store globally
            examinerDataGlobal.examiner_id = data.examiner_id;
            examinerDataGlobal.username = data.username;
            
            const headerNameElement = document.getElementById('examinerName');
            const sidebarNameElement = document.getElementById('sidebarExaminerName');
            
            if (headerNameElement) headerNameElement.textContent = examinerName;
            if (sidebarNameElement) sidebarNameElement.textContent = examinerName;
        }
    } catch (error) {
        console.error('Error loading examiner data:', error);
    }
}
