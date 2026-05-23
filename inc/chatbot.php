<!-- ══ XOOS AI CHATBOT ══ -->
<div id="xdWidget">
  <div class="xd-bubble" id="xdBubble">
    <div class="xd-bubble-glow"></div>
    <span id="xdBubbleText">Hey! 👋 Got a question?</span>
    <span class="xd-cursor"></span>
  </div>
  <button id="xdChatBtn" onclick="window.xdToggle()" aria-label="Toggle AI chat">
    <span class="xd-ripple"></span>
    <span class="xd-ripple-2"></span>
    <span class="xd-btn-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#CCFF00" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
      </svg>
    </span>
  </button>
  <div id="xdChatWindow">
    <div class="xd-header">
      <div class="xd-avatar">AI</div>
      <div class="xd-header-info">
        <div class="xd-header-name">Xoos AI</div>
        <div class="xd-header-status">
          <span class="xd-status-dot"></span>
          <span>Online — Ask me anything!</span>
        </div>
      </div>
      <button class="xd-clear-btn" onclick="window.xdClear()">✕ CLEAR</button>
    </div>
    <div id="xdMessages"></div>
    <div id="xdQuickReplies">
      <button class="xd-qr" onclick="window.xdSend('Tell me about your pricing and packages')">💰 Pricing & Packages</button>
      <button class="xd-qr" onclick="window.xdSend('What branding services do you offer?')">🎨 Branding Services</button>
      <button class="xd-qr" onclick="window.xdSend('Tell me about your WordPress development service')">🌐 Website Development</button>
      <button class="xd-qr" onclick="window.xdSend('I want to start a project')">🚀 Start a Project</button>
    </div>
    <div class="xd-input-area">
      <textarea class="xd-textarea" id="xdInput" rows="1" placeholder="Ask me anything..." onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();window.xdSend()}"></textarea>
      <button class="xd-send-btn" id="xdSendBtn" onclick="window.xdSend()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
        </svg>
      </button>
    </div>
    <div class="xd-powered">Powered by Xoos AI</div>
  </div>
</div>
<!-- ══ END XOOS AI CHATBOT ══ -->
