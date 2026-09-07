<div class="modal fade" id="media-library-modal" tabindex="-1" aria-labelledby="media-library-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="media-library-modal-label">Biblioteca de mídia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="media-modal-search">Buscar</label>
                        <input class="form-control" id="media-modal-search" type="search" placeholder="Nome do arquivo">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="media-modal-kind">Tipo</label>
                        <select class="form-select" id="media-modal-kind">
                            <option value="">Todos</option>
                            <option value="image">Imagens</option>
                            <option value="file">Documentos</option>
                        </select>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <button class="btn btn-outline-primary" type="button" id="media-modal-upload-trigger">Enviar novo arquivo</button>
                        <input type="file" id="media-modal-upload-input" name="files[]" multiple hidden>
                        <div class="progress mt-2 d-none" id="media-modal-upload-progress" style="height: 6px;">
                            <div class="progress-bar" id="media-modal-upload-progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
                <div id="media-modal-grid-wrap"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="media-modal-insert" disabled>Inserir</button>
            </div>
        </div>
    </div>
</div>
