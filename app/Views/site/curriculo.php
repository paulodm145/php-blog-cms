<?php require dirname(__DIR__) . '/partials/site-top.php'; ?>
    <main class="container-lg py-5 resume-page">
        <div class="resume-header mb-5 pb-4 border-b">
            <div class="d-flex flex-wrap align-items-start gap-4">
                <?php if (!empty($settings['resume_photo'])): ?>
                    <img src="<?= htmlspecialchars($settings['resume_photo'], ENT_QUOTES, 'UTF-8') ?>" alt="Foto de Paulo Roberto Bolsanello" class="resume-photo">
                <?php endif; ?>
                <div class="flex-fill" style="min-width:240px">
                    <div class="accent mb-2" style="font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;font-weight:600">Currículo</div>
                    <h1 class="mb-2" style="font-size:clamp(1.6rem,1.2rem+1.6vw,2.1rem);font-weight:700">Paulo Roberto Bolsanello</h1>
                    <p class="text-muted mb-3" style="font-size:1rem;max-width:60ch"><?= htmlspecialchars($settings['resume_tagline'], ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="d-flex flex-wrap gap-2 no-print">
                        <?php if (!empty($settings['linkedin_url'])): ?>
                            <a class="chip-pill" href="<?= htmlspecialchars($settings['linkedin_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><i class="fa-brands fa-linkedin"></i> LinkedIn</a>
                        <?php endif; ?>
                        <?php if (!empty($settings['github_url'])): ?>
                            <a class="chip-pill" href="<?= htmlspecialchars($settings['github_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><i class="fa-brands fa-github"></i> GitHub</a>
                        <?php endif; ?>
                        <a class="chip-pill" href="mailto:paulo.bolsanello@gmail.com"><i class="fa-regular fa-envelope"></i> E-mail</a>
                        <a class="btn-accent" style="display:inline-flex;align-items:center;gap:.4rem" href="/curriculo/pdf">
                            <i class="fa-solid fa-file-arrow-down"></i> Baixar PDF
                        </a>
                        <button class="chip-pill" type="button" onclick="window.print()" style="border:1px solid var(--border);cursor:pointer;background:transparent">
                            <i class="fa-solid fa-print"></i> Imprimir
                        </button>
                    </div>
                    <!-- Contato em texto puro, só visível na impressão/extração — mantém o LinkedIn/GitHub/e-mail legíveis mesmo sem os ícones (importante pra leitura por sistemas de triagem de currículo/ATS). -->
                    <p class="print-only-contact" style="display:none">
                        <?php if (!empty($settings['linkedin_url'])): ?>LinkedIn: <?= htmlspecialchars($settings['linkedin_url'], ENT_QUOTES, 'UTF-8') ?><br><?php endif; ?>
                        <?php if (!empty($settings['github_url'])): ?>GitHub: <?= htmlspecialchars($settings['github_url'], ENT_QUOTES, 'UTF-8') ?><br><?php endif; ?>
                        E-mail: paulo.bolsanello@gmail.com
                    </p>
                </div>
            </div>
        </div>

        <div class="row g-5">
            <div class="col-lg-8">
                <section class="mb-5">
                    <h2 class="widget-title mb-3">Experiência profissional</h2>
                    <?php foreach ($experience as $job): ?>
                        <div class="resume-item">
                            <div class="resume-item-dot"></div>
                            <div class="resume-item-body">
                                <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2">
                                    <h3 class="resume-item-title"><?= htmlspecialchars($job['role'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <span class="text-muted num" style="font-size:.75rem"><?= htmlspecialchars($job['period'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="accent mb-2" style="font-size:.85rem;font-weight:600"><?= htmlspecialchars($job['company'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if (!empty($job['description'])): ?>
                                    <p class="text-muted mb-0" style="font-size:.88rem;line-height:1.6"><?= htmlspecialchars($job['description'], ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($experience) === 0): ?>
                        <p class="text-muted">Nenhuma experiência cadastrada ainda.</p>
                    <?php endif; ?>
                </section>

                <section class="mb-5">
                    <h2 class="widget-title mb-3">Formação acadêmica</h2>
                    <?php foreach ($education as $item): ?>
                        <div class="resume-item">
                            <div class="resume-item-dot"></div>
                            <div class="resume-item-body">
                                <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2">
                                    <h3 class="resume-item-title"><?= htmlspecialchars($item['course'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <span class="text-muted num" style="font-size:.75rem"><?= htmlspecialchars($item['period'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="text-muted" style="font-size:.85rem"><?= htmlspecialchars($item['institution'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($education) === 0): ?>
                        <p class="text-muted">Nenhuma formação cadastrada ainda.</p>
                    <?php endif; ?>
                </section>

                <?php if (count($courses) > 0): ?>
                    <section class="mb-5">
                        <h2 class="widget-title mb-3">Cursos</h2>
                        <?php foreach ($courses as $item): ?>
                            <div class="resume-item">
                                <div class="resume-item-dot"></div>
                                <div class="resume-item-body">
                                    <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2">
                                        <h3 class="resume-item-title"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                        <span class="text-muted num" style="font-size:.75rem"><?= htmlspecialchars($item['period'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-baseline gap-2">
                                        <div class="text-muted" style="font-size:.85rem">
                                            <?= htmlspecialchars($item['institution'], ENT_QUOTES, 'UTF-8') ?>
                                            <?php if ($item['duration_text'] !== ''): ?>
                                                · <?= htmlspecialchars($item['duration_text'], ENT_QUOTES, 'UTF-8') ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($item['certificate_media_url'])): ?>
                                            <a class="no-print" style="font-size:.8rem" href="<?= htmlspecialchars($item['certificate_media_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Ver certificado <i class="fa-solid fa-file-pdf" style="font-size:.7em"></i></a>
                                        <?php endif; ?>
                                        <?php if (!empty($item['certificate_url'])): ?>
                                            <a class="no-print" style="font-size:.8rem" href="<?= htmlspecialchars($item['certificate_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Verificar <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:.7em"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>

                <?php if (count($certifications) > 0): ?>
                    <section class="mb-5">
                        <h2 class="widget-title mb-3">Certificações</h2>
                        <?php foreach ($certifications as $item): ?>
                            <div class="resume-item">
                                <div class="resume-item-dot"></div>
                                <div class="resume-item-body">
                                    <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2">
                                        <h3 class="resume-item-title"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                        <span class="text-muted num" style="font-size:.75rem"><?= htmlspecialchars($item['period'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-baseline gap-2">
                                        <div class="text-muted" style="font-size:.85rem"><?= htmlspecialchars($item['issuer'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if (!empty($item['credential_url'])): ?>
                                            <a class="no-print" style="font-size:.8rem" href="<?= htmlspecialchars($item['credential_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Verificar <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:.7em"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>

                <?php if (count($projects) > 0): ?>
                    <section>
                        <h2 class="widget-title mb-3">Projetos em destaque</h2>
                        <?php foreach ($projects as $item): ?>
                            <div class="resume-item">
                                <div class="resume-item-dot"></div>
                                <div class="resume-item-body">
                                    <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2">
                                        <h3 class="resume-item-title"><a class="text-reset" href="/projetos/<?= rawurlencode($item['slug']) ?>"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></a></h3>
                                        <span class="text-muted num" style="font-size:.75rem"><?= htmlspecialchars($item['period'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <?php if ($item['resume_description'] !== ''): ?>
                                        <p class="text-muted mb-2" style="font-size:.85rem"><?= htmlspecialchars($item['resume_description'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php endif; ?>
                                    <?php if (count($item['technology_list']) > 0): ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($item['technology_list'] as $tech): ?>
                                                <span class="chip-pill"><?= htmlspecialchars($tech, ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <?php if (count($skills) > 0): ?>
                    <div class="widget">
                        <h4 class="widget-title">Habilidades</h4>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($skills as $skill): ?>
                                <span class="chip-pill"><?= htmlspecialchars($skill, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="no-print">
                    <?php $sidebarOrder = ['categories', 'archive']; ?>
                    <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
                </div>
            </div>
        </div>
    </main>
<?php require dirname(__DIR__) . '/partials/site-bottom.php'; ?>
