/* ══ Xoos AI Chatbot — Powered by Puter.js ══ */

const XD_SYSTEM_PROMPT = [
  'You are Xoos AI, the smart assistant for Xoos Digital —',
  'a full-service creative agency based in Dhaka, Bangladesh.',
  'Founded by Raihan Islam, Founder & Digital Growth Strategist.',
  '',
  'YOUR PERSONALITY:',
  '- Friendly, professional, confident',
  '- Speak like a knowledgeable agency team member',
  '- Keep replies concise (2-4 sentences max unless asked for detail)',
  '- Use occasional line breaks for readability',
  '- Never use bullet points — write in natural sentences',
  '- End responses with a subtle nudge toward starting a project',
  '',
  'ABOUT XOOS DIGITAL:',
  'Services: Creative Branding & Logo Design, Custom WordPress',
  '& WooCommerce Development, Digital Marketing (Facebook/Google Ads,',
  'Social Media), SEO & Organic Growth, Video Production & Motion Graphics.',
  '',
  'Stats: 80+ clients served, 50+ brands designed, 30+ websites',
  'launched, 4+ years experience, clients across 12+ countries.',
  '',
  'Pricing: Branding from $299, WordPress websites from $499,',
  'SEO from $349/month, Digital Marketing from $399/month,',
  'Full packages from $799. Custom quotes available.',
  '',
  'Process: Discovery \u2192 Strategy \u2192 Execution \u2192 Launch & Grow.',
  'Typical timelines: Branding 5-7 days, Website 2-4 weeks,',
  'SEO results in 60-90 days.',
  '',
  'Contact: xoosdigital@gmail.com | +880 1572-932943',
  'WhatsApp: https://wa.me/8801572932943',
  'Location: Khilgaon, Dhaka, Bangladesh',
  '',
  'WHAT YOU CAN HELP WITH:',
  '- Answer questions about services, pricing, timeline, process',
  '- Help visitors decide which service they need',
  '- Encourage visitors to start a project (trigger the form)',
  '- Answer general branding, web, SEO, marketing questions',
  '- Provide basic advice on digital strategy',
  '',
  'WHAT YOU CANNOT DO:',
  '- Make binding quotes or guarantees',
  '- Access real-time data or the visitor website',
  '- Remember previous conversations (each chat starts fresh)',
  '',
  'SPECIAL TRIGGER:',
  'If the user seems ready to start a project or asks how to',
  'get started, end your reply with this exact string on its own line:',
  '[SHOW_PROJECT_FORM]',
  'This will trigger the project form popup automatically.',
  '',
  'Keep all replies under 100 words unless the user explicitly',
  'asks for a detailed explanation.'
].join('\n');

