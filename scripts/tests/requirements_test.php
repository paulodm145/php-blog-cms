<?php

declare(strict_types=1);

use App\Core\Requirements;

echo "\nRequirements\n";

function requirementsRoot(): string
{
    $root = tempDir();
    mkdir($root . '/public/uploads', 0777, true);

    return $root;
}

function requirementByLabel(array $items, string $needle): array
{
    foreach ($items as $item) {
        if (strpos($item['label'], $needle) !== false) {
            return $item;
        }
    }

    throw new RuntimeException('requisito nao encontrado: ' . $needle);
}

it('aprova um diretorio bem configurado', function (): void {
    $requirements = new Requirements(requirementsRoot());

    assertTrue($requirements->passes());
});

it('reporta a versao do PHP como aprovada', function (): void {
    $items = (new Requirements(requirementsRoot()))->check();

    assertTrue(requirementByLabel($items, 'PHP')['ok']);
});

it('reporta pdo_mysql', function (): void {
    $items = (new Requirements(requirementsRoot()))->check();

    assertSame(extension_loaded('pdo_mysql'), requirementByLabel($items, 'pdo_mysql')['ok']);
});

it('reporta mbstring', function (): void {
    $items = (new Requirements(requirementsRoot()))->check();

    assertSame(extension_loaded('mbstring'), requirementByLabel($items, 'mbstring')['ok']);
});

it('reporta dom', function (): void {
    $items = (new Requirements(requirementsRoot()))->check();

    assertSame(extension_loaded('dom'), requirementByLabel($items, 'dom')['ok']);
});

it('reprova quando a raiz nao e gravavel', function (): void {
    $root = requirementsRoot();
    chmod($root, 0555);

    $requirements = new Requirements($root);
    $passes = $requirements->passes();

    chmod($root, 0777);

    assertFalse($passes);
});

it('avisa quando public/uploads nao existe, sem reprovar', function (): void {
    $root = requirementsRoot();
    rmdir($root . '/public/uploads');

    $requirements = new Requirements($root);

    assertFalse(requirementByLabel($requirements->check(), 'uploads')['ok']);
    assertTrue($requirements->passes(), 'public/uploads e aviso, nao deve reprovar');
});
