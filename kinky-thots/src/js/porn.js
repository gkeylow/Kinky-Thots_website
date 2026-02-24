import '../css/media-gallery.css';

// Navbar scroll shadow
function initNavbar() {
  const navbar = document.getElementById('navbar');
  if (!navbar) return;
  window.addEventListener('scroll', () => {
    navbar.style.boxShadow = window.pageYOffset <= 0
      ? '0 2px 8px rgba(0, 0, 0, 0.2)'
      : '0 4px 16px rgba(0, 0, 0, 0.4)';
  });
}

// Video lightbox — mobile-compatible
function initLightbox() {
  window.openLightbox = function(el) {
    const container = el.closest('.video-container');
    if (!container) return;

    const src = container.dataset.videoUrl;
    const type = container.dataset.videoType || 'video/mp4';

    // Build DOM safely (no innerHTML with untrusted data)
    const overlay = document.createElement('div');
    overlay.className = 'video-lightbox';

    const content = document.createElement('div');
    content.className = 'lightbox-content';

    const closeBtn = document.createElement('button');
    closeBtn.className = 'lightbox-close';
    closeBtn.textContent = '\u00d7';

    const video = document.createElement('video');
    video.controls = true;
    video.setAttribute('playsinline', '');  // Required for inline playback on iOS

    content.appendChild(closeBtn);
    content.appendChild(video);
    overlay.appendChild(content);
    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';

    // Set src after element is in the DOM — Safari/iOS rejects src on detached elements
    video.src = src;
    video.load();

    const close = () => {
      video.pause();
      video.removeAttribute('src');
      video.load();
      overlay.remove();
      document.body.style.overflow = '';
    };

    overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
    closeBtn.addEventListener('click', close);

    const onKey = e => {
      if (e.key === 'Escape') { close(); document.removeEventListener('keydown', onKey); }
    };
    document.addEventListener('keydown', onKey);
  };
}

// Filter buttons (aspect ratio)
function initFilters() {
  const btns = document.querySelectorAll('.filter-btn');
  const items = document.querySelectorAll('.video-container');
  if (!btns.length) return;

  btns.forEach(btn => {
    btn.addEventListener('click', function() {
      const filter = this.dataset.filter;
      btns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      items.forEach(item => {
        item.style.display = (filter === 'all' || item.dataset.aspect === filter) ? 'block' : 'none';
      });
    });
  });
}

function init() {
  initNavbar();
  initLightbox();
  initFilters();
}

document.readyState === 'loading'
  ? document.addEventListener('DOMContentLoaded', init)
  : init();
