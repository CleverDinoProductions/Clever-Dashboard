const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const source=fs.readFileSync(__dirname+'/../assets/grouped-navigation.js','utf8');
const saved=new Map();
function boot(scope,fail=false){
 function element(){return {children:[],dataset:{},attrs:{},append(...nodes){this.children.push(...nodes);},replaceChildren(){this.children=[];},setAttribute(key,value){this.attrs[key]=value;}};}
 const catalog={'premier-league/matches':{label:'Matches',context:'Premier League',url:'?tab=2025-2026&league=premier-league&subtab=matches',active:true}};
 const favorites=element(),original=element(),empty=element(),status=element();original.dataset.navPin='premier-league/matches';
 const group={dataset:{navGroup:'favorites'},open:true,addEventListener(){}};
 const expand={addEventListener(_,fn){this.click=fn;}},collapse={addEventListener(_,fn){this.click=fn;}};
 const root={dataset:{storageScope:scope},querySelector(selector){return {'[data-nav-catalog]':{textContent:JSON.stringify(catalog)},'[data-nav-favorites]':favorites,'[data-nav-empty]':empty,'[data-nav-status]':status,'[data-nav-expand]':expand,'[data-nav-collapse]':collapse}[selector];},querySelectorAll(selector){if(selector==='[data-nav-group]')return [group];if(selector==='[data-nav-pin]')return [original,...favorites.children.map(wrapper=>wrapper.children[1])];},addEventListener(_,fn){this.click=fn;},contains(){return true;}};
 const storage={getItem(key){return saved.get(key)||null;},setItem(key,value){if(fail)throw Error('unavailable');saved.set(key,value);}};
 vm.runInNewContext(source,{document:{querySelector(){return root;},createElement:element},localStorage:storage});
 return {favorites,original,empty,status,group,expand,collapse,pin(){root.click({target:{closest(){return original;}}});}};
}
let ui=boot('42');assert.equal(ui.favorites.children.length,0);ui.pin();assert.equal(ui.favorites.children.length,1);assert.equal(ui.original.attrs['aria-pressed'],'true');assert.equal(ui.favorites.children[0].children[0].href,'?tab=2025-2026&league=premier-league&subtab=matches');
ui=boot('42');assert.equal(ui.favorites.children.length,1);ui.collapse.click();assert.equal(ui.group.open,false);ui.expand.click();assert.equal(ui.group.open,true);ui.pin();assert.equal(ui.favorites.children.length,0);
ui.pin();assert.equal(boot('guest').favorites.children.length,0);
ui=boot('other',true);ui.pin();assert.equal(ui.favorites.children.length,1);assert.match(ui.status.textContent,/storage is unavailable/);
console.log('Navigation pin and collapse behavior tests passed.');
