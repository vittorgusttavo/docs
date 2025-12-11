document.addEventListener('DOMContentLoaded', function() {
    const dialog = document.getElementById('dialog-term');
    const abrir = document.getElementById('open-dialog');
    const fechar = document.getElementById('close-dialog');

    abrir.addEventListener('click', e => {
        e.preventDefault();
        dialog.showModal();
    });

    fechar.addEventListener('click', () => dialog.close());
});