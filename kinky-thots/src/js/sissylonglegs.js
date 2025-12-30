import '../css/landing.css';

function initModelPage() {
  setupHeroSlideshow();
  setupGallery();
}

function setupHeroSlideshow() {
  const slides = document.querySelectorAll('.hero-slide');
  if (slides.length <= 1) {return;}

  let currentSlide = 0;

  setInterval(() => {
    slides[currentSlide].classList.remove('active');
    currentSlide = (currentSlide + 1) % slides.length;
    slides[currentSlide].classList.add('active');
  }, 5000);
}

function setupGallery() {
  const galleryContainer = document.querySelector('#sissy-gallery');
  if (!galleryContainer) {return;}

  const lightbox = document.querySelector('#lightbox');
  const lightboxImg = lightbox?.querySelector('.lightbox-img');

  let images = [];
  let currentIndex = 0;

  galleryContainer.addEventListener('click', (e) => {
    const thumb = e.target.closest('.gallery-thumb');
    if (!thumb || !lightbox) {return;}

    images = Array.from(galleryContainer.querySelectorAll('.gallery-thumb img'));
    const img = thumb.querySelector('img');
    currentIndex = images.indexOf(img);

    if (lightboxImg && img) {
      lightboxImg.src = img.dataset.full || img.src;
    }

    lightbox.classList.add('active');
  });

  if (lightbox) {
    lightbox.addEventListener('click', (e) => {
      if (e.target === lightbox || e.target.classList.contains('lightbox-close')) {
        lightbox.classList.remove('active');
      }
    });
  }

  document.addEventListener('keydown', (e) => {
    if (!lightbox?.classList.contains('active')) {return;}

    switch (e.key) {
      case 'Escape':
        lightbox.classList.remove('active');
        break;
      case 'ArrowLeft':
        currentIndex = (currentIndex - 1 + images.length) % images.length;
        if (lightboxImg) {
          lightboxImg.src = images[currentIndex].dataset.full || images[currentIndex].src;
        }
        break;
      case 'ArrowRight':
        currentIndex = (currentIndex + 1) % images.length;
        if (lightboxImg) {
          lightboxImg.src = images[currentIndex].dataset.full || images[currentIndex].src;
        }
        break;
    }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initModelPage);
} else {
  initModelPage();
}
