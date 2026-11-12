// Ocultar / volver a mostrar una publicación (moderación, no borra nada)
function cambiarVisibilidad(button) {
    const url = button.dataset.url;
    const token = button.dataset.token;
    const titulo = button.dataset.titulo || 'esta publicación';
    const ocultar = button.dataset.ocultar === '1';

    Swal.fire({
        title: ocultar ? '¿Ocultar publicación?' : '¿Mostrar publicación de nuevo?',
        text: ocultar
            ? '"' + titulo + '" dejará de verse en la app para los demás usuarios. Podrás volver a mostrarla cuando quieras.'
            : '"' + titulo + '" volverá a verse en la app.',
        icon: ocultar ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonColor: ocultar ? '#d97706' : '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: ocultar ? 'Sí, ocultar' : 'Sí, mostrar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (!result.isConfirmed) return;

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ oculta: ocultar })
            });

            const data = await response.json();

            if (!response.ok || !data.res) {
                throw new Error(data.message || 'No se pudo cambiar la visibilidad.');
            }

            await Swal.fire({
                title: ocultar ? '¡Publicación oculta!' : '¡Publicación visible!',
                text: data.message,
                icon: 'success'
            });

            window.location.reload();

        } catch (error) {
            Swal.fire({
                title: 'No se pudo completar',
                text: error.message || 'Ocurrió un error al actualizar la publicación.',
                icon: 'error'
            });
        }
    });
}

// Rellena el modal de detalle con los datos de la fila seleccionada
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modalPublicacion');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', (event) => {
        const d = event.relatedTarget.dataset;
        const campo = (n) => modal.querySelector('[data-campo="' + n + '"]');

        campo('titulo').textContent = d.titulo;
        campo('categoria').textContent = d.categoria;
        campo('autor').textContent = d.autor;
        campo('fecha').textContent = d.fecha;
        campo('comentarios').textContent = d.comentarios;
        campo('megusta').textContent = d.megusta;
        campo('contenido').textContent = d.contenido;
        campo('anonimo').classList.toggle('d-none', d.anonimo !== '1');

        const aviso = campo('oculta');
        aviso.classList.toggle('d-none', d.oculta !== '1');
        aviso.textContent = d.ocultaInfo || '';
    });
});
