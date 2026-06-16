/**
 * Load examiner data from backend and populate names in header and sidebar
 * This function can be called on any page that has header and sidebar components
 */
let examinerDataGlobal = { name: 'Examiner', class_assigned: '' }; // Global examiner data store

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
            const classAssigned = data.class_assigned || '';
            
            // Store globally
            examinerDataGlobal.name = examinerName;
            examinerDataGlobal.examiner_id = data.examiner_id;
            examinerDataGlobal.username = data.username;
            examinerDataGlobal.class_assigned = classAssigned;
            
            // Update header and sidebar examiner names
            const headerNameElement = document.getElementById('examinerName');
            const sidebarNameElement = document.getElementById('sidebarExaminerName');
            
            if (headerNameElement) headerNameElement.textContent = examinerName;
            if (sidebarNameElement) sidebarNameElement.textContent = examinerName;
            
            // Update class title if element exists
            const classTitleElement = document.getElementById('classTitle');
            if (classTitleElement && classAssigned) {
                const formattedClass = classAssigned.replace(/_/g, ' ').toUpperCase();
                classTitleElement.textContent = formattedClass;
            }
        }
    } catch (error) {
        console.error('Error loading examiner data:', error);
    }
}
