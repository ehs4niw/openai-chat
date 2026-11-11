(function(){'use strict';
var cfg=window.dgsQuizConfig;if(!cfg)return;
function el(tag,text,cls){var x=document.createElement(tag);if(text!==undefined)x.textContent=text;if(cls)x.className=cls;return x;}
function fa(v){return String(v).replace(/\d/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'[Number(d)];});}
function latin(v){return String(v).replace(/[۰-۹]/g,function(d){return String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d));}).replace(/[٠-٩]/g,function(d){return String('٠١٢٣٤٥٦٧٨٩'.indexOf(d));});}
function data(id){var e=document.getElementById(id);return e?JSON.parse(e.textContent):null;}
function url(year,args){var u=new URL(cfg.portal,location.href);u.searchParams.set('dg_tab','quizzes');u.searchParams.set('dg_year',year);Object.keys(args||{}).forEach(function(k){u.searchParams.set(k,args[k]);});return u.href;}
async function api(op,fields,files){var body=new FormData();body.set('action','dgs_quiz');body.set('nonce',cfg.nonce);body.set('op',op);Object.keys(fields||{}).forEach(function(k){body.set(k,String(fields[k]));});(files||[]).forEach(function(f){body.set(f.key,f.file);});var response=await fetch(cfg.ajax,{method:'POST',body:body,credentials:'same-origin'}),json;try{json=await response.json();}catch(e){throw Error('پاسخ معتبر دریافت نشد. اگر فایل بزرگی بارگذاری می‌کردید، حجم آن از سقف سرور بیشتر است؛ در غیر این صورت اتصال یا ورود به حساب را بررسی کنید.');}if(!json.success){var err=Error(json.data&&json.data.message?json.data.message:'درخواست انجام نشد؛ صفحه را تازه کنید.');err.code=json.data&&json.data.code;throw err;}return json.data;}
function fileSize(n){return n>=1048576?fa((n/1048576).toFixed(1))+' مگابایت':fa(Math.max(1,Math.round(n/1024)))+' کیلوبایت';}

/* ---------------- سازنده آزمون: PDF + کلید پاسخ ---------------- */
var builder=document.getElementById('dgq-builder'),initial=data('dgq-builder-data');
if(builder&&initial){
 var months=initial.months,count=Math.max(1,Math.min(60,initial.count||20)),key=[],MAXFILE=20*1024*1024;
 for(var k=0;k<count;k++)key.push(Number(initial.key.charAt(k))||0);
 var grid=document.getElementById('dgq-key-grid'),countInput=document.getElementById('dgq-count'),quick=document.getElementById('dgq-quick'),quickMsg=document.getElementById('dgq-quick-msg'),status=document.getElementById('dgq-builder-status'),quickDefault=quickMsg.textContent;
 var sum=function(name){return builder.querySelector('[data-sum="'+name+'"]');};
 var ys=initial.year.split('-');
 /* --- تاریخ و ساعت --- */
 var files={},dts={};builder.querySelectorAll('.dgq-dt').forEach(function(box){
  var prefix=box.getAttribute('data-dt-prefix'),f={day:box.querySelector('[data-dt="day"]'),month:box.querySelector('[data-dt="month"]'),year:box.querySelector('[data-dt="year"]'),hour:box.querySelector('[data-dt="hour"]'),minute:box.querySelector('[data-dt="minute"]'),date:box.querySelector('input[name="'+prefix+'_date"]'),time:box.querySelector('input[name="'+prefix+'_time"]')};
  function sync(){var m=Number(f.month.value),max=m<=6?31:30;Array.from(f.day.options).forEach(function(o){var hide=Number(o.value)>max;o.hidden=hide;o.disabled=hide;});if(Number(f.day.value)>max)f.day.value=String(max);var y=m>=7?ys[0]:ys[1];f.year.textContent=fa(y);f.date.value=y+'-'+String(m).padStart(2,'0')+'-'+String(f.day.value).padStart(2,'0');f.time.value=f.hour.value+':'+f.minute.value;summary();}
  [f.day,f.month,f.hour,f.minute].forEach(function(s){s.addEventListener('change',sync);});dts[prefix]={f:f,sync:sync,label:function(){var m=Number(f.month.value);return fa(f.day.value)+' '+months[m-1]+' '+f.year.textContent+'، '+fa(f.hour.value+':'+f.minute.value);}};sync();
 });
 /* --- فایل‌ها --- */
 builder.querySelectorAll('[data-dgq-file]').forEach(function(box){
  var kind=box.getAttribute('data-dgq-file'),st={existing:box.getAttribute('data-has-file')==='1',remove:false,picked:null},cur=box.querySelector('.dgq-file-current'),input=box.querySelector('[data-file-input]'),label=box.querySelector('[data-file-label]'),picked=box.querySelector('.dgq-file-picked'),zone=box.querySelector('.dgq-dropzone');
  function paint(){cur.hidden=!st.existing||st.remove;picked.hidden=!st.picked;zone.hidden=!!st.picked;label.textContent=(st.existing&&!st.remove)?label.getAttribute('data-text-replace'):label.getAttribute('data-text-empty');if(st.picked){box.querySelector('[data-file-name]').textContent=st.picked.name;box.querySelector('[data-file-size]').textContent=fileSize(st.picked.size)+((st.existing&&!st.remove)?' — جایگزین فایل قبلی می‌شود':'');}summary();}
  input.addEventListener('change',function(){var f=input.files[0];if(!f)return;if(!/\.pdf$/i.test(f.name)||(f.type&&f.type!=='application/pdf')){alert('فقط فایل PDF قابل انتخاب است.');input.value='';return;}if(f.size>MAXFILE){alert('حجم فایل باید حداکثر ۲۰ مگابایت باشد.');input.value='';return;}st.picked=f;paint();});
  box.querySelector('[data-file-clear]').addEventListener('click',function(){st.picked=null;input.value='';paint();});
  box.querySelector('[data-file-remove]').addEventListener('click',function(){if(confirm('فایل ذخیره‌شده حذف شود؟ این کار پس از «ذخیره» نهایی می‌شود.')){st.remove=true;paint();}});
  files[kind]={st:st,has:function(){return !!st.picked||(st.existing&&!st.remove);}};paint();
 });
 /* --- کلید پاسخ --- */
 var rows=[];
 function paintRow(i){var row=rows[i];if(!row)return;row.el.classList.toggle('done',key[i]>0);row.btns.forEach(function(b,j){var on=key[i]===j+1;b.classList.toggle('on',on);b.setAttribute('aria-pressed',on?'true':'false');});}
 function buildGrid(){grid.replaceChildren();rows=[];for(var i=0;i<count;i++)(function(i){var r=el('div',undefined,'dgq-key-row'),no=el('b',fa(i+1),'dgq-key-no'),btns=[];r.append(no);[1,2,3,4].forEach(function(n){var b=el('button',fa(n));b.type='button';b.setAttribute('aria-label','سؤال '+fa(i+1)+' گزینه '+fa(n));b.addEventListener('click',function(){key[i]=key[i]===n?0:n;paintRow(i);summary();});btns.push(b);r.append(b);});rows.push({el:r,btns:btns});grid.append(r);paintRow(i);})(i);summary();}
 function setCount(n){n=Math.max(1,Math.min(60,Number(n)||1));count=n;while(key.length<n)key.push(0);key.length=n;countInput.value=n;buildGrid();}
 countInput.addEventListener('change',function(){setCount(countInput.value);});
 quick.addEventListener('input',function(){var raw=latin(quick.value);if(!raw.trim()){quickMsg.textContent=quickDefault;return;}var digits=raw.replace(/[^1-4]/g,'');if(!digits.length){quickMsg.textContent='فقط ارقام ۱ تا ۴ پذیرفته می‌شود.';return;}var used=digits.slice(0,count);for(var i=0;i<count;i++){key[i]=used[i]?Number(used[i]):0;paintRow(i);}quickMsg.textContent=digits.length>count?'تعداد ارقام ('+fa(digits.length)+') بیشتر از تعداد سؤال‌هاست؛ فقط '+fa(count)+' رقم اول استفاده شد.':fa(used.length)+' پاسخ وارد شد'+(used.length<count?'؛ '+fa(count-used.length)+' سؤال بدون کلید مانده است.':'.');summary();});
 function filled(){return key.filter(function(v){return v>0;}).length;}
 function summary(){var t=builder.querySelector('input[name="title"]');if(!sum('title'))return;sum('title').textContent=t&&t.value.trim()?t.value.trim():'آزمون بدون عنوان';sum('count').textContent=fa(count);var d=builder.querySelector('input[name="duration"]');sum('duration').textContent=d&&d.value?fa(d.value)+' دقیقه':'—';if(dts.open)sum('start').textContent=dts.open.label();if(dts.close)sum('end').textContent=dts.close.label();sum('exam').textContent=files.exam&&files.exam.has()?'بارگذاری شده':'—';var f=filled();var kd=sum('key');kd.textContent=fa(f)+' از '+fa(count);kd.className=f===count?'dgq-green':'';}
 builder.addEventListener('input',function(e){if(e.target.name==='title'||e.target.name==='duration')summary();});
 if(initial.published){document.getElementById('dgq-publish').textContent='ذخیره تغییرات';document.getElementById('dgq-save-draft').hidden=true;}
 async function save(publish){
  publish=publish||initial.published;
  if(!builder.reportValidity())return;
  if(publish){var problems=[];if(!files.exam.has())problems.push('فایل PDF آزمون را بارگذاری کنید.');var missing=[];key.forEach(function(v,i){if(!v)missing.push(i+1);});if(missing.length)problems.push('کلید پاسخ ناقص است؛ سؤال‌های بدون پاسخ: '+fa(missing.slice(0,15).join('، '))+(missing.length>15?' و ...':'')+'.');if(problems.length){status.textContent=problems.join(' ');status.className='dgq-error';return;}if(!confirm('تغییرات آزمون ذخیره شود؟ در آزمون موجود، کلید جدید روی نمرات قبلی اعمال می‌شود.'))return;}
  var values=new FormData(builder),pair=String(values.get('pair')).split(':');
  var fields={id:initial.id,revision:initial.revision,year:initial.year,class_id:pair[0],subject_id:pair[1],publish:publish?'1':'0',title:values.get('title'),duration:values.get('duration'),open_date:values.get('open_date'),open_time:values.get('open_time'),close_date:values.get('close_date'),close_time:values.get('close_time'),qcount:count,key:key.join(''),remove_exam:files.exam.st.remove&&!files.exam.st.picked?'1':'0',remove_answer:files.answer.st.remove&&!files.answer.st.picked?'1':'0'};
  var up=[];if(files.exam.st.picked)up.push({key:'exam_pdf',file:files.exam.st.picked});if(files.answer.st.picked)up.push({key:'answer_pdf',file:files.answer.st.picked});
  var buttons=builder.querySelectorAll('button');buttons.forEach(function(b){b.disabled=true;});status.className='';status.textContent=up.length?'در حال بارگذاری فایل‌ها و ذخیره… لطفاً صفحه را نبندید.':'در حال ذخیره…';
  try{var r=await api('editor_save',fields,up);location.href=url(initial.year,r.published?{quiz_saved:1}:{quiz_view:'edit',quiz_id:r.id,quiz_saved:1});}catch(e){status.textContent=e.message;status.className='dgq-error';buttons.forEach(function(b){b.disabled=false;});}
 }
 builder.addEventListener('submit',function(e){e.preventDefault();});
 document.getElementById('dgq-save-draft').addEventListener('click',function(){save(false);});
 document.getElementById('dgq-publish').addEventListener('click',function(){save(true);});
 buildGrid();
}

/* ---------------- شرکت در آزمون (PDF یا حالت قدیمی سؤال‌به‌سؤال) ---------------- */
var take=data('dgq-take-data');
if(take){
 var state=null,current=0,busy=false,dirty=false,finish=false,ended=false,debounce=null,timer=null,anchor=0,serverNow=0,pdfMode=!!take.pdf;
 var saveState=document.getElementById('dgq-save-state'),error=document.getElementById('dgq-take-error'),retry=document.getElementById('dgq-retry'),optionsBox=document.getElementById('dgq-current-options');
 function failure(e){if(e.code==='quiz_changed'){retry.textContent='بارگذاری آزمون به‌روز';retry.dataset.reload='1';}error.textContent=e.message;error.hidden=false;retry.hidden=false;saveState.textContent='ذخیره تأیید نشده است؛ دوباره تلاش کنید.';saveState.className='dgq-error';}
 function progress(){var count=0;state.questions.forEach(function(item){if(state.answers[item.id])count++;});document.getElementById('dgq-answered-count').textContent='پاسخ داده‌شده: '+fa(count)+' از '+fa(state.questions.length);document.getElementById('dgq-progress-fill').style.width=(count/state.questions.length*100)+'%';return count;}
 function renderSheet(){var box=document.getElementById('dgq-sheet');if(!box.children.length){state.questions.forEach(function(q,i){var row=el('div',undefined,'dgq-sheet-row');row.append(el('b',fa(i+1),'dgq-sheet-no'));[1,2,3,4].forEach(function(n){var b=el('button',fa(n),'dgq-bubble');b.type='button';b.dataset.q=String(q.id);b.dataset.n=String(n);b.setAttribute('aria-label','سؤال '+fa(i+1)+' گزینه '+fa(n));b.addEventListener('click',function(){if(finish||ended)return;if(Number(state.answers[q.id])===n)delete state.answers[q.id];else state.answers[q.id]=n;changed();});row.append(b);});box.append(row);});}
  box.querySelectorAll('.dgq-bubble').forEach(function(b){var on=Number(state.answers[b.dataset.q])===Number(b.dataset.n);b.classList.toggle('on',on);b.setAttribute('aria-pressed',on?'true':'false');b.disabled=finish||ended;});progress();}
 function render(){
  if(pdfMode){renderSheet();return;}
  var q=state.questions[current];document.getElementById('dgq-current-number').textContent='سؤال '+fa(current+1)+' از '+fa(state.questions.length);document.getElementById('dgq-current-prompt').textContent=q.prompt;var img=document.getElementById('dgq-current-image');img.hidden=!q.image;if(q.image)img.src=q.image;else img.removeAttribute('src');optionsBox.replaceChildren(el('legend','انتخاب پاسخ سؤال','screen-reader-text'));
  q.options.forEach(function(text,i){var label=el('label'),input=el('input');input.type='radio';input.name='dgq-current-answer';input.value=String(i+1);input.checked=Number(state.answers[q.id])===i+1;input.disabled=finish||ended;input.addEventListener('change',function(){state.answers[q.id]=i+1;changed();});label.append(input,el('span',text));optionsBox.append(label);});
  var map=document.getElementById('dgq-question-map');map.replaceChildren();state.questions.forEach(function(item,i){var b=el('button',fa(i+1),'dgq-map-item'+(state.answers[item.id]?' answered':'')+(i===current?' current':''));b.type='button';b.setAttribute('aria-label','سؤال '+fa(i+1));b.addEventListener('click',function(){current=i;render();});map.append(b);});progress();document.getElementById('dgq-prev').disabled=current===0;document.getElementById('dgq-next').disabled=current===state.questions.length-1;
 }
 function changed(){if(finish||ended){render();return;}dirty=true;saveState.textContent='در انتظار ذخیره…';saveState.className='dgq-save-state';render();clearTimeout(debounce);debounce=setTimeout(function(){send();},450);}
 function goResult(){ended=true;clearInterval(timer);location.href=url(take.year,{quiz_id:take.id,quiz_view:'result'});}
 async function send(){if(busy||!state||ended||(!dirty&&!finish))return;busy=true;var final=finish,snapshot=JSON.stringify(state.answers);dirty=false;saveState.textContent='در حال ذخیره…';
  try{var r=await api(final?'submit':'save',{id:take.id,quiz_revision:state.quiz_revision,revision:state.revision,answers:snapshot});if(r.over){goResult();return;}state.revision=r.revision;state.deadline=r.deadline;serverNow=r.now;anchor=performance.now();error.hidden=true;retry.hidden=true;saveState.textContent='✓ پاسخ شما در سرور ذخیره شد.';saveState.className='dgq-save-state';}catch(e){dirty=true;failure(e);busy=false;return;}busy=false;if(dirty||finish)send();}
 function tick(){if(!state||ended)return;var remaining=Math.max(0,Math.ceil(state.deadline-(serverNow+(performance.now()-anchor)/1000)));document.getElementById('dgq-timer').textContent=fa(Math.floor(remaining/60))+':'+fa(String(remaining%60).padStart(2,'0'));if(!remaining){clearInterval(timer);finish=true;var c=document.getElementById('dgq-clear');if(c)c.disabled=true;if(optionsBox)optionsBox.querySelectorAll('input').forEach(function(x){x.disabled=true;});if(pdfMode)renderSheet();send();}}
 document.getElementById('dgq-start').addEventListener('click',async function(){this.disabled=true;try{var r=await api('start',{id:take.id});if(r.over){goResult();return;}state=r;state.answers=r.answers||{};serverNow=r.now;anchor=performance.now();document.getElementById('dgq-start-card').hidden=true;document.getElementById('dgq-take').hidden=false;
  if(pdfMode){if(r.pdf){document.getElementById('dgq-pdf-frame').src=r.pdf.view+'#toolbar=1&navpanes=0';document.getElementById('dgq-pdf-open').href=r.pdf.view;document.getElementById('dgq-pdf-download').href=r.pdf.download;}else{error.textContent='فایل PDF این آزمون پیدا نشد؛ موضوع را به دبیر اطلاع دهید.';error.hidden=false;}}
  render();tick();timer=setInterval(tick,1000);}catch(e){failure(e);this.disabled=false;}});
 var clearBtn=document.getElementById('dgq-clear'),prevBtn=document.getElementById('dgq-prev'),nextBtn=document.getElementById('dgq-next');
 if(clearBtn)clearBtn.addEventListener('click',function(){if(!state)return;delete state.answers[state.questions[current].id];changed();});
 if(prevBtn)prevBtn.addEventListener('click',function(){if(current>0){current--;render();}});if(nextBtn)nextBtn.addEventListener('click',function(){if(current<state.questions.length-1){current++;render();}});
 document.getElementById('dgq-submit').addEventListener('click',function(){if(state&&confirm('آزمون نهایی شود؟ امکان تغییر پاسخ‌ها نخواهید داشت.')){finish=true;this.disabled=true;send();}});retry.addEventListener('click',function(){if(retry.dataset.reload==='1'){ended=true;location.reload();return;}if(state)send();else document.getElementById('dgq-start').click();});window.addEventListener('online',function(){if(state&&(dirty||finish))send();});window.addEventListener('beforeunload',function(e){if((dirty||busy)&&!ended){e.preventDefault();e.returnValue='';}});
}
document.querySelectorAll('[data-quiz-print]').forEach(function(button){button.addEventListener('click',function(){document.body.classList.add('dgq-printing');window.print();document.body.classList.remove('dgq-printing');});});window.addEventListener('afterprint',function(){document.body.classList.remove('dgq-printing');});
document.querySelectorAll('[data-quiz-delete]').forEach(function(button){button.addEventListener('click',async function(){if(!confirm('آزمون از فهرست و دسترسی کاربران حذف شود؟ سوابق در پایگاه داده نگه‌داری می‌شوند.'))return;button.disabled=true;try{await api('editor_delete',{id:button.dataset.quizDelete,revision:button.dataset.revision});var u=new URL(location.href);u.searchParams.delete('quiz_id');u.searchParams.delete('quiz_view');u.searchParams.delete('quiz_student');location.href=u.href;}catch(e){alert(e.message);button.disabled=false;}});});

})();
