function destroy(button) {
    const url = button.dataset.url;
    const token = button.dataset.token;
    const nombre = button.dataset.nombre || 'esta categoría';

    Swal.fire({
        title: '¿Eliminar categoría?',
        text: 'Se eliminará "' + nombre + '". Esta acción no se puede deshacer.',
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
                throw new Error(data.message || 'No se pudo eliminar la categoría.');
            }

            await Swal.fire({
                title: '¡Eliminada!',
                text: 'La categoría se eliminó correctamente.',
                icon: 'success'
            });

            window.location.reload();

        } catch (error) {
            Swal.fire({
                title: 'No se pudo eliminar',
                text: error.message || 'Ocurrió un error al eliminar.',
                icon: 'error'
            });
        }
    });
}

// Buscador en vivo sobre la tabla
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('buscar');
    const filas = document.querySelectorAll('#tabla-categorias tbody tr.fila');
    const vacio = document.getElementById('sin-resultados');
    if (!input) return;

    input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();
        let visibles = 0;
        filas.forEach(tr => {
            const coincide = tr.dataset.nombre.includes(q);
            tr.style.display = coincide ? '' : 'none';
            if (coincide) visibles++;
        });
        if (vacio) vacio.classList.toggle('d-none', visibles !== 0);
    });
});
