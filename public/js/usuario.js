function cambiarEstado(button) {
    const url = button.dataset.url;
    const token = button.dataset.token;
    const nuevo = button.dataset.nuevo;      // 'activo' | 'suspendido'
    const correo = button.dataset.correo;
    const desactivar = nuevo === 'suspendido';

    Swal.fire({
        title: desactivar ? '¿Desactivar usuario?' : '¿Activar usuario?',
        text: desactivar
            ? 'La cuenta de ' + correo + ' quedará desactivada.'
            : 'La cuenta de ' + correo + ' volverá a estar activa.',
        icon: desactivar ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonColor: desactivar ? '#d33' : '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: desactivar ? 'Sí, desactivar' : 'Sí, activar',
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
                body: JSON.stringify({ estado: nuevo })
            });

            const data = await response.json();

            if (!response.ok || !data.res) {
                throw new Error(data.message || 'No se pudo cambiar el estado.');
            }

            await Swal.fire({
                title: desactivar ? '¡Desactivado!' : '¡Activado!',
                text: data.message,
                icon: 'success'
            });

            window.location.reload();

        } catch (error) {
            Swal.fire({
                title: 'Error',
                text: error.message || 'Ocurrió un error al cambiar el estado.',
                icon: 'error'
            });
        }
    });
}
