function destroy(button) {
    const url = button.dataset.url;
    const token = button.dataset.token;
    const nombre = button.dataset.nombre || 'este médico';

    Swal.fire({
        title: '¿Eliminar médico?',
        text: 'Se eliminará el perfil de ' + nombre + '. Esta acción no se puede deshacer.',
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
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
            });

            const data = await response.json();

            if (!response.ok || !data.res) {
                throw new Error(data.message || 'No se pudo eliminar el médico.');
            }

            await Swal.fire({ title: '¡Eliminado!', text: 'El médico se eliminó correctamente.', icon: 'success' });
            window.location.reload();

        } catch (error) {
            Swal.fire({ title: 'Error', text: error.message || 'Ocurrió un error al eliminar.', icon: 'error' });
        }
    });
}

async function toggleVerificado(input) {
    const quedoMarcado = input.checked;
    input.disabled = true;

    try {
        const response = await fetch(input.dataset.url, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': input.dataset.token, 'Accept': 'application/json' }
        });
        const data = await response.json();

        if (!response.ok || !data.res) {
            throw new Error(data.message || 'No se pudo cambiar la verificación.');
        }

        input.checked = data.verificado;
    } catch (error) {
        input.checked = !quedoMarcado; // revierte el interruptor
        Swal.fire({ title: 'Error', text: error.message, icon: 'error' });
    } finally {
        input.disabled = false;
    }
}
