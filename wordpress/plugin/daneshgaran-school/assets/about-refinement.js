(function () {
  function refine() {
    if (!document.body.classList.contains('dgs-about-refined')) return;
    document.querySelectorAll('.dse-document .elementor-widget-heading .elementor-heading-title').forEach(function (heading) {
      if (!heading.textContent.includes('پایه‌های تحصیلی')) return;
      var column = heading.closest('.elementor-column');
      if (!column || column.querySelector('.dsh-grade-chips')) return;
      var text = column.querySelector('.elementor-widget-text-editor .elementor-widget-container');
      if (!text) return;
      text.replaceChildren();
      var intro = document.createElement('p');
      intro.className = 'abr-grade-intro';
      intro.textContent = 'متوسطه دوم (دوره دوم دبیرستان)';
      var chips = document.createElement('div');
      chips.className = 'dsh-grade-chips';
      ['پایه دهم', 'پایه یازدهم', 'پایه دوازدهم'].forEach(function (label) {
        var chip = document.createElement('span'); chip.textContent = label; chips.appendChild(chip);
      });
      text.append(intro, chips);
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', refine);
  else refine();
})();
