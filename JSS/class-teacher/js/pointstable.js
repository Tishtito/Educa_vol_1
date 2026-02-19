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
      loadComponentPromise('components/footer.html', 'footerContainer'),
      loadTeacherData()
   ]);

   // Load points table
   await loadPointsTable();
});

/**
 * Load points table
 */
async function loadPointsTable() {
   try {
      const response = await fetch('../backend/public/index.php?route=/points-table', {
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
         displayPointsTable(data);
      } else {
         // No exam selected - show error and redirect
         showExamNotSelectedError();
      }
   } catch (error) {
      console.error('Error loading points table:', error);
      showExamNotSelectedError();
   }
}

/**
 * Display points table
 */
function displayPointsTable(data) {
   const subjects = data.subjects || [];
   const students = data.students || [];

   // Build main table header
   let html = '<table class="content-table"><thead><tr>';
   html += '<th>Name</th>';

   subjects.forEach(subject => {
      html += `<th>${escapeHtml(subject)}</th>`;
   });

   html += '</tr></thead><tbody>';

   // Build student rows
   students.forEach(student => {
      html += '<tr>';
      html += `<td>${escapeHtml(student.Name || '')}</td>`;

      subjects.forEach(subject => {
         const grade = student['Grade_' + subject] || '-';
         const ab = student['Ab_' + subject] || '-';
         html += `<td class="grade-cell"><div class="grade">${escapeHtml(String(ab))}</div><div class="ab">${escapeHtml(String(grade))}</div></td>`;
      });

      html += '</tr>';
   });

   html += '</tbody></table>';

   document.getElementById('tableContainer').innerHTML = html;

   // Display summary table per subject
   displaySummaryTablePerSubject(subjects, students);
}

/**
 * Display grade abbreviation summary table - single table format
 */
function displaySummaryTablePerSubject(subjects, students) {
   if (!subjects || subjects.length === 0) {
      document.getElementById('summaryContainer').innerHTML = '';
      return;
   }

   // Count AB grades for each subject
   const subjectGradeCounts = {};
   const allGrades = new Set();

   subjects.forEach(subject => {
      subjectGradeCounts[subject] = {};

      students.forEach(student => {
         const ab = student['Ab_' + subject];
         if (ab && ab !== '-') {
            allGrades.add(ab);
            subjectGradeCounts[subject][ab] = (subjectGradeCounts[subject][ab] || 0) + 1;
         }
      });
   });

   // Sort grades
   const sortedGrades = Array.from(allGrades).sort();

   if (sortedGrades.length === 0) {
      document.getElementById('summaryContainer').innerHTML = '';
      return;
   }

   // Build single table with subjects as columns and grades as rows
   let html = '<div class="summary-section">';
   html += '<div class="summary-heading">Grade Distribution by Subject</div>';
   html += '<div class="summary-table-responsive">';
   html += '<table class="summary-table"><thead><tr>';
   html += '<th>Grade</th>';

   subjects.forEach(subject => {
      html += `<th>${escapeHtml(subject)}</th>`;
   });

   html += '</tr></thead><tbody>';

   // Build rows for each grade
   sortedGrades.forEach(grade => {
      html += '<tr>';
      html += `<td><strong>${escapeHtml(grade)}</strong></td>`;

      subjects.forEach(subject => {
         const count = subjectGradeCounts[subject][grade] || 0;
         html += `<td>${count}</td>`;
      });

      html += '</tr>';
   });

   html += '</tbody></table>';
   html += '</div>'; // Close summary-table-responsive
   html += '</div>'; // Close summary-section

   document.getElementById('summaryContainer').innerHTML = html;
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
 * Show error when exam is not selected
 */
function showExamNotSelectedError() {
   Swal.fire({
      title: 'No Exam Selected',
      text: 'Please select an exam first',
      icon: 'error',
      confirmButtonText: 'Go to Exams',
      allowOutsideClick: false,
      allowEscapeKey: false
   }).then(() => {
      window.location.href = 'exam.html';
   });
}
