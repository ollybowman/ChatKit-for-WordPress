import { applyBodyAttributes, loadChatkitScript, createClientSecretFetcher, toBool, showUserError } from './utils';
import { initNudge } from './nudge';

const config = window.chatkitConfig || {};
const getClientSecret = createClientSecretFetcher(config);

const buildPrompts = () => {
	const prompts = [];
	const configuredPrompts = config.prompts;

	if (Array.isArray(configuredPrompts) && configuredPrompts.length > 0) {
		configuredPrompts.forEach((prompt) => {
			if (prompt && prompt.label && prompt.text) {
				prompts.push({
					icon: prompt.icon || 'circle-question',
					label: prompt.label,
					prompt: prompt.text,
				});
			}
		});
	} else {
		[1, 2, 3, 4, 5].forEach((index) => {
			const camelLabel = config[`defaultPrompt${index}`];
			const snakeLabel = config[`default_prompt_${index}`];
			const label = camelLabel || snakeLabel;

			const camelText = config[`defaultPrompt${index}Text`];
			const snakeText = config[`default_prompt_${index}_text`];
			const text = camelText || snakeText;

			const camelIcon = config[`defaultPrompt${index}Icon`];
			const snakeIcon = config[`default_prompt_${index}_icon`];
			const icon = camelIcon || snakeIcon || 'circle-question';

			if (label && text) {
				prompts.push({
					icon,
					label,
					prompt: text,
				});
			}
		});
	}

	if (!prompts.length) {
		prompts.push({
			icon: 'circle-question',
			label: 'How can I assist you?',
			prompt: 'Hi! How can I assist you today?',
		});
	}

	return prompts;
};

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
				enabled: toBool(config.enableAttachments),
			},
			placeholder: config.placeholderText || 'Send a message...',
		},
		startScreen: {
			greeting: config.greetingText || 'How can I help you today?',
			prompts: buildPrompts(),
		},
	};

	if (toBool(config.enableAttachments)) {
		const maxSize = parseInt(config.attachmentMaxSize || 20, 10);
		const maxCount = parseInt(config.attachmentMaxCount || 3, 10);

		options.composer.attachments = {
			enabled: true,
			maxSize: maxSize * 1024 * 1024,
			maxCount,
			accept: {
				'application/pdf': ['.pdf'],
				'image/*': ['.png', '.jpg', '.jpeg', '.gif', '.webp'],
				'text/plain': ['.txt'],
			},
		};
	}

	if (config.initialThreadId && config.initialThreadId.trim() !== '') {
		options.initialThread = config.initialThreadId;
	}

	if (config.disclaimerText && config.disclaimerText.trim() !== '') {
		options.disclaimer = {
			text: config.disclaimerText,
			highContrast: toBool(config.disclaimerHighContrast),
		};
	}

	if (config.customFont && config.customFont.fontFamily && config.customFont.fontFamily.trim() !== '') {
		options.theme.typography = {
			fontFamily: config.customFont.fontFamily,
			baseSize: parseInt(config.customFont.baseSize || 16, 10),
		};
	}

	if (toBool(config.showHeader)) {
		const header = { enabled: true };

		if (config.headerTitleText && config.headerTitleText.trim() !== '') {
			header.title = {
				enabled: true,
				text: config.headerTitleText,
			};
		}

		if (config.headerLeftIcon && config.headerLeftUrl && config.headerLeftUrl.trim() !== '') {
			try {
				new URL(config.headerLeftUrl);
				header.leftAction = {
					icon: config.headerLeftIcon,
					onClick: () => {
						window.location.href = config.headerLeftUrl;
					},
				};
			} catch (error) {
				console.warn('⚠️ Invalid left button URL, skipping');
			}
		}

		if (config.headerRightIcon && config.headerRightUrl && config.headerRightUrl.trim() !== '') {
			try {
				new URL(config.headerRightUrl);
				header.rightAction = {
					icon: config.headerRightIcon,
					onClick: () => {
						window.location.href = config.headerRightUrl;
					},
				};
			} catch (error) {
				console.warn('⚠️ Invalid right button URL, skipping');
			}
		}

		options.header = header;
	} else {
		options.header = { enabled: false };
	}

	options.history = { enabled: toBool(config.historyEnabled) };

	if (config.locale && config.locale.trim() !== '') {
		options.locale = config.locale;
	}

	console.log('🚀 Initializing ChatKit with final config:', options);

	chatkitElement.style.display = 'block';
	chatkitElement.setOptions(options);

	if (typeof gtag !== 'undefined') {
		gtag('event', 'chatkit_initialized', {
			event_category: 'engagement',
			event_label: 'ChatKit Ready',
		});
	}
};

