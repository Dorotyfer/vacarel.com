const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
async function scenario(test=false,overflow=false,fail=false){
 const handlers={},button={disabled:false,addEventListener:(name,fn)=>handlers[name]=fn},message={textContent:''},result={hidden:true};
 let nativePrints=0,requests=0;
 const context={console,crypto:require('node:crypto'),document:{body:{dataset:{test:test?'1':'0',saleId:'1',auto:'0'}},fonts:{ready:Promise.resolve()},querySelector:s=>({'#print-button':button,'#print-message':message,'#print-result':result,'meta[name="csrf-token"]':{content:'csrf'}}[s]??null),querySelectorAll:s=>s==='.field'&&overflow?[{scrollWidth:100,clientWidth:10}]:[]},fetch:async()=>{requests++;return {ok:!fail,json:async()=>fail?{error:'Sin permiso'}:{id:9}}},addEventListener:()=>{},print:()=>nativePrints++};
 context.window=context;const native=context.print;vm.createContext(context);vm.runInContext(fs.readFileSync('assets/preimpresa.js','utf8'),context);
 await handlers.click();assert.equal(context.print,native,'No sustituir window.print');
 return {nativePrints,requests,message:message.textContent,result};
}
(async()=>{let r=await scenario();assert.equal(r.nativePrints,1);assert.equal(r.requests,1);assert.equal(r.result.hidden,false);console.log('OK intento registrado y diálogo nativo solicitado una sola vez');r=await scenario(true);assert.equal(r.nativePrints,1);assert.equal(r.requests,0);console.log('OK prueba no consume numeración ni registra factura');r=await scenario(false,true);assert.equal(r.nativePrints,0);assert.equal(r.requests,0);console.log('OK desbordamiento bloquea impresión');r=await scenario(false,false,true);assert.equal(r.nativePrints,0);assert.equal(r.message,'Sin permiso');console.log('OK denegación de permiso no abre impresión');})().catch(e=>{console.error(e);process.exitCode=1;});
