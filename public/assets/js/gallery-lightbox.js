// Lightbox generico pra grades de galeria na pagina publica — reusado
// tanto pela galeria de screenshots de projeto (project_images) quanto
// por quantas galerias [@slug@] estiverem embutidas no conteudo (posts e
// projetos), foto ou video. Cada grade tem um data-gallery diferente (o
// slug, ou "project-{id}" pra galeria de projeto); ao clicar numa
// miniatura, o grupo de navegacao (anterior/proximo/contador) e
// recalculado na hora filtrando so as miniaturas com o MESMO
// data-gallery — miniaturas de grupos diferentes na mesma pagina nunca
// se misturam.
(function () {
    var thumbs = document.querySelectorAll('.gallery-grid-thumb');

    if (thumbs.length === 0) {
        return;
    }

    var modalEl = document.getElementById('gallery-lightbox');

    if (!modalEl) {
        return;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var image = document.getElementById('lightbox-image');
    var video = document.getElementById('lightbox-video');
    var counter = document.getElementById('lightbox-counter');
    var currentGroup = [];
    var currentIndex = 0;

    function show(index) {
        currentIndex = (index + currentGroup.length) % currentGroup.length;
        var item = currentGroup[currentIndex];

        if (item.type === 'video') {
            // Limpa o src da imagem antes de trocar — senao ela fica
            // guardada escondida (sem efeito pratico, mas evita um
            // download desnecessario da foto anterior).
            image.src = '';
            image.classList.add('d-none');
            video.src = item.url;
            video.classList.remove('d-none');
        } else {
            // Limpar o src do iframe ao sair de um video e o que
            // realmente para a reproducao — só escondê-lo (d-none)
            // deixa o video tocando invisível atrás do modal.
            video.src = '';
            video.classList.add('d-none');
            image.src = item.url;
            image.classList.remove('d-none');
        }

        counter.textContent = (currentIndex + 1) + ' / ' + currentGroup.length;
    }

    thumbs.forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            var groupKey = thumb.getAttribute('data-gallery');
            var groupThumbs = Array.prototype.filter.call(thumbs, function (t) {
                return t.getAttribute('data-gallery') === groupKey;
            });
            currentGroup = groupThumbs.map(function (t) {
                return { url: t.getAttribute('data-url'), type: t.getAttribute('data-type') || 'image' };
            });
            show(groupThumbs.indexOf(thumb));
            modal.show();
        });
    });

    document.getElementById('lightbox-prev').addEventListener('click', function () {
        show(currentIndex - 1);
    });

    document.getElementById('lightbox-next').addEventListener('click', function () {
        show(currentIndex + 1);
    });

    document.addEventListener('keydown', function (event) {
        if (!modalEl.classList.contains('show')) {
            return;
        }

        if (event.key === 'ArrowLeft') {
            show(currentIndex - 1);
        } else if (event.key === 'ArrowRight') {
            show(currentIndex + 1);
        }
    });

    // Trocar de item ja limpa o iframe (show() acima), mas fechar o
    // modal com um video tocando tambem precisa parar — dismiss por
    // ESC/fora do modal nao passa por nenhum dos dois botoes prev/next.
    modalEl.addEventListener('hidden.bs.modal', function () {
        video.src = '';
        video.classList.add('d-none');
    });
})();
