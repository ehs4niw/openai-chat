(function(){'use strict';
const toggle=document.querySelector('.dg-mobile-toggle'),sidebar=document.getElementById('dg-sidebar'),backdrop=document.querySelector('.dg-backdrop');
function menu(open){document.body.classList.toggle('dg-menu-open',open);if(toggle)toggle.setAttribute('aria-expanded',String(open));if(backdrop)backdrop.hidden=!open;}
// Mobile button uses its immediate handler so optimization plugins cannot delay it.

document.addEventListener('keydown',e=>{if(e.key==='Escape')menu(false);});
if(sidebar)sidebar.addEventListener('click',e=>{if(e.target.closest('a'))menu(false);});
document.querySelectorAll('[data-dg-tablist]').forEach(list=>{
 const tabs=Array.from(list.querySelectorAll('[role=tab]'));const wrap=list.closest('[data-dg-tabs]');
 function select(tab){tabs.forEach(t=>{const on=t===tab;t.setAttribute('aria-selected',String(on));t.tabIndex=on?0:-1;const panel=wrap.querySelector('#'+t.getAttribute('aria-controls'));if(panel)panel.hidden=!on;});}
 tabs.forEach((tab,index)=>{tab.addEventListener('click',()=>select(tab));tab.addEventListener('keydown',event=>{if(!['ArrowLeft','ArrowRight','Home','End'].includes(event.key))return;event.preventDefault();let n=event.key==='Home'?0:event.key==='End'?tabs.length-1:(index+(event.key==='ArrowLeft'?1:-1)+tabs.length)%tabs.length;select(tabs[n]);tabs[n].focus();});});
});
document.querySelectorAll('[data-dg-dialog]').forEach(button=>button.addEventListener('click',()=>{const dialog=document.getElementById(button.dataset.dgDialog);if(dialog&&dialog.showModal)dialog.showModal();}));
document.querySelectorAll('dialog [data-dg-dismiss]').forEach(button=>button.addEventListener('click',()=>button.closest('dialog').close()));
})();
