import '../css/media-gallery.css';

function init() {
  setupNavbarScroll();
  setupLightbox();
  setupFilters();
}

function setupNavbarScroll() {
  const navbar = document.getElementById('navbar');
  if (!navbar) {return;}

  window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;

    if (currentScroll <= 0) {
      navbar.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.2)';
    } else {
      navbar.style.boxShadow = '0 4px 16px rgba(0, 0, 0, 0.4)';
    }
  });
}

function setupLightbox() {
  window.openLightbox = function (thumbnail) {
    const container = thumbnail.closest('.video-container');
    if (!container) {return;}

    const videoUrl = container.dataset.videoUrl;
    const videoType = container.dataset.videoType || 'video/mp4';

    const lightbox = document.createElement('div');
    lightbox.className = 'video-lightbox';
    lightbox.innerHTML = `
      <div class="lightbox-content">
        <button class="lightbox-close">&times;</button>
        <video controls autoplay>
          <source src="${videoUrl}" type="${videoType}">
          Your browser does not support the video tag.
        </video>
      </div>
    `;

    document.body.appendChild(lightbox);
    document.body.style.overflow = 'hidden';

    const closeLightbox = () => {
      const video = lightbox.querySelector('video');
      if (video) {
        video.pause();
        video.src = '';
      }
      lightbox.remove();
      document.body.style.overflow = '';
    };

    lightbox.addEventListener('click', (e) => {
      if (e.target === lightbox) closeLightbox();
    });
    lightbox.querySelector('.lightbox-close')?.addEventListener('click', closeLightbox);

    const escHandler = (e) => {
      if (e.key === 'Escape') {
        closeLightbox();
        document.removeEventListener('keydown', escHandler);
      }
    };
    document.addEventListener('keydown', escHandler);
  };
}

function setupFilters() {
  const filterBtns = document.querySelectorAll('.filter-btn');
  const videoContainers = document.querySelectorAll('.video-container');

  if (!filterBtns.length) {return;}

  filterBtns.forEach((btn) => {
    btn.addEventListener('click', function () {
      const filter = this.dataset.filter;

      filterBtns.forEach((b) => b.classList.remove('active'));
      this.classList.add('active');

      videoContainers.forEach((container) => {
        if (filter === 'all' || container.dataset.aspect === filter) {
          container.style.display = 'block';
        } else {
          container.style.display = 'none';
        }
      });
    });
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
