<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <a class="text-secondary" href="<?= htmlspecialchars($backHref, ENT_QUOTES, 'UTF-8') ?>">Voltar</a>
            <h1 class="h3 mt-3 mb-4">Mover "<?= htmlspecialchars($subjectLabel, ENT_QUOTES, 'UTF-8') ?>"</h1>

            <?php if (!empty($_GET['erro'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_GET['erro'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="post" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>">
                <div class="mb-3">
                    <label class="form-label" for="destination">Novo local</label>
                    <select class="form-select" id="destination" name="destination" required>
                        <?php $currentGroup = null; ?>
                        <?php foreach ($options as $option): ?>
                            <?php if ($option['group'] !== $currentGroup): ?>
                                <?php if ($currentGroup !== null): ?></optgroup><?php endif; ?>
                                <optgroup label="<?= htmlspecialchars($option['group'], ENT_QUOTES, 'UTF-8') ?>">
                                <?php $currentGroup = $option['group']; ?>
                            <?php endif; ?>
                            <option value="<?= htmlspecialchars($option['value'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                        <?php if ($currentGroup !== null): ?></optgroup><?php endif; ?>
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Mover</button>
            </form>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
</body>
</html>
