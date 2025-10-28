import { toBool } from './utils';
import { FeatureFlags } from './pro-module';

export class AdvancedOptionsConfigurator {
	constructor(config, flags = new FeatureFlags(config)) {
		this.config = config || {};
		this.flags = flags;
	}

	enhancementsEnabled() {
		return this.flags.isAdvancedOptionsEnabled();
	}

	applyAttachments(options) {
		const enabled = toBool(this.config.enableAttachments);
		options.composer = options.composer || {};
		options.composer.attachments = options.composer.attachments || {};
		options.composer.attachments.enabled = enabled;

		if (!enabled) {
			return;
		}

		const maxSize = parseInt(this.config.attachmentMaxSize || 20, 10);
		const maxCount = parseInt(this.config.attachmentMaxCount || 3, 10);

		options.composer.attachments = Object.assign(options.composer.attachments, {
			maxSize: maxSize * 1024 * 1024,
			maxCount,
			accept: {
				'application/pdf': ['.pdf'],
				'image/*': ['.png', '.jpg', '.jpeg', '.gif', '.webp'],
				'text/plain': ['.txt'],
			},
		});
	}

	applyInitialThread(options) {
		const initialThread = this.config.initialThreadId || this.config.initial_thread_id;

		if (initialThread && initialThread.trim() !== '') {
			options.initialThread = initialThread;
		}
	}

	applyDisclaimer(options) {
		const disclaimer = this.config.disclaimerText || this.config.disclaimer_text;

		if (disclaimer && disclaimer.trim() !== '') {
			options.disclaimer = {
				text: disclaimer,
				highContrast: toBool(this.config.disclaimerHighContrast || this.config.disclaimer_high_contrast),
			};
		}
	}

	applyTypography(options) {
		const customFont = this.config.customFont || {
			fontFamily: this.config.font_family,
			baseSize: this.config.font_size,
		};

		if (customFont && customFont.fontFamily && customFont.fontFamily.trim() !== '') {
			options.theme.typography = {
				fontFamily: customFont.fontFamily,
				baseSize: parseInt(customFont.baseSize || 16, 10),
			};
		}
	}

	buildHeaderConfig() {
		if (!toBool(this.config.showHeader)) {
			return { enabled: false };
		}

		const header = { enabled: true };
		const title = this.config.headerTitleText || this.config.header_title_text;

		if (title && title.trim() !== '') {
			header.title = {
				enabled: true,
				text: title,
			};
		}

		const handlers = [
			{
				icon: this.config.headerLeftIcon || this.config.header_left_icon,
				url: this.config.headerLeftUrl || this.config.header_left_url,
				target: 'leftAction',
			},
			{
				icon: this.config.headerRightIcon || this.config.header_right_icon,
				url: this.config.headerRightUrl || this.config.header_right_url,
				target: 'rightAction',
			},
		];

		handlers.forEach(({ icon, url, target }) => {
			if (!icon || !url || url.trim() === '') {
				return;
			}

			try {
				new URL(url);
				header[target] = {
					icon,
					onClick: () => {
						window.location.href = url;
					},
				};
			} catch (error) {
				// eslint-disable-next-line no-console
				console.warn('⚠️ Invalid header button URL, skipping', url);
			}
		});

		return header;
	}

	applyHeader(options) {
		options.header = this.buildHeaderConfig();
	}

	applyHistory(options) {
		options.history = { enabled: toBool(this.config.showHistory || this.config.show_history) };
	}

	applyLocale(options) {
		const locale = this.config.locale;

		if (locale && locale.trim() !== '') {
			options.locale = locale;
		}
	}

	apply(options) {
		if (!this.enhancementsEnabled()) {
			options.header = { enabled: false };
			options.history = { enabled: true };
			return;
		}

		this.applyAttachments(options);
		this.applyInitialThread(options);
		this.applyDisclaimer(options);
		this.applyTypography(options);
		this.applyHeader(options);
		this.applyHistory(options);
		this.applyLocale(options);
	}
}
