// Gallery frontend logic - No admin/secret required
const API_BASE = window.location.origin;
const GALLERY_API = API_BASE + '/gallery';
const UPLOAD_API = API_BASE + '/upload';
const DELETE_API = API_BASE + '/delete';
const UPLOADS_BASE = API_BASE + '/backend/uploads/';

// State management
let galleryData = [];

// Fetch and display gallery images
async function loadGallery() {
    const grid = document.getElementById('gallery-grid');
    if (!grid) return;
    
    grid.innerHTML = '<div class="loading">Loading gallery...</div>';
    
    try {
        const res = await fetch(GALLERY_API);
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const images = await res.json();
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
        
        // Create gallery grid (mosaic: images must be direct children)
        grid.innerHTML = images.map((img, idx) => `
            <div class="gallery-item" data-id="${img.id}" data-idx="${idx}">
                <img src="${UPLOADS_BASE + encodeURIComponent(img.filename)}" 
                     alt="Gallery Image" 
                     loading="lazy"
                     data-idx="${idx}"
                     onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'200\'%3E%3Crect width=\'200\' height=\'200\' fill=\'%23f0f0f0\'/%3E%3Ctext x=\'100\' y=\'100\' text-anchor=\'middle\' fill=\'%23999\'%3EImage not found%3C/text%3E%3C/svg%3E'"
                />
                <div class="gallery-meta">
                    <div class="upload-date">Uploaded: ${new Date(img.upload_time).toLocaleDateString()}</div>
                    <div class="upload-time">${new Date(img.upload_time).toLocaleTimeString()}</div>
                </div>
                <button class="delete-btn" onclick="deleteImage(${img.id})" title="Delete image">×</button>
            </div>
        `).join('');
        
        // Add lightbox functionality
        setupLightbox(images);
        
    } catch (err) {
        console.error('Error loading gallery:', err);
        grid.innerHTML = `
            <div class="error-message">
                <div class="error-icon">⚠️</div>
                <div class="error-text">Error loading gallery</div>
                <div class="error-subtext">Please try again later</div>
            </div>
        `;
    }
}

// Mosaic Lightbox functionality
function setupLightbox(images) {
    const galleryImgs = document.querySelectorAll('.gallery-item img');
    const overlay = document.getElementById('lightbox-overlay');
    const overlayImg = document.getElementById('lightbox-img');
    const closeBtn = document.getElementById('lightbox-close');
    const prevBtn = document.getElementById('lightbox-prev');
    const nextBtn = document.getElementById('lightbox-next');
    let currentIdx = 0;

    function showLightbox(idx) {
        if (!images[idx]) return;
        overlayImg.src = `${UPLOADS_BASE + encodeURIComponent(images[idx].filename)}`;
        overlayImg.alt = 'Gallery Image';
        overlay.style.display = 'flex';
        currentIdx = idx;
        document.body.style.overflow = 'hidden';
    }
    function hideLightbox() {
        overlay.style.display = 'none';
        overlayImg.src = '';
        document.body.style.overflow = '';
    }
    function showPrev() {
        if (currentIdx > 0) showLightbox(currentIdx - 1);
    }
    function showNext() {
        if (currentIdx < images.length - 1) showLightbox(currentIdx + 1);
    }
    galleryImgs.forEach(img => {
        img.addEventListener('click', e => {
            const idx = parseInt(img.getAttribute('data-idx'));
            showLightbox(idx);
        });
    });
    closeBtn.onclick = hideLightbox;
    overlay.onclick = e => { if (e.target === overlay) hideLightbox(); };
    prevBtn.onclick = showPrev;
    nextBtn.onclick = showNext;
    document.addEventListener('keydown', function lightboxKey(e) {
        if (overlay.style.display !== 'none') {
            if (e.key === 'Escape') hideLightbox();
            if (e.key === 'ArrowLeft') showPrev();
            if (e.key === 'ArrowRight') showNext();
        }
    });
}

