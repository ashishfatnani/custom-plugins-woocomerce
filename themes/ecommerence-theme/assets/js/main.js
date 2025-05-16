// assets/js/main.js

document.addEventListener('DOMContentLoaded', function() {
    console.log('Custom theme JavaScript loaded.');

    // Example: Mobile Navigation Toggle
    var menuToggle = document.getElementById('menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            var nav = document.querySelector('.main-navigation');
            if (nav) {
                nav.classList.toggle('open');
            }
        });
    }

    // Example: Smooth scrolling for anchor links
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});
