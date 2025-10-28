export class ChannelPicker {
	constructor(config, { showError, flags }) {
		this.config = config || {};
		this.flags = flags;
		this.showError = showError;

		this.picker = document.getElementById('chatkitChannelPicker');
		this.emailPanel = document.getElementById('chatkitEmailPanel');
		this.emailForm = document.getElementById('chatkitEmailForm');
		this.backButtons = Array.from(document.querySelectorAll('.chatkit-back-button'));

		this.onChannelSelected = null;
		this.onEmailSent = null;
	}

	isEnabled() {
		return this.flags.isChannelPickerEnabled() && this.picker && this.emailPanel;
	}

	getContainers() {
		return [this.picker, this.emailPanel].filter(Boolean);
	}

	hideAll() {
		if (this.picker) {
			this.picker.hidden = true;
			this.picker.style.display = 'none';
		}

		if (this.emailPanel) {
			this.emailPanel.hidden = true;
			this.emailPanel.style.display = 'none';
		}
	}

	showPicker() {
		if (!this.isEnabled()) {
			return;
		}

		if (this.emailPanel) {
			this.emailPanel.hidden = true;
			this.emailPanel.style.display = 'none';
		}

		this.picker.hidden = false;
		this.picker.style.display = 'flex';
	}

	focusPicker() {
		if (!this.isEnabled()) {
			return;
		}

		const firstCard = this.picker.querySelector('.chatkit-channel-card');
		firstCard?.focus();
	}

	showEmail() {
		if (!this.isEnabled()) {
			return;
		}

		if (this.picker) {
			this.picker.hidden = true;
			this.picker.style.display = 'none';
		}

		if (this.emailPanel) {
			this.emailPanel.hidden = false;
			this.emailPanel.style.display = 'block';

			setTimeout(() => {
				const emailInput = this.emailPanel.querySelector('input[name="email"]');
				emailInput?.focus();
			}, 120);
		}
	}

	handleEmailSubmit(event) {
		event.preventDefault();

		const destination = (this.emailPanel?.dataset?.contactEmail || '').trim();

		if (!destination) {
			this.showError?.('Email destination not configured. Please contact the site administrator.');
			return;
		}

		const emailInput = this.emailForm?.querySelector('input[name="email"]');
		const messageInput = this.emailForm?.querySelector('textarea[name="message"]');

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

		this.onEmailSent?.();
	}

	attachChannelHandlers() {
		if (!this.picker) {
			return;
		}

		this.picker.querySelectorAll('[data-channel]').forEach((card) => {
			card.addEventListener('click', () => {
				const channel = card.getAttribute('data-channel');
				this.onChannelSelected?.(channel);
			});
		});
	}

	attachBackHandlers() {
		this.backButtons.forEach((button) => {
			button.addEventListener('click', () => {
				this.showPicker();
				this.focusPicker();
			});
		});
	}

	init({ onChannelSelected, onEmailSent }) {
		if (!this.isEnabled()) {
			return;
		}

		this.onChannelSelected = onChannelSelected;
		this.onEmailSent = onEmailSent;

		this.attachChannelHandlers();
		this.attachBackHandlers();

		if (this.emailForm) {
			this.emailForm.addEventListener('submit', (event) => this.handleEmailSubmit(event));
		}
	}
}
