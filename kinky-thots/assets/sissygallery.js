// Sissy Gallery JavaScript
(function() {
  'use strict';

  // Use relative URLs to work with Apache proxy (supports HTTPS)
  const CONFIG = {
    apiBase: '',  // Empty string = relative URLs, works with Apache proxy
    endpoints: {
      gallery: '/api/gallery'
    },
    uploadsPath: '/uploads/'
  };

  let galleryData = [];
  let currentLightboxIndex = 0;

  document.addEventListener('DOMContentLoaded', function() {
    console.log('Sissy Gallery initialized');
    loadGallery();
    setupLightbox();
  });

  async function loadGallery() {
    const grid = document.getElementById('gallery-grid-sissy');
    if (!grid) return;

    grid.innerHTML = '<div class="loading">Loading gallery...</div>';

    try {
      const response = await fetch(CONFIG.apiBase + CONFIG.endpoints.gallery);
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      
      const images = await response.json();
      galleryData = images;

      if (!Array.isArray(images) || images.length === 0) {
        grid.innerHTML = '<div class="empty-gallery"><p>No images yet</p></div>';
        return;
      }

      renderGallery(images, grid);
    } catch (error) {
      console.error('Gallery error:', error);
      grid.innerHTML = `<div class="error-message"><p>Error loading gallery</p><button onclick="location.reload()">Retry</button></div>`;
    }
  }

  function renderGallery(images, grid) {
    grid.innerHTML = images.map((img, idx) => {
      const fileUrl = CONFIG.apiBase + CONFIG.uploadsPath + encodeURIComponent(img.filename);
      const isVideo = /\.(mp4|mov|avi|mkv|webm|mpeg|flv)$/i.test(img.filename);

      if (isVideo) {
        return `
          <div class="gallery-item video-item" data-idx="${idx}">
            <video
              src="${fileUrl}"
              onclick="window.openSissyLightbox(${idx})"
              style="width: 100%; height: auto; cursor: pointer; background: #000;"
              preload="auto"
              muted
              playsinline
            ></video>
          </div>
        `;
      } else {
        return `
          <div class="gallery-item" data-idx="${idx}">
            <img
              src="${fileUrl}"
              alt="Gallery Image"
              loading="eager"
              onclick="window.openSissyLightbox(${idx})"
              style="cursor: pointer; width: 100%; height: auto;"
            />
          </div>
        `;
      }
    }).join('');

    // Video event listeners
    const videos = grid.querySelectorAll('video');
    videos.forEach(video => {
      video.addEventListener('mouseenter', () => video.play());
      video.addEventListener('mouseleave', () => video.pause());
      video.addEventListener('loadeddata', function() {
        this.currentTime = 0.1;
      });
    });
  }

  function setupLightbox() {
    const overlay = document.getElementById('lightbox-overlay-sissy');
    const overlayImg = document.getElementById('lightbox-img-sissy');
    const overlayVideo = document.getElementById('lightbox-video-sissy');
    const closeBtn = document.getElementById('lightbox-close-sissy');
    const prevBtn = document.getElementById('lightbox-prev-sissy');
    const nextBtn = document.getElementById('lightbox-next-sissy');

    if (!overlay) return;

    window.openSissyLightbox = function(idx) {
      if (!galleryData[idx]) return;

      const fileUrl = CONFIG.apiBase + CONFIG.uploadsPath + encodeURIComponent(galleryData[idx].filename);
      const isVideo = /\.(mp4|mov|avi|mkv|webm|mpeg|flv)$/i.test(galleryData[idx].filename);

      if (overlayImg) {
        overlayImg.style.display = 'none';
        overlayImg.src = '';
      }
      if (overlayVideo) {
        overlayVideo.style.display = 'none';
        overlayVideo.src = '';
      }

      if (isVideo && overlayVideo) {
        overlayVideo.src = fileUrl;
        overlayVideo.style.display = 'block';
        overlayVideo.load();
      } else if (overlayImg) {
        overlayImg.src = fileUrl;
        overlayImg.style.display = 'block';
      }

      overlay.style.display = 'flex';
      currentLightboxIndex = idx;
      document.body.style.overflow = 'hidden';
    };

    function closeLightbox() {
      overlay.style.display = 'none';
      if (overlayImg) overlayImg.src = '';
      if (overlayVideo) {
        overlayVideo.src = '';
        overlayVideo.pause();
      }
      document.body.style.overflow = '';
    }

    function showPrev() {
      if (currentLightboxIndex > 0) {
        window.openSissyLightbox(currentLightboxIndex - 1);
      }
    }

    function showNext() {
      if (currentLightboxIndex < galleryData.length - 1) {
        window.openSissyLightbox(currentLightboxIndex + 1);
      }
    }

    if (closeBtn) closeBtn.onclick = closeLightbox;
    if (overlay) overlay.onclick = (e) => {
      if (e.target === overlay) closeLightbox();
    };
    if (prevBtn) prevBtn.onclick = showPrev;
    if (nextBtn) nextBtn.onclick = showNext;

    document.addEventListener('keydown', (e) => {
      if (overlay.style.display === 'flex') {
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') showPrev();
        if (e.key === 'ArrowRight') showNext();
      }
    });
  }

})();
