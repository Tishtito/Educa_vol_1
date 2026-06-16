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

   // Load student profile
   await loadStudentProfile();
});

// Retrieve student ID from token
function getStudentIdFromToken(token) {
   const tokenMap = JSON.parse(sessionStorage.getItem('studentTokenMap') || '{}');
   const studentId = tokenMap[token];
   
   if (!studentId) {
      console.error('Invalid token: ' + token);
      return null;
   }
   
   return studentId;
}

/*Load student profile*/
async function loadStudentProfile() {
   try {
      // Get token from URL query parameter
      const params = new URLSearchParams(window.location.search);
      const token = params.get('token');
      const studentId = params.get('id'); // Fallback for backward compatibility

      let finalStudentId = studentId;

      // Try to resolve token to student ID
      if (token) {
         finalStudentId = getStudentIdFromToken(token);
         
         if (!finalStudentId) {
            document.getElementById('profileContainer').innerHTML = 
               '<p class="error-message">Invalid or expired link. <a href="students.html">Go back to students</a></p>';
            return;
         }
      } else if (!finalStudentId) {
         document.getElementById('profileContainer').innerHTML = 
            '<p class="error-message">Student ID not provided. <a href="students.html">Go back to students</a></p>';
         return;
      }

      const response = await fetch(`../backend/public/index.php/student-profile?id=${encodeURIComponent(finalStudentId)}`, {
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
         displayStudentProfile(data);
      } else {
         document.getElementById('profileContainer').innerHTML = 
            `<p class="error-message">${escapeHtml(data.message)}</p>`;
      }
   } catch (error) {
      console.error('Error loading student profile:', error);
      document.getElementById('profileContainer').innerHTML = 
         `<p class="error-message">Error loading student profile. Please refresh the page.</p>`;
   }
}

/*Display student profile*/
function displayStudentProfile(data) {
   const student = data.student || {};
   const marks = data.marks || {};
   const totalMarks = data.total_marks || 0;

   // Display student details
   let profileHtml = `
      <div class="details">
         <div class="tutor">
            <img src="../photos/students.png" alt="">
            <div>
               <h3>${escapeHtml(student.name || 'N/A')}</h3>
               <span>Student</span>
               <p><b>Class:</b> ${escapeHtml(student.class || 'N/A')}</p>
               <p><b>Status:</b> ${escapeHtml(student.status || 'N/A')}</p>
            </div>
         </div>
         <div class="flex">
            <p><b>Total Marks:</b> ${totalMarks}</p>
         </div>
      </div>
   `;

   document.getElementById('profileContainer').innerHTML = profileHtml;

   // Display marks
   let marksHtml = '';
   const subjectLabels = {
      'Math': 'Math',
      'English': 'English',
      'Kiswahili': 'Kiswahili',
      'Creative': 'Creative Arts',
      'SciTech': 'Science & Tech',
      'AgricNutri': 'Agriculture & Nutrition',
      'SST': 'Social Studies',
      'CRE': 'CRE'
   };

   for (const [key, label] of Object.entries(subjectLabels)) {
      const mark = marks[key] ?? '-';
      marksHtml += `
         <p>
            <b>${label}:</b>
            <span>${escapeHtml(String(mark))}</span>
         </p>
      `;
   }

   document.getElementById('marksBox').innerHTML = marksHtml;
   document.getElementById('marksContainer').style.display = 'block';
}

/*Escape HTML to prevent XSS*/
function escapeHtml(text) {
   const div = document.createElement('div');
   div.textContent = text;
   return div.innerHTML;
}
