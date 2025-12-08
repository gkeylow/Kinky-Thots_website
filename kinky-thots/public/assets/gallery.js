// Gallery JavaScript - Rebuilt for reliability
(function() {
  'use strict';

  // Configuration - Use relative URLs to work with Apache proxy (supports HTTPS)
  const CONFIG = {
    apiBase: '',  // Empty string = relative URLs, works with Apache proxy
    endpoints: {
      gallery: '/api/gallery',
      upload: '/api/upload',
      delete: '/api/gallery'
    },
    uploadsPath: '/uploads/'
  };

  // State
  let galleryData = [];
  let currentLightboxIndex = 0;

  // Initialize
  document.addEventListener('DOMContentLoaded', function() {
    console.log('Gallery initialized');
    console.log('API Base:', CONFIG.apiBase);
    
    loadGallery();
    setupUploadForm();
    setupLightbox();
  });

  // Load gallery images
  async function loadGallery() {
    const grid = document.getElementById('gallery-grid');
    if (!grid) {
      console.warn('Gallery grid element not found');
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
            <div class="empty-gallery-subtext">Upload some images to get started</div>
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
      // Use full_url from API if available, otherwise construct from uploads path
      const fileUrl = img.full_url 
        ? CONFIG.apiBase + img.full_url 
        : CONFIG.apiBase + CONFIG.uploadsPath + encodeURIComponent(img.filename);
      const uploadDate = new Date(img.upload_time);
      // Use file_type from API if available, otherwise detect from filename
      const isVideo = img.file_type === 'video' || /\.(mp4|mov|avi|mkv|webm|mpeg|flv|m4v)$/i.test(img.filename);
      
      let mediaElement;
      if (isVideo) {
        mediaElement = `
          <video 
            src="${fileUrl}" 
            data-idx="${idx}"
            onclick="window.openLightbox(${idx})"
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
            onclick="window.openLightbox(${idx})"
            onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%22%23ddd%22 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22%3EImage not found%3C/text%3E%3C/svg%3E'"
          />
        `;
      }
      
      return `
        <div class="gallery-item ${isVideo ? 'video-item' : ''}" data-id="${img.id}" data-idx="${idx}">
          ${mediaElement}
          <div class="gallery-meta">
            <div class="upload-date">${uploadDate.toLocaleDateString()}</div>
            <div class="upload-time">${uploadDate.toLocaleTimeString()}</div>
          </div>
          <button class="delete-btn" onclick="window.deleteImage(${img.id})" title="Delete ${isVideo ? 'video' : 'image'}">×</button>
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

  // Setup upload form
  function setupUploadForm() {
    const form = document.getElementById('upload-form');
    const fileInput = document.getElementById('image-input');
    const uploadArea = document.querySelector('.upload-area');
    const uploadText = document.querySelector('.upload-text');
    const preview = document.getElementById('image-preview');

    if (!form || !fileInput || !uploadArea) {
      console.warn('Upload form elements not found');
      return;
    }

    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
      e.preventDefault();
      uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
      uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
      e.preventDefault();
      uploadArea.classList.remove('dragover');
      
      if (e.dataTransfer.files.length > 0) {
        fileInput.files = e.dataTransfer.files;
        updateFileDisplay();
      }
    });

    // Click to select
    uploadArea.addEventListener('click', () => {
      fileInput.click();
    });

    // File selection
    fileInput.addEventListener('change', updateFileDisplay);

    // Form submit
    form.addEventListener('submit', handleUpload);

    function updateFileDisplay() {
      if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        uploadText.textContent = `Selected: ${file.name}`;
        
        if (preview) {
          const reader = new FileReader();
          reader.onload = (e) => {
            preview.src = e.target.result;
            preview.style.display = 'block';
          };
          reader.readAsDataURL(file);
        }
      }
    }
  }

  // Handle upload
  async function handleUpload(e) {
    e.preventDefault();

    const fileInput = document.getElementById('image-input');
    const uploadBtn = document.querySelector('.upload-btn');
    const progressBar = document.querySelector('.progress-bar');
    const progressFill = document.querySelector('.progress-fill');

    if (!fileInput.files[0]) {
      showStatus('Please select an image', 'error');
      return;
    }

    const file = fileInput.files[0];

    // Validate
    const allowedTypes = [
      'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
      'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska',
      'video/webm', 'video/mpeg', 'video/x-flv'
    ];
    if (!allowedTypes.includes(file.type)) {
      showStatus('Please select a valid image or video file', 'error');
      return;
    }

    if (file.size > 500 * 1024 * 1024) {
      showStatus('File size must be less than 500MB', 'error');
      return;
    }

    // Prepare upload
    const formData = new FormData();
    formData.append('image', file);

    uploadBtn.disabled = true;
    uploadBtn.textContent = 'Uploading...';
    showStatus('Uploading image...', 'loading');

    if (progressBar) {
      progressBar.style.display = 'block';
      progressFill.style.width = '0%';
    }

    try {
      const xhr = new XMLHttpRequest();

      xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable && progressFill) {
          const percent = (e.loaded / e.total) * 100;
          progressFill.style.width = percent + '%';
        }
      });

      xhr.onload = function() {
        uploadBtn.disabled = false;
        uploadBtn.textContent = 'Upload Image';
        if (progressBar) progressBar.style.display = 'none';

        if (xhr.status === 200) {
          const data = JSON.parse(xhr.responseText);
          if (data.success) {
            showStatus('Upload successful!', 'success');
            fileInput.value = '';
            document.querySelector('.upload-text').textContent = 'Drag & drop or click to select an image';
            const preview = document.getElementById('image-preview');
            if (preview) preview.style.display = 'none';
            loadGallery();
          } else {
            showStatus('Upload failed: ' + (data.error || 'Unknown error'), 'error');
          }
        } else {
          showStatus('Upload failed: Server error', 'error');
        }
      };

      xhr.onerror = function() {
        uploadBtn.disabled = false;
        uploadBtn.textContent = 'Upload Image';
        if (progressBar) progressBar.style.display = 'none';
        showStatus('Upload failed: Network error', 'error');
      };

      xhr.open('POST', CONFIG.apiBase + CONFIG.endpoints.upload);
      xhr.send(formData);

    } catch (error) {
      console.error('Upload error:', error);
      uploadBtn.disabled = false;
      uploadBtn.textContent = 'Upload Image';
      if (progressBar) progressBar.style.display = 'none';
      showStatus('Upload failed: ' + error.message, 'error');
    }
  }

  // Delete image
  window.deleteImage = async function(imageId) {
    if (!confirm('Are you sure you want to delete this image?')) {
      return;
    }

    console.log('Deleting image ID:', imageId);
    showStatus('Deleting...', 'loading');

    try {
      const url = CONFIG.apiBase + CONFIG.endpoints.delete + '/' + imageId;
      console.log('Delete URL:', url);
      
      const response = await fetch(url, { 
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json'
        }
      });
      
      console.log('Delete response status:', response.status);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      const result = await response.json();
      console.log('Delete result:', result);

      if (result.success) {
        showStatus('Image deleted successfully', 'success');
        loadGallery();
      } else {
        showStatus('Delete failed: ' + (result.error || 'Unknown error'), 'error');
      }
    } catch (error) {
      console.error('Delete error:', error);
      showStatus('Delete failed: ' + error.message, 'error');
    }
  };

  // Setup lightbox
  function setupLightbox() {
    const overlay = document.getElementById('lightbox-overlay');
    const overlayImg = document.getElementById('lightbox-img');
    const overlayVideo = document.getElementById('lightbox-video');
    const closeBtn = document.getElementById('lightbox-close');
    const prevBtn = document.getElementById('lightbox-prev');
    const nextBtn = document.getElementById('lightbox-next');

    if (!overlay || !overlayImg || !overlayVideo) {
      console.warn('Lightbox elements not found');
      return;
    }

    window.openLightbox = function(idx) {
      if (!galleryData[idx]) return;
      
      const img = galleryData[idx];
      // Use full_url from API if available, otherwise construct from uploads path
      const fileUrl = img.full_url 
        ? CONFIG.apiBase + img.full_url 
        : CONFIG.apiBase + CONFIG.uploadsPath + encodeURIComponent(img.filename);
      // Use file_type from API if available, otherwise detect from filename
      const isVideo = img.file_type === 'video' || /\.(mp4|mov|avi|mkv|webm|mpeg|flv|m4v)$/i.test(img.filename);
      
      // Hide both elements first
      overlayImg.style.display = 'none';
      overlayVideo.style.display = 'none';
      overlayImg.src = '';
      overlayVideo.src = '';
      
      if (isVideo) {
        // Show video
        overlayVideo.src = fileUrl;
        overlayVideo.style.display = 'block';
        overlayVideo.load();
      } else {
        // Show image
        overlayImg.src = fileUrl;
        overlayImg.style.display = 'block';
      }
      
      overlay.style.display = 'flex';
      currentLightboxIndex = idx;
      document.body.style.overflow = 'hidden';
    };

    function closeLightbox() {
      overlay.style.display = 'none';
      overlayImg.src = '';
      overlayImg.style.display = 'none';
      overlayVideo.src = '';
      overlayVideo.style.display = 'none';
      overlayVideo.pause();
      document.body.style.overflow = '';
    }

    function showPrev() {
      if (currentLightboxIndex > 0) {
        window.openLightbox(currentLightboxIndex - 1);
      }
    }

    function showNext() {
      if (currentLightboxIndex < galleryData.length - 1) {
        window.openLightbox(currentLightboxIndex + 1);
      }
    }

    if (closeBtn) closeBtn.onclick = closeLightbox;
    overlay.onclick = (e) => { 
      if (e.target === overlay || e.target.id === 'lightbox-content') {
        closeLightbox(); 
      }
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

  // Show status message
  function showStatus(message, type) {
    const statusDiv = document.getElementById('upload-status');
    if (statusDiv) {
      statusDiv.textContent = message;
      statusDiv.className = `status ${type}`;
      statusDiv.style.display = 'block';

      setTimeout(() => {
        statusDiv.style.display = 'none';
      }, 5000);
    }
  }

  // Auto-refresh every 30 seconds
  setInterval(loadGallery, 30000);

})();
