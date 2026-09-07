<div class="widget">
    <form class="searchbox" action="/blog/busca" method="get">
        <input type="text" name="q" placeholder="buscar no blog..." value="<?= htmlspecialchars($term ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" aria-label="Buscar"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>
</div>
