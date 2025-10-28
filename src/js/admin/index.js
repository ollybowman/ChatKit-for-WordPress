const activatePanel = (panelId, navButtons, panels) => {
	navButtons.forEach((btn) => {
		const isActive = btn.dataset.panel === panelId;
		btn.classList.toggle('is-active', isActive);
	});

	panels.forEach((panel) => {
		const isActive = panel.dataset.panel === panelId;
		panel.classList.toggle('is-active', isActive);
	});
};

const initialiseNavigation = () => {
	const navButtons = Array.from(document.querySelectorAll('.ck-nav-link'));
	const panels = Array.from(document.querySelectorAll('.ck-panel'));

	if (!navButtons.length || !panels.length) {
		return;
	}

	const defaultPanel = navButtons[0].dataset.panel;
	activatePanel(defaultPanel, navButtons, panels);

	navButtons.forEach((button) => {
		button.addEventListener('click', () => {
			activatePanel(button.dataset.panel, navButtons, panels);
		});
	});
};

const initialiseTestButton = () => {
	const button = document.querySelector('[data-chatkit-test]');
	const status = document.querySelector('[data-chatkit-test-result]');
	const config = window.chatkitAdminConfig || {};

	if (!button || !status || !config.testEndpoint) {
		return;
	}

	const { testEndpoint, restNonce, strings = {} } = config;

	button.addEventListener('click', async () => {
		const originalText = button.textContent;
		button.disabled = true;
		button.textContent = strings.testing || 'Testing…';
		status.innerHTML = '';

		try {
			const response = await fetch(testEndpoint, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': restNonce,
				},
			});

			const payload = await response.json();

			if (response.ok) {
				status.innerHTML = `<div class="notice notice-success inline"><p>✅ ${payload.message || strings.success || ''}</p></div>`;
			} else {
				status.innerHTML = `<div class="notice notice-error inline"><p>❌ ${payload.message || strings.error || ''}</p></div>`;
			}
		} catch (error) {
			status.innerHTML = `<div class="notice notice-error inline"><p>❌ ${error.message}</p></div>`;
		} finally {
			button.disabled = false;
			button.textContent = originalText;
		}
	});
};

const boot = () => {
	initialiseNavigation();
	initialiseTestButton();
};

document.addEventListener('DOMContentLoaded', boot);
