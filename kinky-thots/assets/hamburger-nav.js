// Hamburger Navigation JavaScript

(function() {
    'use strict';

    // Initialize immediately and also wait for DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHamburgerNav);
    } else {
        initHamburgerNav();
    }

    function initHamburgerNav() {
        console.log('Initializing hamburger navigation...');
        
        const hamburger = document.querySelector('.hamburger');
        const navMenu = document.querySelector('.nav-menu');
        const navOverlay = document.querySelector('.nav-overlay');
        const navLinks = document.querySelectorAll('.nav-links a:not(.coming-soon)');
        const navbar = document.querySelector('.navbar');

        console.log('Hamburger:', hamburger);
        console.log('Nav Menu:', navMenu);
        console.log('Nav Overlay:', navOverlay);

        if (!hamburger || !navMenu) {
            console.error('Hamburger navigation elements not found!');
            console.error('Hamburger exists:', !!hamburger);
            console.error('Nav menu exists:', !!navMenu);
            return;
        }

        console.log('All elements found, setting up event listeners...');

        // Toggle menu
        hamburger.addEventListener('click', toggleMenu);
        
        // Close menu when clicking overlay
        if (navOverlay) {
            navOverlay.addEventListener('click', closeMenu);
        }

        // Close menu when clicking a link
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // If it's an anchor link on the same page, smooth scroll
                if (this.getAttribute('href').startsWith('#')) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    
                    if (targetElement) {
                        closeMenu();
                        setTimeout(() => {
                            targetElement.scrollIntoView({ behavior: 'smooth' });
                        }, 300);
                    }
                } else {
                    // For regular links, just close the menu
                    closeMenu();
                }
            });
        });

        // Close menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && navMenu.classList.contains('active')) {
                closeMenu();
            }
        });

        // Navbar scroll effect
        let lastScroll = 0;
        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
            
            lastScroll = currentScroll;
        });

        // Highlight active page
        highlightActivePage();

        function toggleMenu() {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
            if (navOverlay) {
                navOverlay.classList.toggle('active');
            }
            document.body.classList.toggle('menu-open');
        }

        function closeMenu() {
            hamburger.classList.remove('active');
            navMenu.classList.remove('active');
            if (navOverlay) {
                navOverlay.classList.remove('active');
            }
            document.body.classList.remove('menu-open');
        }

        function highlightActivePage() {
            const currentPath = window.location.pathname;
            const currentPage = currentPath.split('/').pop() || 'index.html';
            
            navLinks.forEach(link => {
                const linkPath = link.getAttribute('href');
                const linkPage = linkPath.split('/').pop();
                
                // Check if this link matches the current page
                if (linkPage === currentPage || 
                    (currentPage === '' && linkPage === 'index.html') ||
                    (currentPage === '/' && linkPage === 'index.html')) {
                    link.classList.add('active');
                }
            });
        }
    }
})();
