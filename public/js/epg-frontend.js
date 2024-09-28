let debugMode = false;

function logDebug(...args) {
    if (debugMode) {
        console.log(...args);
    }
}

function initializeEPG() {
    logDebug('Initializing EPG');
    attachListeners();
    scrollToCurrentTime();
    applyStoredFilter();
}

document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM fully loaded');
    console.log('EPG container:', document.getElementById('modern-epg-container'));
    
    const channelLinks = document.querySelectorAll('.channel-link');
    console.log('Found ' + channelLinks.length + ' channel links');
    
    if (channelLinks.length === 0) {
        console.log('EPG HTML structure:', document.getElementById('modern-epg-container').innerHTML);
    }
    
    initializeEPG();
    
    // Set up intervals
    setInterval(updateEPG, 3 * 60 * 1000); // Every 3 minutes
    setInterval(adjustProgramBoxes, 60 * 1000); // Every minute
});

function attachListeners() {
    logDebug('Attaching listeners');

    // Category filter buttons
    const groupFilters = document.querySelectorAll('.group-filter');
    logDebug('Found ' + groupFilters.length + ' group filter buttons');
    groupFilters.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const group = this.dataset.group;
            logDebug('Group filter clicked:', group);
            filterChannelsByGroup(group);
            
            // Add active class to clicked button and remove from others
            groupFilters.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Channel buttons
    const channelLinks = document.querySelectorAll('.channel-link');
    logDebug('Found ' + channelLinks.length + ' channel links');
    channelLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const channelName = this.dataset.channelName;
            const kodiChannelId = this.dataset.kodiChannelId;
            logDebug('Channel link clicked:', channelName, 'Kodi Channel ID:', kodiChannelId);
            if (kodiChannelId && kodiChannelId !== "") {
                switchKodiChannel(kodiChannelId);
            } else {
                logDebug('No Kodi Channel ID available for this channel:', channelName);
            }
        });
    });

    // Add click event listeners to programs
    document.querySelectorAll('.programme').forEach(program => {
        program.addEventListener('click', showProgramDetails);
    });

    // Add scroll synchronization to programme list containers
    const programmeListContainers = document.querySelectorAll('.programme-list-container');
    programmeListContainers.forEach(container => {
        container.addEventListener('scroll', function () {
            syncScroll(this);
        });
    });
}

function syncScroll(scrolledContainer) {
    const containers = document.querySelectorAll('.programme-list-container');
    containers.forEach(container => {
        if (container !== scrolledContainer) {
            container.scrollLeft = scrolledContainer.scrollLeft;
        }
    });
}

function applyStoredFilter() {
    const storedGroup = localStorage.getItem('selectedEpgGroup');
    if (storedGroup) {
        filterChannelsByGroup(storedGroup);
        const button = document.querySelector(`.group-filter[data-group="${storedGroup}"]`);
        if (button) {
            button.classList.add('active');
        }
    }
}

function updateEPG() {
    logDebug('updateEPG called at', new Date());
    const currentGroup = localStorage.getItem('selectedEpgGroup') || 'all';
    logDebug('Current group before update:', currentGroup);

    fetch(modernEpgData.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'update_epg',
            nonce: modernEpgData.nonce,
            group: currentGroup // Send the current group to the server
        })
    })
    .then(response => response.json())
    .then(data => {
        logDebug('EPG update response:', data);
        if (data.success) {
            const epgContainer = document.getElementById('modern-epg-container');
            if (epgContainer) {
                epgContainer.innerHTML = data.data.html;
                logDebug('EPG HTML updated');
                initializeEPG(); // This will reattach all listeners
                // Remove these lines as initializeEPG will handle them
                // filterChannelsByGroup(currentGroup);
                // updateActiveGroupButton(currentGroup);
            } else {
                logDebug('EPG container not found in the DOM');
            }
        } else {
            console.error('Error updating EPG:', data.data);
        }
    })
    .catch(error => console.error('Error updating EPG:', error));
}

function updateActiveGroupButton(group) {
    logDebug('Updating active group button:', group);
    const buttons = document.querySelectorAll('.group-filter');
    buttons.forEach(button => {
        if (button.dataset.group.toLowerCase() === group.toLowerCase()) {
            button.classList.add('active');
            logDebug('Activated button:', button.dataset.group);
        } else {
            button.classList.remove('active');
        }
    });
}

function adjustProgramBoxes() {
    const now = new Date();
    const programBoxes = document.querySelectorAll('.programme');
    const gridStartTime = new Date(now);
    gridStartTime.setHours(0, 0, 0, 0);

    programBoxes.forEach(box => {
        const startTime = new Date(box.dataset.startTime);
        const endTime = new Date(box.dataset.endTime);
        
        const startColumn = Math.floor((startTime - gridStartTime) / (5 * 60 * 1000)) + 2; // +2 because grid starts at column 2
        const endColumn = Math.floor((endTime - gridStartTime) / (5 * 60 * 1000)) + 2;
        const span = endColumn - startColumn;

        box.style.gridColumn = `${startColumn} / span ${span}`;
    });
}

