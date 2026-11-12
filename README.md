# Rúbrica — GitHub Pages + edición local

- `index.html`: documento estático para GitHub Pages.
- `contenido.json`: fuente de datos que lee el navegador al cargar.
- `editor.js`: carga el JSON y activa `contenteditable` únicamente cuando el host es localhost **y** `api.php` confirma que la petición procede de loopback.
- `api.php`: guarda cambios en `contenido.json` únicamente desde `127.0.0.1` o `::1`.

## Edición local

Desde esta carpeta ejecuta `php -S 127.0.0.1:8000` y abre `http://localhost:8000/` (o `http://127.0.0.1:8000/`). Edita los textos y pulsa **Guardar cambios**. Necesitas PHP con extensión DOM para el saneamiento del HTML.

## Publicación

Sube `index.html`, `editor.js` y `contenido.json` a la raíz del repositorio de GitHub Pages. Puedes subir también `api.php` si quieres compartir la misma carpeta: GitHub Pages no lo ejecuta y nunca se activa el editor en el dominio publicado. Tras editar localmente, haz commit y push del `contenido.json` modificado.

Si abres `index.html` con `file://`, se muestra el HTML inicial, pero para cargar JSON debes usar un servidor HTTP.

**Seguridad:** No expongas el servidor PHP local a Internet ni a una red de confianza dudosa. `localhost` no es un sistema de autenticación; la protección real de escritura es la restricción de IP de `api.php`.
