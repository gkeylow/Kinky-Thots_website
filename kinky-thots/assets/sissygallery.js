// Sissy Gallery JavaScript - Rebuilt for reliability
(function() {
  'use strict';

  // Configuration
  const CONFIG = {
    apiBase: window.location.origin,
    endpoints: {
      gallery: '/api/gallery'
    },
    uploadsPath: '/uploads/'
  };

  // State
  let galleryData = [];
  let currentLightboxIndex = 0;

  // Initialize
  document.addEventListener('DOMContentLoaded', function() {
    console.log('Sissy Gallery initialized');
    console.log('API Base:', CONFIG.apiBase);
    
    loadGallery();
    setupLightbox();
  });

  // Load gallery images
  async function loadGallery() {
    const grid = document.getElementById('gallery-grid-sissy');
    if (!grid) {
      console.warn('Sissy gallery grid element not found');
      return;
    }

    grid.innerHTML = '<div class="loading">Loading gallery...</div>';

    try {
      const url = CONFIG.apiBase + CONFIG.endpoints.gallery;
      console.log('Fetching from:', url);
      
      const response = await fetch(url);
      console.log('Response status:', response.status);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const images = await response.json();
      console.log('Loaded images:', images.length);
      
      galleryData = images;

      if (!Array.isArray(images) || images.length === 0) {
        grid.innerHTML = `
          <div class="empty-gallery">
            <div class="empty-gallery-icon">📷</div>
            <div class="empty-gallery-text">No images yet</div>
            <div class="empty-gallery-subtext">Check back soon for new content</div>
          </div>
        `;
        return;
      }

      renderGallery(images, grid);

    } catch (error) {
      console.error('Gallery load error:', error);
      grid.innerHTML = `
        <div class="error-message">
          <div class="error-icon">⚠️</div>
          <div class="error-text">Error loading gallery</div>
          <div class="error-subtext">${error.message}</div>
        </div>
      `;
    }
  }

  // Render gallery grid
  function renderGallery(images, grid) {
    grid.innerHTML = images.map((img, idx) => {
      const fileUrl = CONFIG.apiBase + CONFIG.uploadsPath + encodeURIComponent(img.filename);
      const isVideo = /\.(mp4|mov|avi|mkv|webm|mpeg|flv)$/i.test(img.filename);
      
      let mediaElement;
      if (isVideo) {
        mediaElement = `
          <video 
            src="${fileUrl}" 
            data-idx="${idx}"
            onclick="window.openSissyLightbox(${idx})"
            style="width: 100%; height: auto; cursor: pointer;"
            muted
            loop
            playsinline
          ></video>
        `;
      } else {
        mediaElement = `
          <img 
            src="${fileUrl}" 
            alt="Gallery Image" 
            loading="lazy"
            data-idx="${idx}"
            onclick="window.openSissyLightbox(${idx})"
            style="cursor: pointer;"
            onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%22%23ddd%22 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22%3EImage not found%3C/text%3E%3C/svg%3E'"
          />
        `;
      }
      
      return `
        <div class="gallery-item ${isVideo ? 'video-item' : ''}" data-id="${img.id}" data-idx="${idx}">
          ${mediaElement}
        </div>
      `;
    }).join('');
    
    // Add hover play for videos
    const videos = grid.querySelectorAll('video');
    videos.forEach(video => {
      video.addEventListener('mouseenter', () => video.play());
      video.addEventListener('mouseleave', () => video.pause());
    });
  }

  // Setup lightbox
  function setupLightbox() {
    const overlay = document.getElementById('lightbox-overlay-sissy');
    const overlayImg = document.getElementById('lightbox-img-sissy');
    const closeBtn = document.getElementById('lightbox-close-sissy');
    const prevBtn = document.getElementById('lightbox-prev-sissy');
    const nextBtn = document.getElementById('lightbox-next-sissy');

    if (!overlay || !overlayImg) {
      console.warn('Sissy lightbox elements not found');
      return;
    }

    window.openSissyLightbox = function(idx) {
      if (!galleryData[idx]) return;
      
      const imgUrl = CONFIG.apiBase + CONFIG.uploadsPath + encodeURIComponent(galleryData[idx].filename);
      overlayImg.src = imgUrl;
      overlay.style.display = 'flex';
      currentLightboxIndex = idx;
      document.body.style.overflow = 'hidden';
    };

    function closeLightbox() {
      overlay.style.display = 'none';
      overlayImg.src = '';
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
    overlay.onclick = (e) => { if (e.target === overlay) closeLightbox(); };
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
