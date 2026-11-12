function destroy(button) {
    const url = button.dataset.url;
    const token = button.dataset.token;
    const nombre = button.dataset.nombre || 'este centro de salud';

    Swal.fire({
        title: '¿Eliminar centro de salud?',
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
                throw new Error(data.message || 'No se pudo eliminar el centro de salud.');
            }

            await Swal.fire({
                title: '¡Eliminado!',
                text: 'El centro de salud se eliminó correctamente.',
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
