'use strict';
definitions.reports={title:'Informes',description:'Consultá tu operación con filtros, totales y resultados listos para exportar.'};
const reportLabels={from:'Desde',to:'Hasta',product_id:'Producto',supplier_id:'Proveedor',customer_id:'Cliente',operator_id:'Operador emisor / responsable',condition:'Condición de venta',status:'Estado',lot:'Código de lote contiene',band:'Plazo / antigüedad'};
let selectedReport='inventory',reportSavedFilters={};
function reportAmount(value,decimals=false){
    const parts=String(value??'0').split('.');const grouped=new Intl.NumberFormat('es-PY').format(BigInt(parts[0]||'0'));
    const fraction=decimals?(parts[1]??'').replace(/0+$/,''):'';return grouped+(fraction?','+fraction:'');
}
async function reportRequest(action,type='',filters={}){
    const query=new URLSearchParams({action,type,...filters});const response=await fetch('api.php?'+query);const result=await response.json();
    if(response.status===401){location.reload();throw new Error('La sesión venció.');}if(!response.ok)throw new Error(result.error||'No se pudo consultar el informe.');return result.data;
}
async function renderReports(ticket){
    const catalog=await reportRequest('report_catalog');if(ticket!==loadId)return;
    if(!catalog.some(r=>r.id===selectedReport))selectedReport=catalog[0]?.id;
    const root=$('#page-body'),layout=node('div',undefined,'reports-layout'),aside=node('aside',undefined,'report-catalog'),workspace=node('div',undefined,'report-workspace');
    aside.setAttribute('aria-label','Tipos de informe');
    for(const group of [...new Set(catalog.map(r=>r.group))]){
        const section=node('section');section.append(node('h2',group));
        for(const r of catalog.filter(r=>r.group===group)){
            const b=button(r.title,()=>{selectedReport=r.id;show(r);},'report-choice');b.dataset.report=r.id;section.append(b);
        }aside.append(section);
    }
    const future=node('div',undefined,'report-future');future.append(icon('calendar-clock'),node('strong','Próximos circuitos'),node('p','Caja, cobros, pagos, reparto y liquidación de comisiones tendrán sus informes al completar esos módulos.'));aside.append(future);
    layout.append(aside,workspace);root.replaceChildren(layout);
    let sequence=0;
    async function show(def){
        const seq=++sequence;aside.querySelectorAll('[data-report]').forEach(b=>{b.classList.toggle('selected',b.dataset.report===def.id);b.setAttribute('aria-pressed',String(b.dataset.report===def.id));});
        workspace.replaceChildren(node('div','Preparando informe…','loading'));
        try{
            const options=await reportRequest('report_options',def.id);if(seq!==sequence||ticket!==loadId)return;
            const heading=node('div',undefined,'report-heading'),info=node('div');info.append(node('span',def.period?'POR PERÍODO':'SITUACIÓN ACTUAL','eyebrow green'),node('h2',def.title));heading.append(info);
            const filters=node('form',undefined,'report-filters card'),fields=node('div',undefined,'report-filter-grid'),saved=reportSavedFilters[def.id]??{};
            const today=new Date().toLocaleDateString('en-CA',{timeZone:'America/Asuncion'});
            for(const key of [...(def.period?['from','to']:[]),...def.filters]){
                const label=node('label',reportLabels[key]);let input;
                if(options[key]){input=node('select');const all=node('option','Todos');all.value='';input.append(all);for(const o of options[key]){const option=node('option',(o.code?o.code+' · ':'')+o.name);option.value=o.id;input.append(option);}}
                else{input=node('input');input.type=['from','to'].includes(key)?'date':'text';if(key==='lot'){input.maxLength=80;input.placeholder='Ej. LOTE-01';}}
                input.name=key;input.value=saved[key]??(key==='from'?today.slice(0,7)+'-01':key==='to'?today:'');if(['from','to'].includes(key))input.required=true;label.append(input);fields.append(label);
            }
            const actions=node('div',undefined,'report-filter-actions'),apply=node('button','Consultar','primary');apply.type='submit';
            actions.append(apply,button('Restablecer',()=>{delete reportSavedFilters[def.id];show(def);},'text-button'));filters.append(fields,actions);
            const result=node('div');workspace.replaceChildren(heading,filters,result);let querySeq=0;
            async function query(values,pageNumber=1){
                const run=++querySeq;apply.disabled=true;result.replaceChildren(node('div','Consultando resultados…','loading'));
                // Disable old exports immediately; displayed results always match the submitted filter snapshot.
                heading.querySelector('.report-exports')?.remove();
                try{
                    const report=await reportRequest('report_data',def.id,{...values,page:pageNumber});if(seq!==sequence||ticket!==loadId||run!==querySeq)return;
                    reportSavedFilters[def.id]={...values};draw(report,values);
                }catch(err){if(seq!==sequence||ticket!==loadId||run!==querySeq)return;const error=node('p',err.message,'notice error');error.setAttribute('role','alert');result.replaceChildren(error);}
                finally{if(run===querySeq)apply.disabled=false;}
            }
            function draw(report,values){
                result.replaceChildren();const exports=node('div',undefined,'report-exports');
                for(const [label,path,ico,format]of [['Excel','informes-exportar.php','download','xlsx'],['PDF','informes-exportar.php','download','pdf'],['CSV','informes-exportar.php','download','csv'],['Imprimir','informes-imprimir.php','printer','']]){
                    const a=node('a',undefined,'secondary');a.append(icon(ico),node('span',label));a.href=path+'?'+new URLSearchParams({type:def.id,...values,...(format?{format}:{})});if(label==='Imprimir'){a.target='_blank';a.rel='noopener';}exports.append(a);
                }heading.append(exports);
                const note=node('p',report.note,'notice');result.append(note);
                if(Object.keys(report.filter_labels).length)result.append(node('p',Object.entries(report.filter_labels).map(([k,v])=>k+': '+v).join(' · '),'report-footnote muted'));
                const stats=node('div',undefined,'report-metrics');for(const s of report.summary){const metric=node('div',undefined,'stat');metric.append(node('span',s.label,'muted'),node('strong',(s.format==='money'?'Gs. ':'')+reportAmount(s.value)));stats.append(metric);}result.append(stats);
                if(report.chart.length){const chart=node('section',undefined,'card report-chart');chart.append(node('h3','Facturación por fecha de emisión'),node('p','Importes con IVA incluido · todos los resultados del filtro','muted'));const max=report.chart.reduce((m,c)=>Math.max(m,Number(c.total)),1);const list=node('div',undefined,'report-bars');for(const item of report.chart){const row=node('div',undefined,'report-bar-row'),meter=node('meter');meter.min=0;meter.max=max;meter.value=Number(item.total);meter.setAttribute('aria-label',item.date+' · Gs. '+reportAmount(item.total));row.append(node('span',date(item.date)),meter,node('strong','Gs. '+reportAmount(item.total)));list.append(row);}chart.append(list);result.append(chart);}
                const box=node('section',undefined,'card'),toolbar=node('div',undefined,'toolbar');toolbar.append(node('strong',`${report.pagination.total} registros`),node('span',`Generado ${report.generated_at} · Paraguay`,'count'));box.append(toolbar);
                const wrap=node('div',undefined,'table-wrap');if(!report.rows.length)empty(wrap,'No hay resultados con estos filtros.');else{
                    const table=node('table'),caption=node('caption',report.title,'sr-only'),thead=node('thead'),tr=node('tr');for(const c of report.columns){const th=node('th',c.label);th.scope='col';tr.append(th);}thead.append(tr);table.append(caption,thead);const tbody=node('tbody');
                    for(const row of report.rows){const tr=node('tr');for(const c of report.columns){const value=row[c.key],td=node('td',value==null?'—':c.format==='money'?'Gs. '+reportAmount(value,c.key==='unit_cost'):String(value));if(c.format==='money')td.className='report-number';tr.append(td);}tbody.append(tr);}table.append(tbody);wrap.append(table);
                }box.append(wrap);
                const pager=node('div',undefined,'report-pager'),prev=button('Anterior',()=>query(values,report.pagination.page-1),'secondary'),next=button('Siguiente',()=>query(values,report.pagination.page+1),'secondary');prev.disabled=report.pagination.page<=1;next.disabled=report.pagination.page>=report.pagination.pages;
                pager.append(prev,node('span',`Página ${report.pagination.page} de ${report.pagination.pages}`,'muted'),next);box.append(pager);result.append(box,node('p','Los totales corresponden a todos los resultados filtrados. El CSV y la impresión incluyen todas las páginas.','report-footnote muted'));
            }
            filters.addEventListener('submit',e=>{e.preventDefault();query(Object.fromEntries([...new FormData(filters)].filter(([,v])=>v!=='')));});
            await query(Object.fromEntries([...new FormData(filters)].filter(([,v])=>v!=='')));
        }catch(err){if(seq!==sequence||ticket!==loadId)return;workspace.replaceChildren(node('p',err.message,'notice error'),button('Reintentar',()=>show(def),'secondary'));}
    }
    if(catalog.length)await show(catalog.find(r=>r.id===selectedReport));else empty(workspace,'No hay informes habilitados para tu cuenta.');
}
