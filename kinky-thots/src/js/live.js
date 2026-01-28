import '../css/live.css';
import '../css/chat.css';
import '../css/auth-modal.css';
import { auth } from './auth.js';

const CONFIG = {
  stream: {
    hlsUrl: '/hls/stream.m3u8',
    retryInterval: 10000,
    maxRetries: 5,
  },
  rtmp: {
    server: 'rtmp://YOUR_SERVER_IP:1935/live',
    streamKey: 'stream',
  },
};

const MobileSupport = {
  isMobile: () =>
    /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),

  isTablet: () => /iPad|Android(?!.*Mobile)|(Silk)|(Windows.*Touch)/i.test(navigator.userAgent),

  getDeviceType: () => {
    if (MobileSupport.isTablet()) {return 'tablet';}
    if (MobileSupport.isMobile()) {return 'mobile';}
    return 'desktop';
  },

  getOrientation: () => (window.innerHeight > window.innerWidth ? 'portrait' : 'landscape'),

  supportsWebWorkers: () => typeof Worker !== 'undefined',

  supportsLocalStorage: () => {
    try {
      localStorage.setItem('test', 'test');
      localStorage.removeItem('test');
      return true;
    } catch {
      return false;
    }
  },

  getConnectionSpeed: async () => {
    if ('connection' in navigator) {
      const connection = /** @type {any} */ (navigator).connection;
      return {
        type: connection.effectiveType,
        downlink: connection.downlink,
        rtt: connection.rtt,
        saveData: connection.saveData,
      };
    }
    return null;
  },
};

class LivePlayer {
  constructor() {
    this.video = document.getElementById('liveVideo');
    this.placeholder = document.getElementById('offlinePlaceholder');
    this.hls = null;
    this.isLive = false;
    this.retryCount = 0;
    this.init();
  }

  init() {
    this.bindEvents();
    this.setupMobileOptimizations();
    this.setupOrientationDetection();

    if (CONFIG.stream.hlsUrl) {
      this.loadStream();
    }
  }

  setupMobileOptimizations() {
    const deviceType = MobileSupport.getDeviceType();
    document.documentElement.setAttribute('data-device', deviceType);

    MobileSupport.getConnectionSpeed().then((conn) => {
      if (conn?.saveData) {
        CONFIG.stream.lowDataMode = true;
        const lowDataEl = document.getElementById('lowDataMode');
        if (lowDataEl) {lowDataEl.checked = true;}
      }
    });

    document.addEventListener(
      'touchmove',
      (e) => {
        if (e.touches.length > 1) {e.preventDefault();}
      },
      { passive: false }
    );
  }

  setupOrientationDetection() {
    const handleOrientationChange = () => {
      const orientation = MobileSupport.getOrientation();
      document.documentElement.setAttribute('data-orientation', orientation);

      const container = document.querySelector('.live-container');
      if (orientation === 'landscape' && MobileSupport.isMobile() && container) {
        container.style.marginTop = '50px';
      } else if (container) {
        container.style.marginTop = '';
      }
    };

    window.addEventListener('orientationchange', handleOrientationChange);
    window.addEventListener('resize', handleOrientationChange);
    handleOrientationChange();
  }

  bindEvents() {
    const fullscreenBtn = document.getElementById('fullscreenBtn');
    const theaterBtn = document.getElementById('theaterBtn');
    const settingsBtn = document.getElementById('settingsBtn');
    const closeSettings = document.getElementById('closeSettings');
    const lowDataMode = document.getElementById('lowDataMode');
    const volumeControl = document.getElementById('volumeControl');

    if (fullscreenBtn) {
      fullscreenBtn.addEventListener('click', () => this.toggleFullscreen());
    }

    if (theaterBtn) {
      theaterBtn.addEventListener('click', () => {
        document.querySelector('.live-content')?.classList.toggle('theater-mode');
      });
    }

    if (settingsBtn) {
      settingsBtn.addEventListener('click', () => {
        const panel = document.getElementById('settingsPanel');
        if (panel) {panel.style.display = panel.style.display === 'none' ? 'block' : 'none';}
      });
    }

    if (closeSettings) {
      closeSettings.addEventListener('click', () => {
        const panel = document.getElementById('settingsPanel');
        if (panel) {panel.style.display = 'none';}
      });
    }

    if (lowDataMode) {
      lowDataMode.addEventListener('change', (e) => {
        CONFIG.stream.lowDataMode = e.target.checked;
        if (this.hls) {this.adjustHLSQuality();}
      });
    }

    if (volumeControl) {
      volumeControl.addEventListener('change', (e) => {
        if (this.video) {this.video.volume = e.target.value / 100;}
      });
    }

    this.setupTouchGestures();
  }