// Delete image function (no admin required)
async function deleteImage(imageId) {
    if (!confirm('Are you sure you want to delete this image?')) {
        return;
    }
    
    try {
        const res = await fetch(`${DELETE_API}/${imageId}`, {
            method: 'DELETE'
        });
        
        const result = await res.json();
        
        if (result.success) {
            showStatus('Image deleted successfully', 'success');
            loadGallery(); // Reload gallery
        } else {
            showStatus('Failed to delete image: ' + (result.error || 'Unknown error'), 'error');
        }
    } catch (err) {
        console.error('Delete error:', err);
        showStatus('Failed to delete image: Network error', 'error');
    }
}

// Show status message
function showStatus(message, type) {
    const statusDiv = document.getElementById('upload-status');
    if (statusDiv) {
        statusDiv.textContent = message;
        statusDiv.className = `status ${type}`;
        statusDiv.style.display = 'block';
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            statusDiv.style.display = 'none';
        }, 5000);
    }
}

// Enhanced file upload with progress
function setupFileUpload() {
    const fileInput = document.getElementById('image-input');
    const uploadArea = document.querySelector('.upload-area');
    
    if (!fileInput || !uploadArea) return;
    
    // Drag and drop functionality
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
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            updateFileDisplay();
        }
    });
    
    // File input change
    fileInput.addEventListener('change', updateFileDisplay);
    
    // Click to select file
    uploadArea.addEventListener('click', () => {
        fileInput.click();
    });
}

function updateFileDisplay() {
    const fileInput = document.getElementById('image-input');
    const uploadText = document.querySelector('.upload-text');
    
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        uploadText.textContent = `Selected: ${file.name}`;
        
        // Show preview
        const reader = new FileReader();
        reader.onload = (e) => {
            const preview = document.getElementById('image-preview');
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(file);
    }
}

// On page load
window.addEventListener('DOMContentLoaded', () => {
    loadGallery();
    setupFileUpload();
});

// Handle upload form submit
const uploadForm = document.getElementById('upload-form');
if (uploadForm) {
    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const imageInput = document.getElementById('image-input');
        const uploadBtn = document.querySelector('.upload-btn');
        const progressBar = document.querySelector('.progress-bar');
        const progressFill = document.querySelector('.progress-fill');
        
        if (!imageInput.files[0]) {
            showStatus('Please select an image.', 'error');
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(imageInput.files[0].type)) {
            showStatus('Please select a valid image file (JPEG, PNG, GIF, or WebP).', 'error');
            return;
        }
        
        // Validate file size (50MB max)
        if (imageInput.files[0].size > 50 * 1024 * 1024) {
            showStatus('File size must be less than 50MB.', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('image', imageInput.files[0]);
        
        // Show progress
        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Uploading...';
        showStatus('Uploading image...', 'loading');
        
        if (progressBar) {
            progressBar.style.display = 'block';
            progressFill.style.width = '0%';
        }
        
        try {
            const xhr = new XMLHttpRequest();
            
            // Upload progress
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable && progressFill) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressFill.style.width = percentComplete + '%';
                }
            });
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        showStatus('Upload successful!', 'success');
                        imageInput.value = '';
                        updateFileDisplay();
                        loadGallery();
                    } else {
                        showStatus('Upload failed: ' + (data.error || 'Unknown error'), 'error');
                    }
                } else {
                    showStatus('Upload failed: Server error', 'error');
                }
                
                // Reset UI
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'Upload Image';
                if (progressBar) progressBar.style.display = 'none';
            };
            
            xhr.onerror = function() {
                showStatus('Upload failed: Network error', 'error');
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'Upload Image';
                if (progressBar) progressBar.style.display = 'none';
            };
            
            xhr.open('POST', UPLOAD_API);
            xhr.send(formData);
            
        } catch (err) {
            console.error('Upload error:', err);
            showStatus('Upload failed: Network error', 'error');
            uploadBtn.disabled = false;
            uploadBtn.textContent = 'Upload Image';
            if (progressBar) progressBar.style.display = 'none';
        }
    });
}

// Auto-refresh gallery every 30 seconds
setInterval(loadGallery, 30000);