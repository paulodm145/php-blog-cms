// Modal leve pra escolher uma galeria ja cadastrada e inserir o codigo
// [@slug@] certinho no editor, sem precisar digitar (e sem risco de
// digitar o slug errado). Mesmo espirito de MediaLibrary.open(), so que
// busca a lista via GET /admin/galerias/picker (fragmento HTML pronto)
// em vez de imagens.
window.GalleryPicker = {
    open: function (onSelect) {
        var modalEl = document.getElementById('gallery-picker-modal');
        var listEl = document.getElementById('gallery-picker-list');
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        listEl.innerHTML = '<p class="text-secondary mb-0">Carregando...</p>';

        fetch('/admin/galerias/picker')
            .then(function (response) {
                return response.text();
            })
            .then(function (html) {
                listEl.innerHTML = html;
                listEl.querySelectorAll('.gallery-picker-item').forEach(function (item) {
                    item.addEventListener('click', function () {
                        onSelect(item.getAttribute('data-slug'));
                        modal.hide();
                    });
                });
            })
            .catch(function () {
                listEl.innerHTML = '<p class="text-danger mb-0">Não foi possível carregar as galerias.</p>';
            });

        modal.show();
    }
};
