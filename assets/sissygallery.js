// Fetch and display gallery images for sissylonglegs
        (function() {
            const API_BASE = window.location.origin;
            const GALLERY_API = API_BASE + '/gallery';
            const UPLOADS_BASE = API_BASE + '/backend/uploads/';
            async function loadGallerySissy() {
                const grid = document.getElementById('gallery-grid-sissy');
                if (!grid) return;
                grid.innerHTML = '<div class="loading">Loading gallery...</div>';
                try {
                    const res = await fetch(GALLERY_API);
                    if (!res.ok) throw new Error('Failed to fetch');
                    const images = await res.json();
                    if (!Array.isArray(images) || images.length === 0) {
                        grid.innerHTML = `<div class="empty-gallery"><div class="empty-gallery-icon">📷</div><div class="empty-gallery-text">No images yet</div><div class="empty-gallery-subtext">Upload some images to get started</div></div>`;
                        return;
                    }
                    grid.innerHTML = images.map((img, idx) => `
                        <div class="gallery-item" data-id="${img.id}" data-idx="${idx}">
                            <img src="${UPLOADS_BASE + encodeURIComponent(img.filename)}" alt="Gallery Image" loading="lazy" data-idx="${idx}" style="cursor:pointer;" onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'200\'%3E%3Crect width=\'200\' height=\'200\' fill=\'%23f0f0f0\'/%3E%3Ctext x=\'100\' y=\'100\' text-anchor=\'middle\' fill=\'%23999\'%3EImage not found%3C/text%3E%3C/svg%3E'" />
                        </div>
                    `).join('');
                    // Lightbox setup
                    setupLightboxSissy(images);
                } catch (err) {
                    grid.innerHTML = `<div class="error-message"><div class="error-icon">⚠️</div><div class="error-text">Error loading gallery</div><div class="error-subtext">Please try again later</div></div>`;
                }
            }
            // Lightbox logic for sissylonglegs
            function setupLightboxSissy(images) {
                let currentIdx = 0;
                const overlay = document.getElementById('lightbox-overlay-sissy');
                const overlayImg = document.getElementById('lightbox-img-sissy');
                const closeBtn = document.getElementById('lightbox-close-sissy');
                const prevBtn = document.getElementById('lightbox-prev-sissy');
                const nextBtn = document.getElementById('lightbox-next-sissy');
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
                document.querySelectorAll('#gallery-grid-sissy .gallery-item img').forEach(img => {
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
            window.addEventListener('DOMContentLoaded', loadGallerySissy);
        })();