function scrollToCurrentTime() {
    const now = new Date();
    const startOfHour = new Date(now.getFullYear(), now.getMonth(), now.getDate(), now.getHours(), 0, 0);
    const minutesPassed = (now - startOfHour) / (1000 * 60);
    const scrollPercentage = (minutesPassed / (3 * 60)) * 100;

    const programmeListContainers = document.querySelectorAll('.programme-list-container');
    programmeListContainers.forEach(container => {
        const scrollPosition = (container.scrollWidth * scrollPercentage) / 100;
        const containerWidth = container.clientWidth;
        // Adjust by 50% of the container width (25% + 25%)
        const adjustedScrollPosition = Math.max(0, scrollPosition - containerWidth * 0.5);
        container.scrollLeft = adjustedScrollPosition;
    });
}

function filterChannelsByGroup(group) {
    logDebug('Filtering channels by group:', group);
    const channels = document.querySelectorAll('.channel');
    let visibleCount = 0;
    channels.forEach(channel => {
        const channelGroup = (channel.dataset.group || '').toLowerCase();
        const filterGroup = group.toLowerCase();
        logDebug('Channel:', channel.dataset.channelNumber, 'Group:', channelGroup);
        if (filterGroup === 'all' || channelGroup === filterGroup) {
            channel.style.display = '';
            visibleCount++;
        } else {
            channel.style.display = 'none';
        }
    });
    logDebug('Visible channels after filtering:', visibleCount);
    
    // Store the selected category in localStorage
    localStorage.setItem('selectedEpgGroup', group);
    logDebug('Stored selected group:', group);
}

function showProgramDetails(event) {
    logDebug('showProgramDetails called', event);
    const program = event.currentTarget;
    logDebug('Program data:', program.dataset);
    const title = program.dataset.title;
    const startTime = new Date(program.dataset.startTime).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    const endTime = new Date(program.dataset.endTime).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    const description = program.dataset.description || 'No description available.';

    const popupHTML = `
        <div class="popup-overlay">
            <div class="popup">
                <span class="close-popup">&times;</span>
                <h3>${title}</h3>
                <p><strong>Time:</strong> ${startTime} - ${endTime}</p>
                <p>${description}</p>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', popupHTML);

    // Add event listener to close button
    document.querySelector('.close-popup').addEventListener('click', closePopup);
    document.querySelector('.popup-overlay').addEventListener('click', function(e) {
        if (e.target === this) {
            closePopup();
        }
    });
}

function closePopup() {
    const popup = document.querySelector('.popup-overlay');
    if (popup) {
        popup.remove();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded');

    const settingsButton = document.getElementById('epg-settings-button');
    const settingsOverlay = document.getElementById('epg-settings-overlay');
    const settingsForm = document.getElementById('epg-settings-form');
    const closeButton = document.getElementById('epg-settings-close');

    console.log('Settings button:', settingsButton);
    console.log('Settings overlay:', settingsOverlay);
    console.log('Settings form:', settingsForm);
    console.log('Close button:', closeButton);

    if (settingsButton && settingsOverlay && settingsForm && closeButton) {
        console.log('All required elements found');

        settingsButton.addEventListener('click', function(e) {
            console.log('Settings button clicked');
            e.preventDefault();
            settingsOverlay.style.display = 'block';
            console.log('Overlay display style:', settingsOverlay.style.display);
        });

        closeButton.addEventListener('click', function(e) {
            console.log('Close button clicked');
            e.preventDefault();
            settingsOverlay.style.display = 'none';
            console.log('Overlay display style:', settingsOverlay.style.display);
        });

        settingsOverlay.addEventListener('click', function(e) {
            if (e.target === settingsOverlay) {
                console.log('Overlay background clicked');
                settingsOverlay.style.display = 'none';
                console.log('Overlay display style:', settingsOverlay.style.display);
            }
        });

        settingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(settingsForm);
            formData.append('action', 'save_epg_settings');
            formData.append('nonce', modernEpgData.nonce);

            fetch(modernEpgData.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Settings saved successfully!');
                    settingsOverlay.style.display = 'none';
                } else {
                    alert('Error saving settings: ' + data.data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving settings.');
            });
        });
    } else {
        console.error('One or more required elements are missing');
    }
});

// Log any JavaScript errors
window.onerror = function(message, source, lineno, colno, error) {
    console.error('JavaScript error:', message, 'at', source, lineno, colno);
    console.error('Error object:', error);
};

document.addEventListener('DOMContentLoaded', function() {
    const epgContainer = document.getElementById('modern-epg-container');
    const currentTime = new Date();
    const scrollPosition = (currentTime.getHours() * 60 + currentTime.getMinutes() - 30) / (3 * 60) * epgContainer.scrollWidth;
    epgContainer.scrollLeft = scrollPosition;

    // Add other event listeners and functionality as needed
});