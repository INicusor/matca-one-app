<section id="view-dashboard" class="view-section active">
  
  <input type="text" style="display:none" aria-hidden="true" autocomplete="username">
  <input type="password" style="display:none" aria-hidden="true" autocomplete="current-password">

  <input type="text" 
         id="dashboard-search" 
         placeholder="🔎 Caută stup (nume sau ID)..." 
         autocomplete="off" 
         readonly 
         onfocus="this.removeAttribute('readonly');">
         
  <div id="container"></div>
</section>