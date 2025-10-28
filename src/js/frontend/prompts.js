import { FeatureFlags } from './pro-module';

export class PromptManager {
	constructor(config, flags = new FeatureFlags(config)) {
		this.config = config || {};
		this.flags = flags;
	}

	isEnabled() {
		return this.flags.isQuickPromptEnabled();
	}

	extractPrompt(labelBase, textBase, iconBase) {
		const label = this.config[labelBase] ?? this.config[this.toSnakeCase(labelBase)];
		const text = this.config[textBase] ?? this.config[this.toSnakeCase(textBase)];
		const icon = this.config[iconBase] ?? this.config[this.toSnakeCase(iconBase)] ?? 'circle-question';

		if (!label || !text) {
			return null;
		}

		return {
			icon,
			label,
			prompt: text,
		};
	}

	toSnakeCase(key) {
		return key
			.replace(/([a-z0-9])([A-Z])/g, '$1_$2')
			.replace(/([A-Za-z])([0-9]+)/g, '$1_$2')
			.toLowerCase();
	}

	getPrompts() {
		if (!this.isEnabled()) {
			return [];
		}

		const configuredPrompts = this.config.prompts;
		const prompts = [];

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
			for (let index = 1; index <= 5; index += 1) {
				const prompt = this.extractPrompt(
					`defaultPrompt${index}`,
					`defaultPrompt${index}Text`,
					`defaultPrompt${index}Icon`,
				);

				if (prompt) {
					prompts.push(prompt);
				}
			}
		}

		if (!prompts.length) {
			prompts.push({
				icon: 'circle-question',
				label: 'How can I assist you?',
				prompt: 'Hi! How can I assist you today?',
			});
		}

		return prompts;
	}
}
