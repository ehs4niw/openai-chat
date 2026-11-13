(function () {
  document.querySelectorAll('.dgg-preview-body').forEach(function (wrapper) {
    var track = wrapper.querySelector('.dgg-track');
    if (!track) return;
    wrapper.querySelectorAll('[data-dgg-scroll]').forEach(function (button) {
      button.addEventListener('click', function () {
        track.scrollBy({
          left: (button.dataset.dggScroll === 'next' ? -1 : 1) * Math.max(160, track.clientWidth * 0.7),
          behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
        });
      });
    });
  });
  var dialog = document.getElementById('dgg-lightbox');
  if (!dialog) return;
  var items = Array.from(document.querySelectorAll('.dgg-open'));
  var active = 0;
  function show(index) {
    if (!items.length) return;
    active = (index + items.length) % items.length;
    var link = items[active];
    var image = dialog.querySelector('img');
    image.src = link.href;
    image.alt = link.dataset.dggTitle || '';
    dialog.querySelector('p').textContent = link.dataset.dggTitle || '';
  }
  items.forEach(function (link, index) {
    link.addEventListener('click', function (event) {
      if (!dialog.showModal) return;
      event.preventDefault();
      show(index);
      document.documentElement.classList.add('dgg-open-page');
      if (!dialog.open) dialog.showModal();
    });
  });
  dialog.querySelector('[data-dgg-close]').addEventListener('click', function () { dialog.close(); });
  dialog.addEventListener('close', function () {
    document.documentElement.classList.remove('dgg-open-page');
    dialog.querySelector('img').removeAttribute('src');
  });
  dialog.querySelectorAll('[data-dgg-step]').forEach(function (button) {
    button.addEventListener('click', function () { show(active + Number(button.dataset.dggStep)); });
  });
  dialog.addEventListener('keydown', function (event) {
    if (event.key === 'ArrowLeft') { event.preventDefault(); show(active + 1); }
    if (event.key === 'ArrowRight') { event.preventDefault(); show(active - 1); }
  });
})();
