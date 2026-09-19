-- Miniatura gerada no upload (ver App\Core\ImageThumbnail), usada na grade
-- de selecao da Biblioteca de Midia e nas galerias — carregamento mais
-- rapido que exibir o arquivo original reduzido via CSS. NULL quando a
-- imagem ja e pequena, quando a geracao falhou, ou pra arquivos nao-imagem;
-- quem consome sempre cai de volta pro `path` original nesse caso.
ALTER TABLE media
    ADD COLUMN thumbnail_path VARCHAR(255) NULL AFTER path;
