<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>DailyFix - Your Everyday Expert (One-Page)</title>
  
  <!-- Links to combined CSS from all uploaded files -->
  <link rel="stylesheet" href="./css/index.css" />
  <link rel="stylesheet" href="./css/services.css" />
  <link rel="stylesheet" href="./css/about.css" />
  <link rel="stylesheet" href="./css/contact.css" />
  <link rel="stylesheet" href="./css/login.css" />
  <link rel="stylesheet" href="./css/signup.css" />

  <!-- External Font and Icon Libraries -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body class="light-mode">
  <!-- Navbar -->
  <nav class="navbar">
    <div class="logo">
      <a href="#home"><img src="./img/logo.png" style="width: 50px;" alt="DailyFix Logo" /></a>
    </div>
    <div class="menu-toggle" id="mobile-menu">
      <i class="fas fa-bars"></i>
    </div>
    <ul class="nav-links" id="navLinks">
      <li><a href="#home" class="nav-mo active">Home</a></li>
      <li><a href="#services" class="nav-mo">Services</a></li>
      <li><a href="#about" class="nav-mo">About Us</a></li>
      <li><a href="#contact" class="nav-mo">Help</a></li>
      <li><a href="#login" class="btn-login">Log In</a></li>
    </ul>

    <div class="user-menu" id="userMenu">
    </div>
  </nav>

  <!-- Section 1: Hero -->
  <section class="hero" id="home">
    <div class="hero-bg-carousel">
      <div class="slide active" style="background-image: url('./img/01.jpg');"></div>
      <div class="slide" style="background-image: url('./img/02.jpg');"></div>
      <div class="slide" style="background-image: url('./img/03.jpg');"></div>
      <div class="slide" style="background-image: url('./img/04.jpg');"></div>
    </div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
      <h1>Welcome to DailyFix</h1>
      <h3>Your Everyday Expert</h3>
      <p>
        Your One-Stop Solution For Fast, Reliable, And Affordable Daily Services.
        <br> Whether You Need A Ride, Delivery, Or Support, Our Trusted Professionals Are Here For You 24/7.
        <br> Experience Seamless Booking, Real-Time Tracking, And Top-Nofch Customer Care—All In One Place.
      </p>
      <a href="../login.php" class="btn-main">Get Started</a>
    </div>
  </section>


  <!-- Section 3: Full Services List -->
  <section id="services" class="page-header">
    <h1>Our Services</h1>
    <p>Select from our range of expert household help</p>
  </section>
  <section id="main-services-grid" class="services-grid section-fly">
    <div class="service-card" data-service-name="Cleaning">
      <i class="fas fa-broom"></i>
      <h3>Cleaning</h3>
      <button>Book Now</button>
    </div>
    <div class="service-card" data-service-name="Cooking">
      <i class="fas fa-utensils"></i>
      <h3>Cooking</h3>
      <button>Book Now</button>
    </div>
    <div class="service-card" data-service-name="Electrician">
      <i class="fas fa-bolt"></i>
      <h3>Electrician</h3>
      <button>Book Now</button>
    </div>
    <div class="service-card" data-service-name="Plumber">
      <i class="fas fa-tools"></i>
      <h3>Plumber</h3>
      <button>Book Now</button>
    </div>
    <div class="service-card" data-service-name="Driver">
      <i class="fas fa-car"></i>
      <h3>Driver</h3>
      <button>Book Now</button>
    </div>
    <div class="service-card" data-service-name="Watchman">
      <i class="fas fa-user-shield"></i>
      <h3>Watchman</h3>
      <button>Book Now</button>
    </div>
    <div class="service-card" data-service-name="Gardener">
      <i class="fas fa-seedling"></i>
      <h3>Gardener</h3>
      <button>Book Now</button>
    </div>
    <div class="service-card" data-service-name="Carpenter">
      <i class="fas fa-hammer"></i>
      <h3>Carpenter</h3>
      <button>Book Now</button>
    </div>
    <div class="service-card" data-service-name="Packers And Movers">
      <i class="fas fa-truck"></i>
      <h3>Packers And Movers</h3>
      <button>Book Now</button>
    </div>
  </section>
  
  
  <!-- Section 4: Features -->
  <section class="features section-fly">
    <h2>Why Choose DailyFix?</h2>
    <div class="feature-row">
      <div class="feature-img">
        <img src="./img/1.jpg" alt="Verified Worker Profiles">
      </div>
      <div class="feature-info">
        <h2>Verified Worker Profiles</h2>
        <p>Each Worker Has A Verified Profiles With Details Such As Name, Photo, Skill Set, ID Proof, Experience, And Service Area To Build Trust With Customers.</p>
      </div>
    </div>
    <div class="feature-row reverse">
      <div class="feature-img">
        <img src="./img/2.jpg" alt="Affordable Pricing">
      </div>
      <div class="feature-info">
        <h2>Affordable Pricing</h2>
        <p>Enjoy Competitive Rates And Transparent Pricing With No Hidden Fees, Anytime.</p>
      </div>
    </div>
    <div class="feature-row">
      <div class="feature-img">
        <img src="./img/3.jpg" alt="24/7 Support">
      </div>
      <div class="feature-info">
        <h2>24/7 Customer Support</h2>
        <p>Our Support Team Is Always Available To Help You With Any Questions Or Concerns.</p>
      </div>
    </div>
  </section>

  <!-- Section 5: About Us -->
  <section id="about">
    <section class="about-hero">
      <h1>About DailyFix</h1>
      <p>Your Everyday Expert – Built for Every Home</p>
    </section>
    
    <section class="about-section section-fly">
      <div class="about-card-grid">
        <div class="about-card">
          <h2>Who We Are</h2>
          <p>
            DailyFix is your everyday solution for household services — a modern,
            local-first platform connecting people with verified workers for daily
            tasks. Whether it's a last-minute plumbing fix or a long-term cook, we
            match you with trusted professionals in your neighborhood.
          </p>
        </div>
        <div class="about-card">
          <h2>What We Do</h2>
          <p>
            We simplify the process of finding help for your home. Our digital
            platform allows you to browse, compare, and book local workers based on
            ratings, availability, and skill — all from your phone or desktop. With
            DailyFix, getting help is just a few clicks away.
          </p>
        </div>
        <div class="about-card about-card-center">
            <i class="fas fa-bullseye"></i>
            <h3>Our Mission</h3>
            <p>
              To empower households with easy access to reliable services, and uplift
              skilled local workers by opening new income opportunities — all while
              fostering safety, trust, and simplicity.
            </p>
        </div>
        <div class="about-card about-card-center">
            <i class="fas fa-eye"></i>
            <h3>Our Vision</h3>
            <p>
              We envision a future where household help is seamless, secure, and
              accessible to every family, everywhere. DailyFix stands for dignity in
              work and comfort at home.
            </p>
        </div>
      </div>
    </section>
    
    <section class="team-section section-fly">
      <h2 class="team-title">Meet Our Team</h2>
      <div class="team-list">
        <div class="team-member">
          <img
            src="https://img.icons8.com/color/96/000000/manager--v1.png"
            alt="Meet Patel" />
          <h3>Meet Patel</h3>
          <p>Backend Developer & Database Manager</p>
        </div>
        <div class="team-member">
          <img
            src="https://img.icons8.com/color/96/000000/user-male-circle--v2.png"
            alt="Fenil Pastagia" />
          <h3>Fenil Pastagia</h3>
          <p>Backend Developer</p>
        </div>
        <div class="team-member">
          <img
            src="https://img.icons8.com/color/96/000000/user-male-circle--v1.png"
            alt="Jay Parmar" />
          <h3>Jay Parmar</h3>
          <p>Frontend Developer</p>
        </div>
      </div>
    </section>

    

  <!-- Section 6: Contact & FAQ -->
  <section id="contact" class="page-header">
    <h1>Help & Contact</h1>
    <p>Need assistance? We’re here to help!</p>
  </section>
  <section class="contact-section section-fly">
    <div class="contact-form">
      <h2>Contact Us</h2>
      <form action="#" method="post">
        <input type="text" name="name" placeholder="Your Name" required />
        <input type="email" name="email" placeholder="Your Email" required />
        <textarea name="message" placeholder="Your Message..." required></textarea>
        <button type="submit">Send Message</button>
      </form>
    </div>
    <div class="faq-section">
      <h2>Frequently Asked Questions</h2>
      <details>
        <summary>How do I book a service?</summary>
        <p>
          Simply go to the Services section, choose your service, and click "Book
          Now". Log in or sign up if prompted.
        </p>
      </details>
      <details>
        <summary>Can I cancel a booking?</summary>
        <p>
          Yes. After booking, you can cancel from your dashboard up to 1 hour
          before service time.
        </p>
      </details>
      <details>
        <summary>Are the workers verified?</summary>
        <p>
          All workers go through a thorough background and ID check before
          being listed.
        </p>
      </details>
    </div>
  </section>

  

  <!-- Section 9: Call to Action -->
  <section class="cta section-fly">
    <h2>Ready To Book Your Service?</h2>
    <p>Experience The Convenience Of DailyFix Today!</p>
    <a href="#services" class="btn-main">Book Now</a>
  </section>

  <!-- Footer -->
  <footer>
    <p>&copy; 2025 DailyFix. All Rights Reserved.</p>
    <div class="social-icons">
      <a href="mailto:jayrajparmar1509@gmail.com" title="Email"><i class="fas fa-envelope"></i></a>
      <a href="https://www.linkedin.com/in/jay-parmar-106195295/" target="_blank" title="LinkedIn"><i class="fab fa-linkedin"></i></a>
      <a href="https://x.com/jayraj1509" target="_blank" title="X (Twitter)"><i class="fab fa-x-twitter"></i></a>
      <a href="https://github.com/Jayraj1509" target="_blank" title="GitHub"><i class="fab fa-github-alt"></i></a>
      <a href="https://www.instagram.com/_jayrajsinh_parmar_/" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
    </div>
  </footer>
  
  <script src="./js/app.js"></script>

  <!-- New script to handle dynamic sub-services on one_page.php -->
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const mainServicesGrid = document.getElementById('main-services-grid');
      
      const mainServiceCards = document.querySelectorAll('#main-services-grid .service-card');
      const mainServiceHTML = mainServicesGrid.innerHTML; // Store the original HTML

      // Hardcoded sub-services data
      const subServicesData = {
        "Driver": [
          { name: "Car Driver", icon: "fas fa-car" },
          { name: "Truck Driver", icon: "fas fa-truck" },
        ],
        "Cleaning": [
          { name: "House Cleaning", icon: "fas fa-house" },
          { name: "Car Cleaning", icon: "fas fa-car-wash" },
        ],
        "Electrician": [
          { name: "Wiring", icon: "fas fa-bolt" },
          { name: "Appliance Repair", icon: "fas fa-wrench" },
        ],
        "Plumber": [
          { name: "Pipe Fixing", icon: "fas fa-tools" },
          { name: "Drain Cleaning", icon: "fas fa-toilet-paper" },
        ],
        "Cooking": [
          { name: "Full-time Chef", icon: "fas fa-user-chef" },
          { name: "Party Catering", icon: "fas fa-birthday-cake" },
        ],
        "Watchman": [
          { name: "Day Watchman", icon: "fas fa-sun" },
          { name: "Night Watchman", icon: "fas fa-moon" },
        ],
        "Gardener": [
          { name: "Lawn Mowing", icon: "fas fa-grass" },
          { name: "Planting", icon: "fas fa-seedling" },
        ],
        "Carpenter": [
          { name: "Furniture Repair", icon: "fas fa-hammer" },
          { name: "Custom Woodwork", icon: "fas fa-ruler-combined" },
        ],
        "Packers And Movers": [
          { name: "Home Moving", icon: "fas fa-house-suitcase" },
          { name: "Office Moving", icon: "fas fa-building" },
        ]
      };

      // Function to show sub-services for a given service name
      function showSubServices(serviceName) {
        mainServicesGrid.innerHTML = ''; // Clear the current grid

        // Add a back button
        const backButtonHtml = `
          <a href="#" id="back-to-main" class="back-link" style="
            grid-column: 1 / -1; 
            margin-bottom: 20px; 
            display: inline-block; 
            text-decoration: none; 
            color: #007bff;
            font-weight: 500;
          ">
            <i class="fas fa-arrow-left"></i> Back to Main Services
          </a>
          <h2 style="grid-column: 1 / -1; text-align: center;">Sub-services for ${serviceName}</h2>
        `;
        mainServicesGrid.innerHTML = backButtonHtml;
        
        // Add sub-service cards
        const subServices = subServicesData[serviceName];
        if (subServices) {
          subServices.forEach(subService => {
            const subServiceCard = document.createElement('div');
            subServiceCard.classList.add('service-card');
            subServiceCard.innerHTML = `
              <i class="${subService.icon}"></i>
              <h3>${subService.name}</h3>
              <button>Book Now</button>
            `;
            mainServicesGrid.appendChild(subServiceCard);
          });
        }

        // Add event listener for the new back button
        document.getElementById('back-to-main').addEventListener('click', function(e) {
          e.preventDefault();
          mainServicesGrid.innerHTML = mainServiceHTML;
          // Re-attach listeners to the new main service cards
          addMainServiceCardListeners();
        });
      }

      // Function to add click listeners to main service cards
      function addMainServiceCardListeners() {
        document.querySelectorAll('#main-services-grid .service-card').forEach(card => {
          card.addEventListener('click', function(e) {
            // Check if the clicked element is not a button
            if (e.target.tagName !== 'BUTTON') {
              const serviceName = card.dataset.serviceName;
              showSubServices(serviceName);
            }
          });
        });
      }

      // Initial call to set up listeners for the default cards
      addMainServiceCardListeners();
    });
  </script>
</body>

</html>
