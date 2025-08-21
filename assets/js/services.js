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
                // 1. Create a DIV for the card, which is not a link.
                const subServiceCard = document.createElement('div');
                subServiceCard.classList.add('sub-service-card');
                
                // 2. Create the inner content (icon and name).
                const cardContent = `
                    <i class="${sub.icon}"></i>
                    <span>${sub.name}</span>
                `;
                
                // 3. Create a separate <a> tag specifically for the button.
                const bookButton = document.createElement('a');
                bookButton.href = `/dailyfix/customer/find_workers.php?service=${sub.slug}`;
                bookButton.classList.add('book-now-btn');
                bookButton.textContent = 'Book Now';

                // 4. Add the content and the button to the card.
                subServiceCard.innerHTML = cardContent;
                subServiceCard.appendChild(bookButton);
                
                // 5. Add the final card to the grid.
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