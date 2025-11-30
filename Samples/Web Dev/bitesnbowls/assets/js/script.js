document.addEventListener('DOMContentLoaded', () => {
	let schedules = [];
	let history = [];
	let alerts = [];

	let deviceSettings = {
		ssid: 'Bites_n_Bowls',
		timezone: 'PST (UTC-8)',
		lastUpdated: new Date().toISOString(),
		isConnected: true,
		batteryLevel: 85,
	};

	let userAccount = {
		username: 'Owner ID',
		currentPassword: '1234'
	};

	let currentFeedWeight = 1000;
	const ROUND_WEIGHT_G = 20;

	const DOM = {
		scheduleList: document.getElementById('schedule-list'),
		historyList: document.getElementById('history-list'),
		weightDisplay: document.getElementById('current-weight'),
		historySearchInput: document.getElementById('history-search-input'),
		alertListContainer: document.getElementById('alert-list'),
		lowFeedWarning: document.getElementById('low-feed-warning'),

		settingStatusSsid: document.getElementById('setting-status-ssid'),
		settingStatusTimezone: document.getElementById('setting-status-timezone'),
		settingStatusLastUpdated: document.getElementById('setting-status-last-updated'),
		settingStatusBattery: document.getElementById('setting-status-battery'),
		deviceStatusIndicator: document.getElementById('device-status-indicator'),
		deviceStatusText: document.querySelector('.header-status .status-text'),
        // 🔑 FIX 1: Select the parent wrapper element (assuming you added id="header-status-wrapper" to the HTML)
        headerStatusWrapper: document.querySelector('.header-status'), // Use class selector if ID isn't added, but ID is safer if available
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

		closeAllSchedulesBtn: document.getElementById('close-all-schedules-btn'),
		closeAllHistoryBtn: document.getElementById('close-all-history-btn'),
		closeAllAlertsBtn: document.getElementById('close-all-alerts-btn'),
		allAlertsModalList: document.getElementById('all-alerts-list'),
		allSchedulesModalList: document.getElementById('all-schedules-list'),
		allHistoryModalList: document.getElementById('all-history-list'),
		modalHistorySearchInput: document.getElementById('modal-history-search-input'),

		scheduleModalTitle: document.getElementById('schedule-modal-title'),
		editingIndexInput: document.getElementById('editing-schedule-index'),
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

    /**
     * Utility function to ensure all modals are hidden.
     */
    const closeAllModals = () => {
        const modals = [
            DOM.scheduleModal, DOM.settingsModal, DOM.allSchedulesModal,
            DOM.allHistoryModal, DOM.allAlertsModal
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

	/**
     * Calculates the number of feeding rounds in a 24-hour cycle starting from a specific time.
     */
	const calculateRounds = (intervalValue, startTime) => {
		if (intervalValue === 'free') {
			return -1;
		}

		const intervalHours = parseInt(intervalValue.replace('h', ''));
		if (isNaN(intervalHours) || intervalHours <= 0) return 0;

        // Fallback: Assume a full 24h cycle if no time is set
        if (!startTime || startTime.startsWith('00:')) {
            return Math.max(1, Math.floor(24 / intervalHours));
        }

        // Get the starting hour (e.g., "06:00" becomes 6)
        const startHour = parseInt(startTime.split(':')[0]);
        
        // We use (24 - startHour) which is the total time remaining in the day (e.g., 24 - 6 = 18).
        const remainingHours = 24 - startHour;

        // 🚀 FIXED: The correct calculation is the starting feed (1) plus all subsequent full intervals that fit.
        // For 6 AM start, 2h interval: 1 + floor(18 / 2) = 1 + 9 = 10.
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

	// 🔑 FIXED: Ensure startTime is correctly passed to calculateRounds.
	const updateRoundsDisplay = () => {
		const interval = DOM.scheduleIntervalSelect ? DOM.scheduleIntervalSelect.value : '';
        // 🔑 Fetch the selected start time
        const startTime = DOM.scheduleTimeInput ? DOM.scheduleTimeInput.value : '';

		if (DOM.scheduleRoundsInput) DOM.scheduleRoundsInput.style.display = 'none';
		if (DOM.roundsDisplayContainer) DOM.roundsDisplayContainer.style.display = 'block';

		// Now requires both interval AND time to calculate rounds based on daily remaining time
		if (!interval || !startTime) {
			if (DOM.roundsDisplayContainer) DOM.roundsDisplayContainer.textContent = 'Select Interval and Start Time to calculate rounds.';
			if (DOM.scheduleRoundsInput) DOM.scheduleRoundsInput.value = 0;
			return;
		}

		// 🔑 Pass the start time to the calculation
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

// 🔑 FIX 2: Apply status classes to the parent wrapper
const updateHeaderStatus = () => {
		const isConnected = deviceSettings.isConnected;
		const connectedClass = 'status-connected';
		const disconnectedClass = 'status-disconnected';
		
		const statusText = isConnected ? 'Device Status: Online' : 'Device Status: Offline';

		if (DOM.headerStatusWrapper) {
			// Toggle the status class on the PARENT div (.header-status)
			DOM.headerStatusWrapper.classList.toggle(connectedClass, isConnected);
			DOM.headerStatusWrapper.classList.toggle(disconnectedClass, !isConnected);
            
			if (DOM.deviceStatusText) DOM.deviceStatusText.textContent = statusText;
		}
	}

	const renderWeightStatus = () => {
		if (DOM.weightDisplay) DOM.weightDisplay.textContent = `${currentFeedWeight}g`;

		const isTooLowToDispense = currentFeedWeight < ROUND_WEIGHT_G;

		if (DOM.lowFeedWarning) {
			if (currentFeedWeight < 100) {
				DOM.lowFeedWarning.textContent = 'CRITICAL LOW: Refill Required';
				DOM.lowFeedWarning.className = 'status-error';
				alerts.unshift({ type: 'Warning', message: 'Feed supply critically low (<100g).', timestamp: new Date().toISOString() });
				renderAlerts(DOM.alertListContainer, 3);
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

	const dispenseFood = (rounds) => {
		const actualRounds = rounds === -1 ? 1 : rounds;
		const weightDispensed = actualRounds * ROUND_WEIGHT_G;
		const now = new Date();
		const date = now.toISOString().split('T')[0];
		const time = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });

		if (currentFeedWeight < weightDispensed) {
			alerts.unshift({ type: 'Error', message: `Dispense failed: Insufficient feed for ${actualRounds} rounds.`, timestamp: now.toISOString() });
			renderAlerts(DOM.alertListContainer, 3);

			history.unshift({ date, time, rounds: actualRounds, type: 'Manual', status: 'Failed (Low Feed)' });
			renderHistory(DOM.historyList, 3);
			renderHistory(DOM.allHistoryModalList);
			showFeedback(`Error: Insufficient feed. Only ${currentFeedWeight}g remaining.`, true);
			return false;
		}

		currentFeedWeight -= weightDispensed;
		history.unshift({ date, time, rounds: actualRounds, type: 'Manual', status: 'Success' });
		alerts.unshift({ type: 'Info', message: `Successfully dispensed ${actualRounds} rounds.`, timestamp: now.toISOString() });

		renderWeightStatus();
		renderHistory(DOM.historyList, 3);
		renderHistory(DOM.allHistoryModalList);
		renderAlerts(DOM.alertListContainer, 3);
		showFeedback(`Successfully dispensed ${actualRounds} rounds (${weightDispensed}g).`);
		return true;
	};

	const renderSettingsStatus = () => {
		if (DOM.settingStatusSsid) DOM.settingStatusSsid.textContent = deviceSettings.ssid;
		if (DOM.settingStatusTimezone) DOM.settingStatusTimezone.textContent = deviceSettings.timezone;
		if (DOM.settingStatusBattery) DOM.settingStatusBattery.textContent = `${deviceSettings.batteryLevel}%`;

		if (DOM.settingStatusLastUpdated) {
			DOM.settingStatusLastUpdated.textContent = deviceSettings.lastUpdated === 'N/A'
				? 'N/A'
				: new Date(deviceSettings.lastUpdated).toLocaleString();
		}
	};

	const simulateRecalibration = () => {
		if (!DOM.recalibrateBtn) return;
		DOM.recalibrateBtn.disabled = true;
		if (DOM.dispenseNowBtn) DOM.dispenseNowBtn.disabled = true;
		DOM.quickFeedButtons.forEach(btn => btn.disabled = true);

		showFeedback('Recalibration in progress... Please wait.', false);

		setTimeout(() => {
			const oldWeight = currentFeedWeight;
			currentFeedWeight = 1000;
			const now = new Date();
			const date = now.toISOString().split('T')[0];
			const time = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });

			alerts.unshift({ type: 'Info', message: `Sensor recalibrated. Weight reset from ${oldWeight}g to ${currentFeedWeight}g.`, timestamp: now.toISOString() });
			history.unshift({ date, time, rounds: 0, type: 'Recalibrate', status: 'Success' });

			renderWeightStatus();
			renderHistory(DOM.historyList, 3);
			renderAlerts(DOM.alertListContainer, 3);

			showFeedback(`Recalibration Complete! Weight is now ${currentFeedWeight}g.`);

			DOM.recalibrateBtn.disabled = false;
		}, 2000);
	}

	const renderSchedules = (targetElement, includeActions = true) => {
		if (!targetElement) return;
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
				<td>${portionSizeText}</td>
				<td>${frequencyString}</td>
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
			renderSchedules(DOM.scheduleList, true);
			renderSchedules(DOM.allSchedulesModalList, true);
			showFeedback('Schedule successfully deleted.', false);
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

	const openScheduleModal = (index = -1) => {
		const isEditing = index !== -1;

        closeAllModals(); // Hide all other modals just in case

		if (DOM.scheduleModalTitle) DOM.scheduleModalTitle.textContent = isEditing ? 'Edit Feeding Schedule' : 'Add New Feeding Schedule';
		if (DOM.editingIndexInput) DOM.editingIndexInput.value = index;
		if (DOM.scheduleTimeInput) DOM.scheduleTimeInput.value = '';
		if (DOM.scheduleRoundsInput) DOM.scheduleRoundsInput.value = '';
		if (DOM.scheduleIntervalSelect) DOM.scheduleIntervalSelect.value = '';
		if (DOM.scheduleFrequencySelect) DOM.scheduleFrequencySelect.value = 'daily';
		if (DOM.customDaysInput) DOM.customDaysInput.value = '';

		if (isEditing) {
			const sched = schedules[index];
			if (DOM.scheduleTimeInput) DOM.scheduleTimeInput.value = sched.time;
			if (DOM.scheduleIntervalSelect) DOM.scheduleIntervalSelect.value = sched.interval;
			if (DOM.scheduleFrequencySelect) DOM.scheduleFrequencySelect.value = sched.frequency;

			if (sched.frequency === 'custom' && DOM.customDaysInput) {
				DOM.customDaysInput.value = sched.customDays || '';
			}
		}

		toggleCustomDaysInput();
		updateRoundsDisplay();

		if (DOM.scheduleModal) DOM.scheduleModal.style.display = 'flex';
	}

	const renderHistory = (targetElement, limit = -1, searchTerm = '') => {
		if (!targetElement) return;
		targetElement.innerHTML = '';
		const searchLower = searchTerm.toLowerCase();

		let filteredHistory = history.filter(item => {
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

	const renderAlerts = (targetElement, limit = -1) => {
		if (!targetElement) return;
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

	const clearAllSchedules = () => {
		schedules.length = 0;
		renderSchedules(DOM.scheduleList, true);
		if (DOM.allSchedulesModalList) renderSchedules(DOM.allSchedulesModalList, true);
	};

	const clearAllHistory = () => {
		history.length = 0;
		renderHistory(DOM.historyList, 3);
		if (DOM.allHistoryModalList) renderHistory(DOM.allHistoryModalList);
	};

	const handleFactoryReset = async () => {
		const isConfirmed = confirm("⚠️ WARNING: Are you absolutely sure you want to perform a factory reset? This action is irreversible and all data will be lost.");

		if (!isConfirmed) {
			return;
		}

		try {
			await new Promise(resolve => setTimeout(resolve, 2000));

			const success = Math.random() > 0.1;

			if (success) {
				clearAllSchedules();
				clearAllHistory();
				alerts.length = 0;
				currentFeedWeight = 0;

				deviceSettings = {
					ssid: 'Not Set',
					timezone: 'UTC',
					lastUpdated: new Date().toISOString(),
					isConnected: false,
					batteryLevel: 0,
				};

				userAccount.username = 'default_user';
				userAccount.currentPassword = '1234';

				renderSettingsStatus();
				renderWeightStatus();
				updateHeaderStatus();
				renderAlerts(DOM.alertListContainer, 3);

				alert("Factory reset complete. The device may restart now. (Simulated)");
			} else {
				throw new Error("Device failed to respond to the reset command.");
			}

		} catch (error) {
			alert(`Factory reset failed: ${error.message}. Please check device connectivity.`);
		}
	};

	const loadAccountSettings = () => {
		if (DOM.usernameDisplay) DOM.usernameDisplay.textContent = userAccount.username;

		if (DOM.accountOldPwInput) DOM.accountOldPwInput.value = '';
		if (DOM.accountNewPwInput) DOM.accountNewPwInput.value = '';
		if (DOM.accountConfirmPwInput) DOM.accountConfirmPwInput.value = '';
	}

	const saveAccountSettings = () => {
		const oldPassword = DOM.accountOldPwInput ? DOM.accountOldPwInput.value : '';
		const newPassword = DOM.accountNewPwInput ? DOM.accountNewPwInput.value : '';
		const confirmPassword = DOM.accountConfirmPwInput ? DOM.accountConfirmPwInput.value : '';

		const attemptingPasswordChange = oldPassword || newPassword || confirmPassword;

		if (!attemptingPasswordChange) {
			alert('No password changes requested.');
			loadAccountSettings();
			return;
		}

		if (oldPassword !== userAccount.currentPassword) {
			alert('Error: Old Password is incorrect.');
			if (DOM.accountOldPwInput) DOM.accountOldPwInput.value = '';
			return;
		}

		// --- NEW PASSWORD VALIDATION ---

		// 1. Password Match Check
		if (newPassword !== confirmPassword) {
			alert('Error: New Password and Confirm Password do not match.');
			if (DOM.accountNewPwInput) DOM.accountNewPwInput.value = '';
			if (DOM.accountConfirmPwInput) DOM.accountConfirmPwInput.value = '';
			return;
		}
		
		// 2. Blacklist Check (Old Password, Username, Reversed Username)
		const username = userAccount.username;
		const reversedUsername = username.split('').reverse().join('');

		if (newPassword === oldPassword) {
			alert('Error: The new password cannot be the same as the old password.');
			if (DOM.accountNewPwInput) DOM.accountNewPwInput.value = '';
			if (DOM.accountConfirmPwInput) DOM.accountConfirmPwInput.value = '';
			return;
		}
		if (newPassword === username) {
			alert('Error: The new password cannot be your username.');
			if (DOM.accountNewPwInput) DOM.accountNewPwInput.value = '';
			if (DOM.accountConfirmPwInput) DOM.accountConfirmPwInput.value = '';
			return;
		}
		if (newPassword === reversedUsername) {
			alert('Error: The new password cannot be your username in reverse.');
			if (DOM.accountNewPwInput) DOM.accountNewPwInput.value = '';
			if (DOM.accountConfirmPwInput) DOM.accountConfirmPwInput.value = '';
			return;
		}

		// 3. Length Check
		const minLength = 8;
		if (newPassword.length < minLength) {
			alert(`Error: New password must contain at least ${minLength} characters.`);
			if (DOM.accountNewPwInput) DOM.accountNewPwInput.value = '';
			if (DOM.accountConfirmPwInput) DOM.accountConfirmPwInput.value = '';
			return;
		}

		// 4. Complexity Check (At least two of 4 types: Uppercase, Lowercase, Digit, Special)
		const hasUppercase = /[A-Z]/.test(newPassword);
		const hasLowercase = /[a-z]/.test(newPassword);
		const hasDigit = /[0-9]/.test(newPassword);
		// Note: The special characters listed are: ~ ! @ # $ % ^ & * ( ) _ - = + \ | { } [ ] ; : " ' < , > . / ?
		// To match any of these in a regex, they must be escaped or inside a character class.
		// Using a standard regex for common symbols:
		const hasSpecial = /[~`!@#\$%\^&\*\(\)_\-=\+\[\]\{\}\|\\;:'"<,>\./\?]/.test(newPassword);
		
		let complexityCount = 0;
		if (hasUppercase) complexityCount++;
		if (hasLowercase) complexityCount++;
		if (hasDigit) complexityCount++;
		if (hasSpecial) complexityCount++;

		if (complexityCount < 2) {
			alert('Error: New password must contain at least two of the following character types: digits, uppercase letters, lowercase letters, or special characters.');
			if (DOM.accountNewPwInput) DOM.accountNewPwInput.value = '';
			if (DOM.accountConfirmPwInput) DOM.accountConfirmPwInput.value = '';
			return;
		}

		// --- END VALIDATION ---

		userAccount.currentPassword = newPassword;

		loadAccountSettings();

		alert('Password successfully updated. (Simulated)');
	};

	// --- INITIALIZATION ---
    // 🔑 CRITICAL FIX: Ensure all modals are closed before rendering any content.
    closeAllModals(); 
    
    // These functions populate the dashboard data but should NOT open modals
	setInterval(updateDeviceTime, 1000);
	updateDeviceTime();
	loadAccountSettings();
	renderSchedules(DOM.scheduleList, true);
	renderHistory(DOM.historyList, 3);
	renderAlerts(DOM.alertListContainer, 3);
	renderWeightStatus();
	updateHeaderStatus();
	renderSettingsStatus();
    
    // ----------------------

	const navLinks = document.querySelectorAll('.nav-tabs .nav-link-custom');
	const tabPanes = document.querySelectorAll('.tab-content .tab-pane');

	const switchTab = (e) => {
		e.preventDefault();
		
		const targetId = e.target.getAttribute('href');	
		if (!targetId) return;

		navLinks.forEach(link => {
			link.classList.remove('active');
			link.setAttribute('aria-selected', 'false');
		});
		e.target.classList.add('active');
		e.target.setAttribute('aria-selected', 'true');

		tabPanes.forEach(pane => {
			pane.classList.remove('active');
			pane.removeAttribute('aria-expanded');
			
			if (`#${pane.id}` === targetId) {
				pane.classList.add('active');
				pane.setAttribute('aria-expanded', 'true');
			}
		});

		if (targetId === '#account-settings-tab') {
			loadAccountSettings();
		}
	};

    // --- EVENT LISTENERS ---

	navLinks.forEach(link => {
		link.addEventListener('click', switchTab);
	});
	
	if (DOM.scheduleFrequencySelect) DOM.scheduleFrequencySelect.addEventListener('change', toggleCustomDaysInput);
	if (DOM.scheduleIntervalSelect) DOM.scheduleIntervalSelect.addEventListener('change', updateRoundsDisplay);
    // 🆕 ADDED: Trigger rounds recalculation when the Start Time changes
    if (DOM.scheduleTimeInput) DOM.scheduleTimeInput.addEventListener('change', updateRoundsDisplay);

    // SCHEDULE MODAL TRIGGERS
	if (DOM.addScheduleBtn) DOM.addScheduleBtn.addEventListener('click', () => openScheduleModal());
	if (DOM.cancelScheduleBtn) DOM.cancelScheduleBtn.addEventListener('click', closeAllModals);

	if (DOM.saveScheduleBtn) DOM.saveScheduleBtn.addEventListener('click', (e) => {
		e.preventDefault();

        // 🔑 FIX 3: Run the rounds calculation *immediately* before reading the values
        // to ensure the scheduleRoundsInput is fully updated based on the selection.
        updateRoundsDisplay(); 
        // --------------------------------------------------------------------------

		const interval = DOM.scheduleIntervalSelect ? DOM.scheduleIntervalSelect.value : '';
		const time = DOM.scheduleTimeInput ? DOM.scheduleTimeInput.value : '';
		const rounds = parseInt(DOM.scheduleRoundsInput ? DOM.scheduleRoundsInput.value : 0);
		const frequency = DOM.scheduleFrequencySelect ? DOM.scheduleFrequencySelect.value : '';
		let customDays = '';

		if (frequency === 'custom') {
			customDays = DOM.customDaysInput ? DOM.customDaysInput.value.trim() : '';
		}

		const index = parseInt(DOM.editingIndexInput ? DOM.editingIndexInput.value : -1);

		const isValidRounds = rounds === -1 || rounds > 0;
		const isValidSchedule = isValidRounds && interval && time && frequency && (frequency !== 'custom' || customDays);

		if (isValidSchedule) {
			const newSchedule = {
				interval,
				time,
				rounds,
				frequency,
				customDays: frequency === 'custom' ? customDays : ''
			};

			if (index !== -1) {
				schedules[index] = newSchedule;
				showFeedback('Schedule successfully updated.', false);
			} else {
				schedules.push(newSchedule);
				showFeedback('New schedule successfully added.', false);
			}

			renderSchedules(DOM.scheduleList, true);
			renderSchedules(DOM.allSchedulesModalList, true);
			closeAllModals(); // Use utility function to close
		} else {
			let missingFields = [];
			if (!interval) missingFields.push('Feeding Interval');
			if (!time) missingFields.push('Start Time');
			if (!isValidRounds) missingFields.push('A valid combination of Time/Interval');
			if (!frequency) missingFields.push('Frequency');
			if (frequency === 'custom' && !customDays) missingFields.push('Custom Days');

			alert(`Please set the following fields: ${missingFields.join(', ')}.`);
		}
	});

    // ALL SCHEDULES MODAL TRIGGERS
	if (DOM.viewSchedulesBtn) DOM.viewSchedulesBtn.addEventListener('click', () => {
        closeAllModals(); 
		renderSchedules(DOM.allSchedulesModalList, true);
		if (DOM.allSchedulesModal) DOM.allSchedulesModal.style.display = 'flex';
	});
	if (DOM.closeAllSchedulesBtn) DOM.closeAllSchedulesBtn.addEventListener('click', closeAllModals);

   // QUICK FEED & CALIBRATION
if (DOM.dispenseNowBtn) DOM.dispenseNowBtn.addEventListener('click', () => {
	const customValue = parseInt(DOM.customRoundsInput ? DOM.customRoundsInput.value : 0);

	if (customValue > 0) {
		// Ensure no quick-feed button is active when using custom rounds
		DOM.quickFeedButtons.forEach(b => b.classList.remove('active'));

		dispenseFood(customValue);
	} else {
		alert('Please enter a valid number of rounds in the Custom Rounds input, or click a Quick Feed button.');
	}
});

DOM.quickFeedButtons.forEach(btn => {
	btn.addEventListener('click', function() {
		const rounds = parseInt(this.dataset.rounds);
        
        // Check if the button is already active. If it is, this is the second click, so just dispense.
        // If it's not active, set it active and dispense. This makes the action instant.
		
        DOM.quickFeedButtons.forEach(b => b.classList.remove('active'));
		this.classList.add('active');
		
		if (DOM.customRoundsInput) DOM.customRoundsInput.value = '';
		
		// 🔑 CRITICAL FIX: Dispense food immediately
		if (rounds > 0) {
			dispenseFood(rounds);
			// Optional: Remove 'active' class after dispense to signify a completed action
			this.classList.remove('active'); 
		} else {
			alert('Invalid rounds value on quick feed button.');
		}
	});
});

if (DOM.customRoundsInput) DOM.customRoundsInput.addEventListener('input', () => {
	DOM.quickFeedButtons.forEach(b => b.classList.remove('active'));
});

if (DOM.recalibrateBtn) DOM.recalibrateBtn.addEventListener('click', simulateRecalibration);
    // HISTORY MODAL TRIGGERS
	if (DOM.viewHistoryBtn) DOM.viewHistoryBtn.addEventListener('click', () => {
        closeAllModals(); 
		renderHistory(DOM.allHistoryModalList);
		if (DOM.allHistoryModal) DOM.allHistoryModal.style.display = 'flex';
	});

	if (DOM.closeAllHistoryBtn) DOM.closeAllHistoryBtn.addEventListener('click', closeAllModals);

	if (DOM.historySearchInput) DOM.historySearchInput.addEventListener('input', (e) => {
		renderHistory(DOM.historyList, 3, e.target.value);
	});

	if (DOM.modalHistorySearchInput) DOM.modalHistorySearchInput.addEventListener('input', (e) => {
		renderHistory(DOM.allHistoryModalList, -1, e.target.value);
	});
	
    // ALERTS MODAL TRIGGERS
	if (DOM.viewAllAlertsBtn) DOM.viewAllAlertsBtn.addEventListener('click', () => {
        closeAllModals(); 
		renderAlerts(DOM.allAlertsModalList, -1);
		if (DOM.allAlertsModal) DOM.allAlertsModal.style.display = 'flex';
	});
	if (DOM.closeAllAlertsBtn) DOM.closeAllAlertsBtn.addEventListener('click', closeAllModals);

    // SETTINGS MODAL TRIGGERS & ACTIONS
	if (DOM.viewSettingsBtn) DOM.viewSettingsBtn.addEventListener('click', () => { 
        closeAllModals(); 
        if (DOM.settingsModal) DOM.settingsModal.style.display = 'flex'; 
    });
	if (DOM.settingsCancelBtn) DOM.settingsCancelBtn.addEventListener('click', closeAllModals);

    if (DOM.resetButton) DOM.resetButton.addEventListener('click', handleFactoryReset);
    if (DOM.applyAccountBtn) DOM.applyAccountBtn.addEventListener('click', saveAccountSettings);
    if (DOM.cancelAccountBtn) DOM.cancelAccountBtn.addEventListener('click', loadAccountSettings);
});