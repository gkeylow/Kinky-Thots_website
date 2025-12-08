// Dropdown Navigation JavaScript

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initDropdownNav();
    });

    function initDropdownNav() {
        const navToggle = document.querySelector('.nav-toggle');
        const navLinks = document.querySelector('.nav-links');
        const dropdowns = document.querySelectorAll('.dropdown');

        // Mobile menu toggle
        if (navToggle) {
            navToggle.addEventListener('click', function() {
                navLinks.classList.toggle('active');
            });
        }

        // Mobile dropdown toggle
        dropdowns.forEach(dropdown => {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            
            toggle.addEventListener('click', function(e) {
                // On mobile, toggle dropdown
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    dropdown.classList.toggle('active');
                    
                    // Close other dropdowns
                    dropdowns.forEach(other => {
                        if (other !== dropdown) {
                            other.classList.remove('active');
                        }
                    });
                }
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('nav') && navLinks.classList.contains('active')) {
                navLinks.classList.remove('active');
            }
        });

        // Navbar scroll effect
        const navbar = document.querySelector('nav');
        let lastScroll = 0;
        
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
})();