const setupToggle = () => {
	const button = document.getElementById('chatToggleBtn');
	const chatkit = document.getElementById('myChatkit');
	const picker = document.getElementById('chatkitChannelPicker');
	const emailPanel = document.getElementById('chatkitEmailPanel');
	const emailForm = document.getElementById('chatkitEmailForm');

	if (!button || !chatkit || !picker || !emailPanel) {
		console.warn('ChatKit toggle elements not found');
		return;
	}

	const accentColor = config.accentColor || '#FF4500';
	const containers = [chatkit, picker, emailPanel];
	const originalLabel = button.getAttribute('aria-label') || 'Toggle chat window';
	const closeLabel = config.closeText || originalLabel;
	let isOpen = false;
	let nudgeManager;

	const toggleBodyScroll = (lock) => {
		document.body.style.overflow = lock ? 'hidden' : '';
	};

	const showView = (view) => {
		const showPicker = view === 'picker';
		picker.hidden = !showPicker;
		picker.style.display = showPicker ? 'flex' : 'none';

		if (showPicker) {
			setTimeout(() => {
				const firstCard = picker.querySelector('.chatkit-channel-card');
				firstCard?.focus();
			}, 80);
		}

		const showEmail = view === 'email';
		emailPanel.hidden = !showEmail;
		emailPanel.style.display = showEmail ? 'block' : 'none';

		if (showEmail) {
			setTimeout(() => {
				const emailInput = emailPanel.querySelector('input[name="email"]');
				emailInput?.focus();
			}, 120);
		}

		const showChat = view === 'chat';
		chatkit.hidden = !showChat;
		chatkit.style.display = showChat ? 'block' : 'none';
		chatkit.setAttribute('aria-modal', showChat ? 'true' : 'false');

		if (showChat) {
			setTimeout(() => chatkit.focus(), 140);
		}
	};

	const openPopup = (initialView = 'picker') => {
		isOpen = true;
		button.classList.add('chatkit-open');
		button.style.backgroundColor = accentColor;
		button.setAttribute('aria-expanded', 'true');
		button.setAttribute('aria-label', closeLabel);
		showView(initialView);
		nudgeManager?.cancel();
		nudgeManager?.hide();

		if (window.innerWidth <= 768) {
			toggleBodyScroll(true);
		}
	};

	const closePopup = () => {
		isOpen = false;
		button.classList.remove('chatkit-open');
		button.style.backgroundColor = accentColor;
		button.setAttribute('aria-expanded', 'false');
		button.setAttribute('aria-label', originalLabel);
		showView(null);
		button.focus();
		nudgeManager?.scheduleRepeat();
		toggleBodyScroll(false);
	};

	nudgeManager = initNudge(config.nudge, button, () => openPopup('picker'));

	button.addEventListener('click', () => {
		if (isOpen) {
			closePopup();
		} else {
			openPopup('picker');
		}
	});

	picker.querySelectorAll('[data-channel]').forEach((card) => {
		card.addEventListener('click', () => {
			const channel = card.getAttribute('data-channel');

			if (channel === 'email') {
				showView('email');
			} else if (channel === 'chat') {
				showView('chat');
			}
		});
	});

	document.querySelectorAll('.chatkit-back-button').forEach((backBtn) => {
		backBtn.addEventListener('click', () => {
			showView('picker');
		});
	});

	if (emailForm) {
		emailForm.addEventListener('submit', (event) => {
			event.preventDefault();
			const emailInput = emailForm.querySelector('input[name="email"]');
			const messageInput = emailForm.querySelector('textarea[name="message"]');
			const destination = (emailPanel.dataset.contactEmail || '').trim();

			if (!destination) {
				showUserError('Email destination not configured. Please contact the site administrator.');
				return;
			}

			const fromEmail = emailInput ? emailInput.value.trim() : '';
			const message = messageInput ? messageInput.value.trim() : '';
			const subject = encodeURIComponent(`Website enquiry from ${fromEmail || 'visitor'}`);
			const lines = [];

			if (fromEmail) {
				lines.push(`From: ${fromEmail}`);
				lines.push('');
			}

			if (message) {
				lines.push(message);
			}

			const body = encodeURIComponent(lines.join('\n'));
			window.location.href = `mailto:${destination}?subject=${subject}&body=${body}`;
			closePopup();
		});
	}

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && isOpen) {
			closePopup();
		}
	});

	document.addEventListener('click', (event) => {
		if (!isOpen) {
			return;
		}

		const isInside = containers.some((container) => container && container.contains(event.target));
		const isButton = button.contains(event.target);

		if (!isInside && !isButton) {
			closePopup();
		}
	});

	button.addEventListener('mouseenter', () => nudgeManager.hide());
	nudgeManager.scheduleInitial();

	return { openPopup, closePopup };
};

const initChatKit = async () => {
	if (!config.restUrl) {
		console.error('ChatKit configuration missing: restUrl not defined');
		const errorMsg = config.i18n?.configError || '⚠️ Chat configuration error. Please contact support.';
		showUserError(errorMsg);
		return;
	}

	applyBodyAttributes(config);
await loadChatkitScript(config);

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