  setupTouchGestures() {
    if (!this.video) {return;}

    let touchStartX = 0;
    let touchStartY = 0;

    this.video.addEventListener('touchstart', (e) => {
      touchStartX = e.touches[0].clientX;
      touchStartY = e.touches[0].clientY;
    });

    this.video.addEventListener('touchend', (e) => {
      const touchEndX = e.changedTouches[0].clientX;
      const touchEndY = e.changedTouches[0].clientY;
      const diffX = touchEndX - touchStartX;
      const diffY = touchEndY - touchStartY;

      if (Math.abs(diffY) > Math.abs(diffX) && Math.abs(diffY) > 30) {
        const volumeControl = document.getElementById('volumeControl');
        let newVolume = this.video.volume * 100 + (diffY > 0 ? -10 : 10);
        newVolume = Math.max(0, Math.min(100, newVolume));
        this.video.volume = newVolume / 100;
        if (volumeControl) {volumeControl.value = newVolume;}
      }

      if (Math.abs(diffX) < 10 && Math.abs(diffY) < 10) {
        if (this.video.paused) {
          this.video.play();
        } else {
          this.video.pause();
        }
      }
    });
  }

  adjustHLSQuality() {
    if (!this.hls) {return;}

    if (CONFIG.stream.lowDataMode) {
      this.hls.currentLevel =
        this.hls.levels.map((l, i) => ({ level: i, height: l.height })).filter((l) => l.height <= 480)[0]
          ?.level || 0;
    } else {
      this.hls.currentLevel = -1;
    }
  }

  loadStream() {
    const streamUrl = CONFIG.stream.hlsUrl;
    if (typeof Hls === 'undefined') {return;}

    if (Hls.isSupported()) {
      // Low-latency HLS configuration
      const hlsConfig = {
        enableWorker: MobileSupport.supportsWebWorkers(),
        lowLatencyMode: true,
        // Aggressive low-latency buffer settings
        liveSyncDurationCount: 2,
        liveMaxLatencyDurationCount: 4,
        liveDurationInfinity: true,
        // Smaller buffers for faster start
        maxBufferLength: 4,
        maxMaxBufferLength: 8,
        maxBufferSize: 0,
        maxBufferHole: 0.5,
        // Back buffer (how much played content to keep)
        backBufferLength: 5,
        // Faster fragment loading
        fragLoadingTimeOut: 10000,
        fragLoadingMaxRetry: 3,
        fragLoadingRetryDelay: 500,
        // Start from live edge
        startPosition: -1,
      };

      if (MobileSupport.isMobile()) {
        // Slightly larger buffers for mobile stability
        hlsConfig.maxBufferLength = 6;
        hlsConfig.maxMaxBufferLength = 12;
        hlsConfig.liveSyncDurationCount = 3;
      }

      if (this.hls) {
        this.hls.destroy();
      }
      this.hls = new Hls(hlsConfig);

      this.hls.loadSource(streamUrl);
      this.hls.attachMedia(this.video);

      this.hls.on(Hls.Events.MANIFEST_PARSED, () => {
        this.setLiveStatus(true);
        this.video?.play().catch(() => {});
        // Periodically sync to live edge to prevent drift
        this.startLiveSync();
      });

      this.hls.on(Hls.Events.ERROR, (event, data) => {
        if (data.fatal) {
          this.handleStreamError();
        }
      });
    } else if (this.video?.canPlayType('application/vnd.apple.mpegurl')) {
      this.video.src = streamUrl;
      this.video.addEventListener('loadedmetadata', () => {
        this.setLiveStatus(true);
        this.video?.play();
      });
      this.video.addEventListener('error', () => {
        this.handleStreamError();
      });
    }
  }

