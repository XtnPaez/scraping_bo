// app.js — comportamiento general de la interfaz

// ─── UPLOAD ZONE drag & drop ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    const zone = document.querySelector('.upload-zone');
    const input = document.getElementById('pdf_file');

    if (zone && input) {
        zone.addEventListener('click', () => input.click());

        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('dragover');
        });

        zone.addEventListener('dragleave', () => {
            zone.classList.remove('dragover');
        });

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                actualizarNombreArchivo(e.dataTransfer.files[0].name);
            }
        });

        input.addEventListener('change', function () {
            if (this.files.length) actualizarNombreArchivo(this.files[0].name);
        });

        function actualizarNombreArchivo(nombre) {
            const p = zone.querySelector('p');
            if (p) p.innerHTML = '📄 <strong>' + nombre + '</strong>';
        }
    }

    // ─── LOADER en formularios de ejecución ──────────────────────────────────
    const formEjecutar = document.getElementById('form-ejecutar');
    const loader       = document.getElementById('loader-ejecutar');

    if (formEjecutar && loader) {
        formEjecutar.addEventListener('submit', function () {
            loader.classList.add('active');
        });
    }

});
