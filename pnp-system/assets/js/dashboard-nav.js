/**
 * Dashboard Navigation Functionality for Municipal PNP System
 */
document.addEventListener('DOMContentLoaded', function() {
    const toggleNavButton = document.getElementById('toggle-nav');
    const sideNav = document.getElementById('dashboard-nav');
    const mainContent = document.getElementById('main-content');
    const topNav = document.getElementById('top-nav');
    const mobileToggle = document.getElementById('mobile-toggle');
    const body = document.body;
    
    // Toggle side navigation
    if (toggleNavButton && sideNav) {
        toggleNavButton.addEventListener('click', function(e) {
            e.preventDefault();
            sideNav.classList.toggle('collapsed');
            
            if (mainContent) {
                mainContent.classList.toggle('expanded');
            }
            
            if (topNav) {
                topNav.classList.toggle('expanded');
            }
            
            // Save state in localStorage
            const isCollapsed = sideNav.classList.contains('collapsed');
            localStorage.setItem('sideNavCollapsed', isCollapsed);
        });
    }
    
    // Mobile toggle
    if (mobileToggle && sideNav) {
        mobileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            sideNav.classList.toggle('mobile-visible');
            body.classList.toggle('nav-open');
        });
    }
    
    // Handle outside click to close mobile nav
    document.addEventListener('click', function(event) {
        const isClickInsideNav = sideNav && sideNav.contains(event.target);
        const isClickOnMobileToggle = mobileToggle && mobileToggle.contains(event.target);
        
        if (!isClickInsideNav && !isClickOnMobileToggle && window.innerWidth < 992 && 
            sideNav && sideNav.classList.contains('mobile-visible')) {
            sideNav.classList.remove('mobile-visible');
            body.classList.remove('nav-open');
        }
    });
    
    // Close navigation when clicking a link on mobile
    if (sideNav) {
        const navLinks = sideNav.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992 && sideNav.classList.contains('mobile-visible')) {
                    sideNav.classList.remove('mobile-visible');
                    body.classList.remove('nav-open');
                }
            });
        });
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            if (sideNav && sideNav.classList.contains('mobile-visible')) {
                sideNav.classList.remove('mobile-visible');
                body.classList.remove('nav-open');
            }
        }
    });
    
    // Restore collapsed state from localStorage
    const savedCollapsedState = localStorage.getItem('sideNavCollapsed');
    if (savedCollapsedState === 'true' && sideNav) {
        sideNav.classList.add('collapsed');
        
        if (mainContent) {
            mainContent.classList.add('expanded');
        }
        
        if (topNav) {
            topNav.classList.add('expanded');
        }
    }
    
    // Set active link based on current page
    setActiveLink();
    
    // Add class to stat cards if missing
    addStatCardClasses();
});

/**
 * Set active class on the current page's navigation link
 */
function setActiveLink() {
    const currentPath = window.location.pathname;
    const fileName = currentPath.split('/').pop();
    
    // Get all navigation links
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        // Get the href attribute
        const href = link.getAttribute('href');
        
        // If link href ends with current file name or both are dashboards
        if (href && href.endsWith(fileName)) {
            link.classList.add('active');
            
            // If it's in a collapsible, open it
            const parent = link.closest('.collapse');
            if (parent) {
                parent.classList.add('show');
                
                // Activate the parent button too
                const id = parent.getAttribute('id');
                const parentButton = document.querySelector(`[data-bs-target="#${id}"]`);
                if (parentButton) {
                    parentButton.classList.remove('collapsed');
                    parentButton.setAttribute('aria-expanded', 'true');
                }
            }
        }
    });
}

/**
 * Add necessary classes to stat cards if they're missing
 */
function addStatCardClasses() {
    const statCards = document.querySelectorAll('.stats-card');
    statCards.forEach(card => {
        if (!card.classList.contains('border-left-primary') && 
            !card.classList.contains('border-left-success') && 
            !card.classList.contains('border-left-info') && 
            !card.classList.contains('border-left-warning')) {
            card.classList.add('border-left-primary');
        }
    });
} 