'use strict';
definitions.setup={title:'Preparación del sistema',description:'Revisá los datos iniciales, los respaldos y los requisitos para comenzar.'};
async function renderSetup(ticket){
    const data=await api('setup_status');if(ticket!==loadId)return;
    const root=$('#page-body');root.replaceChildren();
    const intro=node('p','El núcleo disponible permite cargar productos, proveedores y clientes, recibir mercadería y consultar informes. La facturación requiere autorización vigente y calibración física.','notice');root.append(intro);
    const list=node('div',undefined,'setup-checks');for(const check of data.checks){const c=node('section',undefined,'card setup-check'),row=node('div',undefined,'setup-check-heading');row.append(icon(check.ok?'file-check':'calendar-clock'),node('h3',check.title),node('span',check.ok?'Listo':'Pendiente','badge'+(check.ok?'':' warning')));c.append(row,node('p',check.detail,'muted'));if(check.link){const link=node('a',check.ok?'Consultar':'Completar','text-button');link.href='#'+check.link;c.append(link);}list.append(c);}root.append(list);
    const pending=card('Circuitos que todavía necesitan desarrollo','La instalación no sustituye los módulos pendientes.');const ul=node('ul');for(const title of data.pending_modules)ul.append(node('li',title));pending.append(ul);root.append(pending);
    root.append(node('p','Los respaldos se guardan en esta computadora: '+data.backup_directory+'. Conservá también una copia externa bajo tu control.','report-footnote muted'));
    $('#page-actions').append(button('Actualizar comprobaciones',load,'secondary'));
}
