const MILLISECONDS = 1000;

export const initNudge = (config = {}, button, openCallback) => {
	const nudge = document.getElementById('chatkitNudge');
	const dismissButton = nudge ? nudge.querySelector('.chatkit-nudge__dismiss') : null;

	const enabled = Boolean(config.enabled && nudge);
	const initialDelay = Math.max(3, parseInt(config.initialDelay || 12, 10)) * MILLISECONDS;
	const repeatDelay = Math.max(10, parseInt(config.repeatDelay || 36, 10)) * MILLISECONDS;

	let timerId = null;
	let dismissed = false;

	const show = () => {
		if (!enabled || dismissed || !nudge) {
			return;
		}

		nudge.hidden = false;
	};

	const hide = () => {
		if (!nudge) {
			return;
		}

		nudge.hidden = true;
	};

	const cancel = () => {
		if (timerId) {
			clearTimeout(timerId);
			timerId = null;
		}
	};

	const schedule = (delay) => {
		if (!enabled || dismissed) {
			return;
		}

		cancel();
		timerId = setTimeout(() => {
			show();
			schedule(repeatDelay);
		}, delay);
	};

	if (enabled && button) {
		button.addEventListener('mouseenter', hide);
	}

	if (enabled && nudge) {
		nudge.addEventListener('click', (event) => {
			if (event.target === dismissButton) {
				return;
			}

			if (typeof openCallback === 'function') {
				openCallback();
			}
		});
	}

	if (enabled && dismissButton) {
		dismissButton.addEventListener('click', (event) => {
			event.stopPropagation();
			dismissed = true;
			hide();
			cancel();
		});
	}

	return {
		enabled,
		scheduleInitial: () => schedule(initialDelay),
		scheduleRepeat: () => schedule(repeatDelay),
		hide,
		cancel,
		markDismissed: () => {
			dismissed = true;
			hide();
			cancel();
		},
	};
};
