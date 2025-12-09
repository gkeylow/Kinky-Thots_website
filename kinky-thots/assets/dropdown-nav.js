// Mobile Navigation Handler - Unified across all pages

(function() {
    'use strict';

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileNav);
    } else {
        initMobileNav();
    }

    function initMobileNav() {
        const navToggle = document.querySelector('.nav-toggle');
        const navLinks = document.querySelector('.nav-links');
        const dropdowns = document.querySelectorAll('.dropdown');
        const navbar = document.querySelector('nav');
        
        if (!navToggle || !navLinks) {
            console.warn('Navigation elements not found');
            return;
        }

        // Mobile menu toggle
        navToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isActive = navLinks.classList.contains('active');
            navLinks.classList.toggle('active');
            navToggle.setAttribute('aria-expanded', String(!isActive));
            
            console.log('Menu toggled:', navLinks.classList.contains('active'));
        });

        // Mobile dropdown toggle
        dropdowns.forEach(dropdown => {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            
            if (!toggle) return;
            
            toggle.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const isActive = dropdown.classList.contains('active');
                    
                    // Close other dropdowns
                    dropdowns.forEach(other => {
                        if (other !== dropdown) {
                            other.classList.remove('active');
                        }
                    });
                    
                    // Toggle current dropdown
                    dropdown.classList.toggle('active');
                }
            });
        });

        // Close menu when clicking nav links
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                navLinks.classList.remove('active');
                navToggle.setAttribute('aria-expanded', 'false');
                dropdowns.forEach(d => d.classList.remove('active'));
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('nav')) {
                if (navLinks.classList.contains('active')) {
                    navLinks.classList.remove('active');
                    navToggle.setAttribute('aria-expanded', 'false');
                }
                dropdowns.forEach(d => d.classList.remove('active'));
            }
        });

        // Handle window resize - reset menu on desktop size
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth > 768) {
                    navLinks.classList.remove('active');
                    navToggle.setAttribute('aria-expanded', 'false');
                    dropdowns.forEach(d => d.classList.remove('active'));
                }
            }, 250);
        });

        // Navbar scroll effect
        let lastScroll = 0;
        
        if (navbar) {
            window.addEventListener('scroll', function() {
                const currentScroll = window.pageYOffset;
                
                if (currentScroll > 50) {
                    navbar.style.boxShadow = '0 4px 16px rgba(0, 0, 0, 0.4)';
                } else {
                    navbar.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.2)';
                }
                
                lastScroll = currentScroll;
            });
        }
    }
})();
