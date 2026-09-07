/**
 * Nucleo compartilhado da biblioteca de midia: usado tanto pela tela cheia
 * (/admin/media, via MediaLibrary.mountPage) quanto pelo seletor embutido
 * no editor Quill de posts e paginas (via MediaLibrary.open). Reaproveita
 * o endpoint GET /admin/media?ajax=1 (retorna so o HTML da grade+paginacao,
 * a mesma partial usada na pagina cheia) e POST /admin/media/upload.
 *
 * Mensagens usam SweetAlert2 (carregado via CDN nas paginas que incluem
 * este script) com fallback pro alert()/confirm() nativo do browser caso
 * a lib nao tenha carregado por algum motivo.
 */
(function () {
    'use strict';

    var MIN_PROGRESS_VISIBLE_MS = 500;

    function debounce(fn, wait) {
        var timer = null;

        return function () {
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(null, args);
            }, wait);
        };
    }

    function formatSize(bytes) {
        bytes = parseInt(bytes, 10) || 0;

        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(1) + ' MB';
        }

        if (bytes >= 1024) {
            return (bytes / 1024).toFixed(1) + ' KB';
        }

        return bytes + ' B';
    }

    function notifySuccess(title) {
        if (window.Swal) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: title,
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true
            });
            return;
        }

        alert(title);
    }

    function notifyError(title, text) {
        if (window.Swal) {
            Swal.fire({ icon: 'error', title: title, text: text || '' });
            return;
        }

        alert(title + (text ? ': ' + text : ''));
    }

    function confirmDelete(item, onDeleted) {
        function proceed() {
            fetch('/admin/media/' + item.id + '/delete', { method: 'POST' })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('status ' + response.status);
                    }

                    notifySuccess('Arquivo excluído');

                    if (onDeleted) {
                        onDeleted();
                    }
                })
                .catch(function () {
                    notifyError('Falha ao excluir o arquivo');
                });
        }

        if (window.Swal) {
            Swal.fire({
                icon: 'warning',
                title: 'Excluir este arquivo?',
                text: item.name || '',
                showCancelButton: true,
                confirmButtonText: 'Excluir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#b42318'
            }).then(function (result) {
                if (result.isConfirmed) {
                    proceed();
                }
            });
            return;
        }

        if (confirm('Excluir "' + (item.name || 'este arquivo') + '" permanentemente?')) {
            proceed();
        }
    }

    function cardToItem(card) {
        var iconEl = card.querySelector('i');

        return {
            id: card.getAttribute('data-id'),
            url: card.getAttribute('data-url'),
            kind: card.getAttribute('data-kind'),
            name: card.getAttribute('data-name'),
            original_name: card.getAttribute('data-name'),
            title: card.getAttribute('data-title'),
            alt: card.getAttribute('data-alt'),
            alt_text: card.getAttribute('data-alt'),
            mime: card.getAttribute('data-mime'),
            size: card.getAttribute('data-size'),
            created: card.getAttribute('data-created'),
            iconClass: iconEl ? iconEl.className.replace('fa-solid', '').trim() : ''
        };
    }

    function fetchGrid(gridWrap, state) {
        var qs = 'ajax=1'
            + '&q=' + encodeURIComponent(state.q || '')
            + '&kind=' + encodeURIComponent(state.kind || '')
            + '&view=' + encodeURIComponent(state.view || 'grid')
            + '&page=' + encodeURIComponent(state.page || 1);

        fetch('/admin/media?' + qs)
            .then(function (response) { return response.text(); })
            .then(function (html) { gridWrap.innerHTML = html; });
    }

    /**
     * Usa XMLHttpRequest (nao fetch) de proposito: e a unica API do browser
     * com evento de progresso de upload de verdade (xhr.upload.progress),
     * necessaria pra barra de progresso. onProgress e opcional.
     */
    function uploadFiles(fileList, onDone, onProgress) {
        if (!fileList || !fileList.length) {
            return;
        }

        var formData = new FormData();

        for (var i = 0; i < fileList.length; i++) {
            formData.append('files[]', fileList[i]);
        }

        function fail(message) {
            notifyError('Falha ao enviar o(s) arquivo(s)', message);

            if (onDone) {
                onDone({ items: [], errors: [] });
            }
        }

        var xhr = new XMLHttpRequest();

        if (onProgress) {
            xhr.upload.addEventListener('progress', function (event) {
                if (event.lengthComputable) {
                    onProgress(Math.round((event.loaded / event.total) * 100));
                }
            });
        }

        xhr.addEventListener('load', function () {
            if (xhr.status < 200 || xhr.status >= 300) {
                fail('o servidor respondeu com erro (' + xhr.status + ')');
                return;
            }

            var payload;

            try {
                payload = JSON.parse(xhr.responseText);
            } catch (error) {
                // Sem isso, uma resposta inesperada (ex: erro fatal do PHP
                // devolvendo HTML em vez de JSON) ficava muda: nenhum item
                // aparecia e nenhum erro era mostrado, como se nada tivesse
                // acontecido.
                fail('resposta inesperada do servidor');
                return;
            }

            if (payload.errors && payload.errors.length) {
                var names = payload.errors.map(function (item) {
                    return item.name + ': ' + item.error;
                });
                notifyError('Alguns arquivos não foram enviados', names.join('\n'));
            }

            if (payload.items && payload.items.length) {
                notifySuccess(
                    payload.items.length === 1
                        ? 'Arquivo enviado com sucesso'
                        : payload.items.length + ' arquivos enviados com sucesso'
                );
            }

            if (onDone) {
                onDone(payload);
            }
        });

        xhr.addEventListener('error', function () {
            fail('erro de rede');
        });

        xhr.open('POST', '/admin/media/upload');
        xhr.send(formData);
    }

    function showProgress(wrap, bar) {
        if (!wrap || !bar) {
            return null;
        }

        bar.style.width = '0%';
        wrap.classList.remove('d-none');

        return Date.now();
    }

    function updateProgress(bar, percent) {
        if (!bar) {
            return;
        }

        bar.style.width = percent + '%';
    }

    /**
     * Garante que a barra fique visivel por pelo menos
     * MIN_PROGRESS_VISIBLE_MS antes de sumir — uploads de arquivos
     * pequenos terminam rapido demais pra a barra ser percebida, e o
     * usuario nao via nem progresso nem confirmacao nenhuma.
     */
    function hideProgress(wrap, startedAt, callback) {
        if (!wrap) {
            if (callback) {
                callback();
            }
            return;
        }

        var elapsed = startedAt ? Date.now() - startedAt : MIN_PROGRESS_VISIBLE_MS;
        var remaining = Math.max(0, MIN_PROGRESS_VISIBLE_MS - elapsed);

        setTimeout(function () {
            wrap.classList.add('d-none');

            if (callback) {
                callback();
            }
        }, remaining);
    }

    function pageFromLink(link) {
        var url = new URL(link.href, window.location.origin);

        return url.searchParams.get('page') || 1;
    }

    /**
     * Delegação de clique compartilhada pela grade da tela cheia e do
     * modal: ícone de excluir (aparece no hover do card) confirma e
     * apaga; ícone de baixar deixa o navegador seguir o link normalmente,
     * só evita que o clique tambem conte como selecionar o card; clique
     * fora dos ícones cai no handler de card passado por quem chamou;
     * clique na paginação troca de página sem recarregar a tela toda.
     */
    function bindGridClicks(gridWrap, state, refresh, onCardClick) {
        return function (event) {
            var deleteButton = event.target.closest ? event.target.closest('.media-card-action-delete') : null;

            if (deleteButton) {
                event.preventDefault();
                event.stopPropagation();
                confirmDelete(cardToItem(deleteButton.closest('.media-card')), refresh);
                return;
            }

            var otherAction = event.target.closest ? event.target.closest('.media-card-action') : null;

            if (otherAction) {
                event.stopPropagation();
                return;
            }

            var card = event.target.closest ? event.target.closest('.media-card') : null;

            if (card) {
                onCardClick(cardToItem(card), card);
                return;
            }

            var link = event.target.closest ? event.target.closest('.page-num, .page-btn') : null;

            if (link && link.tagName === 'A') {
                event.preventDefault();
                state.page = pageFromLink(link);
                refresh();
            }
        };
    }

    /**
     * A visualizacao (grade/lista/agrupado) preferida do usuario: respeita
     * ?view= na URL quando presente (ex: link de paginacao, favorito) —
     * nesse caso ja bate com o que o servidor renderizou, sem precisar
     * buscar de novo — senao cai pro que ficou salvo no navegador da
     * ultima vez, senao "grid".
     */
    function initialView() {
        var validViews = ['grid', 'list', 'grouped'];
        var urlView = new URLSearchParams(window.location.search).get('view');

        if (validViews.indexOf(urlView) !== -1) {
            return { view: urlView, matchesServerRender: true };
        }

        var stored = null;

        try {
            stored = window.localStorage.getItem('mediaLibraryView');
        } catch (error) {
            stored = null;
        }

        if (validViews.indexOf(stored) !== -1) {
            return { view: stored, matchesServerRender: false };
        }

        return { view: 'grid', matchesServerRender: true };
    }

    function mountPage(opts) {
        var viewInfo = initialView();
        var state = { q: opts.searchInput.value, kind: opts.kindSelect.value, page: 1, view: viewInfo.view };

        function refresh() {
            fetchGrid(opts.gridWrap, state);
        }

        function setActiveViewButton() {
            if (!opts.viewButtons) {
                return;
            }

            opts.viewButtons.forEach(function (button) {
                button.classList.toggle('active', button.getAttribute('data-view') === state.view);
            });
        }

        if (opts.viewButtons) {
            opts.viewButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    state.view = button.getAttribute('data-view');
                    state.page = 1;
                    setActiveViewButton();

                    try {
                        window.localStorage.setItem('mediaLibraryView', state.view);
                    } catch (error) {
                        // localStorage indisponivel (ex: modo privado) — segue sem lembrar a preferencia
                    }

                    refresh();
                });
            });

            setActiveViewButton();
        }

        opts.gridWrap.addEventListener('click', bindGridClicks(opts.gridWrap, state, refresh, opts.onCardClick));

        var filterForm = opts.searchInput.form;

        if (filterForm) {
            filterForm.addEventListener('submit', function (event) {
                event.preventDefault();
                state.q = opts.searchInput.value;
                state.kind = opts.kindSelect.value;
                state.page = 1;
                refresh();
            });
        }

        opts.uploadTrigger.addEventListener('click', function () {
            opts.uploadInput.click();
        });

        opts.uploadInput.addEventListener('change', function () {
            var startedAt = showProgress(opts.progressWrap, opts.progressBar);

            uploadFiles(
                opts.uploadInput.files,
                function () {
                    opts.uploadInput.value = '';
                    hideProgress(opts.progressWrap, startedAt, refresh);
                },
                function (percent) {
                    updateProgress(opts.progressBar, percent);
                }
            );
        });

        window.MediaLibrary.refreshPage = refresh;

        if (!viewInfo.matchesServerRender) {
            refresh();
        }
    }

    function open(onSelect) {
        var modalEl = document.getElementById('media-library-modal');
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        var gridWrap = document.getElementById('media-modal-grid-wrap');
        var searchInput = document.getElementById('media-modal-search');
        var kindSelect = document.getElementById('media-modal-kind');
        var uploadTrigger = document.getElementById('media-modal-upload-trigger');
        var uploadInput = document.getElementById('media-modal-upload-input');
        var progressWrap = document.getElementById('media-modal-upload-progress');
        var progressBar = document.getElementById('media-modal-upload-progress-bar');
        var insertButton = document.getElementById('media-modal-insert');
        var state = { q: '', kind: '', page: 1 };
        var selected = null;

        function refresh() {
            fetchGrid(gridWrap, state);
        }

        function selectCard(item, card) {
            var previous = gridWrap.querySelector('.media-card.is-selected');

            if (previous) {
                previous.classList.remove('is-selected');
            }

            card.classList.add('is-selected');
            selected = item;
            insertButton.disabled = false;
        }

        // Os elementos do modal sao fixos na pagina (a partial e incluida
        // uma vez); usar .onX em vez de addEventListener evita empilhar um
        // handler novo a cada chamada de MediaLibrary.open().
        gridWrap.onclick = bindGridClicks(gridWrap, state, refresh, selectCard);

        searchInput.oninput = debounce(function () {
            state.q = searchInput.value;
            state.page = 1;
            refresh();
        }, 300);

        kindSelect.onchange = function () {
            state.kind = kindSelect.value;
            state.page = 1;
            refresh();
        };

        uploadTrigger.onclick = function () {
            uploadInput.click();
        };

        uploadInput.onchange = function () {
            var startedAt = showProgress(progressWrap, progressBar);

            uploadFiles(
                uploadInput.files,
                function () {
                    uploadInput.value = '';
                    hideProgress(progressWrap, startedAt, refresh);
                },
                function (percent) {
                    updateProgress(progressBar, percent);
                }
            );
        };

        insertButton.onclick = function () {
            if (!selected) {
                return;
            }

            modal.hide();
            onSelect(selected);
        };

        searchInput.value = '';
        kindSelect.value = '';
        state = { q: '', kind: '', page: 1 };
        selected = null;
        insertButton.disabled = true;

        if (progressWrap) {
            progressWrap.classList.add('d-none');
        }

        refresh();
        modal.show();
    }

    window.MediaLibrary = {
        open: open,
        mountPage: mountPage,
        formatSize: formatSize,
        confirmDelete: confirmDelete,
        notifySuccess: notifySuccess,
        notifyError: notifyError
    };
}());
