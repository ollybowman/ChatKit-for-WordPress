import {
	applyBodyAttributes,
	loadChatkitScript,
	createClientSecretFetcher,
	showUserError,
} from '../frontend/utils';

const config = window.chatkitConfig || {};
const getClientSecret = createClientSecretFetcher(config);

const initialiseChatKitElement = async (chatkitElement) => {
	const options = {
		api: {
			getClientSecret,
		},
		theme: {
			colorScheme: config.themeMode || 'dark',
			radius: 'round',
			density: 'normal',
			color: {
				accent: {
					primary: config.accentColor || '#FF4500',
					level: parseInt(config.accentLevel || 2, 10),
				},
			},
			typography: {
				baseSize: 16,
				fontFamily: '"OpenAI Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
			},
		},
		composer: {
			attachments: {
				enabled: false,
			},
			placeholder: config.placeholderText || 'Send a message...',
		},
		startScreen: {
			greeting: config.greetingText || 'How can I help you today?',
			prompts: [],
		},
		history: {
			enabled: true,
		},
	};

	console.log('🚀 Initializing ChatKit (free) with config:', options);

	chatkitElement.style.display = 'block';
	chatkitElement.setOptions(options);
};

const setupToggle = () => {
	const button = document.getElementById('chatToggleBtn');
	const chatkit = document.getElementById('myChatkit');

	if (!button || !chatkit) {
		console.warn('ChatKit toggle elements not found');
		return;
	}

	let isOpen = false;

	const open = () => {
		isOpen = true;
		button.classList.add('chatkit-open');
		button.setAttribute('aria-expanded', 'true');
		chatkit.hidden = false;
		chatkit.style.display = 'block';

		if (window.innerWidth <= 768) {
			document.body.style.overflow = 'hidden';
		}

		setTimeout(() => chatkit.focus(), 140);
	};

	const close = () => {
		isOpen = false;
		button.classList.remove('chatkit-open');
		button.setAttribute('aria-expanded', 'false');
		chatkit.hidden = true;
		chatkit.style.display = 'none';
		document.body.style.overflow = '';
	};

	button.addEventListener('click', () => {
		if (isOpen) {
			close();
		} else {
			open();
		}
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && isOpen) {
			close();
		}
	});
}

const initChatKit = async () => {
	if (!config.restUrl) {
		console.error('ChatKit configuration missing: restUrl not defined');
		const errorMsg = config.i18n?.configError || '⚠️ Chat configuration error. Please contact support.';
		showUserError(errorMsg);
		return;
	}

	applyBodyAttributes(config);

	try {
		await loadChatkitScript(config);
	} catch (error) {
		console.error('Failed to load ChatKit loader', error);
		showUserError(config.i18n?.loadFailed || '⚠️ Chat widget failed to load. Please refresh the page.');
		return;
	}

	if (!customElements.get('openai-chatkit')) {
		await customElements.whenDefined('openai-chatkit');
	}

	const chatkitElement = document.getElementById('myChatkit');

	if (!chatkitElement) {
		console.error('Element #myChatkit not found in DOM');
		showUserError('⚠️ Chat widget failed to load. Please refresh the page.');
		return;
	}

	setupToggle();
	await initialiseChatKitElement(chatkitElement);
};

const boot = () => {
	initChatKit().catch((error) => {
		console.error('❌ ChatKit Initialization Error:', error);
		showUserError(config.i18n?.loadFailed || '⚠️ Chat initialization failed. Please refresh the page.');
	});
};

document.addEventListener('DOMContentLoaded', boot);

document.addEventListener('visibilitychange', () => {
	if (!document.hidden) {
		applyBodyAttributes(config);
	}
});

window.addEventListener('beforeunload', () => {
	document.body.style.overflow = '';
});
