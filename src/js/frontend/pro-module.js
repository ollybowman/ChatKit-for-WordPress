export const isProEnabled = (config = {}) => {
	if (config?.features?.mode) {
		return config.features.mode === 'pro';
	}

	if (typeof config.isPro === 'boolean') {
		return config.isPro;
	}

	return true;
};

export class FeatureFlags {
	constructor(config) {
		this.config = config || {};
	}

	resolveFlag(key) {
		if (this.config?.features && Object.prototype.hasOwnProperty.call(this.config.features, key)) {
			return this.config.features[key] !== false;
		}

		return isProEnabled(this.config);
	}

	isQuickPromptEnabled() {
		return this.resolveFlag('quickPrompts');
	}

	isAdvancedOptionsEnabled() {
		return this.resolveFlag('advancedOptions');
	}

	isChannelPickerEnabled() {
		return this.resolveFlag('channelPicker');
	}
}
