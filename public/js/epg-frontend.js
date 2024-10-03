(function() {
    let debugMode = true; // Set to true for debugging
    let attempts = 0;
    const maxAttempts = 20; // Increase max attempts

    function logDebug(...args) {
        if (debugMode) {
            console.log('[EPG Debug]', ...args);
        }
    }

    // Move switchKodiChannel inside the IIFE
    function switchKodiChannel(channelId) {
        logDebug('Switching to Kodi channel:', channelId);
        
        fetch(modernEpgData.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'epg_action',
                epg_action: 'switch_kodi_channel',
                nonce: modernEpgData.nonce,
                channel_id: channelId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                logDebug('Successfully switched Kodi channel:', data.data.message);
            } else {
                console.error('Error switching Kodi channel:', data.data.message);
            }
        })
        .catch(error => {
            console.error('Error switching Kodi channel:', error);
        });
    }

    function initializeEPG() {
        logDebug('Initializing EPG');
        attachListeners();
        scrollToCurrentTime();
        applyStoredFilter();
    }

    function waitForEpgContainer() {
        const outerContainer = document.getElementById('modern-epg-container');
        const innerContainer = document.getElementById('epg-container');
        logDebug('Attempting to find EPG containers, attempt:', attempts + 1);

        if (outerContainer && innerContainer) {
            logDebug('EPG containers found:', outerContainer, innerContainer);
            initializeEPG();
        } else if (attempts < maxAttempts) {
            attempts++;
            setTimeout(waitForEpgContainer, 500);
        } else {
            console.error('EPG containers not found after', maxAttempts, 'attempts');
            logDebug('Outer container:', outerContainer);
            logDebug('Inner container:', innerContainer);
            logDebug('Document body HTML:', document.body.innerHTML);
        }
    }

    function documentReady(fn) {
        if (document.readyState === "complete" || document.readyState === "interactive") {
            setTimeout(fn, 1);
        } else {
            document.addEventListener("DOMContentLoaded", fn);
        }
    }

    documentReady(function() {
        logDebug('Document ready, starting EPG initialization');
        waitForEpgContainer();
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
                filterChannels(group);

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
                const kodiChannelId = this.dataset.kodiChannelId;
                const channelName = this.dataset.channelName;
                
                if (kodiChannelId) {
                    console.log(`Switching to channel: ${channelName} (Kodi ID: ${kodiChannelId})`);
                    switchKodiChannel(kodiChannelId);
                } else {
                    console.log(`No Kodi channel ID available for ${channelName}`);
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
        const storedGroup = localStorage.getItem('selectedEpgGroup') || 'all';
        filterChannels(storedGroup);
        const button = document.querySelector(`.group-filter[data-group="${storedGroup}"]`);
        if (button) {
            button.classList.add('active');
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
                    initializeEPG();
                    filterChannels(currentGroup); // Re-apply the filter
                    updateActiveGroupButton(currentGroup); // Update the active button
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
        const buttons = document.querySelectorAll('.group-filter');
        buttons.forEach(button => {
            if (button.dataset.group === group) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        });
    }

    function adjustProgramBoxes() {
        const now = new Date();
        const programBoxes = document.querySelectorAll('.programme');
        const gridStartTime = new Date(now);
        gridStartTime.setMinutes(0, 0, 0);

        programBoxes.forEach(box => {
            const startTime = new Date(box.dataset.startTime);
            const endTime = new Date(box.dataset.endTime);

            // Calculate position and width
            const leftPosition = (startTime - gridStartTime) / (3600 * 1000) * (100 / 3);
            const width = (endTime - startTime) / (3600 * 1000) * (100 / 3);

            box.style.left = `${leftPosition}%`;
            box.style.width = `${width}%`;

            // Highlight current program
            if (now >= startTime && now < endTime) {
                box.classList.add('current-program');
            } else {
                box.classList.remove('current-program');
            }
        });

        // Remove this line to reduce logging
        // logDebug('Adjusted program boxes at', now);
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

    function filterChannels(group) {
        const epgContainer = document.getElementById('epg-container');
        if (!epgContainer) {
            console.error('[EPG Error] EPG container not found');
            return;
        }

        const channels = epgContainer.querySelectorAll('.channel');
        let visibleCount = 0;

        channels.forEach(channel => {
            const channelGroup = channel.dataset.group;
            const channelNumber = channel.dataset.channelNumber;
            logDebug(`Channel: ${channelNumber}, Group: ${channelGroup}`);

            if (group.toLowerCase() === 'all' || channelGroup.toLowerCase() === group.toLowerCase()) {
                channel.style.display = '';
                visibleCount++;
            } else {
                channel.style.display = 'none';
            }
        });

        logDebug('Visible channels after filtering:', visibleCount);
        logDebug('Selected group:', group);

        // Store the selected group
        localStorage.setItem('selectedEpgGroup', group);
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

    function initEPG() {
        console.log('[EPG Debug] Initializing EPG');
        const epgContainer = document.getElementById('modern-epg-container');
        if (!epgContainer) {
            console.error('[EPG Error] EPG container not found');
            return;
        }
        // ... rest of your initialization code ...
    }

    // Call initEPG when the DOM is ready
    document.addEventListener('DOMContentLoaded', initEPG);

    document.addEventListener('DOMContentLoaded', function() {
        const channelLinks = document.querySelectorAll('.channel-link');
        
        channelLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const kodiChannelId = this.dataset.kodiChannelId;
                const channelName = this.dataset.channelName;
                
                if (kodiChannelId && kodiChannelId !== 'undefined') {
                    console.log(`Switching to channel: ${channelName} (Kodi ID: ${kodiChannelId})`);
                    switchKodiChannel(kodiChannelId);
                } else {
                    console.log(`No Kodi channel ID available for ${channelName}`);
                }
            });
        });
    });

    function switchKodiChannel(channelId) {
        fetch(modernEpgData.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'epg_action',
                epg_action: 'switch_kodi_channel',
                nonce: modernEpgData.nonce,
                channel_id: channelId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Successfully switched Kodi channel:', data.data.message);
            } else {
                console.error('Error switching Kodi channel:', data.data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
})();