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

// Load exam details and mark list
window.addEventListener('DOMContentLoaded', async () => {
   // Load teacher data
   loadTeacherData();

   // Load exam details
   await loadExamDetails();

   // Load mark list
   await loadMarkList();
});

/**
 * Load exam details
 */
async function loadExamDetails() {
   try {
      const response = await fetch('../backend/public/index.php?route=/marklist/exam-details', {
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
         document.getElementById('examName').textContent = escapeHtml(data.examName);
         document.getElementById('classTitle').textContent = escapeHtml(data.classTitle);
      } else {
         // No exam selected - show error and redirect
         showExamNotSelectedError();
      }
   } catch (error) {
      console.error('Error loading exam details:', error);
      showExamNotSelectedError();
   }
}

/**
 * Load mark list
 */
async function loadMarkList() {
   try {
      const response = await fetch('../backend/public/index.php?route=/marklist', {
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
         displayMarkList(data);
      } else {
         document.getElementById('tableContainer').innerHTML = 
            `<p style="text-align: center; color: red;">${escapeHtml(data.message)}</p>`;
      }
   } catch (error) {
      console.error('Error loading mark list:', error);
      document.getElementById('tableContainer').innerHTML = 
         `<p style="text-align: center; color: red;">Error loading mark list. Please refresh the page.</p>`;
   }
}

/**
 * Display mark list table
 */
function displayMarkList(data) {
   const subjects = data.subjects || [];
   const componentSubjects = data.component_subjects || [];
   const students = data.students || [];
   const subjectMeans = data.subjectMeans || {};
   const totalMean = data.totalMean || 0;
   const previousMeans = data.previousMeans || {};

   // Update total mean
   document.getElementById('totalMean').textContent = totalMean;

   // Build table header
   let html = '<table><thead><tr>';
   html += '<th rowspan="2">Rank</th>';
   html += '<th rowspan="2">Name</th>';

   subjects.forEach(subject => {
      if (componentSubjects.includes(subject)) {
         // Component subjects: 4 columns
         if (subject === 'English') {
            html += '<th colspan="4">English</th>';
         } else if (subject === 'Kiswahili') {
            html += '<th colspan="4">Kiswahili</th>';
         }
      } else {
         // Regular subjects: 2 columns (Marks, PL)
         html += `<th colspan="2">${escapeHtml(subject)}</th>`;
      }
   });

   html += '<th colspan="2">Total Marks</th>';
   html += '</tr><tr>';

   subjects.forEach(subject => {
      if (componentSubjects.includes(subject)) {
         if (subject === 'English') {
            html += '<th>Gramma</th><th>Compo</th><th>Total</th><th>PL</th>';
         } else if (subject === 'Kiswahili') {
            html += '<th>Lugha</th><th>Insha</th><th>Total</th><th>PL</th>';
         }
      } else {
         html += '<th>Marks</th><th>PL</th>';
      }
   });

   html += '<th>TOTAL</th><th>PL</th>';
   html += '</tr></thead><tbody>';

   // Build student rows
   let rank = 1;
   students.forEach(student => {
      html += '<tr>';
      html += `<td>${student.position || rank}</td>`;
      html += `<td>${escapeHtml(student.Name || '')}</td>`;

      subjects.forEach(subject => {
         if (componentSubjects.includes(subject)) {
            if (subject === 'English') {
               // English: show Gramma, Compo, Total, PL
               const gramma = student.Gramma || '-';
               const compo = student.Compo || '-';
               const total = student.English || '-';
               const pl = student.PL_English || '-';
               html += `<td>${escapeHtml(String(gramma))}</td>`;
               html += `<td>${escapeHtml(String(compo))}</td>`;
               html += `<td>${escapeHtml(String(total))}</td>`;
               html += `<td>${escapeHtml(String(pl))}</td>`;
            } else if (subject === 'Kiswahili') {
               // Kiswahili: show Lugha, Insha, Total, PL
               const lugha = student.Lugha || '-';
               const insha = student.Insha || '-';
               const total = student.Kiswahili || '-';
               const pl = student.PL_Kiswahili || '-';
               html += `<td>${escapeHtml(String(lugha))}</td>`;
               html += `<td>${escapeHtml(String(insha))}</td>`;
               html += `<td>${escapeHtml(String(total))}</td>`;
               html += `<td>${escapeHtml(String(pl))}</td>`;
            }
         } else {
            // Regular subjects: show Marks and PL
            const mark = student[subject] || '-';
            const pl = student[`PL_${subject}`] || '-';
            html += `<td>${escapeHtml(String(mark))}</td>`;
            html += `<td>${escapeHtml(String(pl))}</td>`;
         }
      });

      const totalMarks = student.total_marks || '-';
      const totalPL = '-'; // Could be calculated if needed
      html += `<td>${escapeHtml(String(totalMarks))}</td>`;
      html += `<td>${escapeHtml(String(totalPL))}</td>`;
      html += '</tr>';

      rank++;
   });

   // Mean score row
   html += '<tr class="mean-row">';
   html += '<td colspan="2">Mean Score</td>';

   subjects.forEach(subject => {
      const colspan = componentSubjects.includes(subject) ? '4' : '2';
      const mean = subjectMeans[subject] || 0;
      html += `<td colspan="${colspan}">${mean}</td>`;
   });

   html += `<td colspan="2">${totalMean}</td>`;
   html += '</tr>';

   // Previous mean row
   html += '<tr class="mean-row">';
   html += '<td colspan="2">Previous Mean</td>';

   subjects.forEach(subject => {
      const colspan = componentSubjects.includes(subject) ? '4' : '2';
      const prevMean = previousMeans[subject] || 0;
      html += `<td colspan="${colspan}">${prevMean}</td>`;
   });

   const prevTotalMean = previousMeans.total_mean || 0;
   html += `<td colspan="2">${prevTotalMean}</td>`;
   html += '</tr>';

   // Deviation row
   html += '<tr class="deviation-row">';
   html += '<td colspan="2">Deviation</td>';

   subjects.forEach(subject => {
      const colspan = componentSubjects.includes(subject) ? '4' : '2';
      const current = subjectMeans[subject] || 0;
      const previous = previousMeans[subject] || 0;
      const deviation = (current - previous).toFixed(2);
      html += `<td colspan="${colspan}">${deviation}</td>`;
   });

   const totalDeviation = (totalMean - (previousMeans.total_mean || 0)).toFixed(2);
   html += `<td colspan="2">${totalDeviation}</td>`;
   html += '</tr>';

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
