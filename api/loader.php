<style>
  /* This is the main loader "curtain" */
  .skeleton-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: var(--background-color-body, #f9f9f9);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    opacity: 1;
    transition: opacity 0.5s ease;
  }

  /* This class will be added by JavaScript to fade the loader out */
  .skeleton-loader.hidden {
    opacity: 0;
    pointer-events: none;
  }

  .skeleton-container {
    max-width: 1100px;
    width: 100%;
    padding: 2rem;
    margin-top: 80px; /* Match your page's top margin/header height */
  }

  /* Define the animation */
  @keyframes shimmer {
    0% { background-position: -400px 0; }
    100% { background-position: 400px 0; }
  }

  /* The shimmering animated background */
  .skeleton {
    animation: shimmer 1.5s infinite linear;
    background: linear-gradient(to right, 
      var(--hover-color, #f0f0f0) 8%, 
      var(--border-color, #e2e8f0) 18%, 
      var(--hover-color, #f0f0f0) 33%
    );
    background-size: 800px 104px;
    border-radius: 6px;
  }
  
  /* Dark mode support */
  body.dark-mode .skeleton-loader {
     background-color: var(--background-color-body, #121212);
  }
  body.dark-mode .skeleton {
    background: linear-gradient(to right, 
      var(--hover-color, #2c2c2c) 8%, 
      var(--border-color, #334155) 18%, 
      var(--hover-color, #2c2c2c) 33%
    );
    background-size: 800px 104px;
  }

  /* Example layout: A title and two boxes */
  .skeleton-title {
    height: 38px;
    width: 40%;
    margin-bottom: 2rem;
  }
  .skeleton-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
  }
  .skeleton-card {
    height: 200px;
    width: 100%;
  }

</style>

<div class="skeleton-loader" id="page-loader">
  <div class="skeleton-container">
    <div class="skeleton skeleton-title"></div>
    <div class="skeleton-grid">
      <div class="skeleton skeleton-card"></div>
      <div class="skeleton skeleton-card"></div>
    </div>
  </div>
</div>