(function(){'use strict';
var root=document.querySelector('[data-attendance-root]');if(!root)return;
var form=root.querySelector('.dga-editor'),all=root.querySelector('[data-att-all]');
var gradeFilter=root.querySelector('[name=att_grade]'),classFilter=root.querySelector('[data-att-class-options]');
if(gradeFilter&&classFilter){
 var classOptions=[];try{classOptions=JSON.parse(classFilter.dataset.attClassOptions);}catch(e){}
 function staleEditor(){var editor=root.querySelector('.dga-editor');if(editor){editor.querySelectorAll('button,input,select,textarea').forEach(function(x){x.disabled=true;});editor.closest('.dga-card').hidden=true;}var stats=root.querySelector('.dga-stats');if(stats)stats.hidden=true;var note=root.querySelector('[data-att-filter-note]');if(!note){note=document.createElement('p');note.className='dga-help';note.dataset.attFilterNote='';classFilter.closest('form').after(note);}note.textContent='کلاس را انتخاب کنید و برای نمایش فرم مربوط به آن، «نمایش» را بزنید.';}
 gradeFilter.addEventListener('change',function(){var g=Number(this.value),list=classOptions.filter(function(x){return !g||x.grade===g;});classFilter.replaceChildren();var placeholder=document.createElement('option');placeholder.value='';placeholder.textContent=list.length?'انتخاب کلاس':'کلاسی برای این پایه ثبت نشده است';classFilter.append(placeholder);list.forEach(function(x){var o=document.createElement('option');o.value=x.id;o.textContent=x.label;classFilter.append(o);});classFilter.value='';classFilter.required=true;staleEditor();});
 classFilter.addEventListener('change',staleEditor);
}

function fa(n){return String(n).replace(/\d/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'[Number(d)];});}
function refresh(){if(!form)return;var closed=form.querySelector('[data-att-closed]').checked,counts={present:0,absent:0,late:0},pending=0;
form.dataset.closed=String(closed);form.querySelectorAll('[data-att-row]').forEach(function(row){var selected=row.querySelector('input[type=radio]:checked'),minutes=row.querySelector('[data-att-minutes]');row.querySelectorAll('input').forEach(function(i){i.disabled=closed;});if(!closed){if(selected){counts[selected.value]++;if(selected.value!=='late')minutes.value='0';}else pending++;}});
Object.keys(counts).forEach(function(k){var el=root.querySelector('[data-att-count="'+k+'"]');if(el)el.textContent=fa(counts[k]);});var p=form.querySelector('[data-att-pending]');if(p)p.textContent=closed?'— تعطیلی کلاس':('— '+fa(pending)+' نفر بدون انتخاب');if(all)all.disabled=closed;
}
if(form){form.addEventListener('change',refresh);if(all)all.addEventListener('click',function(){form.querySelectorAll('input[type=radio][value=present]').forEach(function(i){i.checked=true;});refresh();});
form.addEventListener('submit',function(event){var closed=form.querySelector('[data-att-closed]').checked;if(closed&&!window.confirm('تعطیلی ثبت شود؟ وضعیت‌های قبلی این کلاس در این روز جایگزین می‌شوند.')){event.preventDefault();delete form.dataset.sending;return;}
if(!closed){var invalid=false;form.querySelectorAll('[data-att-row]').forEach(function(row){var selected=row.querySelector('input[type=radio]:checked'),minutes=row.querySelector('[data-att-minutes]');if(!selected||(selected.value==='late'&&Number(minutes.value)<1))invalid=true;});if(invalid){event.preventDefault();delete form.dataset.sending;window.alert('وضعیت همه دانش‌آموزان و دقایق تأخیر (حداقل یک دقیقه) را مشخص کنید.');}}
});refresh();}
root.querySelectorAll('[data-att-print]').forEach(function(button){button.addEventListener('click',function(){document.body.classList.add('dga-printing');window.print();document.body.classList.remove('dga-printing');});});window.addEventListener('afterprint',function(){document.body.classList.remove('dga-printing');});
})();