  setLiveStatus(isLive) {
    this.isLive = isLive;
    const chatStatusDot = document.getElementById('chatStatusDot');
    const chatStatusLabel = document.getElementById('chatStatusLabel');

    if (isLive) {
      if (this.placeholder) {this.placeholder.style.display = 'none';}
      if (this.video) {this.video.style.display = 'block';}
      chatStatusDot?.classList.replace('offline', 'live');
      chatStatusLabel?.classList.replace('offline', 'live');
      if (chatStatusLabel) {chatStatusLabel.textContent = 'LIVE';}
    } else {
      if (this.placeholder) {this.placeholder.style.display = 'flex';}
      if (this.video) {this.video.style.display = 'none';}
      chatStatusDot?.classList.replace('live', 'offline');
      chatStatusLabel?.classList.replace('live', 'offline');
      if (chatStatusLabel) {chatStatusLabel.textContent = 'Offline';}
    }
  }

  handleStreamError() {
    this.setLiveStatus(false);
    if (this.retryCount < CONFIG.stream.maxRetries) {
      this.retryCount++;
      setTimeout(() => this.loadStream(), CONFIG.stream.retryInterval);
    }
  }

  toggleFullscreen() {
    const player = document.querySelector('.video-wrapper');
    if (!player) {return;}

    if (!document.fullscreenElement) {
      player.requestFullscreen().catch(() => {});
    } else {
      document.exitFullscreen();
    }
  }

  /**
   * Periodically check if viewer has drifted behind live edge and sync back
   */
  startLiveSync() {
    if (this.liveSyncInterval) {
      clearInterval(this.liveSyncInterval);
    }

    this.liveSyncInterval = setInterval(() => {
      if (!this.hls || !this.video || this.video.paused) {return;}

      const liveEdge = this.hls.liveSyncPosition;
      const currentTime = this.video.currentTime;
      const latency = liveEdge - currentTime;

      // If more than 5 seconds behind live, jump to live edge
      if (latency > 5) {
        this.video.currentTime = liveEdge - 1;
      }
    }, 5000);
  }

  /**
   * Manual jump to live edge (can be called from UI)
   */
  jumpToLive() {
    if (!this.hls || !this.video) {return;}
    const liveEdge = this.hls.liveSyncPosition;
    if (liveEdge) {
      this.video.currentTime = liveEdge - 0.5;
    }
  }
}

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

    this.connect();
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
    };

    this.ws.onmessage = (event) => {
      this.handleMessage(JSON.parse(event.data));
    };

    this.ws.onclose = () => {
      this.addSystemMessage('Disconnected from chat');
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

  /**
   * Reconnect with fresh auth state (called after login/logout)
   */
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

  /**
   * Handle moderation actions
   */
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

  /**
   * Add moderation message
   */
  addModMessage(text) {
    if (!this.messagesContainer) {return;}
    const msgEl = document.createElement('div');
    msgEl.className = 'chat-mod-action';
    msgEl.textContent = `[MOD] ${text}`;
    this.messagesContainer.appendChild(msgEl);
    this.scrollToBottom();
  }

  /**
   * Clear all chat messages
   */
  clearChat() {
    if (!this.messagesContainer) {return;}
    this.messagesContainer.innerHTML = '<div class="chat-welcome">Chat was cleared</div>';
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

    // Build badge HTML for subscription tiers
    let badge = '';
    if (tier && tier !== 'free') {
      badge = `<span class="chat-badge chat-badge-${tier}">${tier.toUpperCase()}</span>`;
    }

    // Add verified checkmark for authenticated users
    const verified = isAuthenticated ? '<span class="chat-verified" title="Verified User">✓</span>' : '';

    msgEl.innerHTML = `${badge}<span class="chat-username" style="color: ${color}">${this.escapeHtml(username)}${verified}:</span> <span class="chat-text">${this.escapeHtml(message)}</span>`;
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
    if (this.viewersDisplay) {this.viewersDisplay.textContent = `${count} watching`;}
    const mainViewer = document.getElementById('viewerCount');
    if (mainViewer) {mainViewer.textContent = `${count} viewers`;}
  }

  scrollToBottom() {
    if (this.messagesContainer) {
      this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }
  }

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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
  new LivePlayer();
  new LiveChat();
});
