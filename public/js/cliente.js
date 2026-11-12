function destroy(button) {
    const url = button.dataset.url;
    const token = button.dataset.token;
    const pedidos = parseInt(button.dataset.pedidos || '0');

    const texto = pedidos > 0
        ? `Se eliminarán también sus ${pedidos} pedido(s). Esta acción no se puede deshacer.`
        : 'Esta acción no se puede deshacer.';

    Swal.fire({
        title: '¿Eliminar cliente?',
        text: texto,
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
                throw new Error(data.message || 'No se pudo eliminar el cliente.');
            }

            await Swal.fire({
                title: '¡Eliminado!',
                text: 'El cliente se eliminó correctamente.',
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