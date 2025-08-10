// Handles mobile menu toggle functionality
document.addEventListener("DOMContentLoaded", () => {
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
});

// Handles the hero section carousel
document.addEventListener("DOMContentLoaded", () => {
  const slides = document.querySelectorAll(".hero-bg-carousel .slide");
  let current = 0;

  setInterval(() => {
    slides[current].classList.remove("active");
    current = (current + 1) % slides.length;
    slides[current].classList.add("active");
  }, 5000); // 5 seconds
});

// Handles the theme toggle functionality
document.addEventListener("DOMContentLoaded", () => {
  const themeToggle = document.getElementById("theme-toggle");
  const icon = themeToggle.querySelector("i");

  // Load saved theme
  const currentTheme = localStorage.getItem("theme");
  if (currentTheme === "dark") {
    document.body.classList.add("dark-mode");
    icon.classList.remove("fa-moon");
    icon.classList.add("fa-sun");
  }

  themeToggle.addEventListener("click", () => {
    document.body.classList.toggle("dark-mode");
    const isDark = document.body.classList.contains("dark-mode");
    icon.classList.toggle("fa-moon", !isDark);
    icon.classList.toggle("fa-sun", isDark);
    localStorage.setItem("theme", isDark ? "dark" : "light");
  });
});

// Toggles login/signup role form (if applicable)
document.addEventListener("DOMContentLoaded", function () {
  const roleToggles = document.querySelectorAll(".role-toggle");
  const roleInputs = document.querySelectorAll(".role-input");

  roleToggles.forEach((toggle) => {
    toggle.addEventListener("click", () => {
      roleToggles.forEach((el) => el.classList.remove("active-role"));
      toggle.classList.add("active-role");
      const role = toggle.dataset.role;

      roleInputs.forEach((input) => {
        if (input.classList.contains(role)) {
          input.style.display = "block";
        } else {
          input.style.display = "none";
        }
      });
    });
  });

  // Basic form validation (email + required)
  const forms = document.querySelectorAll("form");
  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      const requiredInputs = form.querySelectorAll("input[required], textarea[required]");
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
        // Replace alert with a custom message box or display an error on the page
        console.error("Please fill out all required fields.");
      }
    });
  });
});

// Handles the "fly-in" animation for sections as they come into view
document.addEventListener("DOMContentLoaded", function() {
  const flySections = document.querySelectorAll('.section-fly');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });

  flySections.forEach(section => observer.observe(section));
});

// NEW CODE: Handles the dynamic active state of the navigation links
document.addEventListener("DOMContentLoaded", function () {
  const navLinks = document.querySelectorAll('.nav-mo');

  function updateActiveLink() {
    let current = '';
    const sections = document.querySelectorAll('section');

    sections.forEach(section => {
      const sectionTop = section.offsetTop;
      const sectionHeight = section.clientHeight;
      if (pageYOffset >= (sectionTop - sectionHeight / 3)) {
        current = section.getAttribute('id');
      }
    });

    navLinks.forEach(link => {
      link.classList.remove('active');
      if (link.href.includes(current)) {
        link.classList.add('active');
      }
    });
  }

  // Initial call to set the active link on page load
  updateActiveLink();
  // Event listener to update the active link on scroll
  window.addEventListener('scroll', updateActiveLink);
  // Event listener for clicks on navigation links
  navLinks.forEach(link => {
    link.addEventListener('click', () => {
      // Small delay to let the scroll complete before updating
      setTimeout(updateActiveLink, 50);
    });
  });
});
