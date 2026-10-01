<?php
/** @var array $allFolders */
/** @var int|null $currentFolderId */

$childrenByParent = [];

foreach ($allFolders as $folder) {
    $parentKey = $folder['parent_id'] !== null ? (int) $folder['parent_id'] : 0;
    $childrenByParent[$parentKey][] = $folder;
}

// Fechamento recursivo em vez de funcao global: evita poluir o namespace
// global de uma view que, em outra pagina, poderia ser incluida de novo.
$renderFolderTreeLevel = function (array $nodes) use (&$renderFolderTreeLevel, &$childrenByParent, $currentFolderId): void {
    ?>
    <ul class="list-unstyled ps-3 mb-0">
        <?php foreach ($nodes as $node): ?>
            <?php $isActive = $currentFolderId !== null && (int) $node['id'] === $currentFolderId; ?>
            <li>
                <a
                    class="d-block py-1 text-decoration-none<?= $isActive ? ' fw-bold text-dark' : ' text-secondary' ?>"
                    href="/admin/curriculo/documentos?tab=pastas&folder_id=<?= (int) $node['id'] ?>"
                ><i class="fa-solid fa-folder me-1"></i><?= htmlspecialchars($node['name'], ENT_QUOTES, 'UTF-8') ?></a>
                <?php if (!empty($childrenByParent[(int) $node['id']])): ?>
                    <?php $renderFolderTreeLevel($childrenByParent[(int) $node['id']]); ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
};
?>
<nav aria-label="Árvore de pastas">
    <a
        class="d-block py-1 text-decoration-none<?= $currentFolderId === null ? ' fw-bold text-dark' : ' text-secondary' ?>"
        href="/admin/curriculo/documentos?tab=pastas"
    ><i class="fa-solid fa-folder-tree me-1"></i>Raiz</a>
    <?php if (!empty($childrenByParent[0])): ?>
        <?php $renderFolderTreeLevel($childrenByParent[0]); ?>
    <?php endif; ?>
    <?php if (empty($allFolders)): ?>
        <p class="text-secondary small mt-2 mb-0">Nenhuma pasta criada ainda.</p>
    <?php endif; ?>
</nav>
