(function () {
  'use strict';
  var actions = document.querySelector('[data-rc-move-actions]');
  var slot = document.querySelector('[data-rc-actions]');
  if (actions && slot) { while (actions.firstChild) slot.appendChild(actions.firstChild); actions.remove(); }
  var term = document.querySelector('.rc-filters [name="rc_term"]');
  var month = document.querySelector('.rc-filters [name="grade_period"]');
  var year = document.querySelector('.rc-filters [name="dg_year"]');
  function filterMonths() {
    if (!term || !month) return;
    Array.from(month.options).forEach(function (o) { o.hidden = o.dataset.rcTerm !== term.value; o.disabled = o.hidden; });
    if (!month.selectedOptions.length || month.selectedOptions[0].disabled) {
      var first = Array.from(month.options).find(function (o) { return !o.disabled; });
      if (first) month.value = first.value;
    }
  }
  if (term) { term.addEventListener('change', filterMonths); filterMonths(); }
  if (year && month) year.addEventListener('change', function () {
    var parts = year.value.split('-');
    Array.from(month.options).forEach(function (o) { var m = o.value.slice(5); o.value = parts[Number(m) >= 7 ? 0 : 1] + '-' + m; });
  });

  if (!document.body.dataset.rcExport) return;
  var status = document.querySelector('[data-rc-status]');
  var pdfButton = document.querySelector('[data-rc-pdf]');
  var printButton = document.querySelector('[data-rc-print]');
  var busy = false;
  async function ready() {
    if (document.fonts && document.fonts.ready) await document.fonts.ready;
    await Promise.all(Array.from(document.querySelectorAll('#rc-export-document img')).map(function (img) {
      return img.decode ? img.decode().catch(function () {}) : Promise.resolve();
    }));
  }
  function chartRaster(svg) {
    // Draw the chart with the browser's loaded Persian font. This avoids SVG/CSS
    // viewport changes inside html2canvas's cloned document.
    var canvas = document.createElement('canvas'); canvas.width = 1080; canvas.height = 500;
    var ctx = canvas.getContext('2d'); ctx.scale(2, 2);
    Array.from(svg.children).forEach(function (node) {
      var style = getComputedStyle(node), tag = node.tagName.toLowerCase();
      var n = function (key) { return Number(node.getAttribute(key) || 0); };
      ctx.save(); ctx.strokeStyle = style.stroke === 'none' ? 'transparent' : style.stroke;
      ctx.fillStyle = style.fill === 'none' ? 'transparent' : style.fill;
      ctx.lineWidth = parseFloat(style.strokeWidth) || 1;
      if (tag === 'line') {
        ctx.setLineDash((style.strokeDasharray === 'none' ? [] : style.strokeDasharray.split(/[ ,]+/).map(parseFloat)));
        ctx.beginPath(); ctx.moveTo(n('x1'), n('y1')); ctx.lineTo(n('x2'), n('y2')); ctx.stroke();
      } else if (tag === 'path') {
        var path = new Path2D(node.getAttribute('d')); if (style.fill !== 'none') ctx.fill(path); if (style.stroke !== 'none') ctx.stroke(path);
      } else if (tag === 'circle') {
        ctx.beginPath(); ctx.arc(n('cx'), n('cy'), n('r'), 0, Math.PI * 2); ctx.fill(); ctx.stroke();
      } else if (tag === 'text') {
        ctx.font = style.fontWeight + ' ' + style.fontSize + ' ' + style.fontFamily;
        var anchor = node.getAttribute('text-anchor'); ctx.textAlign = anchor === 'middle' ? 'center' : (anchor === 'end' ? 'right' : 'left');
        ctx.direction = 'ltr'; ctx.lineJoin = 'round';
        if (style.stroke !== 'none') ctx.strokeText(node.textContent, n('x'), n('y'));
        ctx.fillText(node.textContent, n('x'), n('y'));
      }
      ctx.restore();
    });
    var img = new Image(); img.src = canvas.toDataURL('image/png');
    img.style.cssText = 'display:block;width:100%;height:auto;max-height:260px'; img.alt = svg.querySelector('title').textContent;
    return img;
  }
  async function downloadPDF() {
    if (busy) return;
    busy = true; pdfButton.disabled = true; status.textContent = 'در حال ساخت فایل PDF…';
    try {
      await ready();
      var doc = document.getElementById('rc-export-document');
      var chart = doc.querySelector('.rc-trend>svg'); var image = chart ? chartRaster(chart) : null;
      var canvas, bounds;
      try {
        if (image) { await image.decode(); chart.style.display = 'none'; chart.parentNode.insertBefore(image, chart); }
        bounds = doc.getBoundingClientRect();
        canvas = await window.html2canvas(doc, {scale: 1.8, backgroundColor: '#faf6ff', logging: false, useCORS: false, windowWidth: window.innerWidth, scrollX: 0, scrollY: 0});
      } finally { if (image) { image.remove(); chart.style.display = ''; } }

      var pdf = await window.PDFLib.PDFDocument.create();
      pdf.setTitle('School monthly report'); pdf.setProducer('Daneshgaran School');
      var margin = 25, pageWidth = 595.28, pageHeight = 841.89;
      var imageWidth = pageWidth - margin * 2;
      var maxSlice = Math.floor((pageHeight - margin * 2) / imageWidth * canvas.width);
      var ratio = canvas.width / bounds.width;
      // Cut at table-row/card edges so a long report does not split a row or paragraph.
      var units = Array.from(doc.querySelectorAll('.rc-pdf-block, tr, h3, .rc-footnote, .rc-feedback-text'));
      var blocks = units.map(function (e) { var b = e.getBoundingClientRect(); return {top: Math.round((b.top - bounds.top) * ratio), bottom: Math.round((b.bottom - bounds.top) * ratio)}; });
      var offset = 0;
      while (offset < canvas.height) {
        var end = Math.min(offset + maxSlice, canvas.height);
        if (end < canvas.height) {
          var crossing = blocks.filter(function (b) { return b.top > offset + maxSlice * 0.35 && b.top < end && b.bottom > end; });
          if (crossing.length) end = Math.min.apply(null, crossing.map(function (b) { return b.top; }));
        }
        var height = Math.max(1, end - offset);
        var slice = document.createElement('canvas'); slice.width = canvas.width; slice.height = height;
        slice.getContext('2d').drawImage(canvas, 0, offset, canvas.width, height, 0, 0, canvas.width, height);
        var image = await pdf.embedJpg(slice.toDataURL('image/jpeg', 0.94));
        var page = pdf.addPage([pageWidth, pageHeight]); var imageHeight = height / canvas.width * imageWidth;
        page.drawImage(image, {x: margin, y: pageHeight - margin - imageHeight, width: imageWidth, height: imageHeight});
        offset = end;
      }
      var bytes = await pdf.save(); var blob = new Blob([bytes], {type: 'application/pdf'});
      var url = URL.createObjectURL(blob); var a = document.createElement('a');
      a.href = url; a.download = document.body.dataset.rcFilename || 'report.pdf'; document.body.appendChild(a); a.click(); a.remove();
      setTimeout(function () { URL.revokeObjectURL(url); }, 30000);
      status.textContent = 'فایل PDF آماده شد. اگر مرورگر دانلود را متوقف کرد، دوباره دکمه دانلود را بزنید.';
    } catch (error) {
      status.textContent = 'ساخت PDF انجام نشد. دوباره تلاش کنید یا از چاپ و گزینه ذخیره به صورت PDF مرورگر استفاده کنید.';
    } finally { busy = false; pdfButton.disabled = false; }
  }
  pdfButton.addEventListener('click', downloadPDF);
  printButton.addEventListener('click', async function () { await ready(); window.print(); });
  ready().then(function () {
    if (document.body.dataset.rcExport === 'pdf') downloadPDF();
    else { status.textContent = 'کارنامه آماده چاپ است.'; window.print(); }
  }).catch(function () { status.textContent = 'برای دریافت کارنامه یکی از دکمه‌ها را بزنید.'; });
})();
