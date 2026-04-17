import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import './styles/global.css';
import './porkAIbot.css';
import PorkbotFloatingWidget from './components/PorkbotFloatingWidget';

declare global {
  interface Window {
    PorkbotWidget?: { init: () => void };
  }
}

function mount(): void {
  if (document.getElementById('porkbot-chat-widget-root')) {
    return;
  }

  const container = document.createElement('div');
  container.id = 'porkbot-chat-widget-root';
  document.body.appendChild(container);

  createRoot(container).render(
    <StrictMode>
      <PorkbotFloatingWidget />
    </StrictMode>
  );
}

window.PorkbotWidget = { init: mount };

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mount, { once: true });
} else {
  mount();
}
