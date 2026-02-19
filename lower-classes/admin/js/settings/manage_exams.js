/**
 * Manage Exams Module
 * Handles creating, editing, and deleting exams
 */

const ManageExamsManager = (() => {
   const BASE_URL = "../../backend/public/index.php";

   // Cache DOM elements
   let tableBody, createExamBtn, createExamModal, closeExamModal, cancelExam, createExamForm;
   let editExamModal, closeEditModal, cancelEdit, editExamForm, editExamName, editExamStatus;

   // Current edit exam ID
   let currentEditExamId = null;

   // Status color mapping
   const STATUS_COLORS = {
      'Scheduled': '#3b82f6',
      'Completed': '#10b981',
      'Cancelled': '#ef4444'
   };

   /**
    * Initialize the manage exams page
    */
   async function init() {
      cacheDOMElements();
      attachEventListeners();
      await loadExams();
   }

   /**
    * Cache frequently accessed DOM elements
    */
   function cacheDOMElements() {
      tableBody = document.querySelector('tbody');
      createExamBtn = document.getElementById('createExamBtn');
      createExamModal = document.getElementById('createExamModal');
      closeExamModal = document.getElementById('closeExamModal');
      cancelExam = document.getElementById('cancelExam');
      createExamForm = document.getElementById('createExamForm');
      editExamModal = document.getElementById('editExamModal');
      closeEditModal = document.getElementById('closeEditModal');
      cancelEdit = document.getElementById('cancelEdit');
      editExamForm = document.getElementById('editExamForm');
      editExamName = document.getElementById('editExamName');
      editExamStatus = document.getElementById('editExamStatus');
   }

   /**
    * Attach event listeners
    */
   function attachEventListeners() {
      if (createExamBtn) {
         createExamBtn.addEventListener('click', event => {
            event.preventDefault();
            toggleModal(createExamModal, true);
         });
      }

      if (closeExamModal) {
         closeExamModal.addEventListener('click', () => toggleModal(createExamModal, false));
      }

      if (cancelExam) {
         cancelExam.addEventListener('click', () => toggleModal(createExamModal, false));
      }

      if (closeEditModal) {
         closeEditModal.addEventListener('click', () => toggleModal(editExamModal, false));
      }

      if (cancelEdit) {
         cancelEdit.addEventListener('click', () => toggleModal(editExamModal, false));
      }

      if (createExamModal) {
         createExamModal.addEventListener('click', event => {
            if (event.target === createExamModal) {
               toggleModal(createExamModal, false);
            }
         });
      }

      if (editExamModal) {
         editExamModal.addEventListener('click', event => {
            if (event.target === editExamModal) {
               toggleModal(editExamModal, false);
            }
         });
      }

      if (createExamForm) {
         createExamForm.addEventListener('submit', handleCreateExamSubmit);
      }

      if (editExamForm) {
         editExamForm.addEventListener('submit', handleEditExamSubmit);
      }

      // Event delegation for table actions
      if (tableBody) {
         tableBody.addEventListener('click', event => {
            const editLink = event.target.closest('.edit-link');
            const deleteLink = event.target.closest('.delete-link');

            if (editLink) {
               event.preventDefault();
               handleEditClick(editLink);
            } else if (deleteLink) {
               event.preventDefault();
               handleDeleteClick(deleteLink);
            }
         });
      }
   }

   /**
    * Toggle modal visibility
    */
   function toggleModal(modal, show) {
      if (!modal) return;
      modal.classList.toggle('active', show);
      modal.setAttribute('aria-hidden', show ? 'false' : 'true');
   }

   /**
    * Load exams from backend
    */
   async function loadExams() {
      try {
         showLoadingState();

         const authRes = await fetch(`${BASE_URL}/auth/check`, { credentials: "include" });
         const auth = await authRes.json();

         if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
         }

         const res = await fetch(`${BASE_URL}/settings/exams`, { credentials: 'include' });
         const data = await res.json();

         if (!data.success || !Array.isArray(data.data)) {
            showErrorState("No exams found");
            return;
         }

         if (data.data.length === 0) {
            showEmptyState();
            return;
         }

         renderExamsTable(data.data);
      } catch (error) {
         console.error('Failed to load exams:', error);
         showErrorState("Failed to load exams");
      }
   }

   /**
    * Show loading state
    */
   function showLoadingState() {
      if (tableBody) {
         tableBody.innerHTML = '<tr><td colspan="3" style="text-align:center;">Loading...</td></tr>';
      }
   }

   /**
    * Show empty state
    */
   function showEmptyState() {
      if (tableBody) {
         tableBody.innerHTML = '<tr><td colspan="3" style="text-align:center;">No exams found.</td></tr>';
      }
   }

   /**
    * Show error state
    */
   function showErrorState(message) {
      if (tableBody) {
         tableBody.innerHTML = `<tr><td colspan="3" style="text-align:center; color: #e74c3c;">${escapeHtml(message)}</td></tr>`;
      }
   }

   /**
    * Render exams table with batch rendering
    */
   function renderExamsTable(exams) {
      if (!tableBody) return;

      const rows = exams.map(exam => {
         const statusBg = STATUS_COLORS[exam.status] || '#6b7280';

         return `
            <tr>
               <td><p>${escapeHtml(exam.exam_name)}</p></td>
               <td>
                  <span style="background-color: ${statusBg}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                     ${escapeHtml(exam.status)}
                  </span>
               </td>
               <td>
                  <a href="#" class="edit-link" data-id="${escapeHtml(String(exam.exam_id))}" data-name="${escapeHtml(exam.exam_name)}" data-status="${escapeHtml(exam.status)}">
                     <span class="status pending">edit</span>
                  </a>
                  <a href="#" class="delete-link" data-id="${escapeHtml(String(exam.exam_id))}">
                     <span class="status delete">delete</span>
                  </a>
               </td>
            </tr>
         `;
      }).join('');

      tableBody.innerHTML = rows;
   }

   /**
    * Handle edit link click
    */
   function handleEditClick(link) {
      const examId = link.getAttribute('data-id');
      const examName = link.getAttribute('data-name');
      const examStatus = link.getAttribute('data-status');

      currentEditExamId = examId;
      editExamName.value = examName;
      editExamStatus.value = examStatus;
      toggleModal(editExamModal, true);
   }

   /**
    * Handle delete link click
    */
   async function handleDeleteClick(link) {
      const examId = link.getAttribute('data-id');

      const confirm = await swal({
         title: 'Caution!',
         text: 'Are you sure you want to delete?',
         icon: 'warning',
         buttons: true,
         dangerMode: true,
      });

      if (!confirm) return;

      try {
         const res = await fetch(`${BASE_URL}/settings/exams/delete`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ exam_id: Number(examId) })
         });

         const data = await res.json();

         if (!data.success) {
            swal('Error', data.message || 'Failed to delete exam.', 'error');
            return;
         }

         await loadExams();
         swal('Deleted', 'Exam removed successfully.', 'success');
      } catch (error) {
         console.error('Failed to delete exam:', error);
         swal('Error', 'Failed to delete exam.', 'error');
      }
   }

   /**
    * Handle create exam form submission
    */
   async function handleCreateExamSubmit(event) {
      event.preventDefault();

      const name = createExamForm.name.value.trim();
      const examType = createExamForm.exam_type.value;
      const term = createExamForm.term.value;

      if (!name || !examType || !term) {
         swal('Error', 'All fields are required!', 'error');
         return;
      }

      try {
         const res = await fetch(`${BASE_URL}/settings/exams`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
               name,
               exam_type: examType,
               term
            })
         });

         const data = await res.json();

         if (!data.success) {
            swal('Error', data.message || 'Failed to create exam.', 'error');
            return;
         }

         toggleModal(createExamModal, false);
         createExamForm.reset();
         await loadExams();
         swal('Success', 'Exam created successfully!', 'success');
      } catch (error) {
         console.error('Failed to create exam:', error);
         swal('Error', 'Failed to create exam.', 'error');
      }
   }

   /**
    * Handle edit exam form submission
    */
   async function handleEditExamSubmit(event) {
      event.preventDefault();

      const status = editExamStatus.value;

      if (!status) {
         swal('Error', 'Please select a status!', 'error');
         return;
      }

      try {
         const res = await fetch(`${BASE_URL}/settings/exams/update`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
               exam_id: Number(currentEditExamId),
               status
            })
         });

         const data = await res.json();

         if (!data.success) {
            swal('Error', data.message || 'Failed to update exam.', 'error');
            return;
         }

         toggleModal(editExamModal, false);
         await loadExams();
         swal('Success', 'Exam status updated successfully!', 'success');
      } catch (error) {
         console.error('Failed to update exam:', error);
         swal('Error', 'Failed to update exam.', 'error');
      }
   }

   /**
    * Escape HTML to prevent XSS attacks
    */
   function escapeHtml(text) {
      const map = {
         '&': '&amp;',
         '<': '&lt;',
         '>': '&gt;',
         '"': '&quot;',
         "'": '&#039;'
      };
      return String(text).replace(/[&<>"']/g, m => map[m]);
   }

   // Public API
   return {
      init
   };
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
   ManageExamsManager.init();
});
