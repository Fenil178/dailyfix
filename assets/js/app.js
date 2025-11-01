document.addEventListener("DOMContentLoaded", () => {
  document.body.style.opacity = '1';
  // Mobile menu toggle
  const menuToggle = document.getElementById("mobile-menu");
  const navLinks = document.getElementById("navLinks");

  if (menuToggle && navLinks) {
    menuToggle.addEventListener("click", () => {
      navLinks.classList.toggle("active");
      const icon = menuToggle.querySelector("i");
      if (icon) {
        icon.classList.toggle("fa-bars");
        icon.classList.toggle("fa-times");
      }
    });
  }

  // Hero background carousel
  const slides = document.querySelectorAll(".hero-bg-carousel .slide");
  if (slides.length > 0) {
    let current = 0;
    setInterval(() => {
      slides[current].classList.remove("active");
      current = (current + 1) % slides.length;
      slides[current].classList.add("active");
    }, 5000); // 5 seconds
  }

  // User profile dropdown toggle
  const profileBtn = document.getElementById("profileBtn");
  const dropdownMenu = document.getElementById("dropdownMenu");

  if (profileBtn && dropdownMenu) {
    profileBtn.addEventListener("click", (event) => {
      dropdownMenu.classList.toggle("active");
      event.stopPropagation(); // Prevents the window click event from firing immediately
    });

    // Close dropdown if clicked outside
    window.addEventListener("click", (event) => {
      if (
        !dropdownMenu.contains(event.target) &&
        !profileBtn.contains(event.target)
      ) {
        dropdownMenu.classList.remove("active");
      }
    });
  }

  // Logout confirmation logic
  const logoutLink = document.getElementById("logout-link");
  const customModal = document.getElementById("custom-logout-modal");
  const confirmBtn = document.getElementById("confirm-logout-btn");
  const cancelBtn = document.getElementById("cancel-logout-btn");
  const closeBtn = customModal
    ? customModal.querySelector(".close-button")
    : null;

  if (logoutLink && customModal && confirmBtn && cancelBtn && closeBtn) {
    logoutLink.addEventListener("click", function (event) {
      event.preventDefault();
      customModal.style.display = "block";
    });

    // ** THIS IS THE UPDATED LOGIC **
    // It checks if you are in the admin panel and redirects you correctly.
    confirmBtn.addEventListener("click", function () {
      if (window.location.pathname.includes("/admin/")) {
        window.location.href = "/dailyfix/admin/logout.php";
      } else {
        window.location.href = "/dailyfix/logout.php";
      }
    });

    cancelBtn.addEventListener("click", function () {
      customModal.style.display = "none";
    });

    closeBtn.addEventListener("click", function () {
      customModal.style.display = "none";
    });

    window.addEventListener("click", function (event) {
      if (event.target === customModal) {
        customModal.style.display = "none";
      }
    });
  }

  // Location Dropdown in Header
  const locationToggle = document.getElementById("dashboard-location-toggle");
  const locationDropdown = document.getElementById("location-dropdown");

  if (locationToggle && locationDropdown) {
    locationToggle.addEventListener("click", (event) => {
      event.stopPropagation();
      locationDropdown.classList.toggle("active");
    });

    // Close dropdown if clicked outside
    window.addEventListener("click", (event) => {
      if (
        !locationToggle.contains(event.target) &&
        !locationDropdown.contains(event.target)
      ) {
        locationDropdown.classList.remove("active");
      }
    });
  }

  // Intersection Observer for section fly-in animation
  const flySections = document.querySelectorAll(".section-fly");
  if (flySections.length > 0) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("visible");
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.15 }
    );

    flySections.forEach((section) => observer.observe(section));
  }
  
  // --- Custom Card Slider Logic - V3 (Integrated) ---
  const sliderContainer = document.querySelector(".card-slider-container");

  if (sliderContainer) {
    const cards = sliderContainer.querySelectorAll(".slider-card");
    const prevButton = sliderContainer.querySelector(".slider-arrow.prev");
    const nextButton = sliderContainer.querySelector(".slider-arrow.next");

    if (cards.length === 0) return; // Exit if no cards

    let currentCardIndex = 0;
    const slideInterval = 3500; // 3.5 seconds
    let slideTimer;
    let dots = [];

    // --- Create Navigation Dots ---
    function createDots() {
      const dotsContainer = document.createElement("div");
      dotsContainer.className = "slider-dots";

      cards.forEach((_, index) => {
        const dot = document.createElement("span");
        dot.className = "slider-dot";
        dot.dataset.index = index;

        dot.addEventListener("click", () => {
          showCard(index);
          currentCardIndex = index;
          resetSliderTimer(); // Reset timer on manual dot click
        });

        dotsContainer.appendChild(dot);
        dots.push(dot);
      });

      sliderContainer.appendChild(dotsContainer);
    }

    // --- Show Card Function ---
    function showCard(index) {
      // Update cards
      cards.forEach((card, i) => {
        card.classList.toggle("active", i === index);
      });

      // Update dots
      if (dots.length > 0) {
        dots.forEach((dot, i) => {
          dot.classList.toggle("active", i === index);
        });
      }
    }

    // --- Navigation Functions ---
    function nextCard() {
      currentCardIndex = (currentCardIndex + 1) % cards.length;
      showCard(currentCardIndex);
    }

    function prevCard() {
      currentCardIndex =
        (currentCardIndex - 1 + cards.length) % cards.length;
      showCard(currentCardIndex);
    }

    // --- Timer Controls ---
    function startSlider() {
      stopSlider(); // Clear existing timer
      if (cards.length > 1) {
        slideTimer = setInterval(nextCard, slideInterval);
      }
    }

    function stopSlider() {
      clearInterval(slideTimer);
    }

    function resetSliderTimer() {
      stopSlider();
      startSlider();
    }

    // --- Initialize Slider ---
    if (cards.length > 1) {
      createDots(); // Create dots only if more than 1 card

      // --- NEW: Arrow Button Listeners ---
      if (prevButton && nextButton) {
        nextButton.addEventListener("click", () => {
          nextCard();
          resetSliderTimer(); // Reset timer on manual arrow click
        });

        prevButton.addEventListener("click", () => {
          prevCard();
          resetSliderTimer(); // Reset timer on manual arrow click
        });
      }

      // --- BUG FIX: Use Page Visibility API instead of hover ---
      // This pauses the slider if you change tabs, which is more reliable.
      document.addEventListener("visibilitychange", () => {
        if (document.hidden) {
          stopSlider(); // Pause when tab is hidden
        } else {
          startSlider(); // Resume when tab is visible
        }
      });

      // Start the slider auto-play
      startSlider();
    } // End if (cards.length > 1)

    // Show the first card initially
    showCard(currentCardIndex);
  } // End if (sliderContainer)

  // Basic form validation
  const forms = document.querySelectorAll("form");
  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      const requiredInputs = form.querySelectorAll(
        "input[required], textarea[required]"
      );
      let valid = true;
      requiredInputs.forEach((input) => {
        if (!input.value.trim()) {
          input.style.border = "2px solid red";
          valid = false;
        } else {
          input.style.border = "1px solid #ccc";
        }
      });
      if (!valid) {
        e.preventDefault();
        alert("Please fill out all required fields.");
      }
    });
  });
});