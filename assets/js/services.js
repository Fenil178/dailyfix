// Get all the service cards and the sub-services container
const serviceCards = document.querySelectorAll('.service-card');
const mainServicesContainer = document.querySelector('.main-services-container');
const subServicesContainer = document.querySelector('.sub-services-container');
const subServicesGrid = document.getElementById('sub-services-grid');
const subServiceTitle = document.getElementById('sub-service-title');
const backButton = document.getElementById('back-to-main');

// The subServicesData constant is defined in the services.php file

// Add a click event listener to each main service card
serviceCards.forEach(card => {
    card.addEventListener('click', () => {
        const serviceId = card.dataset.serviceId;
        const serviceName = card.querySelector('h3').textContent;

        if (subServicesData[serviceId] && subServicesData[serviceId].length > 0) {
            subServiceTitle.textContent = serviceName;
            subServicesGrid.innerHTML = '';

            // This is the updated part:
            subServicesData[serviceId].forEach(sub => {
                // 1. Create an ANCHOR <a> tag for the entire card
                const subServiceCard = document.createElement('a');
                
                // 2. Set its href to the find_workers page, using the slug
                subServiceCard.href = `/dailyfix/customer/find_workers.php?service=${sub.slug}`;
                subServiceCard.classList.add('sub-service-card');
                
                // 3. Add the inner content, including the new "Book Now" button
                subServiceCard.innerHTML = `
                    <i class="${sub.icon}"></i>
                    <span>${sub.name}</span>
                    <span class="book-now-btn">Book Now</span>
                `;
                subServicesGrid.appendChild(subServiceCard);
            });

            mainServicesContainer.classList.add('hidden');
            subServicesContainer.classList.remove('hidden');
        } else {
            console.log(`No sub-services found for ${serviceName}`);
        }
    });
});

// Add a click event listener to the "back" button
backButton.addEventListener('click', (e) => {
    e.preventDefault();
    subServicesContainer.classList.add('hidden');
    mainServicesContainer.classList.remove('hidden');
});