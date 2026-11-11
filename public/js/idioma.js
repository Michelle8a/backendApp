function destroy(button) {
    const url = button.dataset.url;
    const token = button.dataset.token;
    const nombre = button.dataset.nombre || 'este idioma';

    Swal.fire({
        title: '¿Eliminar idioma?',
        text: `Se eliminará "${nombre}". Esta acción no se puede deshacer.`,
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

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.res) {
                throw new Error(data.message || 'No se pudo eliminar el idioma.');
            }

            await Swal.fire({
                title: '¡Eliminado!',
                text: 'El idioma se eliminó correctamente.',
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
