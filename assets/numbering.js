'use strict';
definitions.numbering={title:'Numeración de facturas',description:'Definí el rango autorizado y el próximo formulario que propondrá cada venta.'};
async function renderNumbering(ticket){
 const s=await api('numbering');if(ticket!==loadId)return;
 const canEdit=['admin','supervisor'].includes(user.role),form=node('form',undefined,'card billing-form'),grid=node('div',undefined,'form-grid'),inputs={};
 form.append(node('h3','Talonario y secuencia'),node('p','El inicio del rango es el primer número autorizado. El próximo formulario es el primero que todavía está sin usar físicamente; puede ser posterior al inicio. Cada factura confirmada avanza automáticamente al siguiente.','muted'));
 for(const [key,label,type]of [['timbrado','Timbrado','text'],['establishment','Establecimiento (3 dígitos)','text'],['point','Punto de expedición (3 dígitos)','text'],['valid_from','Vigencia desde','date'],['valid_until','Vigencia hasta','date'],['start','Inicio del rango autorizado','number'],['end','Final del rango autorizado','number'],['next','Próximo formulario sin usar','number']]){const f=billingInput(label,key,type,s[key]||'');inputs[key]=f.input;f.input.disabled=!canEdit;if(type==='number'){f.input.min=1;f.input.max=9999999;f.input.step=1;}if(['establishment','point'].includes(key)){f.input.maxLength=3;f.input.pattern='[0-9]{3}';}grid.append(f.wrap);}
 inputs.start.addEventListener('change',()=>{if(!inputs.next.value||Number(inputs.next.value)===0)inputs.next.value=inputs.start.value;});
 form.append(grid);const check=billingCheck('Vacarel está habilitado para utilizar este timbrado y esta modalidad.', 'fiscal_checked',s.fiscal_checked);check.input.disabled=!canEdit;form.append(check.wrap);
 form.append(node('p',s.formatted_next?'La próxima venta propondrá el formulario '+s.formatted_next+'.':'Cargá el próximo formulario sin usar para iniciar la secuencia.','notice'));
 form.append(node('p',s.calibrated?'La prueba de impresión está confirmada en la configuración.':'Falta realizar y confirmar la prueba de alineación en la impresora física.','muted'));
 const actions=node('div',undefined,'billing-actions'),error=node('p','','error');error.setAttribute('role','alert');
 if(canEdit){const save=node('button','Guardar numeración','primary');save.type='submit';actions.append(save);const link=node('a','Configurar papel y probar impresión','secondary');link.href='#billing_settings';actions.append(link);form.addEventListener('submit',async e=>{e.preventDefault();save.disabled=true;error.textContent='';try{const payload={_revision:s._revision,fiscal_checked:check.input.checked};for(const [key,input]of Object.entries(inputs))payload[key]=input.value;await api('numbering_save','',payload);toast('Numeración guardada. Las ventas usarán esta secuencia.');await load();}catch(err){error.textContent=err.message;}finally{save.disabled=false;}});}
 form.append(error,actions);$('#page-body').replaceChildren(form);
}
