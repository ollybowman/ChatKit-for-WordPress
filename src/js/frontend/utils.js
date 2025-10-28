export const toBool = (value) => {
	if (typeof value === 'boolean') {
		return value;
	}

	if (typeof value === 'string') {
		return value === '1' || value.toLowerCase() === 'true';
	}

	if (typeof value === 'number') {
		return value === 1;
	}

	return !!value;
};

export const applyBodyAttributes = (config) => {
	if (!config?.bodyAttributes) {
		return;
	}

	const body = document.body;

	if (!body) {
		return;
	}

	const attributeMap = {
		themeMode: 'theme',
		buttonSize: 'button-size',
		buttonPosition: 'position',
		borderRadius: 'border-radius',
		shadowStyle: 'shadow',
	};

	Object.entries(attributeMap).forEach(([key, attribute]) => {
		const value = config.bodyAttributes[key];
		const attrName = `data-chatkit-${attribute}`;

		if (value === undefined || value === null || value === '') {
			body.removeAttribute(attrName);
			return;
		}

		body.setAttribute(attrName, String(value));
	});
};

export const loadChatkitScript = (config = {}) => {
	if (customElements.get('openai-chatkit')) {
		return Promise.resolve();
	}

	return new Promise((resolve, reject) => {
		const script = document.createElement('script');
		script.src = config.loaderUrl || 'https://cdn.platform.openai.com/deployments/chatkit/chatkit.js';
		script.defer = true;
		script.onload = resolve;
		script.onerror = () => reject(new Error('Failed to load ChatKit loader'));
		document.head.appendChild(script);
	});
};

export const showUserError = (message) => {
	const errorDiv = document.createElement('div');
	errorDiv.style.cssText = 'position: fixed; bottom: 20px; right: 20px; padding: 15px 20px; background: #f8d7da; color: #721c24; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.15); z-index: 9999; max-width: 300px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;';
	errorDiv.setAttribute('role', 'alert');
	errorDiv.innerHTML = `<p style="margin: 0; font-size: 14px;">${message}</p>`;
	document.body.appendChild(errorDiv);

	setTimeout(() => {
		if (errorDiv.parentNode) {
			errorDiv.style.opacity = '0';
			errorDiv.style.transition = 'opacity 0.3s ease';
			setTimeout(() => errorDiv.remove(), 300);
		}
	}, 5000);
};

export const createClientSecretFetcher = (config) => async () => {
	const i18n = config.i18n || {};

	try {
		if (!config.restUrl) {
			throw new Error('Missing configuration');
		}

		const headers = {
			'Content-Type': 'application/json',
		};

		const controller = new AbortController();
		const timeoutId = setTimeout(() => controller.abort(), 10000);

		const response = await fetch(config.restUrl, {
			method: 'POST',
			headers,
			signal: controller.signal,
			credentials: 'same-origin',
		});

		clearTimeout(timeoutId);

		if (!response.ok) {
			const errorData = await response.json().catch(() => ({}));

			console.error('ChatKit Session Error:', {
				status: response.status,
				statusText: response.statusText,
				error: errorData,
			});

			throw new Error(errorData.message || `HTTP ${response.status}`);
		}

		const data = await response.json();

		if (!data.client_secret) {
			throw new Error('Invalid response: missing client_secret');
		}

		return data.client_secret;
	} catch (error) {
		console.error('Fetch Session Error:', error);

		const message = i18n.unableToStart || '⚠️ Unable to start chat. Please try again later.';
		const el = document.getElementById('myChatkit');

		if (el && el.parentNode) {
			const errorDiv = document.createElement('div');
			errorDiv.style.cssText = 'padding: 20px; text-align: center; color: #721c24; background: #f8d7da; border-radius: 8px; margin: 20px;';
			errorDiv.setAttribute('role', 'alert');
			errorDiv.innerHTML = `<p style="margin: 0; font-size: 14px;">${message}</p>`;
			el.parentNode.insertBefore(errorDiv, el);
		}

		if (typeof gtag !== 'undefined') {
			gtag('event', 'exception', {
				description: `ChatKit session error: ${error.message}`,
				fatal: false,
			});
		}

		return null;
	}
};
