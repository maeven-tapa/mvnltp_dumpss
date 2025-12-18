document.addEventListener('DOMContentLoaded', async () => {
    const ROUND_WEIGHT_G = 20;
    let currentFeedWeight = 1000;

    const DOM = {
        scheduleList: document.getElementById('schedule-list'),
        historyList: document.getElementById('history-list'),
        weightDisplay: document.getElementById('current-weight'),
        historySearchInput: document.getElementById('history-search-input'),
        alertListContainer: document.getElementById('alert-list'),
        lowFeedWarning: document.getElementById('low-feed-warning'),

        settingStatusSsid: document.getElementById('setting-status-ssid'),
        settingStatusTimezone: document.getElementById('setting-status-timezone'),
        settingStatusBattery: document.getElementById('setting-status-battery'),
        deviceStatusIndicator: document.getElementById('device-status-indicator'),
        deviceStatusText: document.querySelector('.header-status .status-text'),
        headerStatusWrapper: document.querySelector('.header-status'),
        currentDeviceTime: document.getElementById('current-device-time'),

        addScheduleBtn: document.getElementById('add-schedule-btn'),
        viewSchedulesBtn: document.getElementById('view-schedules-btn'),
        viewHistoryBtn: document.getElementById('view-history-btn'),
        viewSettingsBtn: document.getElementById('view-settings-btn'),
        viewAllAlertsBtn: document.getElementById('view-all-alerts-btn'),
        dispenseNowBtn: document.getElementById('dispense-now-btn'),
        recalibrateBtn: document.getElementById('recalibrate-btn'),
        resetButton: document.getElementById('reset-system-btn'),
        quickFeedButtons: document.querySelectorAll('.quick-feed-btn'),

        scheduleModal: document.getElementById('schedule-modal'),
        settingsModal: document.getElementById('settings-modal'),
        allSchedulesModal: document.getElementById('all-schedules-modal'),
        allHistoryModal: document.getElementById('all-history-modal'),
        allAlertsModal: document.getElementById('all-alerts-modal'),
        commandModal: document.getElementById('command-modal'),
        commandModalIcon: document.getElementById('command-modal-icon'),
        commandModalTitle: document.getElementById('command-modal-title'),
        commandModalMessage: document.getElementById('command-modal-message'),

        closeAllSchedulesBtn: document.getElementById('close-all-schedules-btn'),
        closeAllHistoryBtn: document.getElementById('close-all-history-btn'),
        closeAllAlertsBtn: document.getElementById('close-all-alerts-btn'),
        allAlertsModalList: document.getElementById('all-alerts-list'),
        allSchedulesModalList: document.getElementById('all-schedules-list'),
        allHistoryModalList: document.getElementById('all-history-list'),
        modalHistorySearchInput: document.getElementById('modal-history-search-input'),

        scheduleModalTitle: document.getElementById('schedule-modal-title'),
        editingScheduleId: document.getElementById('editing-schedule-id'),
        saveScheduleBtn: document.getElementById('save-schedule-btn'),
        cancelScheduleBtn: document.getElementById('cancel-schedule-btn'),
        scheduleIntervalSelect: document.getElementById('schedule-interval'),
        scheduleTimeInput: document.getElementById('schedule-time'),
        scheduleRoundsInput: document.getElementById('schedule-rounds'),
        scheduleFrequencySelect: document.getElementById('schedule-frequency'),
        customDaysContainer: document.getElementById('custom-days-container'),
        customDaysInput: document.getElementById('custom-days-input'),
        roundsDisplayContainer: document.getElementById('rounds-display-container'),

        feedFeedback: document.getElementById('feed-feedback'),
        customRoundsInput: document.getElementById('custom-rounds-input'),

        usernameDisplay: document.getElementById('username'),
        accountOldPwInput: document.getElementById('old-password'),
        accountNewPwInput: document.getElementById('new-password'),
        accountConfirmPwInput: document.getElementById('confirm-password'),
        applyAccountBtn: document.getElementById('apply-password-change'),
        cancelAccountBtn: document.getElementById('cancel-password-change'),
        settingsCancelBtn: document.getElementById('settings-cancel-btn'),
    };

    const closeAllModals = () => {
        const modals = [
            DOM.scheduleModal, DOM.settingsModal, DOM.allSchedulesModal,
            DOM.allHistoryModal, DOM.allAlertsModal, DOM.commandModal
        ];
        modals.forEach(modal => {
            if (modal) modal.style.display = 'none';
        });
    };

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

    const calculateRounds = (intervalValue, startTime) => {
        if (intervalValue === 'free') return -1;

        const intervalHours = parseInt(intervalValue.replace('h', ''));
        if (isNaN(intervalHours) || intervalHours <= 0) return 0;

        if (!startTime || startTime.startsWith('00:')) {
            return Math.max(1, Math.floor(24 / intervalHours));
        }

        const startHour = parseInt(startTime.split(':')[0]);
        const remainingHours = 24 - startHour;
        const totalRounds = 1 + Math.floor(remainingHours / intervalHours);

        return Math.max(1, totalRounds);
    };

    const showFeedback = (message, isError = false) => {
        if (DOM.feedFeedback) {
            DOM.feedFeedback.textContent = message;
            DOM.feedFeedback.style.backgroundColor = isError ? 'var(--color-status-error)' : 'var(--color-status-success)';
            DOM.feedFeedback.style.display = 'block';
            setTimeout(() => {
                DOM.feedFeedback.style.display = 'none';
            }, 3000);
        }
    };

    const showCommandModal = (title = 'Sending Command to Device', message = 'Please wait...') => {
        if (DOM.commandModal) {
            DOM.commandModalTitle.textContent = title;
            DOM.commandModalMessage.textContent = message;
            DOM.commandModalIcon.innerHTML = '<div class="spinner"></div>';
            DOM.commandModalIcon.className = 'modal-icon';
            DOM.commandModal.style.display = 'flex';
        }
    };

    const updateCommandModal = (success, title, message, autoClose = true) => {
        if (DOM.commandModal) {
            DOM.commandModalTitle.textContent = title;
            DOM.commandModalMessage.textContent = message;
            
            if (success) {
                DOM.commandModalIcon.innerHTML = '✓';
                DOM.commandModalIcon.className = 'modal-icon success';
            } else {
                DOM.commandModalIcon.innerHTML = '✕';
                DOM.commandModalIcon.className = 'modal-icon error';
            }

            if (autoClose) {
                setTimeout(() => {
                    DOM.commandModal.style.display = 'none';
                }, 2500);
            }
        }
    };

    const hideCommandModal = () => {
        if (DOM.commandModal) {
            DOM.commandModal.style.display = 'none';
        }
    };

    const updateRoundsDisplay = () => {
        const interval = DOM.scheduleIntervalSelect ? DOM.scheduleIntervalSelect.value : '';
        const startTime = DOM.scheduleTimeInput ? DOM.scheduleTimeInput.value : '';

        if (DOM.scheduleRoundsInput) DOM.scheduleRoundsInput.style.display = 'none';
        if (DOM.roundsDisplayContainer) DOM.roundsDisplayContainer.style.display = 'block';

        if (!interval || !startTime) {
            if (DOM.roundsDisplayContainer) DOM.roundsDisplayContainer.textContent = 'Select Interval and Start Time to calculate rounds.';
            if (DOM.scheduleRoundsInput) DOM.scheduleRoundsInput.value = 0;
            return;
        }

        const calculatedRounds = calculateRounds(interval, startTime);
        const totalWeight = calculatedRounds * ROUND_WEIGHT_G;

        if (DOM.roundsDisplayContainer) DOM.roundsDisplayContainer.innerHTML = `
            Calculated Daily Rounds: ${calculatedRounds === -1 ? 'Free Feed' : calculatedRounds + ' times'}
            <span style="font-size: 0.9em; color: var(--color-text-dark); display: block;">
                (Total Daily Portion: ${calculatedRounds === -1 ? 'N/A' : totalWeight + 'g'})
            </span>
        `;
        if (DOM.scheduleRoundsInput) DOM.scheduleRoundsInput.value = calculatedRounds;
    };

    const updateDeviceTime = () => {
        const now = new Date();
        const time24 = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });
        if (DOM.currentDeviceTime) {
            DOM.currentDeviceTime.textContent = convertTo12Hr(time24);
        }
    };

    const updateHeaderStatus = () => {
    };

    const renderWeightStatus = () => {
        if (DOM.weightDisplay) DOM.weightDisplay.textContent = `${currentFeedWeight}g`;

        const isTooLowToDispense = currentFeedWeight < ROUND_WEIGHT_G;

        if (DOM.lowFeedWarning) {
            if (currentFeedWeight < 100) {
                DOM.lowFeedWarning.textContent = 'CRITICAL LOW: Refill Required';
                DOM.lowFeedWarning.className = 'status-error';
            } else if (currentFeedWeight < 200) {
                DOM.lowFeedWarning.textContent = 'Warning: Getting Low';
                DOM.lowFeedWarning.className = 'status-warning';
            } else {
                DOM.lowFeedWarning.textContent = 'None';
                DOM.lowFeedWarning.className = 'status-success';
            }
        }

        const feedControls = [...DOM.quickFeedButtons, DOM.dispenseNowBtn].filter(Boolean);

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

    const dispenseFood = async (rounds) => {
        const actualRounds = rounds === -1 ? 1 : rounds;

        // Show the command modal
        showCommandModal('Sending Command to Device', `Sending ${actualRounds} round${actualRounds > 1 ? 's' : ''} command...`);

        try {
            // Send command to device
            const response = await fetch('../api/send_dispense_command.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    rounds: actualRounds,
                    feedType: 'Quick'
                })
            });

            const data = await response.json();

            if (data.success && data.command_id) {
                // Command sent, now wait for device to pick it up
                showCommandModal('Waiting for Device', 'Device is processing command...');
                
                // Poll for command completion (check for up to 30 seconds)
                let attempts = 0;
                const maxAttempts = 30;
                const checkInterval = 1000; // 1 second
                
                const checkCompletion = setInterval(async () => {
                    attempts++;
                    
                    if (attempts >= maxAttempts) {
                        clearInterval(checkCompletion);
                        updateCommandModal(false, 'Timeout', 'Device did not respond. Please check connection.');
                        await loadHistory();
                        await loadAlerts();
                        return;
                    }
                    
                    // Check command status directly from command_queue table
                    const statusResponse = await fetch(`../api/check_command_status.php?command_id=${data.command_id}`);
                    const statusData = await statusResponse.json();
                    
                    if (statusData.success && statusData.command) {
                        const commandStatus = statusData.command.status;
                        
                        // Check if command is completed or failed
                        if (commandStatus === 'completed' || commandStatus === 'failed') {
                            clearInterval(checkCompletion);
                            
                            // Get updated weight
                            const weightResponse = await fetch('../api/get_settings.php');
                            const weightData = await weightResponse.json();
                            if (weightData.success) {
                                currentFeedWeight = parseInt(weightData.settings.current_weight);
                                renderWeightStatus();
                            }
                            
                            await loadHistory();
                            await loadAlerts();
                            
                            if (commandStatus === 'completed') {
                                const message = statusData.command.message || `Dispensed ${actualRounds} rounds successfully!`;
                                updateCommandModal(true, 'Success!', message);
                            } else {
                                const message = statusData.command.message || 'Command failed';
                                updateCommandModal(false, 'Failed', message);
                            }
                        }
                    }
                }, checkInterval);
                
                return true;
            } else {
                updateCommandModal(false, 'Failed', data.message || 'Failed to send command to device');
                return false;
            }
        } catch (error) {
            updateCommandModal(false, 'Error', 'Error sending command. Please try again.');
            return false;
        }
    };

    const simulateRecalibration = async () => {
        if (!DOM.recalibrateBtn) return;
        
        DOM.recalibrateBtn.disabled = true;
        if (DOM.dispenseNowBtn) DOM.dispenseNowBtn.disabled = true;
        DOM.quickFeedButtons.forEach(btn => btn.disabled = true);

        // Show command modal
        showCommandModal('Sending Command to Device', 'Sending recalibration command...');

        try {
            const response = await fetch('../api/recalibrate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            const data = await response.json();

            if (data.success && data.command_id) {
                // Command sent, now wait for device to complete it
                showCommandModal('Waiting for Device', 'Device is recalibrating...');
                
                // Poll for command completion
                let attempts = 0;
                const maxAttempts = 30;
                const checkInterval = 1000;
                
                const checkCompletion = setInterval(async () => {
                    attempts++;
                    
                    if (attempts >= maxAttempts) {
                        clearInterval(checkCompletion);
                        updateCommandModal(false, 'Timeout', 'Device did not respond. Please check connection.');
                        DOM.recalibrateBtn.disabled = false;
                        if (DOM.dispenseNowBtn) DOM.dispenseNowBtn.disabled = false;
                        DOM.quickFeedButtons.forEach(btn => btn.disabled = false);
                        return;
                    }
                    
                    // Check command status
                    const statusResponse = await fetch(`../api/check_command_status.php?command_id=${data.command_id}`);
                    const statusData = await statusResponse.json();
                    
                    if (statusData.success && statusData.command) {
                        const commandStatus = statusData.command.status;
                        
                        if (commandStatus === 'completed' || commandStatus === 'failed') {
                            clearInterval(checkCompletion);
                            
                            // Get updated weight
                            const weightResponse = await fetch('../api/get_settings.php');
                            const weightData = await weightResponse.json();
                            if (weightData.success) {
                                currentFeedWeight = parseInt(weightData.settings.current_weight);
                                renderWeightStatus();
                            }
                            
                            await loadHistory();
                            await loadAlerts();
                            
                            DOM.recalibrateBtn.disabled = false;
                            if (DOM.dispenseNowBtn) DOM.dispenseNowBtn.disabled = false;
                            DOM.quickFeedButtons.forEach(btn => btn.disabled = false);
                            
                            if (commandStatus === 'completed') {
                                const message = statusData.command.message || 'Recalibration complete!';
                                updateCommandModal(true, 'Success!', message);
                            } else {
                                const message = statusData.command.message || 'Recalibration failed';
                                updateCommandModal(false, 'Failed', message);
                            }
                        }
                    }
                }, checkInterval);
            } else {
                updateCommandModal(false, 'Failed', data.message || 'Failed to send recalibration command');
                DOM.recalibrateBtn.disabled = false;
                if (DOM.dispenseNowBtn) DOM.dispenseNowBtn.disabled = false;
                DOM.quickFeedButtons.forEach(btn => btn.disabled = false);
            }
        } catch (error) {
            updateCommandModal(false, 'Error', 'Error sending command. Please try again.');
            DOM.recalibrateBtn.disabled = false;
            if (DOM.dispenseNowBtn) DOM.dispenseNowBtn.disabled = false;
            DOM.quickFeedButtons.forEach(btn => btn.disabled = false);
        }
    };

    const loadSchedules = async () => {
        try {
            const response = await fetch('../api/get_schedules.php');
            const data = await response.json();

            if (data.success) {
                renderSchedules(DOM.scheduleList, data.schedules, true);
                renderSchedules(DOM.allSchedulesModalList, data.schedules, true);
            }
        } catch (error) {
            console.error('Error loading schedules:', error);
        }
    };

    const renderSchedules = (targetElement, schedules, includeActions = true) => {
        if (!targetElement) return;
        targetElement.innerHTML = '';

        if (schedules.length === 0) {
            const colspan = includeActions ? 5 : 4;
            const row = document.createElement('tr');
            row.innerHTML = `<td colspan="${colspan}" style="text-align: center; color: var(--color-text-dark);">No scheduled feedings set.</td>`;
            targetElement.appendChild(row);
            return;
        }

        schedules.forEach((sched) => {
            const frequencyString = getFrequencyString(sched.frequency, sched.customDays);
            const intervalOption = document.querySelector(`#schedule-interval option[value="${sched.interval}"]`);
            const intervalText = intervalOption ? intervalOption.textContent : sched.interval;

            let portionSizeText;
            if (sched.interval === 'free') {
                portionSizeText = 'Free Feed';
            } else {
                const totalWeight = sched.rounds * ROUND_WEIGHT_G;
                portionSizeText = `${sched.rounds} rounds (${totalWeight}g)`;
            }

            const time12Hr = convertTo12Hr(sched.time);
            const row = document.createElement('tr');
            
            // Add manual-schedule class if it's from Arduino
            if (sched.isManual === 1 || sched.isManual === '1') {
                row.classList.add('manual-schedule');
            }

            let actionsHtml = '';
            if (includeActions) {
                actionsHtml = `
                    <td style="text-align: center;">
                        <div class="btn-action-group" style="display: flex; justify-content: center; gap: 0.5rem;">
                            <button class="btn btn-tertiary edit-schedule-btn btn-small-action" data-id="${sched.id}">Edit</button>
                            <button class="btn btn-tertiary delete-schedule-btn btn-small-action" data-id="${sched.id}">Delete</button>
                        </div>
                    </td>
                `;
            }

            const manualBadge = (sched.isManual === 1 || sched.isManual === '1') 
                ? '<span class="manual-badge">MANUAL</span>' 
                : '';
            
            row.innerHTML = `
                <td>${intervalText}${manualBadge}</td>
                <td>${time12Hr}</td>
                <td>${portionSizeText}</td>
                <td>${frequencyString}</td>
                ${actionsHtml}
            `;
            targetElement.appendChild(row);
        });

        if (includeActions) {
            targetElement.querySelectorAll('.edit-schedule-btn').forEach(btn => {
                btn.addEventListener('click', (e) => openScheduleModal(e.target.dataset.id));
            });
            targetElement.querySelectorAll('.delete-schedule-btn').forEach(btn => {
                btn.addEventListener('click', (e) => deleteSchedule(e.target.dataset.id));
            });
        }
    };

    const deleteSchedule = async (id) => {
        if (confirm('Are you sure you want to delete this schedule?')) {
            try {
                const response = await fetch('../api/delete_schedule.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });

                const data = await response.json();
                if (data.success) {
                    showFeedback('Schedule deleted successfully', false);
                    await loadSchedules();
                }
            } catch (error) {
                showFeedback('Error deleting schedule', true);
            }
        }
    };

    const toggleCustomDaysInput = () => {
        if (DOM.scheduleFrequencySelect && DOM.customDaysContainer && DOM.customDaysInput) {
            const isCustom = DOM.scheduleFrequencySelect.value === 'custom';
            DOM.customDaysContainer.style.display = isCustom ? 'block' : 'none';
            if (isCustom) {
                DOM.customDaysInput.setAttribute('required', 'required');
            } else {
                DOM.customDaysInput.removeAttribute('required');
            }
        }
    };

    const openScheduleModal = async (id = null) => {
        closeAllModals();

        if (DOM.scheduleModalTitle) DOM.scheduleModalTitle.textContent = id ? 'Edit Feeding Schedule' : 'Add New Feeding Schedule';
        if (DOM.editingScheduleId) DOM.editingScheduleId.value = id || '';
        if (DOM.scheduleTimeInput) DOM.scheduleTimeInput.value = '';
        if (DOM.scheduleRoundsInput) DOM.scheduleRoundsInput.value = '';
        if (DOM.scheduleIntervalSelect) DOM.scheduleIntervalSelect.value = '';
        if (DOM.scheduleFrequencySelect) DOM.scheduleFrequencySelect.value = 'daily';
        if (DOM.customDaysInput) DOM.customDaysInput.value = '';

        if (id) {
            try {
                const response = await fetch('../api/get_schedules.php');
                const data = await response.json();
                const schedule = data.schedules.find(s => s.id == id);

                if (schedule) {
                    if (DOM.scheduleTimeInput) DOM.scheduleTimeInput.value = schedule.time;
                    if (DOM.scheduleIntervalSelect) DOM.scheduleIntervalSelect.value = schedule.interval;
                    if (DOM.scheduleFrequencySelect) DOM.scheduleFrequencySelect.value = schedule.frequency;
                    if (schedule.frequency === 'custom' && DOM.customDaysInput) {
                        DOM.customDaysInput.value = schedule.customDays || '';
                    }
                }
            } catch (error) {
                console.error('Error loading schedule:', error);
            }
        }

        toggleCustomDaysInput();
        updateRoundsDisplay();

        if (DOM.scheduleModal) DOM.scheduleModal.style.display = 'flex';
    };

    const loadHistory = async (search = '') => {
        try {
            const url = search ? `../api/get_history.php?search=${encodeURIComponent(search)}` : '../api/get_history.php';
            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                renderHistory(DOM.historyList, data.history.slice(0, 3));
                renderHistory(DOM.allHistoryModalList, data.history);
            }
        } catch (error) {
            console.error('Error loading history:', error);
        }
    };

    const renderHistory = (targetElement, history) => {
        if (!targetElement) return;
        targetElement.innerHTML = '';

        if (history.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = `<td colspan="5" style="text-align: center; color: var(--color-text-dark);">No history found.</td>`;
            targetElement.appendChild(row);
            return;
        }

        history.forEach(item => {
            const row = document.createElement('tr');
            const statusColor = item.status.toLowerCase().includes('success') ? 'success' : 'error';
            row.innerHTML = `
                <td>${item.date}</td>
                <td>${convertTo12Hr(item.time)}</td>
                <td>${item.rounds === -1 ? 'N/A' : item.rounds}</td>
                <td>${item.type}</td>
                <td style="color: var(--color-status-${statusColor}); font-weight: 700;">${item.status}</td>
            `;
            targetElement.appendChild(row);
        });
    };

    const loadAlerts = async () => {
        try {
            const response = await fetch('../api/get_alerts.php');
            const data = await response.json();

            if (data.success) {
                renderAlerts(DOM.alertListContainer, data.alerts.slice(0, 3));
                renderAlerts(DOM.allAlertsModalList, data.alerts);
            }
        } catch (error) {
            console.error('Error loading alerts:', error);
        }
    };

    const renderAlerts = (targetElement, alerts) => {
        if (!targetElement) return;
        targetElement.innerHTML = '';

        if (alerts.length === 0) {
            const placeholder = document.createElement('li');
            placeholder.className = 'info-list-item';
            placeholder.style.justifyContent = 'center';
            placeholder.style.borderBottom = 'none';
            placeholder.style.color = 'var(--color-text-dark)';
            placeholder.textContent = 'No active warnings or alerts.';
            targetElement.appendChild(placeholder);
            return;
        }

        alerts.forEach(alert => {
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

    const loadSettings = async () => {
        try {
            const response = await fetch('../api/get_settings.php');
            const data = await response.json();

            if (data.success) {
                const settings = data.settings;
                currentFeedWeight = parseInt(settings.current_weight) || 0;

                if (DOM.settingStatusSsid) DOM.settingStatusSsid.textContent = settings.wifi_ssid || 'Not Set';
                if (DOM.settingStatusTimezone) DOM.settingStatusTimezone.textContent = settings.timezone || 'UTC';
                if (DOM.settingStatusBattery) DOM.settingStatusBattery.textContent = `${settings.battery_level || 0}%`;

                renderWeightStatus();

                const isConnected = settings.is_connected === '1';
                if (DOM.headerStatusWrapper) {
                    DOM.headerStatusWrapper.classList.toggle('status-connected', isConnected);
DOM.headerStatusWrapper.classList.toggle('status-disconnected', !isConnected);
if (DOM.deviceStatusText) {
DOM.deviceStatusText.textContent = isConnected ? 'Device Status: Online' : 'Device Status: Offline';
}
}
}
} catch (error) {
console.error('Error loading settings:', error);
}
};
const handleFactoryReset = async () => {
    const isConfirmed = confirm("⚠️ WARNING: Are you absolutely sure you want to perform a factory reset? This action is irreversible and will:\\n\\n• Delete ALL schedules and history\\n• Reset device to factory settings\\n• Clear WiFi configuration\\n• Reset admin password to default\\n\\nTHIS CANNOT BE UNDONE!");

    if (!isConfirmed) return;

    // Show command modal
    showCommandModal('Sending Command to Device', 'Sending factory reset command...');

    try {
        const response = await fetch('../api/factory_reset.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });

        const data = await response.json();

        if (data.success && data.command_id) {
            // Command sent, now wait for device to complete it
            showCommandModal('Waiting for Device', 'Device is performing factory reset...');
            
            // Poll for command completion
            let attempts = 0;
            const maxAttempts = 30;
            const checkInterval = 1000;
            
            const checkCompletion = setInterval(async () => {
                attempts++;
                
                if (attempts >= maxAttempts) {
                    clearInterval(checkCompletion);
                    updateCommandModal(false, 'Timeout', 'Device did not respond. Please check connection.');
                    return;
                }
                
                // Check command status
                const statusResponse = await fetch(`../api/check_command_status.php?command_id=${data.command_id}`);
                const statusData = await statusResponse.json();
                
                if (statusData.success && statusData.command) {
                    const commandStatus = statusData.command.status;
                    
                    if (commandStatus === 'completed' || commandStatus === 'failed') {
                        clearInterval(checkCompletion);
                        
                        if (commandStatus === 'completed') {
                            updateCommandModal(true, 'Success!', 'Factory reset complete! You will be logged out.', false);
                            setTimeout(() => {
                                window.location.href = '../logout.php';
                            }, 2000);
                        } else {
                            const message = statusData.command.message || 'Factory reset failed';
                            updateCommandModal(false, 'Failed', message);
                        }
                    }
                }
            }, checkInterval);
        } else {
            updateCommandModal(false, 'Failed', data.message || 'Failed to send factory reset command');
        }
    } catch (error) {
        updateCommandModal(false, 'Error', 'Error sending command. Please try again.');
    }
};

const loadAccountSettings = () => {
    if (DOM.usernameDisplay) DOM.usernameDisplay.textContent = 'Admin';
    if (DOM.accountOldPwInput) DOM.accountOldPwInput.value = '';
    if (DOM.accountNewPwInput) DOM.accountNewPwInput.value = '';
    if (DOM.accountConfirmPwInput) DOM.accountConfirmPwInput.value = '';
};

const saveAccountSettings = async () => {
    const oldPassword = DOM.accountOldPwInput ? DOM.accountOldPwInput.value : '';
    const newPassword = DOM.accountNewPwInput ? DOM.accountNewPwInput.value : '';
    const confirmPassword = DOM.accountConfirmPwInput ? DOM.accountConfirmPwInput.value : '';

    if (!oldPassword || !newPassword || !confirmPassword) {
        alert('Please fill in all password fields');
        return;
    }

    if (newPassword !== confirmPassword) {
        alert('Error: New Password and Confirm Password do not match.');
        return;
    }

    if (newPassword.length < 8) {
        alert('Error: New password must contain at least 8 characters.');
        return;
    }

    const hasUppercase = /[A-Z]/.test(newPassword);
    const hasLowercase = /[a-z]/.test(newPassword);
    const hasDigit = /[0-9]/.test(newPassword);
    const hasSpecial = /[~`!@#\$%\^&\*\(\)_\-=\+\[\]\{\}\|\\;:'"<,>\./\?]/.test(newPassword);

    let complexityCount = 0;
    if (hasUppercase) complexityCount++;
    if (hasLowercase) complexityCount++;
    if (hasDigit) complexityCount++;
    if (hasSpecial) complexityCount++;

    if (complexityCount < 2) {
        alert('Error: New password must contain at least two of the following: digits, uppercase letters, lowercase letters, or special characters.');
        return;
    }

    try {
        const response = await fetch('../api/change_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ oldPassword, newPassword })
        });

        const data = await response.json();

        if (data.success) {
            alert('Password successfully updated.');
            loadAccountSettings();
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('Error updating password');
    }
};

closeAllModals();

setInterval(updateDeviceTime, 1000);
updateDeviceTime();
loadAccountSettings();

await loadSettings();
await loadSchedules();
await loadHistory();
await loadAlerts();

const navLinks = document.querySelectorAll('.nav-tabs .nav-link-custom');
const tabPanes = document.querySelectorAll('.tab-content .tab-pane');

const switchTab = (e) => {
    const targetId = e.target.getAttribute('href');

    if (targetId === '../logout.php') {
        return; 
    }

    e.preventDefault();

    if (!targetId) return;

    navLinks.forEach(link => {
        link.classList.remove('active');
        link.setAttribute('aria-selected', 'false');
    });
    e.target.classList.add('active');
    e.target.setAttribute('aria-selected', 'true');

    tabPanes.forEach(pane => {
        pane.classList.remove('active');
        if (`#${pane.id}` === targetId) {
            pane.classList.add('active');
        }
    });


    if (targetId === '#account-settings-tab') {
        loadAccountSettings();
    }
};

navLinks.forEach(link => {
    link.addEventListener('click', switchTab);
});

if (DOM.scheduleFrequencySelect) DOM.scheduleFrequencySelect.addEventListener('change', toggleCustomDaysInput);
if (DOM.scheduleIntervalSelect) DOM.scheduleIntervalSelect.addEventListener('change', updateRoundsDisplay);
if (DOM.scheduleTimeInput) DOM.scheduleTimeInput.addEventListener('change', updateRoundsDisplay);

if (DOM.addScheduleBtn) DOM.addScheduleBtn.addEventListener('click', () => openScheduleModal());
if (DOM.cancelScheduleBtn) DOM.cancelScheduleBtn.addEventListener('click', closeAllModals);

if (DOM.saveScheduleBtn) DOM.saveScheduleBtn.addEventListener('click', async (e) => {
    e.preventDefault();

    updateRoundsDisplay();

    const id = DOM.editingScheduleId ? DOM.editingScheduleId.value : '';
    const interval = DOM.scheduleIntervalSelect ? DOM.scheduleIntervalSelect.value : '';
    const time = DOM.scheduleTimeInput ? DOM.scheduleTimeInput.value : '';
    const rounds = parseInt(DOM.scheduleRoundsInput ? DOM.scheduleRoundsInput.value : 0);
    const frequency = DOM.scheduleFrequencySelect ? DOM.scheduleFrequencySelect.value : '';
    let customDays = '';

    if (frequency === 'custom') {
        customDays = DOM.customDaysInput ? DOM.customDaysInput.value.trim() : '';
    }

    if (!interval || !time || !frequency || (frequency === 'custom' && !customDays)) {
        alert('Please fill in all required fields');
        return;
    }

    try {
        const response = await fetch('../api/save_schedule.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id || 0, interval, time, rounds, frequency, customDays })
        });

        const data = await response.json();

        if (data.success) {
            showFeedback(id ? 'Schedule updated successfully' : 'Schedule added successfully', false);
            await loadSchedules();
            closeAllModals();
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('Error saving schedule');
    }
});

if (DOM.viewSchedulesBtn) DOM.viewSchedulesBtn.addEventListener('click', () => {
    closeAllModals();
    if (DOM.allSchedulesModal) DOM.allSchedulesModal.style.display = 'flex';
});
if (DOM.closeAllSchedulesBtn) DOM.closeAllSchedulesBtn.addEventListener('click', closeAllModals);

if (DOM.dispenseNowBtn) DOM.dispenseNowBtn.addEventListener('click', () => {
    const customValue = parseInt(DOM.customRoundsInput ? DOM.customRoundsInput.value : 0);
    if (customValue > 0) {
        DOM.quickFeedButtons.forEach(b => b.classList.remove('active'));
        dispenseFood(customValue);
    } else {
        alert('Please enter a valid number of rounds');
    }
});

DOM.quickFeedButtons.forEach(btn => {
    btn.addEventListener('click', function() {
        const rounds = parseInt(this.dataset.rounds);
        DOM.quickFeedButtons.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        if (DOM.customRoundsInput) DOM.customRoundsInput.value = '';
        if (rounds > 0) {
            dispenseFood(rounds);
            this.classList.remove('active');
        }
    });
});

if (DOM.customRoundsInput) DOM.customRoundsInput.addEventListener('input', () => {
    DOM.quickFeedButtons.forEach(b => b.classList.remove('active'));
});

if (DOM.recalibrateBtn) DOM.recalibrateBtn.addEventListener('click', simulateRecalibration);

if (DOM.viewHistoryBtn) DOM.viewHistoryBtn.addEventListener('click', () => {
    closeAllModals();
    if (DOM.allHistoryModal) DOM.allHistoryModal.style.display = 'flex';
});
if (DOM.closeAllHistoryBtn) DOM.closeAllHistoryBtn.addEventListener('click', closeAllModals);

if (DOM.historySearchInput) DOM.historySearchInput.addEventListener('input', (e) => {
    loadHistory(e.target.value);
});

if (DOM.modalHistorySearchInput) DOM.modalHistorySearchInput.addEventListener('input', (e) => {
    loadHistory(e.target.value);
});

if (DOM.viewAllAlertsBtn) DOM.viewAllAlertsBtn.addEventListener('click', () => {
    closeAllModals();
    if (DOM.allAlertsModal) DOM.allAlertsModal.style.display = 'flex';
});
if (DOM.closeAllAlertsBtn) DOM.closeAllAlertsBtn.addEventListener('click', closeAllModals);

if (DOM.viewSettingsBtn) DOM.viewSettingsBtn.addEventListener('click', () => {
    closeAllModals();
    if (DOM.settingsModal) DOM.settingsModal.style.display = 'flex';
});
if (DOM.settingsCancelBtn) DOM.settingsCancelBtn.addEventListener('click', closeAllModals);

if (DOM.resetButton) DOM.resetButton.addEventListener('click', handleFactoryReset);
if (DOM.applyAccountBtn) DOM.applyAccountBtn.addEventListener('click', saveAccountSettings);
if (DOM.cancelAccountBtn) DOM.cancelAccountBtn.addEventListener('click', loadAccountSettings);

// Close command modal when clicking outside
if (DOM.commandModal) {
    DOM.commandModal.addEventListener('click', (e) => {
        if (e.target === DOM.commandModal) {
            hideCommandModal();
        }
    });
}
});

setInterval(async () => {
    try {
        const response = await fetch('../api/get_device_status.php');
        const data = await response.json();
        
        if (data.success) {
            const status = data.status;
            currentFeedWeight = status.weight;
            renderWeightStatus();
            
            if (DOM.headerStatusWrapper) {
                DOM.headerStatusWrapper.classList.toggle('status-connected', status.online);
                DOM.headerStatusWrapper.classList.toggle('status-disconnected', !status.online);
                if (DOM.deviceStatusText) {
                    DOM.deviceStatusText.textContent = status.online ? 
                        'Device Status: Online' : 'Device Status: Offline';
                }
            }

            if (DOM.settingStatusBattery) {
                DOM.settingStatusBattery.textContent = `${status.battery}%`;
            }
            
            // Update firmware version
            const firmwareVersionElement = document.getElementById('firmware-version');
            if (firmwareVersionElement && status.firmware_version) {
                firmwareVersionElement.textContent = 'v' + status.firmware_version;
            }
        }
    } catch (error) {
        console.error('Error polling device status:', error);
    }
}, 5000);

const showLoading = (element) => {
    if (element) {
        element.disabled = true;
        element.innerHTML = '<span>Loading...</span>';
    }
};

const hideLoading = (element, originalText) => {
    if (element) {
        element.disabled = false;
        element.innerHTML = originalText;
    }
};