<?php

declare(strict_types=1);

use App\Core\EnvWriter;
use App\Core\Env;

echo "\nEnvWriter\n";

it('serializa pares simples', function (): void {
    $writer = new EnvWriter(tempDir() . '/.env');

    assertSame("APP_ENV=production\nDB_PORT=3306\n", $writer->render([
        'APP_ENV' => 'production',
        'DB_PORT' => '3306',
    ]));
});

it('coloca aspas em valores com espaco', function (): void {
    $writer = new EnvWriter(tempDir() . '/.env');

    assertSame("APP_NAME=\"Pr2 Consultoria\"\n", $writer->render([
        'APP_NAME' => 'Pr2 Consultoria',
    ]));
});

it('coloca aspas em valor vazio', function (): void {
    $writer = new EnvWriter(tempDir() . '/.env');

    assertSame("DB_PASSWORD=\"\"\n", $writer->render(['DB_PASSWORD' => '']));
});

it('escapa aspas dentro do valor', function (): void {
    $writer = new EnvWriter(tempDir() . '/.env');

    assertSame("DB_PASSWORD=\"a\\\"b c\"\n", $writer->render(['DB_PASSWORD' => 'a"b c']));
});

it('grava o arquivo e le de volta', function (): void {
    $path = tempDir() . '/.env';
    $writer = new EnvWriter($path);

    assertTrue($writer->write(['APP_ENV' => 'production', 'DB_HOST' => 'localhost']));
    assertSame(['APP_ENV' => 'production', 'DB_HOST' => 'localhost'], $writer->read());
});

it('le valores entre aspas removendo as aspas', function (): void {
    $path = tempDir() . '/.env';
    file_put_contents($path, "APP_NAME=\"Pr2 Consultoria\"\n");

    assertSame(['APP_NAME' => 'Pr2 Consultoria'], (new EnvWriter($path))->read());
});

it('merge preserva chaves existentes e sobrepoe as informadas', function (): void {
    $path = tempDir() . '/.env';
    $writer = new EnvWriter($path);
    $writer->write(['APP_ENV' => 'local', 'DB_HOST' => 'localhost']);

    assertTrue($writer->merge(['APP_ENV' => 'production', 'APP_INSTALLED' => 'true']));
    assertSame([
        'APP_ENV' => 'production',
        'DB_HOST' => 'localhost',
        'APP_INSTALLED' => 'true',
    ], $writer->read());
});

it('read devolve vazio quando o arquivo nao existe', function (): void {
    assertSame([], (new EnvWriter(tempDir() . '/.env'))->read());
});

it('write devolve false quando o diretorio nao e gravavel', function (): void {
    $dir = tempDir() . '/travado';
    mkdir($dir, 0555, true);

    assertFalse((new EnvWriter($dir . '/.env'))->write(['APP_ENV' => 'production']));

    chmod($dir, 0777);
});

it('round trip com Env::load e Env::get nao corrompe valores', function (): void {
    $path = tempDir() . '/.env';
    $writer = new EnvWriter($path);

    $testCases = [
        'TEST_QUOTE_SPACE' => 'a"b c',
        'TEST_TRAILING_QUOTE' => 'acaba com aspas"',
        'TEST_SPACE' => 'com espaco',
        'TEST_HASH' => 'tem#hash',
        'TEST_BACKSLASH' => 'senha\comBarra',
    ];

    assertTrue($writer->write($testCases));

    Env::load($path);

    foreach ($testCases as $key => $expectedValue) {
        assertSame($expectedValue, Env::get($key), "Valor corrompido para $key");
        unset($_ENV[$key]);
        putenv($key);
    }
});
