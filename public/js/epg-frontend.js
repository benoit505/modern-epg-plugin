console.log('EPG Frontend JS loaded');

(function() {
    // Configuration
    const config = {
        debugMode: true,
        maxAttempts: 20,
        refreshInterval: modernEpgData.refresh_interval || 300000 // Use the value from PHP, or default to 5 minutes
    };

    // Log the refresh interval when the script loads
    console.log(`[EPG Debug] Initial refresh interval: ${config.refreshInterval / 1000} seconds (${config.refreshInterval} ms)`);

    // Utility functions
    function logDebug(...args) {
        if (config.debugMode) console.log('[EPG Debug]', ...args);
    }

    function switchKodiChannel(channelId) {
        console.log('[EPG Debug] Switching to Kodi channel:', channelId);
        
        const data = new FormData();
        data.append('action', 'epg_action');
        data.append('epg_action', 'switch_kodi_channel');
        data.append('nonce', modernEpgData.nonce);
        data.append('channel_id', channelId);
        
        console.log('[EPG Debug] Sending data:', Object.fromEntries(data));
        
        fetch(modernEpgData.ajax_url, {
            method: 'POST',
            body: data
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('[EPG Debug] Successfully switched Kodi channel:', data.data.message);
            } else {
                console.error('[EPG Error] Error switching Kodi channel:', data.data.message);
            }
        })
        .catch(error => {
            console.error('[EPG Error] Error switching Kodi channel:', error);
        });
    }

    function updateEPG() {
        console.log('updateEPG called at', new Date().toISOString());
        const currentGroup = localStorage.getItem('selectedEpgGroup') || 'all';
        console.log('Current group before update:', currentGroup);

        fetch(modernEpgData.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'epg_action',
                epg_action: 'update_epg',
                nonce: modernEpgData.nonce,
                group: currentGroup
            })
        })
        .then(response => {
            console.log('EPG update response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('EPG data received:', JSON.stringify(data, null, 2));
            if (data.success && !data.html.startsWith('Error:')) {
                console.log('EPG update successful, updating grid');
                updateEPGGrid(data.html);
            } else {
                console.error('Error updating EPG:', data.html || data.message || 'Unknown error');
                console.log('Debug info:', data.debug);
            }
        })
        .catch(error => {
            console.error('Error fetching EPG data:', error);
        });
    }

    function displayEPGData(epgData) {
        console.log('Displaying EPG data');
        const epgContainer = document.querySelector('.epg-container');
        if (epgContainer) {
            // Create a temporary container
            const tempContainer = document.createElement('div');
            tempContainer.innerHTML = epgData.html;
            
            // Find the new EPG content
            const newEpgContent = tempContainer.querySelector('#modern-epg-wrapper');
            
            if (newEpgContent) {
                // Replace the existing content
                epgContainer.innerHTML = '';
                epgContainer.appendChild(newEpgContent);
            } else {
                console.error('New EPG content not found in the received data');
            }
        } else {
            console.error('EPG container not found');
        }
    }

    function reattachEventListeners() {
        console.log('Reattaching event listeners');
        // Remove existing listeners
        document.querySelectorAll('.group-filter, .channel-link, .programme').forEach(el => {
            el.replaceWith(el.cloneNode(true));
        });
        
        // Reattach listeners
        attachListeners();
        setupEventListeners();
    }

    function documentReady(fn) {
        if (document.readyState === "complete" || document.readyState === "interactive") {
            setTimeout(fn, 1);
        } else {
            document.addEventListener("DOMContentLoaded", fn);
        }
    }

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

    function updateActiveGroupButton(group) {
        document.querySelectorAll('.group-filter').forEach(button => {
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
        logDebug('Filtering channels for group:', group);
        const channels = document.querySelectorAll('.channel');
        let visibleCount = 0;

        channels.forEach(channel => {
            const channelGroup = channel.dataset.group;
            if (group === 'all' || channelGroup === group) {
                channel.style.display = '';
                visibleCount++;
            } else {
                channel.style.display = 'none';
            }
        });

        logDebug('Visible channels after filtering:', visibleCount);
        localStorage.setItem('selectedEpgGroup', group);
    }

    function showProgramDetails(event) {
        const program = event.currentTarget;
        const popupContent = `
            <div class="popup">
                <span class="close-popup">&times;</span>
                <h3>${program.dataset.title}</h3>
                <p><strong>Time:</strong> ${new Date(program.dataset.startTime).toLocaleString()} - ${new Date(program.dataset.endTime).toLocaleString()}</p>
                <p>${program.dataset.description}</p>
            </div>
        `;

        const popupOverlay = document.createElement('div');
        popupOverlay.className = 'popup-overlay';
        popupOverlay.innerHTML = popupContent;

        document.body.appendChild(popupOverlay);

        const closePopup = () => popupOverlay.remove();
        popupOverlay.querySelector('.close-popup').addEventListener('click', closePopup);
        popupOverlay.addEventListener('click', (e) => {
            if (e.target === popupOverlay) closePopup();
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
        const epgWrapper = document.getElementById('modern-epg-wrapper');
        if (!epgWrapper) {
            console.error('[EPG Error] EPG wrapper not found');
            return;
        }
        
        const epgContainer = epgWrapper.querySelector('.epg-container');
        if (!epgContainer) {
            console.error('[EPG Error] EPG container not found within wrapper');
            return;
        }
        
        const kodiOnline = epgWrapper.dataset.kodiOnline === 'true';
        logDebug('Kodi online status:', kodiOnline);

        if (!kodiOnline) {
            console.log('Kodi is offline. Some features may be limited.');
            disableKodiDependentFeatures();
        } else {
            enableChannelSwitching();
        }

        setupEventListeners();
        const storedGroup = localStorage.getItem('selectedEpgGroup') || 'all';
        filterChannels(storedGroup);
        updateActiveGroupButton(storedGroup);
        adjustProgramBoxes();
        scrollToCurrentTime();
        setupEPGRefresh();
        attachProgramClickListeners();
    }

    function disableKodiDependentFeatures() {
        // Disable channel switching buttons or other Kodi-dependent features
        const channelLinks = document.querySelectorAll('.channel-link');
        channelLinks.forEach(link => {
            link.classList.add('disabled');
            link.addEventListener('click', (e) => e.preventDefault());
        });
    }

    function enableChannelSwitching() {
        document.querySelectorAll('.channel-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const channelId = this.dataset.kodiChannelId;
                if (channelId) {
                    switchKodiChannel(channelId);
                }
            });
        });
    }

    function setupEventListeners() {
        console.log('Setting up event listeners');
        document.querySelectorAll('.group-filter').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const group = this.dataset.group;
                filterChannels(group);
                updateActiveGroupButton(group);
            });
        });
    }

    function setupEPGRefresh() {
        logDebug('Setting up EPG refresh');
        setInterval(updateEPG, config.refreshInterval);
        logDebug('EPG refresh set to run every', config.refreshInterval / 1000, 'seconds');
    }

    function updateEPGGrid(html) {
        if (!html) {
            console.error('No HTML content provided to updateEPGGrid');
            return;
        }
        console.log('Received HTML content:', html.substring(0, 100) + '...');
        const epgGrid = document.querySelector('.epg');
        if (epgGrid) {
            console.log('Current EPG grid content:', epgGrid.innerHTML.substring(0, 100) + '...');
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            const newGrid = tempDiv.querySelector('.epg');
            if (newGrid) {
                console.log('New EPG grid found, content:', newGrid.innerHTML.substring(0, 100) + '...');
                if (epgGrid.innerHTML !== newGrid.innerHTML) {
                    console.log('EPG content has changed, updating DOM');
                    epgGrid.innerHTML = newGrid.innerHTML;
                    attachProgramClickListeners();
                    console.log('EPG grid updated and listeners reattached');
                } else {
                    console.log('EPG content unchanged, no update needed');
                }
            } else {
                console.error('New EPG grid not found in the received data');
            }
        } else {
            console.error('EPG grid not found in the current DOM');
        }
    }

    function reattachGridEventListeners() {
        console.log('Reattaching grid event listeners');
        document.querySelectorAll('.programme').forEach(program => {
            program.removeEventListener('click', showProgramDetails);
            program.addEventListener('click', showProgramDetails);
            console.log('Event listener attached to program:', program.dataset.title);
        });
        document.querySelectorAll('.channel-link').forEach(link => {
            link.removeEventListener('click', handleChannelSwitch);
            link.addEventListener('click', handleChannelSwitch);
        });
    }

    function attachProgramClickListeners() {
        console.log('Attaching program click listeners');
        document.querySelectorAll('.programme').forEach(program => {
            program.removeEventListener('click', showProgramDetails);
            program.addEventListener('click', showProgramDetails);
        });
        console.log('Program click listeners attached');
    }

    document.addEventListener('DOMContentLoaded', initEPG);
    document.addEventListener('DOMContentLoaded', function() {
        // Your initialization code here
        console.log('Modern EPG JavaScript initialized');
    });
})();