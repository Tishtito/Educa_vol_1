/**
 * Classes Management Module
 * Handles displaying, adding, and deleting classes
 */

const ClassesManager = (() => {
   const BASE_URL = "../../backend/public/index.php";

   // Cache DOM elements
   let tableBody, addClassBtn, addClassModal, closeClassModal, cancelClass, addClassForm;

   /**
    * Initialize the classes management page
    */
   async function init() {
      cacheDOMElements();
      attachEventListeners();
      await loadClasses();
   }

   /**
    * Cache frequently accessed DOM elements
    */
   function cacheDOMElements() {
      tableBody = document.querySelector('tbody');
      addClassBtn = document.getElementById('addClassBtn');
      addClassModal = document.getElementById('addClassModal');
      closeClassModal = document.getElementById('closeClassModal');
      cancelClass = document.getElementById('cancelClass');
      addClassForm = document.getElementById('addClassForm');
   }

   /**
    * Attach event listeners
    */
   function attachEventListeners() {
      if (addClassBtn) {
         addClassBtn.addEventListener('click', event => {
            event.preventDefault();
            toggleModal(true);
         });
      }

      if (closeClassModal) {
         closeClassModal.addEventListener('click', () => toggleModal(false));
      }

      if (cancelClass) {
         cancelClass.addEventListener('click', () => toggleModal(false));
      }

      if (addClassModal) {
         addClassModal.addEventListener('click', event => {
            if (event.target === addClassModal) {
               toggleModal(false);
            }
         });
      }

      if (addClassForm) {
         addClassForm.addEventListener('submit', handleAddClassSubmit);
      }
   }

   /**
    * Toggle modal visibility
    */
   function toggleModal(show) {
      if (!addClassModal) return;
      addClassModal.classList.toggle('active', show);
      addClassModal.setAttribute('aria-hidden', show ? 'false' : 'true');
   }

   /**
    * Load classes from backend
    */
   async function loadClasses() {
      try {
         showLoadingState();

         const authRes = await fetch(`${BASE_URL}/auth/check`, { credentials: "include" });
         const auth = await authRes.json();

         if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
         }

         const res = await fetch(`${BASE_URL}/settings/classes`, { credentials: 'include' });
         const data = await res.json();

         if (!data.success || !Array.isArray(data.data)) {
            showErrorState("Failed to load classes");
            return;
         }

         if (data.data.length === 0) {
            showEmptyState();
            return;
         }

         renderClassesTable(data.data);
         bindDeleteLinks();
      } catch (error) {
         console.error('Failed to load classes:', error);
         showErrorState("Error loading classes");
      }
   }

   /**
    * Show loading state
    */
   function showLoadingState() {
      if (tableBody) {
         tableBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">Loading...</td></tr>';
      }
   }

   /**
    * Show empty state
    */
   function showEmptyState() {
      if (tableBody) {
         tableBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">No classes found.</td></tr>';
      }
   }

   /**
    * Show error state
    */
   function showErrorState(message) {
      if (tableBody) {
         tableBody.innerHTML = `<tr><td colspan="2" style="text-align:center; color: #e74c3c;">${escapeHtml(message)}</td></tr>`;
      }
   }

   /**
    * Render classes table with batch rendering
    */
   function renderClassesTable(classes) {
      const rows = classes.map(classItem => {
         return `
            <tr>
               <td><p>${escapeHtml(classItem.class_name)}</p></td>
               <td><a href="#" class="delete-link" data-id="${escapeHtml(String(classItem.class_id))}"><span class="status delete">delete</span></a></td>
            </tr>
         `;
      }).join('');

      if (tableBody) {
         tableBody.innerHTML = rows;
      }
   }

   /**
    * Bind delete event handlers using event delegation
    */
   function bindDeleteLinks() {
      if (!tableBody) return;

      tableBody.addEventListener('click', async event => {
         const deleteLink = event.target.closest('.delete-link');
         if (!deleteLink) return;

         event.preventDefault();
         const classId = deleteLink.getAttribute('data-id');

         const confirm = await swal({
            title: 'Caution!',
            text: 'Are you sure you want to delete?',
            icon: 'warning',
            buttons: true,
            dangerMode: true,
         });

         if (!confirm) return;

         await deleteClass(classId);
      });
   }

   /**
    * Delete a class
    */
   async function deleteClass(classId) {
      try {
         const res = await fetch(`${BASE_URL}/settings/classes/delete`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ class_id: Number(classId) })
         });

         const data = await res.json();

         if (!data.success) {
            swal('Error', data.message || 'Failed to delete class.', 'error');
            return;
         }

         await loadClasses();
         swal('Deleted', 'Class removed successfully.', 'success');
      } catch (error) {
         console.error('Failed to delete class:', error);
         swal('Error', 'Failed to delete class.', 'error');
      }
   }

   /**
    * Handle add class form submission
    */
   async function handleAddClassSubmit(event) {
      event.preventDefault();

      const className = addClassForm.class_name.value.trim();
      const grade = addClassForm.grade.value;

      if (!className || !grade) {
         swal('Error', 'All fields are required!', 'error');
         return;
      }

      try {
         const res = await fetch(`${BASE_URL}/settings/classes`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
               class_name: className,
               grade: Number(grade)
            })
         });

         const data = await res.json();

         if (!data.success) {
            swal('Error', data.message || 'Failed to create class.', 'error');
            return;
         }

         toggleModal(false);
         addClassForm.reset();
         await loadClasses();
         swal('Success', 'Class created successfully!', 'success');
      } catch (error) {
         console.error('Failed to create class:', error);
         swal('Error', 'Failed to create class.', 'error');
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
   ClassesManager.init();
});
