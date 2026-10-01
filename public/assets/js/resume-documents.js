(function () {
    'use strict';

    var SELECTION_STORAGE_KEY = 'resumeDocumentsSelection';
    var FOLDER_SELECTION_STORAGE_KEY = 'resumeDocumentsFolderSelection';

    function loadStoredMap(key) {
        try {
            var raw = window.sessionStorage.getItem(key);
            return raw ? JSON.parse(raw) : {};
        } catch (error) {
            return {};
        }
    }

    function saveStoredMap(key, map) {
        try {
            window.sessionStorage.setItem(key, JSON.stringify(map));
        } catch (error) {
            // sessionStorage indisponivel (ex.: modo privado) — segue so em memoria pro resto da sessao de navegacao
        }
    }

    function loadSelection() { return loadStoredMap(SELECTION_STORAGE_KEY); }
    function saveSelection() { saveStoredMap(SELECTION_STORAGE_KEY, selectedIds); }
    function saveFolderSelection() { saveStoredMap(FOLDER_SELECTION_STORAGE_KEY, selectedFolderIds); }

    var selectedIds = loadSelection();
    // Selecao de pastas inteiras (com tudo dentro, recursivamente) pro
    // download em ZIP — paralela a selectedIds (documentos), que tambem
    // serve o compartilhamento. Pastas nao entram no compartilhamento.
    var selectedFolderIds = loadStoredMap(FOLDER_SELECTION_STORAGE_KEY);

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
        selectedFolderIds = {};
        saveSelection();
        saveFolderSelection();

        // Zera tambem o estado "herdado" (checked+disabled) de uma pasta-mae
        // que tenha sido desmarcada por tabela — sem isso um checkbox
        // continuaria preso em disabled mesmo com a selecao ja vazia.
        var checkboxes = document.querySelectorAll('[data-document-checkbox], [data-folder-checkbox]');
        var i;

        for (i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = false;
            checkboxes[i].disabled = false;
        }

        var selectAllVisible = document.getElementById('documents-select-all-visible');
        if (selectAllVisible) { selectAllVisible.checked = false; }

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

    function syncFolderCheckboxes() {
        var checkboxes = document.querySelectorAll('[data-folder-checkbox]');
        var i, card, folderId;

        for (i = 0; i < checkboxes.length; i++) {
            card = checkboxes[i].closest('[data-draggable-folder-id]');
            if (!card) { continue; }

            folderId = card.getAttribute('data-draggable-folder-id');
            checkboxes[i].checked = !!selectedFolderIds[folderId];
        }
    }

    /**
     * Se a pasta sendo exibida (ou uma pasta-mae dela) foi marcada pra ZIP
     * na tela anterior, tudo visivel aqui dentro (subpastas e documentos) ja
     * vai junto no zip de qualquer jeito — entao aparece marcado e travado,
     * em vez de deixar parecer que precisa marcar de novo item por item.
     */
    function applyInheritedFolderSelection() {
        var container = document.querySelector('[data-folder-chain]');
        if (!container) { return; }

        var chain = container.getAttribute('data-folder-chain').split(',').filter(function (id) { return id !== ''; });
        var inherited = chain.some(function (id) { return !!selectedFolderIds[id]; });

        if (!inherited) { return; }

        var checkboxes = container.querySelectorAll('[data-folder-checkbox], [data-document-checkbox]');
        var i;

        for (i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = true;
            checkboxes[i].disabled = true;
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
        var shareButton = document.getElementById('documents-share-trigger');
        var zipButton = document.getElementById('documents-zip-trigger');
        var deleteButton = document.getElementById('documents-delete-selected-trigger');
        var documentCount = Object.keys(selectedIds).length;
        var folderCount = Object.keys(selectedFolderIds).length;

        if (countEl) {
            countEl.textContent = folderCount > 0
                ? documentCount + ' documento(s), ' + folderCount + ' pasta(s) selecionado(s)'
                : documentCount + ' selecionado(s)';
        }

        // Pastas nao entram no link compartilhado (so no ZIP e na exclusao
        // em massa) — por isso o botao de compartilhar so olha pra documentCount.
        if (shareButton) { shareButton.disabled = documentCount === 0; }
        if (zipButton) { zipButton.disabled = (documentCount + folderCount) === 0; }
        if (deleteButton) { deleteButton.disabled = (documentCount + folderCount) === 0; }
    }

    function isElementVisible(el) {
        return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
    }

    /**
     * "Visiveis" de proposito (nao "todo documento/pasta que existe na
     * pagina"): aba inativa (Bootstrap tabs) e painel de accordion fechado
     * continuam no DOM só com display:none, e offsetWidth/offsetHeight/
     * getClientRects() e a forma padrao de distinguir isso sem precisar
     * checar classe por classe (d-none, collapse, tab-pane) uma por uma.
     */
    function wireSelectAllVisible() {
        var selectAll = document.getElementById('documents-select-all-visible');
        if (!selectAll) { return; }

        selectAll.addEventListener('change', function () {
            var checked = selectAll.checked;
            var boxes = document.querySelectorAll('[data-document-checkbox]:not(:disabled), [data-folder-checkbox]:not(:disabled)');
            var i;

            for (i = 0; i < boxes.length; i++) {
                if (!isElementVisible(boxes[i]) || boxes[i].checked === checked) { continue; }

                boxes[i].checked = checked;
                // Dispara o "change" que ja esta cablado em cada checkbox
                // (delegado no <ul> pros documentos, direto pros de pasta)
                // em vez de duplicar a logica de atualizar selectedIds/
                // selectedFolderIds aqui.
                boxes[i].dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    function confirmBulkDelete(onConfirmed) {
        var message = 'Excluir os itens selecionados? Pastas são excluídas com tudo dentro.';

        if (window.Swal) {
            Swal.fire({
                icon: 'warning', title: message, showCancelButton: true,
                confirmButtonText: 'Excluir', cancelButtonText: 'Cancelar', confirmButtonColor: '#b42318'
            }).then(function (result) {
                if (result.isConfirmed) { onConfirmed(); }
            });
            return;
        }

        if (confirm(message)) { onConfirmed(); }
    }

    function wireBulkDelete() {
        var deleteButton = document.getElementById('documents-delete-selected-trigger');
        if (!deleteButton) { return; }

        deleteButton.addEventListener('click', function () {
            confirmBulkDelete(function () {
                var form = document.createElement('form');
                form.method = 'post';
                form.action = '/admin/curriculo/documentos/excluir-selecionados';
                form.style.display = 'none';

                function appendHidden(name, value) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    form.appendChild(input);
                }

                Object.keys(selectedIds).forEach(function (id) { appendHidden('document_ids[]', id); });
                Object.keys(selectedFolderIds).forEach(function (id) { appendHidden('folder_ids[]', id); });
                appendHidden('return', window.location.pathname + window.location.search);

                // A pagina vai recarregar via navegacao normal do form —
                // limpa aqui mesmo, sem esperar resposta, senao o contador
                // ficaria preso com IDs de itens que acabaram de sumir.
                selectedIds = {};
                selectedFolderIds = {};
                saveSelection();
                saveFolderSelection();

                document.body.appendChild(form);
                form.submit();
            });
        });
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
            (item.mime_type === 'application/pdf'
                ? '<a class="admin-action-link flex-shrink-0" href="/admin/curriculo/documentos/' + item.id + '/visualizar" target="_blank" rel="noopener" title="Visualizar"><i class="fa-solid fa-eye"></i></a>'
                : '') +
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

    /**
     * Arrasta um card de subpasta (area central) e solta sobre um item da
     * arvore (painel lateral) — reaproveita o mesmo endpoint de "Mover"
     * que o link/form ja usa, so que via fetch em vez de navegacao. Como
     * o endpoint so sabe responder com um redirect (302), a forma mais
     * simples de saber se deu certo sem mudar o backend e olhar
     * response.url depois do fetch seguir o redirect sozinho: se ele
     * carrega "erro=" (ciclo detectado, destino sumiu nesse meio tempo
     * etc.), mostra o erro; senao, navega pra onde o redirect mandou.
     */
    function moveFolderViaDrag(folderId, targetFolderId) {
        var formData = new FormData();
        formData.append('destination', 'folder:' + (targetFolderId || ''));

        fetch('/admin/curriculo/documentos/pastas/' + folderId + '/mover', { method: 'POST', body: formData })
            .then(function (response) {
                var finalUrl = response.url;
                var match = /[?&]erro=([^&]*)/.exec(finalUrl);

                if (match) {
                    notifyError(decodeURIComponent(match[1].replace(/\+/g, ' ')));
                    return;
                }

                window.location.href = finalUrl;
            })
            .catch(function () {
                notifyError('Falha ao mover a pasta');
            });
    }

    function renameFolderViaFetch(folderId, name) {
        var formData = new FormData();
        formData.append('name', name);

        return fetch('/admin/curriculo/documentos/pastas/' + folderId + '/renomear', { method: 'POST', body: formData })
            .then(function (response) {
                // Mesmo truque do moveFolderViaDrag: o endpoint so responde
                // com redirect, entao um "erro=" na URL final (apos o fetch
                // seguir o redirect sozinho) e a unica forma de saber que a
                // validacao falhou sem mudar o backend.
                var match = /[?&]erro=([^&]*)/.exec(response.url);

                if (match) {
                    throw new Error(decodeURIComponent(match[1].replace(/\+/g, ' ')));
                }
            });
    }

    /**
     * Troca o nome exibido (dentro de [data-folder-link]) por um <input>
     * editavel no proprio lugar, sem navegar pra pagina de renomear. O link
     * so fica escondido (d-none), nunca removido, pra nao perder nenhum
     * outro estado do card/linha enquanto edita.
     */
    function startInlineRename(folderId, container) {
        var link = container.querySelector('[data-folder-link]');
        var nameEl = container.querySelector('[data-folder-name-text]');

        if (!link || !nameEl) { return; }

        var currentName = nameEl.textContent.trim();
        var settled = false;

        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control form-control-sm d-inline-block';
        input.style.width = 'auto';
        input.value = currentName;

        link.classList.add('d-none');
        link.insertAdjacentElement('afterend', input);
        input.focus();
        input.select();

        function cleanup() {
            input.remove();
            link.classList.remove('d-none');
        }

        function finish() {
            if (settled) { return; }
            settled = true;

            var newName = input.value.trim();

            if (newName === '' || newName === currentName) {
                cleanup();
                return;
            }

            renameFolderViaFetch(folderId, newName)
                .then(function () {
                    nameEl.textContent = newName;
                    cleanup();
                    notifySuccess('Pasta renomeada');
                })
                .catch(function (error) {
                    notifyError('Falha ao renomear a pasta', error.message);
                    cleanup();
                });
        }

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') { event.preventDefault(); finish(); }

            if (event.key === 'Escape') {
                event.preventDefault();
                settled = true;
                cleanup();
            }
        });
        input.addEventListener('blur', finish);
    }

    function confirmDeleteFolder(onConfirmed) {
        if (window.Swal) {
            Swal.fire({
                icon: 'warning', title: 'Excluir esta pasta e tudo dentro dela?', showCancelButton: true,
                confirmButtonText: 'Excluir', cancelButtonText: 'Cancelar', confirmButtonColor: '#b42318'
            }).then(function (result) {
                if (result.isConfirmed) { onConfirmed(); }
            });
            return;
        }

        if (confirm('Excluir esta pasta e tudo dentro dela?')) { onConfirmed(); }
    }

    function wireFolderCardActions() {
        var cards = document.querySelectorAll('[data-draggable-folder-id]');
        var i;

        for (i = 0; i < cards.length; i++) {
            (function (card) {
                var folderId = card.getAttribute('data-draggable-folder-id');
                var renameButton = card.querySelector('[data-rename-folder]');
                var deleteButton = card.querySelector('[data-delete-folder]');
                var checkbox = card.querySelector('[data-folder-checkbox]');

                if (renameButton) {
                    renameButton.addEventListener('click', function () {
                        startInlineRename(folderId, card);
                    });
                }

                if (deleteButton) {
                    deleteButton.addEventListener('click', function () {
                        confirmDeleteFolder(function () {
                            deleteButton.closest('form').submit();
                        });
                    });
                }

                if (checkbox) {
                    checkbox.addEventListener('change', function () {
                        if (checkbox.checked) {
                            selectedFolderIds[folderId] = true;
                        } else {
                            delete selectedFolderIds[folderId];
                        }

                        saveFolderSelection();
                        updateSelectionBar();
                    });
                }
            }(cards[i]));
        }
    }

    function wireFolderDragAndDrop() {
        var cards = document.querySelectorAll('[data-draggable-folder-id]');
        var dropTargets = document.querySelectorAll('[data-drop-folder-id]');
        var i;

        for (i = 0; i < cards.length; i++) {
            cards[i].addEventListener('dragstart', function (event) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', this.getAttribute('data-draggable-folder-id'));
            });
        }

        for (i = 0; i < dropTargets.length; i++) {
            dropTargets[i].addEventListener('dragover', function (event) {
                event.preventDefault();
                this.classList.add('folder-drop-target-active');
            });

            dropTargets[i].addEventListener('dragleave', function () {
                this.classList.remove('folder-drop-target-active');
            });

            dropTargets[i].addEventListener('drop', function (event) {
                event.preventDefault();
                this.classList.remove('folder-drop-target-active');

                var draggedId = event.dataTransfer.getData('text/plain');
                var targetFolderId = this.getAttribute('data-drop-folder-id');

                if (!draggedId || draggedId === targetFolderId) { return; }

                moveFolderViaDrag(draggedId, targetFolderId);
            });
        }
    }

    function wireFolderTreeContextMenu() {
        // Escuta no painel inteiro (o container com padding do offcanvas),
        // nao so no <nav> da arvore em si — senao botao direito no espaco
        // vazio abaixo da lista (o caso mais comum pra criar pasta na
        // raiz) caia fora da area coberta e abria o menu nativo do
        // navegador em vez do nosso.
        var panel = document.querySelector('[data-folder-tree-panel]');
        var menu = document.getElementById('folder-tree-context-menu');

        if (!panel || !menu) { return; }

        var targetFolderId = '';
        var targetRow = null;
        var newFolderButton = menu.querySelector('[data-context-new-folder]');
        var renameFolderButton = menu.querySelector('[data-context-rename-folder]');
        var deleteFolderButton = menu.querySelector('[data-context-delete-folder]');
        var deleteForm = document.getElementById('folder-tree-delete-form');

        function hideMenu() {
            menu.classList.remove('show');
        }

        panel.addEventListener('contextmenu', function (event) {
            event.preventDefault();

            var row = event.target.closest ? event.target.closest('[data-drop-folder-id]') : null;
            targetRow = row;
            targetFolderId = row ? row.getAttribute('data-drop-folder-id') : '';

            // Renomear/excluir so fazem sentido numa pasta especifica — no
            // clique direito na raiz ou no espaco vazio do painel (onde
            // targetFolderId vem vazio) so "Nova pasta aqui" aparece.
            if (renameFolderButton) { renameFolderButton.classList.toggle('d-none', targetFolderId === ''); }
            if (deleteFolderButton) { deleteFolderButton.classList.toggle('d-none', targetFolderId === ''); }

            menu.style.left = event.clientX + 'px';
            menu.style.top = event.clientY + 'px';
            // ".dropdown-menu" do Bootstrap ja vem com display:none por
            // padrao — so a classe "show" (nao bastava so tirar "d-none")
            // bate a especificidade e faz ele aparecer de verdade.
            menu.classList.add('show');
        });

        document.addEventListener('click', hideMenu);
        document.addEventListener('scroll', hideMenu, true);
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') { hideMenu(); }
        });

        if (newFolderButton) {
            newFolderButton.addEventListener('click', function () {
                window.location.href = '/admin/curriculo/documentos/pastas/criar?parent_id=' + targetFolderId;
            });
        }

        if (renameFolderButton) {
            renameFolderButton.addEventListener('click', function () {
                if (!targetFolderId || !targetRow) { return; }
                startInlineRename(targetFolderId, targetRow);
            });
        }

        if (deleteFolderButton && deleteForm) {
            deleteFolderButton.addEventListener('click', function () {
                if (!targetFolderId) { return; }

                confirmDeleteFolder(function () {
                    deleteForm.setAttribute('action', '/admin/curriculo/documentos/pastas/' + targetFolderId + '/excluir');
                    deleteForm.submit();
                });
            });
        }
    }

    /**
     * Monta um <form> escondido e submete via navegacao normal (nao fetch) —
     * e a unica forma simples de disparar um download de arquivo gerado
     * (Content-Disposition: attachment) com uma lista de IDs via POST sem
     * sair da pagina nem precisar lidar com blob/URL.createObjectURL.
     */
    function wireZipDownload() {
        var zipButton = document.getElementById('documents-zip-trigger');
        if (!zipButton) { return; }

        zipButton.addEventListener('click', function () {
            var form = document.createElement('form');
            form.method = 'post';
            form.action = '/admin/curriculo/documentos/baixar-zip';
            form.style.display = 'none';

            function appendHidden(name, value) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            }

            Object.keys(selectedIds).forEach(function (id) { appendHidden('document_ids[]', id); });
            Object.keys(selectedFolderIds).forEach(function (id) { appendHidden('folder_ids[]', id); });

            document.body.appendChild(form);
            form.submit();
            form.remove();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var managers = document.querySelectorAll('.document-manager');
        var i;

        for (i = 0; i < managers.length; i++) {
            wireManager(managers[i]);
            syncCheckboxes(managers[i]);
        }

        syncFolderCheckboxes();
        applyInheritedFolderSelection();
        updateSelectionBar();
        wireShareModal();
        wireFolderDragAndDrop();
        wireFolderTreeContextMenu();
        wireFolderCardActions();
        wireZipDownload();
        wireSelectAllVisible();
        wireBulkDelete();
    });
}());
