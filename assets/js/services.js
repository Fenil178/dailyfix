// Get all the service cards and the sub-services container
const serviceCards = document.querySelectorAll('.service-card');
const mainServicesContainer = document.querySelector('.main-services-container');
const subServicesContainer = document.querySelector('.sub-services-container');
const subServicesGrid = document.getElementById('sub-services-grid');
const subServiceTitle = document.getElementById('sub-service-title');
const backButton = document.getElementById('back-to-main');

// Get the sub-services data that was passed from PHP
// The subServicesData constant is defined in the services.php file
// <script>const subServicesData = ...</script>

// Add a click event listener to each main service card
serviceCards.forEach(card => {
    card.addEventListener('click', () => {
        // Get the service ID from the data attribute
        const serviceId = card.dataset.serviceId;
        const serviceName = card.querySelector('h3').textContent;

        // Check if there are sub-services for this service ID
        if (subServicesData[serviceId] && subServicesData[serviceId].length > 0) {
            // Update the sub-service section title
            subServiceTitle.textContent = serviceName;

            // Clear any previously displayed sub-services
            subServicesGrid.innerHTML = '';

            // Loop through the sub-services for the selected service
            subServicesData[serviceId].forEach(sub => {
                const subServiceCard = document.createElement('a');
                subServiceCard.href = sub.link;
                subServiceCard.classList.add('sub-service-card');
                subServiceCard.innerHTML = `
                    <i class="${sub.icon}"></i>
                    <span>${sub.name}</span>
                `;
                subServicesGrid.appendChild(subServiceCard);
            });

            // Hide the main services and show the sub-services
            mainServicesContainer.classList.add('hidden');
            subServicesContainer.classList.remove('hidden');
        } else {
            // Optional: Handle cases where a main service has no sub-services
            console.log(`No sub-services found for ${serviceName}`);
            // You can add a notification or redirect the user here if needed
        }
    });
});

// Add a click event listener to the "back" button
backButton.addEventListener('click', (e) => {
    e.preventDefault(); // Prevent the link from navigating
    // Hide the sub-services and show the main services again
    subServicesContainer.classList.add('hidden');
    mainServicesContainer.classList.remove('hidden');
});