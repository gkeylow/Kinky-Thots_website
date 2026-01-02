import '../css/media-gallery.css';

const CONFIG = {
  apiBase: '',
  endpoints: {
    gallery: '/api/gallery',
    upload: '/api/upload',
    delete: '/api/gallery',
  },
  uploadsPath: '/uploads/',
};

let galleryData = [];
let currentLightboxIndex = 0;

function init() {
  loadGallery();
  setupUploadForm();
  setupLightbox();
}

async function loadGallery() {
  const grid = document.getElementById('gallery-grid');
  if (!grid) return;

  grid.innerHTML = '<div class="loading">Loading gallery...</div>';

  try {
    const url = CONFIG.apiBase + CONFIG.endpoints.gallery;
    const response = await fetch(url);

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const images = await response.json();
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
    grid.innerHTML = `
      <div class="error-message">
        <div class="error-icon">⚠️</div>
        <div class="error-text">Error loading gallery</div>
        <div class="error-subtext">${error.message}</div>
      </div>
    `;
  }
}

function renderGallery(images, grid) {
  grid.innerHTML = images
    .map((img, idx) => {
      const fileUrl = img.full_url
        ? CONFIG.apiBase + img.full_url
        : CONFIG.apiBase + CONFIG.uploadsPath + encodeURIComponent(img.filename);
      const uploadDate = new Date(img.upload_time);
      const isVideo =
        img.file_type === 'video' || /\.(mp4|mov|avi|mkv|webm|mpeg|flv|m4v)$/i.test(img.filename);

      const isGif = /\.gif$/i.test(img.filename);
      let mediaElement;
      if (isVideo) {
        mediaElement = `
        <video
          src="${fileUrl}"
          data-idx="${idx}"
          class="lightbox-trigger"
          style="width: 100%; height: auto; cursor: pointer;"
          muted loop playsinline
        ></video>
      `;
      } else {
        const imgSrc = img.cdn_url || fileUrl;
        mediaElement = `
        <img
          src="${imgSrc}"
          alt="Gallery Image"
          ${isGif ? '' : 'loading="lazy"'}
          data-idx="${idx}"
          class="lightbox-trigger"
          onerror="this.src='${img.origin_url || fileUrl}'"
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
        <button class="delete-btn" onclick="window.deleteImage(${img.id})" title="Delete">×</button>
      </div>
    `;
    })
    .join('');

  grid.querySelectorAll('video').forEach((video) => {
    video.addEventListener('mouseenter', () => video.play());
    video.addEventListener('mouseleave', () => video.pause());
  });
}

function setupUploadForm() {
  const form = document.getElementById('upload-form');
  const fileInput = document.getElementById('image-input');
  const uploadArea = document.querySelector('.upload-area');
  const uploadText = document.querySelector('.upload-text');
  const preview = document.getElementById('image-preview');

  if (!form || !fileInput || !uploadArea) return;

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

  uploadArea.addEventListener('click', () => fileInput.click());
  fileInput.addEventListener('change', updateFileDisplay);
  form.addEventListener('submit', handleUpload);

  function updateFileDisplay() {
    if (fileInput.files.length > 0) {
      const file = fileInput.files[0];
      if (uploadText) {uploadText.textContent = `Selected: ${file.name}`;}

      if (preview && file.type.startsWith('image/')) {
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

async function handleUpload(e) {
  e.preventDefault();

  const fileInput = document.getElementById('image-input');
  const uploadBtn = document.querySelector('.upload-btn');
  const progressBar = document.querySelector('.progress-bar');
  const progressFill = document.querySelector('.progress-fill');

  if (!fileInput?.files[0]) {
    showStatus('Please select an image', 'error');
    return;
  }

  const file = fileInput.files[0];

  const allowedTypes = [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/gif',
    'image/webp',
    'video/mp4',
    'video/quicktime',
    'video/x-msvideo',
    'video/x-matroska',
    'video/webm',
    'video/mpeg',
    'video/x-flv',
  ];

  if (!allowedTypes.includes(file.type)) {
    showStatus('Please select a valid image or video file', 'error');
    return;
  }

  if (file.size > 500 * 1024 * 1024) {
    showStatus('File size must be less than 500MB', 'error');
    return;
  }

  const formData = new FormData();
  formData.append('image', file);

  if (uploadBtn) {
    uploadBtn.disabled = true;
    uploadBtn.textContent = 'Uploading...';
  }
  showStatus('Uploading...', 'loading');

  if (progressBar) {
    progressBar.style.display = 'block';
    if (progressFill) {progressFill.style.width = '0%';}
  }

  try {
    const xhr = new XMLHttpRequest();

    xhr.upload.addEventListener('progress', (e) => {
      if (e.lengthComputable && progressFill) {
        progressFill.style.width = `${(e.loaded / e.total) * 100}%`;
      }
    });

    xhr.onload = () => {
      if (uploadBtn) {
        uploadBtn.disabled = false;
        uploadBtn.textContent = 'Upload Image';
      }
      if (progressBar) {progressBar.style.display = 'none';}

      if (xhr.status === 200) {
        const data = JSON.parse(xhr.responseText);
        if (data.success) {
          showStatus('Upload successful!', 'success');
          fileInput.value = '';
          const uploadText = document.querySelector('.upload-text');
          if (uploadText) {uploadText.textContent = 'Drag & drop or click to select';}
          const preview = document.getElementById('image-preview');
          if (preview) {preview.style.display = 'none';}
          loadGallery();
        } else {
          showStatus('Upload failed: ' + (data.error || 'Unknown error'), 'error');
        }
      } else {
        showStatus('Upload failed: Server error', 'error');
      }
    };

    xhr.onerror = () => {
      if (uploadBtn) {
        uploadBtn.disabled = false;
        uploadBtn.textContent = 'Upload Image';
      }
      if (progressBar) {progressBar.style.display = 'none';}
      showStatus('Upload failed: Network error', 'error');
    };

    xhr.open('POST', CONFIG.apiBase + CONFIG.endpoints.upload);
    xhr.send(formData);
  } catch (error) {
    if (uploadBtn) {
      uploadBtn.disabled = false;
      uploadBtn.textContent = 'Upload Image';
    }
    if (progressBar) {progressBar.style.display = 'none';}
    showStatus('Upload failed: ' + error.message, 'error');
  }
}

window.deleteImage = async function (imageId) {
  if (!confirm('Are you sure you want to delete this?')) {return;}

  showStatus('Deleting...', 'loading');

  try {
    const response = await fetch(CONFIG.apiBase + CONFIG.endpoints.delete + '/' + imageId, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const result = await response.json();
    if (result.success) {
      showStatus('Deleted successfully', 'success');
      loadGallery();
    } else {
      showStatus('Delete failed: ' + (result.error || 'Unknown'), 'error');
    }
  } catch (error) {
    showStatus('Delete failed: ' + error.message, 'error');
  }
};

function setupLightbox() {
  const overlay = document.getElementById('lightbox-overlay');
  const overlayImg = document.getElementById('lightbox-img');
  const overlayVideo = document.getElementById('lightbox-video');
  const closeBtn = document.getElementById('lightbox-close');
  const prevBtn = document.getElementById('lightbox-prev');
  const nextBtn = document.getElementById('lightbox-next');
  const grid = document.getElementById('gallery-grid');

  if (!overlay || !overlayImg || !overlayVideo) return;

  if (grid) {
    grid.addEventListener('click', (e) => {
      const trigger = e.target.closest('.lightbox-trigger');
      if (trigger) {
        const idx = parseInt(trigger.dataset.idx, 10);
        if (!isNaN(idx)) {
          openLightbox(idx);
        }
      }
    });
  }

  function openLightbox(idx) {
    if (!galleryData[idx]) {return;}

    const img = galleryData[idx];
    // Prefer origin_url (local) since CDN may not have gallery uploads
    const fileUrl = img.origin_url
      ? CONFIG.apiBase + img.origin_url
      : img.full_url
        ? CONFIG.apiBase + img.full_url
        : CONFIG.apiBase + CONFIG.uploadsPath + encodeURIComponent(img.filename);
    const isVideo =
      img.file_type === 'video' || /\.(mp4|mov|avi|mkv|webm|mpeg|flv|m4v)$/i.test(img.filename);

    overlayImg.style.display = 'none';
    overlayVideo.style.display = 'none';
    overlayImg.src = '';
    overlayVideo.src = '';

    if (isVideo) {
      overlayVideo.src = fileUrl;
      overlayVideo.style.display = 'block';
      overlayVideo.load();
    } else {
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
      openLightbox(currentLightboxIndex - 1);
    }
  }

  function showNext() {
    if (currentLightboxIndex < galleryData.length - 1) {
      openLightbox(currentLightboxIndex + 1);
    }
  }

  if (closeBtn) {closeBtn.onclick = closeLightbox;}
  overlay.onclick = (e) => {
    if (e.target === overlay) {closeLightbox();}
  };
  if (prevBtn) {prevBtn.onclick = showPrev;}
  if (nextBtn) {nextBtn.onclick = showNext;}

  document.addEventListener('keydown', (e) => {
    if (overlay.style.display === 'flex') {
      if (e.key === 'Escape') {closeLightbox();}
      if (e.key === 'ArrowLeft') {showPrev();}
      if (e.key === 'ArrowRight') {showNext();}
    }
  });

  let touchStartX = 0;
  let touchStartY = 0;
  overlay.addEventListener('touchstart', (e) => {
    touchStartX = e.touches[0].clientX;
    touchStartY = e.touches[0].clientY;
  }, { passive: true });

  overlay.addEventListener('touchend', (e) => {
    if (overlay.style.display !== 'flex') return;
    const touchEndX = e.changedTouches[0].clientX;
    const touchEndY = e.changedTouches[0].clientY;
    const diffX = touchEndX - touchStartX;
    const diffY = touchEndY - touchStartY;

    if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
      if (diffX > 0) {
        showPrev();
      } else {
        showNext();
      }
    }
  }, { passive: true });
}

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

// Auto-initialize
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}

setInterval(loadGallery, 30000);
