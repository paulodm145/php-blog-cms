<?php
// $sidebarOrder define quais widgets aparecem e em que ordem. Definido por
// cada view antes de incluir este parcial; default cobre a maioria dos casos.
$sidebarOrder = $sidebarOrder ?? ['search', 'categories', 'archive', 'recent', 'tags'];
$widgetFiles = [
    'search' => 'widget-search.php',
    'about' => 'widget-about.php',
    'categories' => 'widget-categories.php',
    'archive' => 'widget-archive.php',
    'recent' => 'widget-recent.php',
    'tags' => 'widget-tags.php',
];
?>
<aside>
    <?php foreach ($sidebarOrder as $widgetKey): ?>
        <?php if (isset($widgetFiles[$widgetKey])): ?>
            <?php require __DIR__ . '/' . $widgetFiles[$widgetKey]; ?>
        <?php endif; ?>
    <?php endforeach; ?>
</aside>
