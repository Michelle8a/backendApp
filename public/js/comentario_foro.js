function destroy(button) {
    const url = button.dataset.url;
    const token = button.dataset.token;
    const autor = button.dataset.autor || 'este usuario';
    const resumen = button.dataset.resumen || '';

    Swal.fire({
        title: '¿Eliminar comentario?',
        text: 'Comentario de ' + autor + ': "' + resumen + '". Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (!result.isConfirmed) return;

        try {
            const response = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (!response.ok || !data.res) {
                throw new Error(data.message || 'No se pudo eliminar el comentario.');
            }

            await Swal.fire({
                title: '¡Eliminado!',
                text: 'El comentario se eliminó correctamente.',
                icon: 'success'
            });

            window.location.reload();

        } catch (error) {
            Swal.fire({
                title: 'Error',
                text: error.message || 'Ocurrió un error al eliminar.',
                icon: 'error'
            });
        }
    });
}

// Rellena el modal de detalle con los datos de la fila seleccionada
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modalComentario');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', (event) => {
        const d = event.relatedTarget.dataset;
        const campo = (n) => modal.querySelector('[data-campo="' + n + '"]');

        campo('contenido').textContent = d.contenido;
        campo('autor').textContent = d.autor;
        campo('fecha').textContent = d.fecha;
        campo('publicacion').textContent = d.publicacion;
        campo('categoria').textContent = d.categoria;
        campo('anonimo').classList.toggle('d-none', d.anonimo !== '1');
        campo('oculta').classList.toggle('d-none', d.oculta !== '1');
    });
});
