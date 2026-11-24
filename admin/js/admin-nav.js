(function () {
  'use strict';

  var toggle = document.getElementById('adminNavToggle');
  var nav = document.getElementById('floatNav');
  var backdrop = document.getElementById('adminNavBackdrop');
  if (!toggle || !nav) return;

  var icon = toggle.querySelector('i');

  function setOpen(open) {
    document.body.classList.toggle('admin-nav-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
    if (icon) icon.className = open ? 'bi bi-x-lg' : 'bi bi-list';
  }

  toggle.addEventListener('click', function () {
    setOpen(!document.body.classList.contains('admin-nav-open'));
  });

  if (backdrop) {
    backdrop.addEventListener('click', function () { setOpen(false); });
  }

  nav.addEventListener('click', function (event) {
    if (event.target.closest('a')) setOpen(false);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') setOpen(false);
  });

  window.addEventListener('resize', function () {
    if (window.innerWidth >= 992) setOpen(false);
  });
})();
