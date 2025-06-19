// JS para acciones de la tabla "Matches encontrados"
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-delete-match')) {
        const matchId = e.target.dataset.matchId;
        if (!confirm('¿Seguro que deseas eliminar este match?')) return;
        fetch(window.BASE_URL + '/matches/delete/' + matchId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'csrf_token=' + encodeURIComponent(window.csrfToken)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadDirectMatches();
            } else {
                alert(data.message || 'Error al eliminar el match');
            }
        })
        .catch(() => alert('Error de red al eliminar el match'));
    }
    if (e.target.classList.contains('btn-schedule-match')) {
        const matchId = e.target.dataset.matchId;
        // Aquí puedes abrir un modal o redirigir a la página de programación
        window.location.href = window.BASE_URL + '/appointments/schedule/' + matchId;
    }
});
