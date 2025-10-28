export class ChatToggleController {
	constructor({ button, chatkitElement, channelPicker, accentColor, originalLabel, closeLabel, flags }) {
		this.button = button;
		this.chatkitElement = chatkitElement;
		this.channelPicker = channelPicker && channelPicker.isEnabled() ? channelPicker : null;
		this.accentColor = accentColor || '#FF4500';
		this.originalLabel = originalLabel || 'Toggle chat window';
		this.closeLabel = closeLabel || this.originalLabel;
		this.nudgeManager = null;
		this.isOpen = false;
		this.flags = flags;

		this.containers = [this.chatkitElement];
	}

	setNudgeManager(nudgeManager) {
		this.nudgeManager = nudgeManager;
	}

	init({ onOpenFallbackChat } = {}) {
		if (!this.button || !this.chatkitElement) {
			return false;
		}

		if (this.flags.isChannelPickerEnabled() && this.channelPicker) {
			this.channelPicker.init({
				onChannelSelected: (channel) => this.handleChannelSelection(channel, onOpenFallbackChat),
				onEmailSent: () => this.close(),
			});
			this.containers.push(...this.channelPicker.getContainers());
		}

		this.button.addEventListener('click', () => {
			if (this.isOpen) {
				this.close();
			} else {
				this.open();
			}
		});

		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape' && this.isOpen) {
				this.close();
			}
		});

		document.addEventListener('click', (event) => {
			if (!this.isOpen) {
				return;
			}

			const clickedInside = this.containers.some((container) => container && container.contains(event.target));

			if (!clickedInside && !this.button.contains(event.target)) {
				this.close();
			}
		});

		return true;
	}

	handleChannelSelection(channel, onOpenFallbackChat) {
		if (channel === 'email') {
			this.channelPicker?.showEmail();
			return;
		}

		if (channel === 'chat') {
			this.showChat();
			return;
		}

		if (typeof onOpenFallbackChat === 'function') {
			onOpenFallbackChat();
		}
	}

	scheduleInitialNudge() {
		this.nudgeManager?.scheduleInitial?.();
	}

	open(initialView) {
		if (this.isOpen) {
			return;
		}

		this.isOpen = true;
		this.button.classList.add('chatkit-open');
		this.button.style.backgroundColor = this.accentColor;
		this.button.setAttribute('aria-expanded', 'true');
		this.button.setAttribute('aria-label', this.closeLabel);

		if (this.flags.isChannelPickerEnabled() && this.channelPicker && (initialView === 'picker' || !initialView)) {
			this.channelPicker.showPicker();
			this.channelPicker.focusPicker();
		} else if (this.flags.isChannelPickerEnabled() && this.channelPicker && initialView === 'email') {
			this.channelPicker.showEmail();
		} else {
			this.showChat();
		}

		this.nudgeManager?.cancel?.();
		this.nudgeManager?.hide?.();

		if (window.innerWidth <= 768) {
			document.body.style.overflow = 'hidden';
		}
	}

	showChat() {
		this.channelPicker?.hideAll();
		this.chatkitElement.hidden = false;
		this.chatkitElement.style.display = 'block';
		this.chatkitElement.setAttribute('aria-modal', 'true');

		setTimeout(() => this.chatkitElement.focus(), 140);
	}

	close() {
		if (!this.isOpen) {
			return;
		}

		this.isOpen = false;
		this.button.classList.remove('chatkit-open');
		this.button.style.backgroundColor = this.accentColor;
		this.button.setAttribute('aria-expanded', 'false');
		this.button.setAttribute('aria-label', this.originalLabel);

		this.channelPicker?.hideAll();
		this.chatkitElement.hidden = true;
		this.chatkitElement.style.display = 'none';
		this.chatkitElement.setAttribute('aria-modal', 'false');

		document.body.style.overflow = '';
		this.nudgeManager?.scheduleRepeat?.();
	}
}
