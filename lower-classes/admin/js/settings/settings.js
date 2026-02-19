/**
 * Settings Module
 * Handles exam creation modal on settings page
 */

const SettingsManager = (() => {
   const BASE_URL = "../../backend/public/index.php";

   // Cache DOM elements
   let createExamBtn, createExamModal, closeExamModal, cancelExam, createExamForm;

   /**
    * Initialize the settings page
    */
   async function init() {
      cacheDOMElements();
      attachEventListeners();
      validateSession();
   }

   /**
    * Cache frequently accessed DOM elements
    */
   function cacheDOMElements() {
      createExamBtn = document.getElementById('createExamBtn');
      createExamModal = document.getElementById('createExamModal');
      closeExamModal = document.getElementById('closeExamModal');
      cancelExam = document.getElementById('cancelExam');
      createExamForm = document.getElementById('createExamForm');
   }

   /**
    * Attach event listeners
    */
   function attachEventListeners() {
      if (createExamBtn) {
         createExamBtn.addEventListener('click', event => {
            event.preventDefault();
            toggleModal(true);
         });
      }

      if (closeExamModal) {
         closeExamModal.addEventListener('click', () => toggleModal(false));
      }

      if (cancelExam) {
         cancelExam.addEventListener('click', () => toggleModal(false));
      }

      if (createExamModal) {
         createExamModal.addEventListener('click', event => {
            if (event.target === createExamModal) {
               toggleModal(false);
            }
         });
      }

      if (createExamForm) {
         createExamForm.addEventListener('submit', handleCreateExamSubmit);
      }
   }

   /**
    * Toggle modal visibility
    */
   function toggleModal(show) {
      if (!createExamModal) return;
      createExamModal.classList.toggle('active', show);
      createExamModal.setAttribute('aria-hidden', show ? 'false' : 'true');
   }

   /**
    * Validate user session
    */
   async function validateSession() {
      try {
         const authRes = await fetch(`${BASE_URL}/auth/check`, { credentials: "include" });
         const auth = await authRes.json();

         if (!auth.authenticated) {
            window.location.replace("Pages/login.html");
         }
      } catch (error) {
         console.error('Failed to validate session:', error);
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

         toggleModal(false);
         createExamForm.reset();
         swal('Success', 'Exam created successfully!', 'success');
      } catch (error) {
         console.error('Failed to create exam:', error);
         swal('Error', 'Failed to create exam.', 'error');
      }
   }

   // Public API
   return {
      init
   };
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
   SettingsManager.init();
});
