(function () {
  document.querySelectorAll('.dsh-carousel').forEach(function (wrap) {
    var track = wrap.querySelector('.dsh-people-scroll');
    if (!track) return;
    wrap.querySelectorAll('[data-dsh-scroll]').forEach(function (button) {
      button.addEventListener('click', function () {
        track.scrollBy({
          left: (button.dataset.dshScroll === 'next' ? -1 : 1) * Math.max(140, track.clientWidth * 0.65),
          behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
        });
      });
    });
  });
  document.querySelectorAll('.dsh-featured-tabs').forEach(function (list) {
    var tabs = Array.from(list.querySelectorAll('[role=tab]'));
    function select(tab) {
      tabs.forEach(function (item) {
        var active = item === tab;
        item.setAttribute('aria-selected', String(active));
        item.tabIndex = active ? 0 : -1;
        var panel = document.getElementById(item.getAttribute('aria-controls'));
        if (panel) panel.hidden = !active;
      });
    }
    tabs.forEach(function (tab, index) {
      tab.addEventListener('click', function () { select(tab); });
      tab.addEventListener('keydown', function (event) {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        var next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 :
          (index + (event.key === 'ArrowLeft' ? 1 : -1) + tabs.length) % tabs.length;
        select(tabs[next]);
        tabs[next].focus();
      });
    });
  });
})();
