(function(){
'use strict';
const root=document.querySelector('[data-grouped-navigation]');if(!root)return;
const catalog=JSON.parse(root.querySelector('[data-nav-catalog]').textContent);
const storageKey='football-navigation-v1:'+root.dataset.storageScope;
let state={pins:[],groups:{}};
try{const saved=JSON.parse(localStorage.getItem(storageKey));if(saved&&typeof saved==='object'){state.pins=Array.isArray(saved.pins)?Array.from(new Set(saved.pins.filter(id=>typeof id==='string'))).slice(0,100):[];state.groups=saved.groups&&typeof saved.groups==='object'&&!Array.isArray(saved.groups)?saved.groups:{};}}catch(e){}
function save(){try{localStorage.setItem(storageKey,JSON.stringify(state));}catch(e){root.querySelector('[data-nav-status]').textContent='Pins work for this page; browser storage is unavailable.';}}
function pinButton(id){const button=document.createElement('button');button.type='button';button.dataset.navPin=id;return button;}
function render(){
 const favorites=root.querySelector('[data-nav-favorites]');favorites.replaceChildren();
 state.pins.forEach(id=>{const entry=catalog[id];if(!entry)return;const wrapper=document.createElement('span');wrapper.className='navigation-tab';const link=document.createElement('a');link.href=entry.url;link.className='pill-tab'+(entry.active?' active':'');if(entry.active)link.setAttribute('aria-current','page');link.textContent=entry.context+' · '+entry.label;wrapper.append(link,pinButton(id));favorites.append(wrapper);});
 root.querySelector('[data-nav-empty]').hidden=favorites.children.length>0;
 root.querySelectorAll('[data-nav-pin]').forEach(button=>{const id=button.dataset.navPin;const pinned=state.pins.includes(id);button.textContent=pinned?'★':'☆';button.setAttribute('aria-pressed',String(pinned));button.setAttribute('aria-label',(pinned?'Unpin ':'Pin ')+catalog[id].context+' '+catalog[id].label);});
}
root.addEventListener('click',event=>{const button=event.target.closest('[data-nav-pin]');if(!button||!root.contains(button))return;const id=button.dataset.navPin;if(!catalog[id])return;state.pins=state.pins.includes(id)?state.pins.filter(pin=>pin!==id):state.pins.concat(id);save();render();});
const groups=Array.from(root.querySelectorAll('[data-nav-group]'));
groups.forEach(group=>{const id=group.dataset.navGroup;if(group.dataset.activeGroup!=='1'&&typeof state.groups[id]==='boolean')group.open=state.groups[id];group.addEventListener('toggle',()=>{state.groups[id]=group.open;save();});});
root.querySelector('[data-nav-expand]').addEventListener('click',()=>groups.forEach(group=>{group.open=true;}));
root.querySelector('[data-nav-collapse]').addEventListener('click',()=>groups.forEach(group=>{group.open=false;}));
render();
}());
