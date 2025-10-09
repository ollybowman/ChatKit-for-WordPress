(function() {
  'use strict';

  const config = window.chatkitConfig || {};
  let chatkitInstance = null;
  let isOpen = false;

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
      if (el) {
        el.innerHTML = `
          <div style="padding: 20px; text-align: center; color: #721c24; background: #f8d7da;">
            <p style="margin: 0; font-size: 14px;">
              ⚠️ Unable to start chat. Please try again later.
            </p>
          </div>
        `;
      }

      return null;
    }
  }

  function setupToggle() {
    const button = document.getElementById('chatToggleBtn');
    const chatkit = document.getElementById('myChatkit');

    if (!button || !chatkit) return;

    button.addEventListener('click', () => {
      isOpen = !isOpen;
      chatkit.style.display = isOpen ? 'block' : 'none';

      button.setAttribute('aria-expanded', isOpen);
      button.textContent = isOpen ? '✕' : button.getAttribute('data-original-text');

      if (isOpen) {
        chatkit.style.animation = 'chatkit-slide-up 0.3s ease-out';
      }
    });

    button.setAttribute('data-original-text', button.textContent);
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

    return prompts;
  }

  async function initChatKit() {
    try {
      await loadChatkitScript();

      if (!customElements.get('openai-chatkit')) {
        await customElements.whenDefined('openai-chatkit');
      }

      const chatkitElement = document.getElementById('myChatkit');
      if (!chatkitElement) {
        console.error('Element #myChatkit not found');
        return;
      }

      setupToggle();

      const promptsArray = buildPrompts();

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
          greeting: config.greetingText || '',
          prompts: promptsArray.length > 0 ? promptsArray : [
            {
              icon: 'circle-question',
              label: config.defaultPrompt1 || 'How can I assist you?',
              prompt: config.defaultPrompt1Text || 'Hi! How can I assist you today?'
            }
          ]
        }
      });

      console.log('✅ ChatKit initialized successfully');

    } catch (error) {
      console.error('❌ ChatKit Initialization Error:', error);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initChatKit);
  } else {
    initChatKit();
  }
})();