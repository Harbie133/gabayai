// Mobile menu toggle
function toggleMobileMenu() {
  const mobileMenu = document.getElementById('mobileMenu');
  const hamburger = document.getElementById('hamburger');
  
  if (mobileMenu && hamburger) {
    mobileMenu.classList.toggle('show');
    hamburger.classList.toggle('open');
  }
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(e) {
  const mobileMenu = document.getElementById('mobileMenu');
  const hamburger = document.getElementById('hamburger');
  
  if (mobileMenu && hamburger) {
    if (!e.target.closest('.mobile-menu-btn') && !e.target.closest('.mobile-menu')) {
      if (mobileMenu.classList.contains('show')) {
        mobileMenu.classList.remove('show');
        hamburger.classList.remove('open');
      }
    }
  }
});

// Logout function - ADD THIS
function logout() {
  if (confirm('Are you sure you want to logout?')) {
    window.location.href = 'user_logout.php';
  }
}
