// Quill 1.3.7 nao tem suporte a tabela nativo: sem blots registrados pra
// table/thead/tbody/tr/th/td, o modulo de clipboard trata essas tags como
// "desconhecidas" e descarta a estrutura ao colar HTML (o conteudo de
// texto ate sobrevive solto, mas a tabela em si some). Registrando um blot
// por tag — do mesmo jeito que o proprio Quill faz pra <ol>/<li> em
// modules/formats/list.js (ListContainer + ListItem) — o clipboard passa a
// reconhecer as tags automaticamente e preserva a tabela colada/digitada,
// tanto na visualizacao rica quanto no toggle "Ver HTML".
//
// Precisa ser carregado depois do quill.min.js e antes do `new Quill(...)`
// de cada tela (post-edit.php e project-form.php).
(function () {
    if (typeof Quill === 'undefined') {
        return;
    }

    var Container = Quill.import('blots/container');
    var Block = Quill.import('blots/block');

    class TableCell extends Block {}
    TableCell.blotName = 'table-td';
    TableCell.tagName = 'TD';

    class TableHeaderCell extends Block {}
    TableHeaderCell.blotName = 'table-th';
    TableHeaderCell.tagName = 'TH';

    class TableRow extends Container {}
    TableRow.blotName = 'table-tr';
    TableRow.tagName = 'TR';
    TableRow.allowedChildren = [TableCell, TableHeaderCell];

    class TableHead extends Container {}
    TableHead.blotName = 'table-thead';
    TableHead.tagName = 'THEAD';
    TableHead.allowedChildren = [TableRow];

    class TableBody extends Container {}
    TableBody.blotName = 'table-tbody';
    TableBody.tagName = 'TBODY';
    TableBody.allowedChildren = [TableRow];

    class TableFoot extends Container {}
    TableFoot.blotName = 'table-tfoot';
    TableFoot.tagName = 'TFOOT';
    TableFoot.allowedChildren = [TableRow];

    class Table extends Container {}
    Table.blotName = 'table';
    Table.tagName = 'TABLE';
    Table.allowedChildren = [TableHead, TableBody, TableFoot, TableRow];

    Quill.register({
        'formats/table': Table,
        'formats/table-thead': TableHead,
        'formats/table-tbody': TableBody,
        'formats/table-tfoot': TableFoot,
        'formats/table-tr': TableRow,
        'formats/table-td': TableCell,
        'formats/table-th': TableHeaderCell
    }, true);
})();
