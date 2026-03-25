// Exam selection page script

async function loadComponentPromise(componentName, containerId) {
   try {
      const res = await fetch(`components/${componentName}.html`);
      if (!res.ok) throw new Error(`Failed to load ${componentName}`);
      const html = await res.text();
      document.getElementById(containerId).innerHTML = html;
      // Trigger header re-initialization after header loads
      if (containerId === 'headerContainer') {
         document.dispatchEvent(new Event('headerLoaded'));
      }
   } catch (err) {
      console.error(`Error loading ${componentName} component:`, err);
   }
}

async function checkAuth() {
   const res = await fetch('../backend/public/index.php/auth/check', { credentials: 'include' });
   if (!res.ok) {
      window.location.href = 'login.html';
      return null;
   }
   return await res.json();
}

async function loadExams() {
   const examsContainer = document.getElementById('examsContainer');
   examsContainer.innerHTML = '<p>Loading exams...</p>';
   try {
      const res = await fetch('../backend/public/index.php/exams', { credentials: 'include' });
      if (res.status === 401) {
         window.location.href = 'login.html';
         return;
      }
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Failed to load exams');
      if (!data.exams || data.exams.length === 0) {
         examsContainer.innerHTML = '<p>No exams found.</p>';
         return;
      }
      examsContainer.innerHTML = '';
      data.exams.forEach(exam => {
         const box = document.createElement('div');
         box.className = 'box';
         box.innerHTML = `
            <div class="tutor">
               <img src="../photos/user.png" alt="">
               <div class="info">
                  <h3>By Admin</h3>
                  <span>${new Date(exam.date_created).toLocaleDateString()}</span>
               </div>
            </div>
            <h3 class="title">${escapeHtml(exam.exam_name)}</h3>
            <button class="inline-btn" onclick="selectExam(${exam.exam_id})">Select Exam</button>
         `;
         examsContainer.appendChild(box);
      });
   } catch (err) {
      examsContainer.innerHTML = `<p style="color:red;">${err.message}</p>`;
   }
}

function escapeHtml(text) {
   const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
   return text.replace(/[&<>"']/g, m => map[m]);
}

function checkClassAssignment() {
   // Check if teacher has been assigned a class
   if (!teacherDataGlobal || !teacherDataGlobal.class_assigned || teacherDataGlobal.class_assigned.trim() === '') {
      Swal.fire({
         title: 'No Class Assigned',
         text: 'You have not been assigned a class. Please contact the administrator.',
         icon: 'warning',
         confirmButtonText: 'Try Again Later',
         allowOutsideClick: false,
         allowEscapeKey: false
      }).then(() => {
         window.location.href = 'index.html';
      });
      return false;
   }
   return true;
}

async function selectExam(examId) {
   try {
      const res = await fetch('../backend/public/index.php/exams/select', {
         method: 'POST',
         credentials: 'include',
         headers: { 'Content-Type': 'application/json' },
         body: JSON.stringify({ exam_id: examId })
      });
      const data = await res.json();
      if (data.success) {
         window.location.href = 'home.html';
      } else {
         Swal.fire('Error', data.message || 'Failed to select exam', 'error');
      }
   } catch (err) {
      Swal.fire('Error', err.message, 'error');
   }
}

document.addEventListener('DOMContentLoaded', async () => {
   await checkAuth();
   await Promise.all([
      loadComponentPromise('header', 'headerContainer'),
      loadComponentPromise('sidebar', 'sidebarContainer'),
      loadComponentPromise('bottom-navigator', 'bottomNavContainer'),
      loadComponentPromise('footer', 'footerContainer')
   ]);
   await loadTeacherData();
   
   // Check if teacher has a class assigned
   if (!checkClassAssignment()) {
      return; // Stop execution if no class assigned
   }
   
   await loadExams();
});
