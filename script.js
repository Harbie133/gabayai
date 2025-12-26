document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
  const hamburger = this.querySelector('.hamburger');
  const mobileMenu = document.getElementById('mobileMenu');

  hamburger.classList.toggle('open');
  mobileMenu.classList.toggle('show');
});
