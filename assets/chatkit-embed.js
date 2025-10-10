(function() {
  'use strict';

  const config = typeof chatkitConfig !== 'undefined' ? chatkitConfig : {};
  let isOpen = false;
  let retryCount = 0;
  const MAX_RETRIES = 3;

  function loadChatkitScript() {
    return new Promise((resolve, reject) => {
      if (customElements.get('openai-chatkit')) {
        resolve();
        return;
      }

      const script = document.createElement('script');
      script.src = 'https://cdn.platform.openai.com/deployments/chatkit/chatkit.js';
      script.defer = true;
      script.onload = resolve;
      script.onerror = () => reject(new Error('Failed to load ChatKit CDN'));
      document.head.appendChild(script);
    });
  }

  async function getClientSecret() {
    try {
      const response = await fetch(config.restUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        const errorData = await response.json();
        console.error('ChatKit Session Error:', errorData);
        throw new Error(errorData.message || 'Unable to create session');
      }

      const data = await response.json();
      return data.client_secret;
    } catch (error) {
      console.error('Fetch Session Error:', error);

      const el = document.getElementById('myChatkit');
      if (el && el.parentNode) {
        const errorDiv = document.createElement('div');
        errorDiv.style.cssText = 'padding: 20px; text-align: center; color: #721c24; background: #f8d7da; border-radius: 8px; margin: 20px;';
        errorDiv.innerHTML = '<p style="margin: 0; font-size: 14px;">⚠️ Unable to start chat. Please try again later.</p>';
        el.parentNode.insertBefore(errorDiv, el);
      }

      return null;
    }
  }

  function setupToggle() {
    const button = document.getElementById('chatToggleBtn');
    const chatkit = document.getElementById('myChatkit');

    if (!button || !chatkit) {
      console.warn('ChatKit toggle elements not found');
      return;
    }

    const originalText = button.textContent || config.buttonText || 'Chat now';

    button.addEventListener('click', () => {
      isOpen = !isOpen;
      chatkit.style.display = isOpen ? 'block' : 'none';
      button.setAttribute('aria-expanded', isOpen);
      
      if (isOpen) {
        button.classList.add('chatkit-open');
        button.textContent = '✕';
        chatkit.style.animation = 'chatkit-slide-up 0.3s ease-out';
      } else {
        button.classList.remove('chatkit-open');
        button.textContent = originalText;
      }
    });
  }

  function buildPrompts() {
    const prompts = [];

    if (config.defaultPrompt1 && config.defaultPrompt1Text) {
      prompts.push({
        icon: 'circle-question',
        label: config.defaultPrompt1,
        prompt: config.defaultPrompt1Text
      });
    }

    if (config.defaultPrompt2 && config.defaultPrompt2Text) {
      prompts.push({
        icon: 'circle-question',
        label: config.defaultPrompt2,
        prompt: config.defaultPrompt2Text
      });
    }

    if (config.defaultPrompt3 && config.defaultPrompt3Text) {
      prompts.push({
        icon: 'circle-question',
        label: config.defaultPrompt3,
        prompt: config.defaultPrompt3Text
      });
    }

    return prompts.length > 0 ? prompts : [{
      icon: 'circle-question',
      label: config.defaultPrompt1 || 'How can I assist you?',
      prompt: config.defaultPrompt1Text || 'Hi! How can I assist you today?'
    }];
  }

  function showUserError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.style.cssText = 'position: fixed; bottom: 20px; right: 20px; padding: 15px 20px; background: #f8d7da; color: #721c24; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.15); z-index: 999999; max-width: 300px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;';
    errorDiv.innerHTML = `<p style="margin: 0; font-size: 14px;">${message}</p>`;
    document.body.appendChild(errorDiv);

    setTimeout(() => {
      if (errorDiv.parentNode) {
        errorDiv.style.opacity = '0';
        errorDiv.style.transition = 'opacity 0.3s ease';
        setTimeout(() => errorDiv.remove(), 300);
      }
    }, 5000);
  }

  async function initChatKit() {
    try {
      if (!config.restUrl) {
        console.error('ChatKit configuration missing: restUrl not defined');
        showUserError('⚠️ Chat configuration error. Please contact support.');
        return;
      }

      await loadChatkitScript();

      if (!customElements.get('openai-chatkit')) {
        await customElements.whenDefined('openai-chatkit');
      }

      const chatkitElement = document.getElementById('myChatkit');
      if (!chatkitElement) {
        console.error('Element #myChatkit not found in DOM');
        
        if (retryCount < MAX_RETRIES) {
          retryCount++;
          console.log(`Retrying ChatKit initialization (${retryCount}/${MAX_RETRIES})...`);
          setTimeout(initChatKit, 1000);
        } else {
          showUserError('⚠️ Chat widget failed to load. Please refresh the page.');
        }
        return;
      }

      setupToggle();

      chatkitElement.setOptions({
        api: {
          getClientSecret: getClientSecret
        },
        theme: {
          colorScheme: config.themeMode || 'dark',
          color: {
            accent: {
              primary: config.accentColor || '#FF4500',
              level: 2
            }
          },
          radius: 'round',
          density: 'normal',
          typography: {
            baseSize: 16,
            fontFamily: '"OpenAI Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif'
          }
        },
        composer: {
          attachments: {
            enabled: config.enableAttachments === true
          }
        },
        startScreen: {
          greeting: config.greetingText || 'How can I help you today?',
          prompts: buildPrompts()
        }
      });

      console.log('✅ ChatKit initialized successfully');

    } catch (error) {
      console.error('❌ ChatKit Initialization Error:', error);
      
      if (retryCount < MAX_RETRIES) {
        retryCount++;
        console.log(`Retrying after error (${retryCount}/${MAX_RETRIES})...`);
        setTimeout(initChatKit, 2000);
      } else {
        showUserError('⚠️ Chat initialization failed. Please refresh the page.');
      }
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initChatKit);
  } else {
    initChatKit();
  }
})();
