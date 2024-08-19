document.addEventListener('DOMContentLoaded', function () {
    const programmeListContainers = document.querySelectorAll('.programme-list-container');

    // Function to synchronize scrolling between program lists
    function syncScroll(container) {
        const scrollLeft = container.scrollLeft;
        programmeListContainers.forEach(otherContainer => {
            if (otherContainer !== container) {
                otherContainer.scrollLeft = scrollLeft;
            }
        });
    }

    // Attach scroll event listeners for synchronized scrolling
    programmeListContainers.forEach(container => {
        container.addEventListener('scroll', function () {
            syncScroll(this);
        });
    });

    // Attach event listeners for popup functionality
    function attachProgrammeListeners() {
        document.querySelectorAll('.programme').forEach(programme => {
            programme.addEventListener('click', function () {
                console.log('Programme clicked:', this.dataset);
            });
        });
    }

    attachProgrammeListeners();

    // Function to update EPG using AJAX
    function updateEPG() {
        console.log('Updating EPG...');
        fetch(modernEpgData.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=update_epg&nonce=' + modernEpgData.nonce
        })
        .then(response => response.json())
        .then(data => {
            console.log('AJAX Response:', data);
            if (data.success) {
                const epgContainer = document.querySelector('.epg-container');
                if (epgContainer) {
                    epgContainer.innerHTML = data.data.html;
                    console.log('EPG HTML updated:', epgContainer.innerHTML);
                    // Reattach event listeners after updating the HTML
                    attachProgrammeListeners();
                    programmeListContainers.forEach(container => {
                        container.removeEventListener('scroll', syncScroll);
                        container.addEventListener('scroll', function () {
                            syncScroll(this);
                        });
                    });
                } else {
                    console.error('EPG container not found in the DOM');
                }
            } else {
                console.error('Error updating EPG:', data.data);
            }
        })
        .catch(error => console.error('Error updating EPG:', error));
    }

    // Update EPG every 5 minutes
    setInterval(updateEPG, 5 * 60 * 1000);

    // Initial EPG update
    updateEPG();
});