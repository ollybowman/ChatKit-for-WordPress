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
      // Validazione config
      if (!config.restUrl) {
        throw new Error('Missing configuration');
      }

      // Headers base per la richiesta
      const headers = {
        'Content-Type': 'application/json'
      };

      // Timeout per la richiesta
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 10000);

      const response = await fetch(config.restUrl, {
        method: 'POST',
        headers: headers,
        signal: controller.signal,
        credentials: 'same-origin' // Include cookies
      });

      clearTimeout(timeoutId);

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        
        console.error('ChatKit Session Error:', {
          status: response.status,
          statusText: response.statusText,
          error: errorData
        });
        
        throw new Error(errorData.message || `HTTP ${response.status}`);
      }

      const data = await response.json();
      
      if (!data.client_secret) {
        throw new Error('Invalid response: missing client_secret');
      }
      
      return data.client_secret;

    } catch (error) {
      console.error('Fetch Session Error:', error);

      const errorMessage = config.i18n?.unableToStart || '⚠️ Unable to start chat. Please try again later.';
      
      const el = document.getElementById('myChatkit');
      if (el && el.parentNode) {
        const errorDiv = document.createElement('div');
        errorDiv.style.cssText = 'padding: 20px; text-align: center; color: #721c24; background: #f8d7da; border-radius: 8px; margin: 20px;';
        errorDiv.setAttribute('role', 'alert');
        errorDiv.innerHTML = `<p style="margin: 0; font-size: 14px;">${errorMessage}</p>`;
        el.parentNode.insertBefore(errorDiv, el);
      }

      // Analytics opzionale
      if (typeof gtag !== 'undefined') {
        gtag('event', 'exception', {
          description: 'ChatKit session error: ' + error.message,
          fatal: false
        });
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
      chatkit.setAttribute('aria-modal', isOpen);
      
      if (isOpen) {
        button.classList.add('chatkit-open');
        button.textContent = '✕'; // FIX: Carattere unicode corretto
        chatkit.style.animation = 'chatkit-slide-up 0.3s ease-out';
        
        // Focus management
        setTimeout(() => chatkit.focus(), 100);
        
        // Impedisci scroll body su mobile
        if (window.innerWidth <= 768) {
          document.body.style.overflow = 'hidden';
        }
      } else {
        button.classList.remove('chatkit-open');
        button.textContent = originalText;
        button.focus();
        
        // Ripristina scroll
        document.body.style.overflow = '';
      }
    });

    // Gestione ESC key per accessibilità
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && isOpen) {
        button.click();
      }
    });

    // Chiudi al click fuori (opzionale)
    document.addEventListener('click', (e) => {
      if (isOpen && 
          !chatkit.contains(e.target) && 
          !button.contains(e.target)) {
        button.click();
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
    errorDiv.style.cssText = 'position: fixed; bottom: 20px; right: 20px; padding: 15px 20px; background: #f8d7da; color: #721c24; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.15); z-index: 9999; max-width: 300px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;';
    errorDiv.setAttribute('role', 'alert');
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
      // Validazione config iniziale
      if (!config.restUrl) {
        console.error('ChatKit configuration missing: restUrl not defined');
        const errorMsg = config.i18n?.configError || '⚠️ Chat configuration error. Please contact support.';
        showUserError(errorMsg);
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
          const errorMsg = config.i18n?.loadFailed || '⚠️ Chat widget failed to load. Please refresh the page.';
          showUserError(errorMsg);
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

      // Analytics opzionale
      if (typeof gtag !== 'undefined') {
        gtag('event', 'chatkit_initialized', {
          event_category: 'engagement',
          event_label: 'ChatKit Ready'
        });
      }

    } catch (error) {
      console.error('❌ ChatKit Initialization Error:', error);
      
      if (retryCount < MAX_RETRIES) {
        retryCount++;
        console.log(`Retrying after error (${retryCount}/${MAX_RETRIES})...`);
        setTimeout(initChatKit, 2000);
      } else {
        const errorMsg = config.i18n?.loadFailed || '⚠️ Chat initialization failed. Please refresh the page.';
        showUserError(errorMsg);
      }
    }
  }

  // Inizializzazione
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initChatKit);
  } else {
    // Se il DOM è già carico, aspetta un tick
    setTimeout(initChatKit, 0);
  }

  // Cleanup per evitare memory leaks
  window.addEventListener('beforeunload', () => {
    document.body.style.overflow = '';
  });
})();
