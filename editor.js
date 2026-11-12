(() => {
  'use strict';
  const fields = [...document.querySelectorAll('[data-edit-key]')];
  const tools = document.getElementById('edit-tools');
  const save = document.getElementById('save-content');
  const status = document.getElementById('edit-status');
  const localHost = ['localhost','127.0.0.1','[::1]'].includes(location.hostname);
  let canEdit = false, dirty = false, saving = false;
  const setStatus = value => { if(status) status.textContent = value; };
  const snapshot = () => Object.fromEntries(fields.map(el => [el.dataset.editKey, el.innerHTML]));
  const setDirty = () => { if(canEdit){dirty = true;setStatus('Cambios sin guardar');} };
  async function init() {
    // Static JSON is the canonical published data: works on GitHub Pages without PHP.
    try {
      const res = await fetch('./contenido.json', {cache:'no-store'});
      if(!res.ok) throw new Error('HTTP '+res.status);
      const data = await res.json();
      for(const el of fields) {
        if(Object.prototype.hasOwnProperty.call(data.content || {},el.dataset.editKey))
          el.innerHTML = data.content[el.dataset.editKey];
      }
    } catch(error) {
      console.warn('No se pudo leer contenido.json; se conserva el contenido incluido en HTML.',error);
    }
    if(!localHost || !['http:','https:'].includes(location.protocol)) return;
    // Localhost alone is not sufficient: require the local PHP endpoint to approve editing.
    try {
      const res = await fetch('./api.php', {cache:'no-store',credentials:'same-origin'});
      if(!res.ok) return;
      const data = await res.json();
      if(data.canEdit !== true) return;
      canEdit = true;
      fields.forEach(el => {el.contentEditable='true';el.spellcheck=true;el.addEventListener('input',setDirty);});
      if(tools) tools.hidden=false;
      setStatus('Edición local activa · Sin cambios');
    } catch(error) { console.info('Edición desactivada: no hay API PHP local.',error); }
  }
  save?.addEventListener('click', async () => {
    if(!canEdit || saving) return;
    saving=true;save.disabled=true;setStatus('Guardando…');
    const content=snapshot();
    try {
      const res=await fetch('./api.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({content})});
      const result=await res.json();
      if(!res.ok || !result.ok) throw new Error(result.error||'No se pudo guardar');
      dirty=false;setStatus('Guardado en contenido.json · Publica el JSON actualizado en GitHub');
    } catch(error) {setStatus('Error: '+error.message);}
    finally {saving=false;save.disabled=false;}
  });
  window.addEventListener('beforeunload',event=>{if(dirty){event.preventDefault();event.returnValue='';}});
  init();
})();
