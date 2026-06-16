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

// Load header and sidebar on page load
window.addEventListener('DOMContentLoaded', async () => {
   await Promise.all([
      loadComponentPromise('components/header.html', 'headerContainer'),
      loadComponentPromise('components/sidebar.html', 'sidebarContainer'),
      loadComponentPromise('components/bottom-navigator.html', 'bottomNavContainer'),
      loadComponentPromise('components/footer.html', 'footerContainer')
   ]);
   // Load teacher data
   loadTeacherData();
   
   // Load students after components are loaded
   loadStudents();
   
   // Populate graduation class dropdown
   populateGraduationClassDropdown();
   
   // Initialize event listeners
   initializeEventListeners();
});

// Load all students
async function loadStudents(searchTerm = '') {
   try {
      const url = searchTerm 
         ? `../backend/public/index.php?route=/students&search=${encodeURIComponent(searchTerm)}`
         : `../backend/public/index.php?route=/students`;
         
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
      
      if (data.success && data.students) {
         displayStudents(data.students);
      } else {
         document.getElementById('students-container').innerHTML = 
            `<p style="text-align: center;">${data.message || 'No students found.'}</p>`;
      }
   } catch (error) {
      console.error('Error loading students:', error);
      showErrorAlert('Error loading students. Please refresh the page.');
   }
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

/**
 * Show generic error alert
 */
function showErrorAlert(message) {
   Swal.fire({
      title: 'Error!',
      text: message,
      icon: 'error',
      confirmButtonText: 'OK'
   });
}

// Display students
function displayStudents(students) {
   const container = document.getElementById('students-container');
   
   if (!students || students.length === 0) {
      container.innerHTML = '<p style="text-align: center;">No students found.</p>';
      return;
   }
   
   let html = '';
   
   students.forEach(student => {
      const totalMarks = student.total_marks || 0;
      const math = student.Math || 'N/A';
      const english = student.English || 'N/A';
      const kiswahili = student.Kiswahili || 'N/A';
      const currentClass = escapeHtml(student.class || 'N/A');
      const studentName = escapeHtml(student.name || '');
      const studentId = student.student_id || '';
      
      // Generate token for secure link
      const token = generateSecureToken(studentId);
      
      html += `
         <div class="box">
            <div class="tutor">
               <img src="../photos/students.png" alt="">
               <div>
                  <h3>${studentName}</h3>
                  <span>Student</span>
                  <div class="dropdown">
                     <button class="dropdown-btn inline-option-btn" type="button">Change Class</button>
                     <div class="dropdown-content" id="dropdown-${studentId}">
                        <!-- Classes will be populated here -->
                     </div>
                  </div>
               </div>
            </div>
            <p>Total Marks: <span>${totalMarks}</span></p>
            <p>Math: <span>${math}</span></p>
            <p>English: <span>${english}</span></p>
            <p>Kiswahili: <span>${kiswahili}</span></p>
            <p>Current Class: <span>${currentClass}</span></p>
            
            <a href="studentprofile.html?token=${token}" class="inline-btn">View Profile</a>
            <a href="#" data-id="${studentId}" class="delete-btn inline-btn2">Delete Student</a>
         </div>
      `;
   });
   
   container.innerHTML = html;
   
   // Populate class dropdowns
   populateClassDropdowns();
   
   // Re-attach event listeners for delete buttons
   attachDeleteListeners();
}

// Generate secure token for student ID
function generateSecureToken(studentId) {
   const token = 'token_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
   
   // Store token-to-ID mapping in sessionStorage
   let tokenMap = JSON.parse(sessionStorage.getItem('studentTokenMap') || '{}');
   tokenMap[token] = studentId;
   sessionStorage.setItem('studentTokenMap', JSON.stringify(tokenMap));
   
   return token;
}

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

// Populate class dropdowns with available classes
async function populateClassDropdowns() {
   try {
      const response = await fetch('../backend/public/index.php?route=/classes', {
         method: 'GET',
         credentials: 'include'
      });
      
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      
      const data = await response.json();
      
      if (data.success && data.classes) {
         const dropdowns = document.querySelectorAll('.dropdown-content');
         dropdowns.forEach(dropdown => {
            dropdown.innerHTML = '';
            data.classes.forEach(cls => {
               const link = document.createElement('a');
               link.href = '#';
               link.textContent = cls.class_name || cls.name;
               link.onclick = (e) => {
                  e.preventDefault();
                  const studentId = dropdown.id.replace('dropdown-', '');
                  const studentName = dropdown.closest('.box').querySelector('h3').textContent;
                  updateClass(studentId, cls.class_name || cls.name, studentName);
               };
               dropdown.appendChild(link);
            });
         });
      }
   } catch (error) {
      console.error('Error loading classes:', error);
   }
}

/*Populate graduation class dropdown with classes from database*/
async function populateGraduationClassDropdown() {
   try {
      const response = await fetch('../backend/public/index.php?route=/classes', {
         method: 'GET',
         credentials: 'include'
      });
      
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      
      const data = await response.json();
      
      if (data.success && data.classes) {
         const select = document.getElementById('target_class');
         // Add classes from database
         data.classes.forEach(cls => {
            const option = document.createElement('option');
            option.value = cls.class_id || cls.id;
            option.textContent = cls.class_name || cls.name;
            select.appendChild(option);
         });
      }
   } catch (error) {
      console.error('Error loading classes for graduation dropdown:', error);
   }
}

// Update class via API
async function updateClass(studentId, newClass, studentName) {
   const result = await Swal.fire({
      title: 'Change Class?',
      html: `Are you sure you want to change <b>${escapeHtml(studentName)}</b>'s class to <b>${escapeHtml(newClass)}</b>?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, change it!'
   });

   if (result.isConfirmed) {
      Swal.fire({
         title: 'Updating...',
         html: 'Please wait while we update the class',
         allowOutsideClick: false,
         didOpen: () => {
            Swal.showLoading();
         }
      });

      try {
         const response = await fetch('../backend/public/index.php?route=/students/update-class', {
            method: 'POST',
            headers: {
               'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
               student_id: parseInt(studentId),
               new_class: newClass
            })
         });

         if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

         const data = await response.json();

         Swal.fire({
            title: data.success ? 'Success!' : 'Error!',
            text: data.message,
            icon: data.success ? 'success' : 'error',
            confirmButtonText: 'OK'
         }).then(() => {
            if (data.success) {
               loadStudents();
            }
         });
      } catch (error) {
         Swal.fire({
            title: 'Error!',
            text: 'An error occurred: ' + error.message,
            icon: 'error',
            confirmButtonText: 'OK'
         });
      }
   }
}

// Delete student via API
async function deleteStudent(studentId) {
   const box = document.querySelector(`[data-id="${studentId}"]`).closest('.box');
   const studentName = box.querySelector('h3').textContent;

   const result = await Swal.fire({
      title: 'Are you sure?',
      html: `You are about to delete <b>${escapeHtml(studentName)}</b> permanently!`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!'
   });

   if (result.isConfirmed) {
      Swal.fire({
         title: 'Deleting...',
         html: 'Please wait while we delete the student',
         allowOutsideClick: false,
         didOpen: () => {
            Swal.showLoading();
         }
      });

      try {
         const response = await fetch('../backend/public/index.php?route=/students/delete', {
            method: 'POST',
            headers: {
               'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
               student_id: parseInt(studentId)
            })
         });

         if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

         const data = await response.json();

         Swal.fire({
            title: data.success ? 'Deleted!' : 'Error!',
            text: data.message,
            icon: data.success ? 'success' : 'error',
            confirmButtonText: 'OK'
         }).then(() => {
            if (data.success) {
               loadStudents();
            }
         });
      } catch (error) {
         Swal.fire({
            title: 'Error!',
            text: 'An error occurred: ' + error.message,
            icon: 'error',
            confirmButtonText: 'OK'
         });
      }
   }
}

// Initialize event listeners
function initializeEventListeners() {
   // Search form from header
   const searchForm = document.querySelector('.search-form');
   const searchInput = searchForm ? searchForm.querySelector('input[type="text"]') : null;
   
   if (searchForm && searchInput) {
      searchForm.addEventListener('submit', (e) => {
         e.preventDefault();
         const searchTerm = searchInput.value;
         loadStudents(searchTerm);
      });
   }

   // Add student button
   const addStudentBtn = document.getElementById('add-student-btn');
   if (addStudentBtn) {
      addStudentBtn.addEventListener('click', (e) => {
         e.preventDefault();
         openAddStudentModal();
      });
   }

   // Modal close button
   const closeBtn = document.getElementById('close-modal');
   if (closeBtn) {
      closeBtn.addEventListener('click', closeAddStudentModal);
   }

   // Modal cancel button
   const cancelBtn = document.getElementById('cancel-btn');
   if (cancelBtn) {
      cancelBtn.addEventListener('click', closeAddStudentModal);
   }

   // Add student form submission
   const addStudentForm = document.getElementById('add-student-form');
   if (addStudentForm) {
      addStudentForm.addEventListener('submit', (e) => {
         e.preventDefault();
         submitAddStudentForm();
      });
   }

   // Close modal when clicking outside the modal content
   const modal = document.getElementById('addStudentModal');
   if (modal) {
      modal.addEventListener('click', (e) => {
         if (e.target === modal) {
            closeAddStudentModal();
         }
      });
   }
}

// Attach delete button listeners
function attachDeleteListeners() {
   const deleteButtons = document.querySelectorAll('.delete-btn');
   deleteButtons.forEach(btn => {
      btn.addEventListener('click', (e) => {
         e.preventDefault();
         const studentId = btn.getAttribute('data-id');
         deleteStudent(studentId);
      });
   });
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
   const div = document.createElement('div');
   div.textContent = text;
   return div.innerHTML;
}

// Open Add Student Modal
function openAddStudentModal() {
   const modal = document.getElementById('addStudentModal');
   modal.classList.add('show');
   document.getElementById('add-student-form').reset();
}

// Close Add Student Modal
function closeAddStudentModal() {
   const modal = document.getElementById('addStudentModal');
   modal.classList.remove('show');
}

// Submit add student form
async function submitAddStudentForm() {
   const form = document.getElementById('add-student-form');
   const formData = new FormData(form);
   
   const studentName = formData.get('name');
   // const parentPhone = formData.get('pno');

   //PARENT PHONE NUMBER IS NOT REQUIRED UNTILL SMS MODULE IS PAID FOR, SO WE CAN SKIP VALIDATION FOR IT (|| !parentPhone)
   if (!studentName) {
      Swal.fire({
         title: 'Error!',
         text: 'Please fill in all required fields.',
         icon: 'error',
         confirmButtonText: 'OK'
      });
      return;
   }

   Swal.fire({
      title: 'Adding Student...',
      html: 'Please wait while we add the student',
      allowOutsideClick: false,
      didOpen: () => {
         Swal.showLoading();
      }
   });

   try {
      const response = await fetch('../backend/public/index.php?route=/students/create', {
         method: 'POST',
         headers: {
            'Content-Type': 'application/json'
         },
         credentials: 'include',
         body: JSON.stringify({
            name: studentName
            // pno: parentPhone
         })
      });

      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

      const data = await response.json();

      Swal.fire({
         title: data.success ? 'Success!' : 'Error!',
         text: data.message,
         icon: data.success ? 'success' : 'error',
         confirmButtonText: 'OK'
      }).then(() => {
         if (data.success) {
            closeAddStudentModal();
            loadStudents();
         }
      });
   } catch (error) {
      Swal.fire({
         title: 'Error!',
         text: 'An error occurred: ' + error.message,
         icon: 'error',
         confirmButtonText: 'OK'
      });
   }
}
