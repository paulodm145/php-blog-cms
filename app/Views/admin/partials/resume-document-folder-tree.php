<?php
/** @var array $allFolders */
/** @var int|null $currentFolderId */

$childrenByParent = [];
$byId = [];

foreach ($allFolders as $folder) {
    $parentKey = $folder['parent_id'] !== null ? (int) $folder['parent_id'] : 0;
    $childrenByParent[$parentKey][] = $folder;
    $byId[(int) $folder['id']] = $folder;
}

// Pastas que comecam abertas: so a cadeia de ancestrais da pasta atual —
// o resto da arvore comeca fechada, senao toda pasta aparecia aberta de
// uma vez (o problema que motivou isto) independente de onde o admin
// esta navegando.
$expandedIds = [];
$ancestorId = $currentFolderId !== null && isset($byId[$currentFolderId]) ? $byId[$currentFolderId]['parent_id'] : null;

while ($ancestorId !== null && isset($byId[(int) $ancestorId])) {
    $ancestorId = (int) $ancestorId;
    $expandedIds[$ancestorId] = true;
    $ancestorId = $byId[$ancestorId]['parent_id'];
}

// Fechamento recursivo em vez de funcao global: evita poluir o namespace
// global de uma view que, em outra pagina, poderia ser incluida de novo.
$renderFolderTreeLevel = function (array $nodes) use (&$renderFolderTreeLevel, &$childrenByParent, $currentFolderId, $expandedIds): void {
    ?>
    <ul class="list-unstyled ps-3 mb-0">
        <?php foreach ($nodes as $node): ?>
            <?php
                $nodeId = (int) $node['id'];
                $isActive = $currentFolderId !== null && $nodeId === $currentFolderId;
                $hasChildren = !empty($childrenByParent[$nodeId]);
                $isExpanded = isset($expandedIds[$nodeId]) || $isActive;
            ?>
            <li>
                <div class="d-flex align-items-center folder-tree-row">
                    <?php if ($hasChildren): ?>
                        <button
                            class="btn btn-sm btn-link p-0 me-1 folder-tree-toggle"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#folder-tree-children-<?= $nodeId ?>"
                            aria-expanded="<?= $isExpanded ? 'true' : 'false' ?>"
                            aria-label="Expandir/recolher"
                        ><i class="fa-solid fa-chevron-right folder-tree-chevron"></i></button>
                    <?php else: ?>
                        <span class="folder-tree-spacer"></span>
                    <?php endif; ?>
                    <a
                        class="d-block py-1 text-decoration-none<?= $isActive ? ' fw-bold text-dark' : ' text-secondary' ?>"
                        href="/admin/curriculo/documentos?tab=pastas&folder_id=<?= $nodeId ?>"
                    ><i class="fa-solid fa-folder me-1"></i><?= htmlspecialchars($node['name'], ENT_QUOTES, 'UTF-8') ?></a>
                </div>
                <?php if ($hasChildren): ?>
                    <div class="collapse<?= $isExpanded ? ' show' : '' ?>" id="folder-tree-children-<?= $nodeId ?>">
                        <?php $renderFolderTreeLevel($childrenByParent[$nodeId]); ?>
                    </div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
};
?>
<style>
    .folder-tree-spacer { display: inline-block; width: 1.5rem; }
    .folder-tree-toggle { width: 1.5rem; text-align: center; }
    .folder-tree-chevron { transition: transform .15s ease; }
    .folder-tree-toggle[aria-expanded="true"] .folder-tree-chevron { transform: rotate(90deg); }
</style>
<nav aria-label="Árvore de pastas">
    <div class="d-flex align-items-center folder-tree-row">
        <span class="folder-tree-spacer"></span>
        <a
            class="d-block py-1 text-decoration-none<?= $currentFolderId === null ? ' fw-bold text-dark' : ' text-secondary' ?>"
            href="/admin/curriculo/documentos?tab=pastas"
        ><i class="fa-solid fa-folder-tree me-1"></i>Raiz</a>
    </div>
    <?php if (!empty($childrenByParent[0])): ?>
        <?php $renderFolderTreeLevel($childrenByParent[0]); ?>
    <?php endif; ?>
    <?php if (empty($allFolders)): ?>
        <p class="text-secondary small mt-2 mb-0">Nenhuma pasta criada ainda.</p>
    <?php endif; ?>
</nav>
