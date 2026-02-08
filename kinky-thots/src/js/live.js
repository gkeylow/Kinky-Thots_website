import '../css/live.css';
import '../css/chat.css';
import '../css/auth-modal.css';
import { auth } from './auth.js';

class LiveChat {
  constructor() {
    /** @type {WebSocket|null} */
    this.ws = null;
    this.username = 'Guest';
    this.userColor = '#FFFFFF';
    this.isAuthenticated = false;
    this.subscriptionTier = null;
    this.messagesContainer = document.getElementById('chatMessages');
    this.chatInput = document.getElementById('chatInput');
    this.sendBtn = document.getElementById('chatSendBtn');
    this.viewersDisplay = document.getElementById('chatViewers');
    this.reconnectAttempts = 0;
    this.maxReconnects = 5;

    // Initialize auth manager and set up auth change callback
    this.authManager = auth.init();
    this.authManager.onAuthChange = () => this.reconnectWithAuth();

    // Only connect if logged in
    const token = localStorage.getItem('kt_auth_token');
    if (token) {
      this.connect();
    }
    this.bindEvents();
  }

  connect() {
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    let wsUrl = `${protocol}//${window.location.host}/ws/chat`;

    // Append JWT token if authenticated
    const token = this.authManager.getToken();
    if (token) {
      wsUrl += `?token=${encodeURIComponent(token)}`;
    }

    this.ws = new WebSocket(wsUrl);

    this.ws.onopen = () => {
      this.reconnectAttempts = 0;
      this.addSystemMessage('Connected to chat');
      const chatStatusDot = document.getElementById('chatStatusDot');
      const chatStatusLabel = document.getElementById('chatStatusLabel');
      chatStatusDot?.classList.replace('offline', 'live');
      chatStatusLabel?.classList.replace('offline', 'live');
      if (chatStatusLabel) {chatStatusLabel.textContent = 'Online';}
    };

    this.ws.onmessage = (event) => {
      this.handleMessage(JSON.parse(event.data));
    };

    this.ws.onclose = () => {
      this.addSystemMessage('Disconnected from chat');
      const chatStatusDot = document.getElementById('chatStatusDot');
      const chatStatusLabel = document.getElementById('chatStatusLabel');
      chatStatusDot?.classList.replace('live', 'offline');
      chatStatusLabel?.classList.replace('live', 'offline');
      if (chatStatusLabel) {chatStatusLabel.textContent = 'Offline';}
      this.tryReconnect();
    };

    this.ws.onerror = () => {};
  }

