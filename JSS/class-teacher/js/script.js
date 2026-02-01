function initializeScriptListeners() {
   let toggleBtn = document.getElementById('toggle-btn');
   let body = document.body;
   let darkMode = localStorage.getItem('dark-mode');
   let profile = document.querySelector('.header .flex .profile');
   let search = document.querySelector('.header .flex .search-form');

   const enableDarkMode = () =>{
      if (toggleBtn) toggleBtn.classList.replace('fa-sun', 'fa-moon');
      body.classList.add('dark');
      localStorage.setItem('dark-mode', 'enabled');
   }

   const disableDarkMode = () =>{
      if (toggleBtn) toggleBtn.classList.replace('fa-moon', 'fa-sun');
      body.classList.remove('dark');
      localStorage.setItem('dark-mode', 'disabled');
   }

   if(darkMode === 'enabled'){
      enableDarkMode();
   }

   if(toggleBtn) {
      toggleBtn.onclick = (e) =>{
         darkMode = localStorage.getItem('dark-mode');
         if(darkMode === 'disabled'){
            enableDarkMode();
         }else{
            disableDarkMode();
         }
      }
   }

   const userBtn = document.querySelector('#user-btn');
   if(userBtn) {
      userBtn.onclick = () =>{
         if(profile) profile.classList.toggle('active');
         if(search) search.classList.remove('active');
      }
   }

   const searchBtn = document.querySelector('#search-btn');
   if(searchBtn) {
      searchBtn.onclick = () =>{
         if(search) search.classList.toggle('active');
         if(profile) profile.classList.remove('active');
      }
   }

   let menuBtn = document.querySelector('#menu-btn');
   if(menuBtn) {
      menuBtn.onclick = () =>{
         let sideBar = document.querySelector('.side-bar');
         if(sideBar) sideBar.classList.toggle('active');
      }
   }

   let closeBtn = document.querySelector('#close-btn');
   if(closeBtn) {
      closeBtn.onclick = () =>{
         let sideBar = document.querySelector('.side-bar');
         if(sideBar) sideBar.classList.remove('active');
      }
   }

   // Scroll event listeners
   window.onscroll = () =>{
      if(profile) profile.classList.remove('active');
      if(search) search.classList.remove('active');

      if(window.innerWidth < 1200){
         let sideBar = document.querySelector('.side-bar');
         if(sideBar) sideBar.classList.remove('active');
      }
   }
}

// Initialize script listeners when DOM is ready
if (document.readyState === 'loading') {
   document.addEventListener('DOMContentLoaded', initializeScriptListeners);
} else {
   initializeScriptListeners();
}

// Also re-initialize when header is loaded via component system
document.addEventListener('headerLoaded', initializeScriptListeners);