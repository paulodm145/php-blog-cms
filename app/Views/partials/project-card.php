<?php

use App\Core\Text;

$projectUrl = '/projetos/' . rawurlencode($project['slug']);
$hasImage = !empty($project['cover_url']);
$hue = 190 + (crc32($project['slug']) % 60);
$typeLabel = Text::projectTypeLabel($project['project_type']);
$techList = array_slice($project['technology_list'], 0, 4);
$techExtra = count($project['technology_list']) - count($techList);
?>
<article>
    <a href="<?= $projectUrl ?>" class="d-block cover" style="height:150px;<?= $hasImage ? '' : 'background:linear-gradient(125deg, hsl(' . $hue . ' 30% 77%), hsl(' . ($hue - 8) . ' 22% 89%))' ?>">
        <?php if ($hasImage): ?>
            <img src="<?= htmlspecialchars($project['cover_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
        <?php else: ?>
            <div class="cover-stripes"></div>
        <?php endif; ?>
        <span class="project-status-badge"><?= $project['is_ongoing'] ? 'Em andamento' : 'Concluído' ?></span>
    </a>
    <div class="mt-3">
        <div class="post-meta-line d-flex gap-2 align-items-center mb-2 num">
            <span><?= htmlspecialchars($project['period'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php if ($typeLabel !== ''): ?>
                <span class="opacity-50">·</span>
                <span><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
        <h3 class="post-card-title"><a href="<?= $projectUrl ?>"><?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?></a></h3>
        <?php if ($project['resume_description'] !== ''): ?>
            <p class="post-card-excerpt"><?= htmlspecialchars($project['resume_description'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if (count($techList) > 0): ?>
            <div class="d-flex flex-wrap gap-1">
                <?php foreach ($techList as $tech): ?>
                    <span class="chip-pill"><?= htmlspecialchars($tech, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endforeach; ?>
                <?php if ($techExtra > 0): ?>
                    <span class="chip-pill">+<?= $techExtra ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</article>