  tryReconnect() {
    if (this.reconnectAttempts < this.maxReconnects) {
      this.reconnectAttempts++;
      const delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts), 30000);
      setTimeout(() => this.connect(), delay);
    }
  }

  reconnectWithAuth() {
    if (this.ws) {
      this.ws.close();
    }
    setTimeout(() => this.connect(), 100);
  }

  handleMessage(data) {
    switch (data.type) {
      case 'welcome':
        this.username = data.username;
        this.userColor = data.color;
        this.isAuthenticated = data.isAuthenticated || false;
        this.subscriptionTier = data.subscriptionTier || null;
        this.updateViewers(data.viewerCount);
        if (this.isAuthenticated) {
          this.addSystemMessage(`Welcome back, ${this.username}!`);
        }
        break;
      case 'chat':
        this.addChatMessage(data.username, data.message, data.color, data.isAuthenticated, data.subscriptionTier);
        break;
      case 'reaction':
        this.showFloatingEmoji(data.emoji);
        break;
      case 'viewers':
        this.updateViewers(data.count);
        break;
      case 'nameChanged':
        this.username = data.username;
        this.addSystemMessage(`Your name is now ${data.username}`);
        break;
      case 'error':
        this.addSystemMessage(`Error: ${data.message}`);
        break;
      case 'modAction':
        this.handleModAction(data);
        break;
      case 'banned':
        this.addSystemMessage(`You have been banned: ${data.reason}`);
        break;
    }
  }

  handleModAction(data) {
    switch (data.action) {
      case 'ban':
        this.addModMessage(`${data.target} was banned by ${data.moderator}`);
        break;
      case 'unban':
        this.addModMessage(`${data.target} was unbanned by ${data.moderator}`);
        break;
      case 'mute':
        this.addModMessage(`${data.target} was muted for ${data.duration}s by ${data.moderator}`);
        break;
      case 'unmute':
        this.addModMessage(`${data.target} was unmuted by ${data.moderator}`);
        break;
      case 'slow':
        if (data.seconds > 0) {
          this.addModMessage(`Slow mode enabled: ${data.seconds}s between messages`);
        } else {
          this.addModMessage(`Slow mode disabled`);
        }
        break;
      case 'clear':
        this.clearChat();
        this.addModMessage(`Chat cleared by ${data.moderator}`);
        break;
    }
  }

  addModMessage(text) {
    if (!this.messagesContainer) {return;}
    const msgEl = document.createElement('div');
    msgEl.className = 'chat-mod-action';
    msgEl.textContent = `[MOD] ${text}`;
    this.messagesContainer.appendChild(msgEl);
    this.scrollToBottom();
  }

  clearChat() {
    if (!this.messagesContainer) {return;}
    this.messagesContainer.textContent = '';
    const welcome = document.createElement('div');
    welcome.className = 'chat-welcome';
    welcome.textContent = 'Chat was cleared';
    this.messagesContainer.appendChild(welcome);
  }

  bindEvents() {
    if (this.sendBtn) {
      this.sendBtn.addEventListener('click', () => this.sendMessage());
    }

    if (this.chatInput) {
      this.chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {this.sendMessage();}
      });
    }

    document.querySelectorAll('.reaction-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        const emoji = btn.dataset.emoji;
        this.sendReaction(emoji);
        this.showFloatingEmoji(emoji);
      });
    });
  }

  sendMessage() {
    const message = this.chatInput?.value.trim();
    if (!message || !this.ws || this.ws.readyState !== WebSocket.OPEN) {return;}

    if (message.startsWith('/name ')) {
      const newName = message.substring(6).trim();
      this.ws.send(JSON.stringify({ type: 'setName', name: newName }));
    } else {
      this.ws.send(JSON.stringify({ type: 'chat', message }));
    }

    if (this.chatInput) {this.chatInput.value = '';}
  }

  sendReaction(emoji) {
    if (!this.ws || this.ws.readyState !== WebSocket.OPEN) {return;}
    this.ws.send(JSON.stringify({ type: 'reaction', emoji }));
  }

  addChatMessage(username, message, color, isAuthenticated = false, tier = null) {
    if (!this.messagesContainer) {return;}
    const msgEl = document.createElement('div');
    msgEl.className = 'chat-message';

    // Build message using safe DOM methods
    if (tier && tier !== 'free') {
      const badge = document.createElement('span');
      badge.className = `chat-badge chat-badge-${this.escapeHtml(tier)}`;
      badge.textContent = tier.toUpperCase();
      msgEl.appendChild(badge);
    }

    const nameSpan = document.createElement('span');
    nameSpan.className = 'chat-username';
    nameSpan.style.color = color;
    nameSpan.textContent = username;
    if (isAuthenticated) {
      const verified = document.createElement('span');
      verified.className = 'chat-verified';
      verified.title = 'Verified User';
      verified.textContent = '\u2713';
      nameSpan.appendChild(verified);
    }
    nameSpan.appendChild(document.createTextNode(':'));
    msgEl.appendChild(nameSpan);

    msgEl.appendChild(document.createTextNode(' '));

    const textSpan = document.createElement('span');
    textSpan.className = 'chat-text';
    textSpan.textContent = message;
    msgEl.appendChild(textSpan);

    this.messagesContainer.appendChild(msgEl);
    this.scrollToBottom();
  }

  addSystemMessage(text) {
    if (!this.messagesContainer) {return;}
    const msgEl = document.createElement('div');
    msgEl.className = 'chat-system';
    msgEl.textContent = text;
    this.messagesContainer.appendChild(msgEl);
    this.scrollToBottom();
  }

  updateViewers(count) {
    if (this.viewersDisplay) {this.viewersDisplay.textContent = `${count} online`;}
  }

  scrollToBottom() {
    if (this.messagesContainer) {
      this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }
  }

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.textContent;
  }

  showFloatingEmoji(emoji) {
    const container = document.getElementById('floatingEmojis');
    if (!container) {return;}

    const emojiEl = document.createElement('div');
    emojiEl.className = 'floating-emoji';
    emojiEl.textContent = emoji;
    emojiEl.style.left = `${Math.random() * 80 + 10}%`;
    emojiEl.style.animationDuration = `${2 + Math.random() * 2}s`;

    container.appendChild(emojiEl);
    setTimeout(() => emojiEl.remove(), 4000);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  new LiveChat();
});
