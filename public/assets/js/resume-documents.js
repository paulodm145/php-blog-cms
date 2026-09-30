(function () {
    'use strict';

    var selectedIds = {};

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

    function updateSelectionBar() {
        var countEl = document.getElementById('documents-selected-count');
        var button = document.getElementById('documents-share-trigger');
        var count = Object.keys(selectedIds).length;

        if (countEl) { countEl.textContent = String(count); }
        if (button) { button.disabled = count === 0; }
    }

    function buildDocumentItem(item) {
        var li = document.createElement('li');
        li.className = 'list-group-item d-flex align-items-center gap-2';
        li.setAttribute('data-document-id', item.id);
        li.innerHTML =
            '<input class="form-check-input flex-shrink-0" type="checkbox" data-document-checkbox>' +
            '<i class="fa-solid ' + fileIconClass(item.mime_type) + ' flex-shrink-0"></i>' +
            '<div class="flex-grow-1 min-w-0">' +
                '<div class="text-truncate"></div>' +
                '<input class="form-control form-control-sm border-0 bg-transparent px-0 document-caption-input" type="text" placeholder="Adicionar legenda…" value="" data-caption-input>' +
            '</div>' +
            '<span class="text-secondary small flex-shrink-0">' + formatSize(item.size) + '</span>' +
            '<a class="admin-action-link flex-shrink-0" href="/admin/curriculo/documentos/' + item.id + '/download" title="Baixar"><i class="fa-solid fa-download"></i></a>' +
            '<button class="admin-action-link admin-action-danger flex-shrink-0" type="button" data-delete-document title="Excluir"><i class="fa-solid fa-trash"></i></button>';
        li.querySelector('.text-truncate').textContent = item.original_name;

        return li;
    }

    function bumpBadge(manager, delta) {
        var accordionItem = manager.closest('.accordion-item');
        if (!accordionItem) { return; }
        var badge = accordionItem.querySelector('.badge');
        if (!badge) { return; }
        badge.textContent = String((parseInt(badge.textContent, 10) || 0) + delta);
    }

    function uploadFiles(manager, fileList) {
        var experienceId = manager.getAttribute('data-experience-id');
        var progressWrap = manager.querySelector('[data-upload-progress-wrap]');
        var progressBar = manager.querySelector('[data-upload-progress-bar]');
        var list = manager.querySelector('[data-document-list]');
        var emptyMessage = manager.querySelector('.document-empty-message');
        var formData = new FormData();
        var i;

        formData.append('experience_id', experienceId);
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
                    if (list) { list.appendChild(buildDocumentItem(item)); }
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

    function saveCaption(documentId, caption) {
        var formData = new FormData();
        formData.append('caption', caption);

        fetch('/admin/curriculo/documentos/' + documentId, { method: 'POST', body: formData });
    }

    function deleteDocument(manager, li) {
        var documentId = li.getAttribute('data-document-id');

        function proceed() {
            fetch('/admin/curriculo/documentos/' + documentId + '/delete', { method: 'POST' })
                .then(function () {
                    delete selectedIds[documentId];
                    updateSelectionBar();
                    li.remove();
                    bumpBadge(manager, -1);
                    notifySuccess('Documento excluído');
                })
                .catch(function () {
                    notifyError('Falha ao excluir o documento');
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

        if (!trigger) { return; }

        var preset = document.getElementById('share-expires-preset');
        var custom = document.getElementById('share-expires-custom');
        var generateButton = document.getElementById('share-generate-btn');
        var form = document.getElementById('share-modal-form');
        var result = document.getElementById('share-modal-result');
        var resultUrl = document.getElementById('share-result-url');
        var resultExpiry = document.getElementById('share-result-expiry');
        var copyButton = document.getElementById('share-copy-btn');
        var sharesWrap = document.getElementById('shares-table-wrap');
        var modalEl = document.getElementById('share-modal');
        var modal = window.bootstrap ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;

        trigger.addEventListener('click', function () {
            if (form) { form.classList.remove('d-none'); }
            if (result) { result.classList.add('d-none'); }
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

                fetch('/admin/curriculo/documentos/compartilhar', { method: 'POST', body: formData })
                    .then(function (response) { return response.json(); })
                    .then(function (payload) {
                        if (payload.error) {
                            notifyError(payload.error);
                            return;
                        }

                        if (resultUrl) { resultUrl.value = payload.url; }
                        if (resultExpiry) { resultExpiry.textContent = payload.expires_at; }
                        if (form) { form.classList.add('d-none'); }
                        if (result) { result.classList.remove('d-none'); }

                        if (sharesWrap) {
                            fetch('/admin/curriculo/documentos/compartilhamentos')
                                .then(function (response) { return response.text(); })
                                .then(function (html) { sharesWrap.innerHTML = html; });
                        }
                    })
                    .catch(function () {
                        notifyError('Falha ao gerar o link');
                    });
            });
        }

        if (copyButton) {
            copyButton.addEventListener('click', function () {
                if (!resultUrl) { return; }

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(resultUrl.value).then(function () {
                        notifySuccess('Link copiado');
                    });
                    return;
                }

                resultUrl.select();
                document.execCommand('copy');
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var managers = document.querySelectorAll('.document-manager');
        var i;

        for (i = 0; i < managers.length; i++) {
            wireManager(managers[i]);
        }

        wireShareModal();
    });
}());