(function() {
  let xdOpen = false;
  let xdLoading = false;
  let xdHistory = [];
  let xdFirstMessage = true;

  function xdToggle() {
    xdOpen = !xdOpen;
    var win = document.getElementById('xdChatWindow');
    var btn = document.getElementById('xdChatBtn');

    if (xdOpen) {
      win.classList.add('xd-open');
      btn.classList.add('xd-is-open');
      btn.innerHTML = xdCloseIcon();
      xdStopBubbleCycle();
      setTimeout(function() {
        var inp = document.getElementById('xdInput');
        if (inp) inp.focus();
      }, 400);
    } else {
      win.classList.remove('xd-open');
      btn.classList.remove('xd-is-open');
      btn.innerHTML = xdChatIcon();
      setTimeout(function() {
        xdBubbleIndex = 0;
        xdStartBubbleCycle();
      }, 5000);
    }
  }

  async function xdSend(text) {
    var input = document.getElementById('xdInput');
    var msg = (text || input.value).trim();
    if (!msg || xdLoading) return;

    if (!text) {
      input.value = '';
      input.style.height = 'auto';
    }

    if (xdFirstMessage) {
      xdFirstMessage = false;
      var qr = document.getElementById('xdQuickReplies');
      if (qr) qr.style.display = 'none';
    }

    xdAddMessage(msg, 'user');
    xdHistory.push({ role: 'user', content: msg });

    xdLoading = true;
    xdSetSendDisabled(true);
    var typingId = xdShowTyping();

    var messages = [{ role: 'system', content: XD_SYSTEM_PROMPT }];
    for (var i = 0; i < xdHistory.length; i++) {
      messages.push(xdHistory[i]);
    }

    try {
      var response = await fetch('chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ messages: messages })
      });

      if (!response.ok) {
        let errMsg = 'Service unavailable. Please try again.';
        try {
          const errData = await response.json();
          if (response.status === 429) {
            errMsg = errData.error || 'Too many messages. Please wait a moment.';
          } else if (errData.error) {
            errMsg = errData.error;
          }
        } catch(e) {}
        throw new Error(errMsg);
      }

      const data = await response.json();
      var reply = data.reply || "Sorry, I could not process that.";

      xdRemoveTyping(typingId);

      var showForm = false;
      if (reply.indexOf('[SHOW_PROJECT_FORM]') !== -1) {
        reply = reply.replace('[SHOW_PROJECT_FORM]', '').trim();
        var projectIntent = ['start a project', 'i want to start', 'get started', 'hire', 'work with you'];
        var lowerMsg = msg.toLowerCase();
        for (var ki = 0; ki < projectIntent.length; ki++) {
          if (lowerMsg.indexOf(projectIntent[ki]) !== -1) {
            showForm = true;
            break;
          }
        }
      }

      xdAddMessage(reply, 'bot');
      xdHistory.push({ role: 'assistant', content: reply });

      if (xdHistory.length > 20) {
        xdHistory = xdHistory.slice(xdHistory.length - 20);
      }

      if (showForm) {
        setTimeout(function() {
          if (typeof openProjectForm === 'function') {
            openProjectForm();
          }
        }, 800);
      }
    } catch (error) {
      xdRemoveTyping(typingId);
      xdAddMessage(
        error.message || "Sorry, something went wrong. Please try again or reach us on WhatsApp.",
        'bot'
      );
    }

    xdLoading = false;
    xdSetSendDisabled(false);
    var inp = document.getElementById('xdInput');
    if (inp) inp.focus();
  }

  function xdAddMessage(text, role) {
    var container = document.getElementById('xdMessages');
    var div = document.createElement('div');
    div.className = role === 'user' ? 'xd-msg-user' : 'xd-msg-bot';
    div.innerHTML = text.replace(/\n/g, '<br>');
    div.style.opacity = '0';
    div.style.transform = 'translateY(8px)';
    container.appendChild(div);

    requestAnimationFrame(function() {
      div.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
      div.style.opacity = '1';
      div.style.transform = 'translateY(0)';
    });

    container.scrollTop = container.scrollHeight;
  }

  function xdShowTyping() {
    var container = document.getElementById('xdMessages');
    var id = 'xdTyping_' + Date.now();
    var div = document.createElement('div');
    div.className = 'xd-typing';
    div.id = id;
    div.innerHTML = '<span class="xd-dot"></span><span class="xd-dot"></span><span class="xd-dot"></span>';
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    return id;
  }

  function xdRemoveTyping(id) {
    var el = document.getElementById(id);
    if (el) el.remove();
  }

  function xdSetSendDisabled(disabled) {
    var btn = document.getElementById('xdSendBtn');
    if (btn) btn.disabled = disabled;
  }

  function xdClear() {
    xdHistory = [];
    xdFirstMessage = true;
    var container = document.getElementById('xdMessages');
    container.innerHTML = '';
    xdAddMessage("Hi! I'm Xoos AI \uD83D\uDC4B I can help you learn about our services, pricing, and how we can grow your brand online. What can I help you with today?", 'bot');
    var qr = document.getElementById('xdQuickReplies');
    if (qr) qr.style.display = 'flex';
  }

  function xdChatIcon() {
    return '<div class="xd-ripple"></div><div class="xd-ripple-2"></div><div class="xd-btn-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#CCFF00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><circle cx="9" cy="10" r="0.8" fill="#CCFF00" stroke="none"/><circle cx="12" cy="10" r="0.8" fill="#CCFF00" stroke="none"/><circle cx="15" cy="10" r="0.8" fill="#CCFF00" stroke="none"/></svg></div>';
  }

  function xdCloseIcon() {
    return '<div class="xd-btn-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#CCFF00" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div>';
  }

  function xdSendIcon() {
    return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="2.5" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>';
  }

  var XD_BUBBLE_MESSAGES = [
    "What service do you need?",
    "Need a website?",
    "Let's grow your brand!",
    "Branding, SEO, Marketing",
    "Ask me anything!"
  ];
  var xdBubbleIndex = 0;
  var xdBubbleCharPos = 0;
  var xdBubbleTimer = null;
  var xdBubbleVisible = false;

  function xdBubbleTextEl() {
    return document.getElementById('xdBubbleText');
  }

  function xdShowBubble() {
    var bubble = document.getElementById('xdBubble');
    if (!bubble) return;
    bubble.classList.add('xd-bubble-show');
    bubble.classList.remove('xd-bubble-hidden');
    xdBubbleVisible = true;
  }

  function xdHideBubble() {
    var bubble = document.getElementById('xdBubble');
    if (!bubble) return;
    bubble.classList.remove('xd-bubble-show');
    xdBubbleVisible = false;
  }

  function xdTypeNextChar() {
    if (xdOpen) { xdBubbleTimer = setTimeout(xdTypeNextChar, 200); return; }
    var el = xdBubbleTextEl();
    if (!el || !xdBubbleVisible) return;

    var msg = XD_BUBBLE_MESSAGES[xdBubbleIndex];
    if (xdBubbleCharPos < msg.length) {
      xdBubbleCharPos++;
      el.innerHTML = msg.substring(0, xdBubbleCharPos) + '<span class="xd-cursor"></span>';
      xdBubbleTimer = setTimeout(xdTypeNextChar, 35 + Math.random() * 45);
    } else {
      /* Full message typed — pause then erase */
      el.innerHTML = msg + '<span class="xd-cursor"></span>';
      xdBubbleTimer = setTimeout(xdEraseChar, 3000);
    }
  }

  function xdEraseChar() {
    if (xdOpen) { xdBubbleTimer = setTimeout(xdEraseChar, 200); return; }
    var el = xdBubbleTextEl();
    if (!el || !xdBubbleVisible) return;

    var txt = el.textContent;
    if (txt.length > 0) {
      el.innerHTML = txt.substring(0, txt.length - 1) + '<span class="xd-cursor"></span>';
      xdBubbleTimer = setTimeout(xdEraseChar, 15 + Math.random() * 20);
    } else {
      /* Move to next message or cycle */
      xdBubbleCharPos = 0;
      xdBubbleIndex = (xdBubbleIndex + 1) % XD_BUBBLE_MESSAGES.length;

      if (xdBubbleIndex === 0) {
        /* All messages shown — hide for 20s then restart */
        xdHideBubble();
        xdBubbleTimer = setTimeout(function() {
          xdStartBubbleCycle();
        }, 20000);
      } else {
        xdBubbleTimer = setTimeout(xdTypeNextChar, 300);
      }
    }
  }

  function xdStartBubbleCycle() {
    clearTimeout(xdBubbleTimer);
    xdBubbleCharPos = 0;

    xdBubbleTimer = setTimeout(function() {
      xdShowBubble();
      xdTypeNextChar();
    }, 3000);
  }

  function xdStopBubbleCycle() {
    clearTimeout(xdBubbleTimer);
    xdHideBubble();
  }

  function xdInit() {
    xdAddMessage("Hi! I'm Xoos AI \uD83D\uDC4B I can help you learn about our services, pricing, and how we can grow your brand online. What can I help you with today?", 'bot');
    xdStartBubbleCycle();
    window.xdSend = xdSend;
    window.xdClear = xdClear;
    window.xdToggle = xdToggle;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', xdInit);
  } else {
    xdInit();
  }

  document.addEventListener('mouseover', function(e) {
    var btn = document.getElementById('xdChatBtn');
    if (!btn) return;
    if (btn.contains(e.target) && !xdOpen && !xdBubbleVisible) {
      clearTimeout(xdBubbleTimer);
      xdBubbleIndex = 0;
      xdBubbleCharPos = 0;
      xdShowBubble();
      xdTypeNextChar();
      setTimeout(function() {
        if (xdBubbleVisible) xdHideBubble();
      }, 3000);
    }
  });
})();
