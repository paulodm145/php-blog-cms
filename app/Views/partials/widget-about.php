<div class="widget">
    <h4 class="widget-title">Sobre</h4>
    <p class="text-muted mb-3" style="font-size:.85rem;line-height:1.5">
        <?= htmlspecialchars($settings['blog_description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
    </p>
    <div class="d-flex gap-3">
        <?php if (!empty($settings['github_url'])): ?>
            <a href="<?= htmlspecialchars($settings['github_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="d-flex align-items-center gap-2 text-muted" style="font-size:.78rem">
                <i class="fa-brands fa-github"></i> github
            </a>
        <?php endif; ?>
        <?php if (!empty($settings['linkedin_url'])): ?>
            <a href="<?= htmlspecialchars($settings['linkedin_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="d-flex align-items-center gap-2 text-muted" style="font-size:.78rem">
                <i class="fa-brands fa-linkedin"></i> linkedin
            </a>
        <?php endif; ?>
    </div>
</div>
