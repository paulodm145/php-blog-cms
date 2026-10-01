(function () {
    'use strict';

    var SELECTION_STORAGE_KEY = 'resumeDocumentsSelection';

    function loadSelection() {
        try {
            var raw = window.sessionStorage.getItem(SELECTION_STORAGE_KEY);
            return raw ? JSON.parse(raw) : {};
        } catch (error) {
            return {};
        }
    }

    function saveSelection() {
        try {
            window.sessionStorage.setItem(SELECTION_STORAGE_KEY, JSON.stringify(selectedIds));
        } catch (error) {
            // sessionStorage indisponivel (ex.: modo privado) — segue so em memoria pro resto da sessao de navegacao
        }
    }

    var selectedIds = loadSelection();

    // Quando preenchido, "Compartilhar selecionados" vira "Salvar
    // alterações" e grava num link ja existente (compartilhamentos/{id}/
    // editar) em vez de criar um novo (compartilhar).
    var editingShareId = null;
    var editingExpiresAt = null;

    function notifySuccess(title) {
        if (window.Swal) {
            Swal.fire({
                toast: true, position: 'top-end', icon: 'success',
                title: title, showConfirmButton: false, timer: 2500, timerProgressBar: true
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

    function formatSize(bytes) {
        bytes = parseInt(bytes, 10) || 0;
        if (bytes >= 1048576) { return (bytes / 1048576).toFixed(1) + ' MB'; }
        if (bytes >= 1024) { return (bytes / 1024).toFixed(1) + ' KB'; }
        return bytes + ' B';
    }

    function fileIconClass(mimeType) {
        mimeType = mimeType || '';
        if (mimeType.indexOf('image/') === 0) { return 'fa-file-image'; }
        if (mimeType.indexOf('pdf') !== -1) { return 'fa-file-pdf'; }
        if (mimeType.indexOf('msword') !== -1 || mimeType.indexOf('wordprocessingml') !== -1) { return 'fa-file-word'; }
        if (mimeType.indexOf('ms-excel') !== -1 || mimeType.indexOf('spreadsheetml') !== -1) { return 'fa-file-excel'; }
        if (mimeType.indexOf('ms-powerpoint') !== -1 || mimeType.indexOf('presentationml') !== -1) { return 'fa-file-powerpoint'; }
        if (mimeType.indexOf('zip') !== -1) { return 'fa-file-zipper'; }
        if (mimeType.indexOf('text/') === 0) { return 'fa-file-lines'; }
        return 'fa-file';
    }

    function clearSelection() {
        selectedIds = {};
        saveSelection();

        var checked = document.querySelectorAll('[data-document-checkbox]:checked');
        var i;

        for (i = 0; i < checked.length; i++) {
            checked[i].checked = false;
        }

        updateSelectionBar();
    }

    function syncCheckboxes(manager) {
        var checkboxes = manager.querySelectorAll('[data-document-checkbox]');
        var i, li, documentId;

        for (i = 0; i < checkboxes.length; i++) {
            li = checkboxes[i].closest('[data-document-id]');
            documentId = li.getAttribute('data-document-id');
            checkboxes[i].checked = !!selectedIds[documentId];
        }
    }

    function syncAllCheckboxes() {
        var managers = document.querySelectorAll('.document-manager');
        var i;

        for (i = 0; i < managers.length; i++) {
            syncCheckboxes(managers[i]);
        }
    }

    function setEditModeUi(isEditing) {
        var trigger = document.getElementById('documents-share-trigger');
        var cancelButton = document.getElementById('documents-cancel-edit');
        var indicator = document.getElementById('documents-editing-indicator');

        if (trigger) { trigger.textContent = isEditing ? 'Salvar alterações do link' : 'Compartilhar selecionados'; }
        if (cancelButton) { cancelButton.classList.toggle('d-none', !isEditing); }
        if (indicator) { indicator.classList.toggle('d-none', !isEditing); }
    }

    function enterEditMode(shareId, documentIdsCsv, expiresAt) {
        selectedIds = {};

        (documentIdsCsv || '').split(',').forEach(function (id) {
            if (id !== '') { selectedIds[id] = true; }
        });

        saveSelection();
        syncAllCheckboxes();
        updateSelectionBar();

        editingShareId = shareId;
        editingExpiresAt = expiresAt;
        setEditModeUi(true);
    }

    function exitEditMode() {
        editingShareId = null;
        editingExpiresAt = null;
        setEditModeUi(false);
        clearSelection();
    }

    function updateSelectionBar() {
        var countEl = document.getElementById('documents-selected-count');
        var button = document.getElementById('documents-share-trigger');
        var count = Object.keys(selectedIds).length;

        if (countEl) { countEl.textContent = String(count); }
        if (button) { button.disabled = count === 0; }
    }

    function buildDocumentItem(item, selectable) {
        var li = document.createElement('li');
        li.className = 'list-group-item d-flex align-items-center gap-2';
        li.setAttribute('data-document-id', item.id);
        li.innerHTML =
            (selectable ? '<input class="form-check-input flex-shrink-0" type="checkbox" data-document-checkbox>' : '') +
            '<i class="fa-solid ' + fileIconClass(item.mime_type) + ' flex-shrink-0"></i>' +
            '<div class="flex-grow-1 min-w-0">' +
                '<div class="text-truncate"></div>' +
                '<input class="form-control form-control-sm border-0 bg-transparent px-0 document-caption-input" type="text" placeholder="Adicionar legenda…" value="" data-caption-input>' +
            '</div>' +
            '<span class="text-secondary small flex-shrink-0">' + formatSize(item.size) + '</span>' +
            '<a class="admin-action-link flex-shrink-0" href="/admin/curriculo/documentos/' + item.id + '/download" title="Baixar"><i class="fa-solid fa-download"></i></a>' +
            '<a class="admin-action-link flex-shrink-0" href="/admin/curriculo/documentos/' + item.id + '/mover?return=' + encodeURIComponent(window.location.pathname + window.location.search) + '" title="Mover"><i class="fa-solid fa-arrows-up-down-left-right"></i></a>' +
            '<button class="admin-action-link admin-action-danger flex-shrink-0" type="button" data-delete-document title="Excluir"><i class="fa-solid fa-trash"></i></button>';
        li.querySelector('.text-truncate').textContent = item.original_name;

        return li;
    }

    function refreshEmptyState(manager) {
        var list = manager.querySelector('[data-document-list]');
        var emptyMessage = manager.querySelector('.document-empty-message');

        if (!list || !emptyMessage) { return; }

        emptyMessage.classList.toggle('d-none', list.children.length > 0);
    }

    function bumpBadge(manager, delta) {
        var accordionItem = manager.closest('.accordion-item');
        if (!accordionItem) { return; }
        var badge = accordionItem.querySelector('.badge');
        if (!badge) { return; }
        badge.textContent = String((parseInt(badge.textContent, 10) || 0) + delta);
    }

    function uploadFiles(manager, fileList) {
        var selectable = manager.getAttribute('data-selectable') !== 'false';
        var progressWrap = manager.querySelector('[data-upload-progress-wrap]');
        var progressBar = manager.querySelector('[data-upload-progress-bar]');
        var list = manager.querySelector('[data-document-list]');
        var emptyMessage = manager.querySelector('.document-empty-message');
        var formData = new FormData();
        var i;

        if (manager.hasAttribute('data-folder-id')) {
            formData.append('folder_id', manager.getAttribute('data-folder-id'));
        } else {
            formData.append('experience_id', manager.getAttribute('data-experience-id'));
        }

        for (i = 0; i < fileList.length; i++) {
            formData.append('files[]', fileList[i]);
        }

        if (progressWrap) { progressWrap.classList.remove('d-none'); }
        if (progressBar) { progressBar.style.width = '0%'; }

        var xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', function (event) {
            if (event.lengthComputable && progressBar) {
                progressBar.style.width = Math.round((event.loaded / event.total) * 100) + '%';
            }
        });

        xhr.addEventListener('load', function () {
            if (progressWrap) { progressWrap.classList.add('d-none'); }

            var payload;

            try {
                payload = JSON.parse(xhr.responseText);
            } catch (error) {
                notifyError('Falha ao enviar o(s) arquivo(s)', 'resposta inesperada do servidor');
                return;
            }

            if (payload.errors && payload.errors.length) {
                var names = payload.errors.map(function (error) { return error.name + ': ' + error.error; });
                notifyError('Alguns arquivos não foram enviados', names.join('\n'));
            }

            if (payload.items && payload.items.length) {
                if (emptyMessage) { emptyMessage.classList.add('d-none'); }
                payload.items.forEach(function (item) {
                    if (list) { list.appendChild(buildDocumentItem(item, selectable)); }
                });
                bumpBadge(manager, payload.items.length);
                notifySuccess(payload.items.length === 1 ? 'Documento enviado' : payload.items.length + ' documentos enviados');
            }
        });

        xhr.addEventListener('error', function () {
            if (progressWrap) { progressWrap.classList.add('d-none'); }
            notifyError('Falha ao enviar o(s) arquivo(s)', 'erro de rede');
        });

        xhr.open('POST', '/admin/curriculo/documentos/upload');
        xhr.send(formData);
    }

    // fetch() resolve normalmente pra qualquer status HTTP e segue redirect
    // sem avisar — uma sessao expirada faz Auth::requireAdmin() redirecionar
    // pra /admin/login, que responde 200 com HTML. Sem essa checagem, esse
    // HTML de login vira uma "resposta de sucesso" e a UI mente pro usuario
    // que a acao deu certo quando na verdade nada foi salvo/excluido no
    // servidor. response.redirected cobre o caso do redirect; !response.ok
    // cobre um erro HTTP direto (404/500); e so entao tenta interpretar o
    // corpo como o JSON que os endpoints desta tela sempre devolvem.
    function parseApiResponse(response) {
        if (!response.ok || response.redirected) {
            throw new Error('sessão expirada ou falha no servidor');
        }

        return response.json().then(function (payload) {
            if (!payload || payload.ok !== true) {
                throw new Error('resposta inesperada do servidor');
            }

            return payload;
        });
    }

    function saveCaption(documentId, caption) {
        var formData = new FormData();
        formData.append('caption', caption);

        fetch('/admin/curriculo/documentos/' + documentId, { method: 'POST', body: formData })
            .then(parseApiResponse)
            .catch(function () {
                notifyError('Falha ao salvar a legenda', 'sua sessão pode ter expirado — recarregue a página');
            });
    }

    function deleteDocument(manager, li) {
        var documentId = li.getAttribute('data-document-id');

        function proceed() {
            fetch('/admin/curriculo/documentos/' + documentId + '/delete', { method: 'POST' })
                .then(parseApiResponse)
                .then(function () {
                    delete selectedIds[documentId];
                    saveSelection();
                    updateSelectionBar();
                    li.remove();
                    bumpBadge(manager, -1);
                    refreshEmptyState(manager);
                    notifySuccess('Documento excluído');
                })
                .catch(function () {
                    notifyError('Falha ao excluir o documento', 'sua sessão pode ter expirado — recarregue a página');
                });
        }

        if (window.Swal) {
            Swal.fire({
                icon: 'warning', title: 'Excluir este documento?', showCancelButton: true,
                confirmButtonText: 'Excluir', cancelButtonText: 'Cancelar', confirmButtonColor: '#b42318'
            }).then(function (result) {
                if (result.isConfirmed) { proceed(); }
            });
            return;
        }

        if (confirm('Excluir este documento permanentemente?')) { proceed(); }
    }

    function wireManager(manager) {
        var dropzone = manager.querySelector('[data-dropzone]');
        var input = manager.querySelector('[data-upload-input]');
        var trigger = manager.querySelector('[data-upload-trigger]');
        var list = manager.querySelector('[data-document-list]');

        if (trigger && input) {
            trigger.addEventListener('click', function () { input.click(); });
        }

        if (input) {
            input.addEventListener('change', function () {
                if (input.files.length) { uploadFiles(manager, input.files); }
                input.value = '';
            });
        }

        if (dropzone) {
            ['dragover', 'dragenter'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    dropzone.classList.add('border-primary');
                });
            });

            ['dragleave', 'drop'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    dropzone.classList.remove('border-primary');
                });
            });

            dropzone.addEventListener('drop', function (event) {
                if (event.dataTransfer && event.dataTransfer.files.length) {
                    uploadFiles(manager, event.dataTransfer.files);
                }
            });
        }

        if (list) {
            list.addEventListener('change', function (event) {
                var checkbox = event.target.closest ? event.target.closest('[data-document-checkbox]') : null;
                if (!checkbox) { return; }

                var li = checkbox.closest('[data-document-id]');
                var documentId = li.getAttribute('data-document-id');

                if (checkbox.checked) {
                    selectedIds[documentId] = true;
                } else {
                    delete selectedIds[documentId];
                }

                saveSelection();
                updateSelectionBar();
            });

            // focusout (nao "blur") porque precisa propagar ate o <ul> pra
            // funcionar com delegacao — blur nao borbulha (bubbles) no DOM.
            list.addEventListener('focusout', function (event) {
                var captionInput = event.target.closest ? event.target.closest('[data-caption-input]') : null;
                if (!captionInput) { return; }

                var li = captionInput.closest('[data-document-id]');
                saveCaption(li.getAttribute('data-document-id'), captionInput.value.trim());
            });

            list.addEventListener('click', function (event) {
                var deleteButton = event.target.closest ? event.target.closest('[data-delete-document]') : null;
                if (!deleteButton) { return; }

                deleteDocument(manager, deleteButton.closest('[data-document-id]'));
            });
        }
    }

    function formatDateTime(value) {
        // value vem do servidor como "Y-m-d H:i:s" — parse manual (nao
        // `new Date(value)`) pra nao depender de como cada engine de
        // browser interpreta uma string sem fuso explicito.
        var match = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/.exec(value || '');

        if (!match) { return value; }

        return match[3] + '/' + match[2] + '/' + match[1] + ' ' + match[4] + ':' + match[5];
    }

    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                notifySuccess('Link copiado');
            });
            return;
        }

        // Fallback pra navegador sem Clipboard API (ou pagina nao-https):
        // cria um input temporario so pra dar select()+execCommand('copy').
        var temp = document.createElement('input');
        temp.value = text;
        temp.style.position = 'fixed';
        temp.style.opacity = '0';
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        notifySuccess('Link copiado');
    }

    function computeExpiresAt() {
        var preset = document.getElementById('share-expires-preset');
        var custom = document.getElementById('share-expires-custom');

        if (!preset) { return null; }

        if (preset.value === 'custom') {
            return custom && custom.value ? custom.value : null;
        }

        var days = parseInt(preset.value, 10);
        var date = new Date(Date.now() + days * 86400000);

        return date.toISOString();
    }

    function wireShareModal() {
        var trigger = document.getElementById('documents-share-trigger');
        var clearButton = document.getElementById('documents-clear-selection');

        if (clearButton) {
            // Escape hatch pra sessionStorage ficar com IDs de documentos que
            // sumiram (excluidos numa pasta ja recarregada, por exemplo):
            // sem isso, o contador ficava preso num numero > 0 sem nenhum
            // checkbox marcado pra desmarcar, e toda tentativa de compartilhar
            // batia em "documentos invalidos" pro resto da aba do navegador.
            clearButton.addEventListener('click', function () {
                clearSelection();
            });
        }

        var cancelEditButton = document.getElementById('documents-cancel-edit');

        if (cancelEditButton) {
            cancelEditButton.addEventListener('click', function () {
                exitEditMode();
            });
        }

        var sharesWrap = document.getElementById('shares-table-wrap');

        function refreshSharesTable() {
            if (!sharesWrap) { return; }

            fetch('/admin/curriculo/documentos/compartilhamentos')
                .then(function (response) { return response.text(); })
                .then(function (html) { sharesWrap.innerHTML = html; });
        }

        // Delegacao no container, nao nos botoes: a tabela inteira e
        // substituida (innerHTML) a cada criacao/edicao de link, entao um
        // listener preso num botao especifico sumiria no refresh seguinte.
        if (sharesWrap) {
            sharesWrap.addEventListener('click', function (event) {
                var copyTarget = event.target.closest ? event.target.closest('[data-copy-share-url]') : null;

                if (copyTarget) {
                    copyToClipboard(copyTarget.getAttribute('data-copy-share-url'));
                    return;
                }

                var editTarget = event.target.closest ? event.target.closest('[data-edit-share]') : null;

                if (editTarget) {
                    enterEditMode(
                        editTarget.getAttribute('data-share-id'),
                        editTarget.getAttribute('data-document-ids'),
                        editTarget.getAttribute('data-expires-at')
                    );
                    notifySuccess('Documentos deste link marcados — ajuste a seleção e clique em "Salvar alterações do link"');
                }
            });
        }

        if (!trigger) { return; }

        var preset = document.getElementById('share-expires-preset');
        var custom = document.getElementById('share-expires-custom');
        var generateButton = document.getElementById('share-generate-btn');
        var form = document.getElementById('share-modal-form');
        var result = document.getElementById('share-modal-result');
        var resultUrl = document.getElementById('share-result-url');
        var resultExpiry = document.getElementById('share-result-expiry');
        var copyButton = document.getElementById('share-copy-btn');
        var modalTitle = document.getElementById('share-modal-title');
        var modalEl = document.getElementById('share-modal');
        var modal = window.bootstrap ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;

        trigger.addEventListener('click', function () {
            if (form) { form.classList.remove('d-none'); }
            if (result) { result.classList.add('d-none'); }
            if (modalTitle) { modalTitle.textContent = editingShareId ? 'Editar link compartilhado' : 'Compartilhar documentos'; }

            // Pre-preenche com a expiracao atual do link, em "Personalizado",
            // em vez de deixar o preset padrao de 7 dias silenciosamente
            // sobrescrever uma validade que o admin nao pediu pra mudar.
            if (editingShareId && preset && custom && editingExpiresAt) {
                preset.value = 'custom';
                custom.classList.remove('d-none');
                custom.value = editingExpiresAt.replace(' ', 'T').slice(0, 16);
            }

            if (generateButton) { generateButton.textContent = editingShareId ? 'Salvar' : 'Gerar link'; }

            if (modal) { modal.show(); }
        });

        if (preset) {
            preset.addEventListener('change', function () {
                if (custom) { custom.classList.toggle('d-none', preset.value !== 'custom'); }
            });
        }

        if (generateButton) {
            generateButton.addEventListener('click', function () {
                var expiresAt = computeExpiresAt();

                if (!expiresAt) {
                    notifyError('Escolha uma data de expiração válida');
                    return;
                }

                var formData = new FormData();
                Object.keys(selectedIds).forEach(function (id) {
                    formData.append('document_ids[]', id);
                });
                formData.append('expires_at', expiresAt);

                var wasEditing = editingShareId;
                var endpoint = wasEditing
                    ? '/admin/curriculo/documentos/compartilhamentos/' + wasEditing + '/editar'
                    : '/admin/curriculo/documentos/compartilhar';

                fetch(endpoint, { method: 'POST', body: formData })
                    .then(function (response) { return response.json(); })
                    .then(function (payload) {
                        if (payload.error) {
                            notifyError(payload.error);
                            return;
                        }

                        if (resultUrl) { resultUrl.value = payload.url; }
                        if (resultExpiry) { resultExpiry.textContent = formatDateTime(payload.expires_at); }
                        if (form) { form.classList.add('d-none'); }
                        if (result) { result.classList.remove('d-none'); }

                        if (wasEditing) {
                            // exitEditMode() ja chama clearSelection() — nao
                            // precisa fazer os dois.
                            exitEditMode();
                        } else {
                            // Limpa a selecao: sem isso, um segundo clique em
                            // "Compartilhar" (sem querer, ou achando que o
                            // primeiro link nao tinha saido) gerava um SEGUNDO
                            // link ativo sobre os mesmos documentos.
                            clearSelection();
                        }

                        refreshSharesTable();
                    })
                    .catch(function () {
                        notifyError(wasEditing ? 'Falha ao salvar as alterações' : 'Falha ao gerar o link');
                    });
            });
        }

        if (copyButton) {
            copyButton.addEventListener('click', function () {
                if (!resultUrl) { return; }

                copyToClipboard(resultUrl.value);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var managers = document.querySelectorAll('.document-manager');
        var i;

        for (i = 0; i < managers.length; i++) {
            wireManager(managers[i]);
            syncCheckboxes(managers[i]);
        }

        updateSelectionBar();
        wireShareModal();
    });
}());
