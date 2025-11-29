document.addEventListener('DOMContentLoaded', () => {
    let schedules = [];
    let history = [];
    let alerts = [];

    let deviceSettings = {
        ssid: 'AquaFeed-Net',
        timezone: 'PST (UTC-8)',
        lastUpdated: 'N/A',
        isConnected: true,
        batteryLevel: 75,
    };

    let currentFeedWeight = 1000;
    const ROUND_WEIGHT_G = 20;

    const scheduleList = document.getElementById('schedule-list');
    const historyList = document.getElementById('history-list');
    const weightDisplay = document.getElementById('current-weight');
    const historySearchInput = document.getElementById('history-search-input');
    const alertListContainer = document.getElementById('alert-list');    
    
    const settingStatusSsid = document.getElementById('setting-status-ssid');
    const settingStatusTimezone = document.getElementById('setting-status-timezone');
    const settingStatusLastUpdated = document.getElementById('setting-status-last-updated');
    const settingStatusBattery = document.getElementById('setting-status-battery'); 

    const deviceStatusIndicator = document.getElementById('device-status-indicator');
    const deviceStatusText = document.querySelector('.header-status .status-text');

    const addScheduleBtn = document.getElementById('add-schedule-btn');
    const viewSchedulesBtn = document.getElementById('view-schedules-btn');
    const viewHistoryBtn = document.getElementById('view-history-btn');
    const viewSettingsBtn = document.getElementById('view-settings-btn');
    const viewAllAlertsBtn = document.getElementById('view-all-alerts-btn');

    const scheduleModal = document.getElementById('schedule-modal');
    const settingsModal = document.getElementById('settings-modal');
    const allSchedulesModal = document.getElementById('all-schedules-modal');
    const allHistoryModal = document.getElementById('all-history-modal');
    const allAlertsModal = document.getElementById('all-alerts-modal');

    const closeAllSchedulesBtn = document.getElementById('close-all-schedules-btn');
    const closeAllHistoryBtn = document.getElementById('close-all-history-btn');
    const closeAllAlertsBtn = document.getElementById('close-all-alerts-btn');
    const allAlertsModalList = document.getElementById('all-alerts-list');

    const allSchedulesModalList = document.getElementById('all-schedules-list');
    const allHistoryModalList = document.getElementById('all-history-list');
    const modalHistorySearchInput = document.getElementById('modal-history-search-input');

    const scheduleModalTitle = document.getElementById('schedule-modal-title');
    const editingIndexInput = document.getElementById('editing-schedule-index');
    const saveScheduleBtn = document.getElementById('save-schedule-btn');
    const cancelScheduleBtn = document.getElementById('cancel-schedule-btn');
    
    const scheduleIntervalSelect = document.getElementById('schedule-interval');
    const scheduleTimeInput = document.getElementById('schedule-time'); 
    const scheduleRoundsInput = document.getElementById('schedule-rounds'); 
    const scheduleFrequencySelect = document.getElementById('schedule-frequency'); 
    const customDaysContainer = document.getElementById('custom-days-container'); 
    const customDaysInput = document.getElementById('custom-days-input'); 
    const roundsDisplayContainer = document.getElementById('rounds-display-container'); 

    const feedFeedback = document.getElementById('feed-feedback');
    const customRoundsInput = document.getElementById('custom-rounds-input');
    const dispenseNowBtn = document.getElementById('dispense-now-btn');
    const quickFeedButtons = document.querySelectorAll('.quick-feed-btn');
    const recalibrateBtn = document.getElementById('recalibrate-btn');

    const convertTo12Hr = (time24) => {
        if (!time24) return '';
        const [hour, minute] = time24.split(':').map(Number);
        const period = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${String(hour12).padStart(2, '0')}:${String(minute).padStart(2, '0')} ${period}`;
    };
    
    const getFrequencyString = (frequency, customDays) => {
        switch (frequency) {
            case 'daily': return 'Daily';
            case 'weekdays': return 'Weekdays (Mon–Fri)';
            case 'weekends': return 'Weekends (Sat–Sun)';
            case 'custom': return `Custom: ${customDays}`;
            default: return 'N/A';
        }
    };

    const calculateRounds = (startTime24, intervalValue) => {
        if (intervalValue === 'free') {
            return -1; 
        }

        if (!startTime24 || !intervalValue) return 0;
        
        const intervalHours = parseInt(intervalValue.replace('h', ''));
        if (isNaN(intervalHours) || intervalHours <= 0) return 0;
        
        const totalRounds = Math.floor(24 / intervalHours);

        return Math.max(1, totalRounds);
    };
    
    const updateRoundsDisplay = () => {
        const time = scheduleTimeInput.value;
        const interval = scheduleIntervalSelect.value;
        
        scheduleRoundsInput.style.display = 'none';
        roundsDisplayContainer.style.display = 'block';

        if (!time || !interval) {
            roundsDisplayContainer.textContent = 'Select Time and Interval to calculate rounds.';
            scheduleRoundsInput.value = 0;
            return;
        }

        const calculatedRounds = calculateRounds(time, interval);

        if (calculatedRounds === -1 || interval === 'free') {
            roundsDisplayContainer.innerHTML = 'Rounds: Free Feeding (Always Available)';
            scheduleRoundsInput.value = -1;
            return;
        }
        
        const totalWeight = calculatedRounds * ROUND_WEIGHT_G; 
        
        roundsDisplayContainer.innerHTML = `
            Calculated Daily Rounds: ${calculatedRounds} times <span style="font-size: 0.9em; color: var(--color-text-dark); display: block;">(Total Daily Portion: ${totalWeight}g)</span>
        `;
        scheduleRoundsInput.value = calculatedRounds; 
    };

    const updateDeviceTime = () => {
        const now = new Date();
        const time24 = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });
        document.getElementById('current-device-time').textContent = convertTo12Hr(time24);
    };
    setInterval(updateDeviceTime, 1000);
    updateDeviceTime();

    const updateHeaderStatus = () => {
        if (deviceSettings.isConnected) {
            deviceStatusIndicator.className = 'status-dot connected';
            deviceStatusText.textContent = 'Device Status: Online';
        } else {
            deviceStatusIndicator.className = 'status-dot disconnected';
            deviceStatusText.textContent = 'Device Status: Offline';
        }
    }

    const renderWeightStatus = () => {
        weightDisplay.textContent = `${currentFeedWeight}g`;
        const lowWarning = document.getElementById('low-feed-warning');

        const isTooLowToDispense = currentFeedWeight < ROUND_WEIGHT_G;

        if (currentFeedWeight < 100) {
            lowWarning.textContent = 'LOW: Refill Required';
            lowWarning.style.color = 'var(--color-status-error)';
            alerts.unshift({ type: 'Warning', message: 'Feed supply critically low (<100g).', timestamp: new Date().toISOString() });
            renderAlerts(alertListContainer, 3);
        } else if (currentFeedWeight < 200) {
            lowWarning.textContent = 'Warning: Getting Low';
            lowWarning.style.color = 'var(--color-status-warning)';
        } else {
            lowWarning.textContent = 'None';
            lowWarning.style.color = 'var(--color-status-success)';
        }

        const feedControls = [...quickFeedButtons, dispenseNowBtn];

        feedControls.forEach(btn => {
            if (isTooLowToDispense) {
                btn.disabled = true;
                btn.title = "Cannot dispense: Supply too low.";
            } else {
                btn.disabled = false;
                btn.title = "";
            }
        });
    };

    const showFeedback = (message, isError = false) => {
        feedFeedback.textContent = message;
        feedFeedback.style.backgroundColor = isError ? 'var(--color-status-error)' : 'var(--color-status-success)';
        feedFeedback.style.display = 'block';
        setTimeout(() => {
            feedFeedback.style.display = 'none';
        }, 3000);
    };

    const dispenseFood = (rounds) => {
        const weightDispensed = rounds * ROUND_WEIGHT_G;
        const now = new Date();
        const date = now.toISOString().split('T')[0];
        const time = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });

        if (currentFeedWeight < weightDispensed) {
            alerts.unshift({ type: 'Error', message: `Dispense failed: Insufficient feed for ${rounds} rounds.`, timestamp: now.toISOString() });
            renderAlerts(alertListContainer, 3);

            history.unshift({ date, time, rounds, type: 'Manual', status: 'Failed (Low Feed)' });
            renderHistory(historyList, 3);
            renderHistory(allHistoryModalList);
            showFeedback(`Error: Insufficient feed. Only ${currentFeedWeight}g remaining.`, true);
            return false;
        }

        currentFeedWeight -= weightDispensed;
        history.unshift({ date, time, rounds, type: 'Manual', status: 'Success' });
        alerts.unshift({ type: 'Info', message: `Successfully dispensed ${rounds} rounds.`, timestamp: now.toISOString() });

        renderWeightStatus();
        renderHistory(historyList, 3);
        renderHistory(allHistoryModalList);
        renderAlerts(alertListContainer, 3);  
        showFeedback(`Successfully dispensed ${rounds} rounds (${weightDispensed}g).`);
        return true;
    };

    const renderSettingsStatus = () => {
        if (settingStatusSsid) settingStatusSsid.textContent = deviceSettings.ssid;
        if (settingStatusTimezone) settingStatusTimezone.textContent = deviceSettings.timezone;
        if (settingStatusBattery) settingStatusBattery.textContent = `${deviceSettings.batteryLevel}%`;  

        if (settingStatusLastUpdated) {
            settingStatusLastUpdated.textContent = deviceSettings.lastUpdated === 'N/A'
                ? 'N/A'
                : new Date(deviceSettings.lastUpdated).toLocaleString();
        }
    };

    const simulateRecalibration = () => {
        recalibrateBtn.disabled = true;
        dispenseNowBtn.disabled = true;
        quickFeedButtons.forEach(btn => btn.disabled = true);

        showFeedback('Recalibration in progress... Please wait.', false);

        setTimeout(() => {
            const oldWeight = currentFeedWeight;
            currentFeedWeight = 500;    
            const now = new Date();
            const date = now.toISOString().split('T')[0];
            const time = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });

            alerts.unshift({ type: 'Info', message: `Sensor recalibrated. Weight reset from ${oldWeight}g to ${currentFeedWeight}g.`, timestamp: now.toISOString() });
            history.unshift({ date, time, rounds: 0, type: 'Recalibrate', status: 'Success' });

            renderWeightStatus();
            renderHistory(historyList, 3);
            renderAlerts(alertListContainer, 3);

            showFeedback('Recalibration Complete! Weight is now 500g.');

            recalibrateBtn.disabled = false;
        }, 2000);  
    }

    const tabs = document.querySelectorAll('.nav-link-custom');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            const targetId = tab.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');
        });
    });

    const renderSchedules = (targetElement, includeActions = true) => {
        targetElement.innerHTML = '';
        if (schedules.length === 0) {
            const colspan = includeActions ? 5 : 4;  
            const row = document.createElement('tr');
            row.innerHTML = `<td colspan="${colspan}" style="text-align: center; color: var(--color-text-dark);">No scheduled feedings set.</td>`;
            targetElement.appendChild(row);
            return;
        }

        schedules.forEach((sched, index) => {
            const frequencyString = getFrequencyString(sched.frequency, sched.customDays);
            
            const intervalText = document.querySelector(`#schedule-interval option[value="${sched.interval}"]`)  
                                    ? document.querySelector(`#schedule-interval option[value="${sched.interval}"]`).textContent
                                    : sched.interval;

            let roundsDisplayText = `${sched.rounds}`; 
            let portionSizeText = `${sched.rounds} rounds`;
            
            if (sched.interval === 'free') {
                roundsDisplayText = 'Free Feed';
                portionSizeText = 'Free Feed';
            } else {
                const totalWeight = sched.rounds * ROUND_WEIGHT_G;
                portionSizeText = `${sched.rounds} rounds (${totalWeight}g)`; 
            }

            const time12Hr = convertTo12Hr(sched.time);
            const row = document.createElement('tr');

            let actionsHtml = '';
            if (includeActions) {
                actionsHtml = `
                    <td style="text-align: center;">
                        <div class="btn-action-group" style="display: flex; justify-content: center; gap: 0.5rem;">
                            <button class="btn btn-tertiary edit-schedule-btn btn-small-action" data-index="${index}">Edit</button>
                            <button class="btn btn-tertiary delete-schedule-btn btn-small-action" data-index="${index}">Delete</button>
                        </div>
                    </td>
                `;
            }

            row.innerHTML = `
                <td>${intervalText}</td>
                <td>${time12Hr}</td>
                <td>${portionSizeText}</td> <td>${frequencyString}</td>
                ${actionsHtml}
            `;
            targetElement.appendChild(row);
        });

        if (includeActions) {
            targetElement.querySelectorAll('.edit-schedule-btn').forEach(btn => {
                btn.addEventListener('click', (e) => openScheduleModal(parseInt(e.target.dataset.index)));
            });
            targetElement.querySelectorAll('.delete-schedule-btn').forEach(btn => {
                btn.addEventListener('click', (e) => deleteSchedule(parseInt(e.target.dataset.index)));
            });
        }
    };

    const deleteSchedule = (index) => {
        if (confirm('Are you sure you want to delete this schedule?')) {
            schedules.splice(index, 1);
            renderSchedules(scheduleList, true);
            renderSchedules(allSchedulesModalList, true);
        }
    };

    const toggleCustomDaysInput = () => {
        if (scheduleFrequencySelect.value === 'custom') {
            customDaysContainer.style.display = 'block';
            customDaysInput.setAttribute('required', 'required');
        } else {
            customDaysContainer.style.display = 'none';
            customDaysInput.removeAttribute('required');
        }
    };
    
    scheduleFrequencySelect.addEventListener('change', toggleCustomDaysInput);
    scheduleTimeInput.addEventListener('change', updateRoundsDisplay);
    scheduleIntervalSelect.addEventListener('change', updateRoundsDisplay);


    const openScheduleModal = (index = -1) => {
        const isEditing = index !== -1;
        scheduleModalTitle.textContent = isEditing ? 'Edit Feeding Schedule' : 'Add New Feeding Schedule';
        editingIndexInput.value = index;
        
        scheduleTimeInput.value = '';
        scheduleRoundsInput.value = ''; 
        scheduleIntervalSelect.value = ''; 
        scheduleFrequencySelect.value = 'daily'; 
        customDaysInput.value = '';
        roundsDisplayContainer.textContent = 'Select Time and Interval to calculate rounds.'; 
        
        scheduleRoundsInput.style.display = 'none';
        roundsDisplayContainer.style.display = 'block';
        
        if (isEditing) {
            const sched = schedules[index];
            scheduleTimeInput.value = sched.time;
            scheduleRoundsInput.value = sched.interval === 'free' ? -1 : sched.rounds;
            scheduleIntervalSelect.value = sched.interval;
            scheduleFrequencySelect.value = sched.frequency;
            
            if (sched.frequency === 'custom') {
                customDaysInput.value = sched.customDays || '';
            }
        }
        
        toggleCustomDaysInput();
        updateRoundsDisplay(); 
        
        scheduleModal.style.display = 'flex';
        allSchedulesModal.style.display = 'none';
    }

    if (addScheduleBtn) {
        addScheduleBtn.addEventListener('click', () => openScheduleModal());
    } else {
        console.error("Initialization Error: 'addScheduleBtn' element not found in HTML.");
    }
    cancelScheduleBtn.addEventListener('click', () => scheduleModal.style.display = 'none');

    saveScheduleBtn.addEventListener('click', () => {
        const interval = scheduleIntervalSelect.value; 
        const time = scheduleTimeInput.value;
        const rounds = parseInt(scheduleRoundsInput.value); 
        const frequency = scheduleFrequencySelect.value; 
        let customDays = '';

        if (frequency === 'custom') {
            customDays = customDaysInput.value.trim();
        }

        const index = parseInt(editingIndexInput.value);

        const isValidSchedule = (rounds === -1 || rounds > 0) && interval && time && frequency && (frequency !== 'custom' || customDays);

        if (isValidSchedule) {
            
            const newSchedule = { 
                interval,
                time, 
                rounds: rounds === -1 ? 1 : rounds, 
                frequency, 
                customDays: frequency === 'custom' ? customDays : '' 
            };

            if (index !== -1) {
                schedules[index] = newSchedule;
            } else {
                schedules.push(newSchedule);
            }

            renderSchedules(scheduleList, true);
            renderSchedules(allSchedulesModalList, true);
            scheduleModal.style.display = 'none';
        } else {
            let missingFields = [];
            if (!interval) missingFields.push('Feeding Interval');
            if (!time) missingFields.push('Start Time');
            if (rounds === 0 && interval !== 'free') missingFields.push('A valid combination of Time/Interval');
            if (!frequency) missingFields.push('Frequency');
            if (frequency === 'custom' && !customDays) missingFields.push('Custom Days');

            alert(`Please set the following fields: ${missingFields.join(', ')}.`);
        }
    });

    viewSchedulesBtn.addEventListener('click', () => {
        renderSchedules(allSchedulesModalList, true);
        allSchedulesModal.style.display = 'flex';
    });
    closeAllSchedulesBtn.addEventListener('click', () => allSchedulesModal.style.display = 'none');


    const renderHistory = (targetElement, limit = -1, searchTerm = '') => {
        targetElement.innerHTML = '';
        let filteredHistory = history.filter(item => {
            const searchLower = searchTerm.toLowerCase();
            return (
                item.date.includes(searchLower) ||
                convertTo12Hr(item.time).toLowerCase().includes(searchLower) ||
                item.type.toLowerCase().includes(searchLower) ||
                item.status.toLowerCase().includes(searchLower)
            );
        });

        const displayHistory = limit > 0 ? filteredHistory.slice(0, limit) : filteredHistory;

        if (displayHistory.length === 0) {
            const colspan = 5;
            const row = document.createElement('tr');
            row.innerHTML = `<td colspan="${colspan}" style="text-align: center; color: var(--color-text-dark);">No history found.</td>`;
            targetElement.appendChild(row);
            return;
        }

        displayHistory.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.date}</td>
                <td>${convertTo12Hr(item.time)}</td>
                <td>${item.rounds}</td>
                <td>${item.type}</td>
                <td style="color: var(--color-status-${item.status.toLowerCase().includes('success') ? 'success' : 'error'}); font-weight: 700;">${item.status}</td>
            `;
            targetElement.appendChild(row);
        });
    };

    historySearchInput.addEventListener('input', (e) => {
        renderHistory(historyList, 3, e.target.value);
    });
    modalHistorySearchInput.addEventListener('input', (e) => {
        renderHistory(allHistoryModalList, -1, e.target.value);
    });

    viewHistoryBtn.addEventListener('click', () => {
        modalHistorySearchInput.value = '';
        renderHistory(allHistoryModalList);
        allHistoryModal.style.display = 'flex';
    });
    closeAllHistoryBtn.addEventListener('click', () => allHistoryModal.style.display = 'none');


    const renderAlerts = (targetElement, limit = -1) => {
        targetElement.innerHTML = '';
        const displayAlerts = limit > 0 ? alerts.slice(0, limit) : alerts;

        if (displayAlerts.length === 0) {
            const placeholder = document.createElement('li');
            placeholder.className = 'info-list-item';
            placeholder.style.justifyContent = 'center';
            placeholder.style.borderBottom = 'none';
            placeholder.style.color = 'var(--color-text-dark)';
            placeholder.textContent = 'No active warnings or alerts.';
            targetElement.appendChild(placeholder);
            return;
        }

        displayAlerts.forEach(alert => {
            const li = document.createElement('li');
            li.className = 'info-list-item';

            let colorVar;
            if (alert.type.toLowerCase().includes('error')) {
                colorVar = 'var(--color-status-error)';
            } else if (alert.type.toLowerCase().includes('warning')) {
                colorVar = 'var(--color-status-warning)';
            } else {
                colorVar = 'var(--color-primary)';
            }

            const time = new Date(alert.timestamp).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

            li.innerHTML = `
                <span>
                    <span style="color: ${colorVar}; font-weight: 600;">${alert.type}:</span>
                    ${alert.message}
                </span>
                <span style="font-size: 0.8rem; color: #888;">${time}</span>
            `;
            targetElement.appendChild(li);
        });
    };

    viewAllAlertsBtn.addEventListener('click', () => {
        renderAlerts(allAlertsModalList);
        allAlertsModal.style.display = 'flex';
    });

    closeAllAlertsBtn.addEventListener('click', () => {
        allAlertsModal.style.display = 'none';
    });

    quickFeedButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            customRoundsInput.value = '';  
            dispenseFood(parseInt(e.target.dataset.rounds));
        });
    });

    dispenseNowBtn.addEventListener('click', () => {
        const rounds = parseInt(customRoundsInput.value);
        if (rounds > 0) {
            dispenseFood(rounds);
            customRoundsInput.value = '';  
        } else {
            showFeedback('Please enter a valid number of rounds.', true);
        }
    });

    recalibrateBtn.addEventListener('click', () => {
        simulateRecalibration();
    });


    viewSettingsBtn.addEventListener('click', () => {
        document.getElementById('wifi-ssid').value = deviceSettings.ssid !== 'Not Set' ? deviceSettings.ssid : '';
        const currentTZValue = deviceSettings.timezone.split(' ')[0];
        document.getElementById('timezone-setting').value = currentTZValue;
        settingsModal.style.display = 'flex';
    });

    document.getElementById('cancel-settings-btn').addEventListener('click', () => settingsModal.style.display = 'none');

    document.getElementById('save-settings-btn').addEventListener('click', () => {
        const ssid = document.getElementById('wifi-ssid').value;
        const password = document.getElementById('wifi-password').value;
        const timezoneSelect = document.getElementById('timezone-setting');
        const timezone = timezoneSelect.value;
        const timezoneText = timezoneSelect.options[timezoneSelect.selectedIndex].textContent;

        deviceSettings.ssid = ssid || 'Not Set';
        deviceSettings.timezone = timezoneText;
        deviceSettings.lastUpdated = new Date().toISOString();

        renderSettingsStatus();
        updateHeaderStatus();

        console.log(`Saving Settings: SSID=${deviceSettings.ssid}, Password=${password ? 'Set' : 'N/A'}, Timezone=${deviceSettings.timezone}`);
        alert(`Settings saved. New Timezone: ${deviceSettings.timezone}. (Simulated)`);
        settingsModal.style.display = 'none';
    });


    renderSchedules(scheduleList, true);
    renderHistory(historyList, 3);
    renderWeightStatus();
    renderSettingsStatus();
    renderAlerts(alertListContainer, 3);
    updateHeaderStatus();
});