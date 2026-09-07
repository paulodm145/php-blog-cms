<?php

namespace App\Controllers;

use App\Core\Env;
use App\Core\EnvWriter;
use App\Core\InstallState;
use App\Core\Migrator;
use App\Core\Requirements;
use App\Core\View;
use App\Database\Database;
use App\Repositories\UserRepository;
use PDO;
use PDOException;

class InstallController
{
    private $rootPath;
    private $state;

    public function __construct()
    {
        $this->rootPath = dirname(__DIR__, 2);
        $this->state = new InstallState($this->rootPath);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function requirements(): void
    {
        $this->renderRequirements(null);
    }

    public function submitKey(): void
    {
        $submitted = (string) ($_POST['install_key'] ?? '');
        $expected = (string) $this->state->installKey();

        if ($expected === '' || !hash_equals($expected, $submitted)) {
            http_response_code(403);
            $this->renderRequirements('Chave de instalacao invalida.');

            return;
        }

        session_regenerate_id(true);
        $_SESSION['install']['key_ok'] = true;
        $this->redirect('/install/database');
    }

    public function database(): void
    {
        if (!$this->requireStep('key_ok', '/install')) {
            return;
        }

        $this->renderDatabase($this->currentValues(), null);
    }

    public function testConnection(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (($_SESSION['install']['key_ok'] ?? false) !== true) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Sessao expirada. Recarregue a pagina.']);

            return;
        }

        $error = $this->connectionError($this->postedValues());

        echo json_encode($error === null
            ? ['ok' => true, 'message' => 'Conexao estabelecida com sucesso.']
            : ['ok' => false, 'message' => $error]);
    }

    public function saveDatabase(): void
    {
        if (!$this->requireStep('key_ok', '/install')) {
            return;
        }

        $values = $this->postedValues();
        $error = $this->connectionError($values);

        if ($error !== null) {
            $this->renderDatabase($values, $error);

            return;
        }

        $remembered = $values;
        unset($remembered['DB_PASSWORD']);
        $_SESSION['install']['env'] = $remembered;

        $writer = new EnvWriter($this->rootPath . '/.env');

        if (!$writer->write($this->envPayload($values))) {
            $_SESSION['install']['env_manual'] = true;

            View::render('install/env-manual', [
                'title' => 'Instalacao — Arquivo .env',
                'step' => 2,
                'envContents' => $writer->render($this->envPayload($values)),
            ]);

            return;
        }

        $this->finishDatabaseStep();
    }

    public function confirmEnv(): void
    {
        if (!$this->requireStep('key_ok', '/install')) {
            return;
        }

        if (($_SESSION['install']['env_manual'] ?? false) !== true) {
            $this->redirect('/install/database');

            return;
        }

        $values = $_SESSION['install']['env'] ?? [];

        if ($values === []) {
            $this->redirect('/install/database');

            return;
        }

        $stored = (new EnvWriter($this->rootPath . '/.env'))->read();

        foreach (['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME'] as $key) {
            if (($stored[$key] ?? null) !== ($values[$key] ?? null)) {
                $this->renderDatabase(
                    $values,
                    'O arquivo .env encontrado na raiz nao confere com os dados informados. '
                    . 'Confirme o envio e preencha o formulario novamente.'
                );

                return;
            }
        }

        unset($_SESSION['install']['env_manual']);
        $this->finishDatabaseStep();
    }

    public function migrate(): void
    {
        if (!$this->requireStep('db_ok', '/install/database')) {
            return;
        }

        View::render('install/migrate', [
            'title' => 'Instalacao — Migrations',
            'step' => 3,
            'pending' => $this->migrator()->pending(),
        ]);
    }

    public function runMigration(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (($_SESSION['install']['db_ok'] ?? false) !== true) {
            http_response_code(403);
            echo json_encode(['done' => false, 'status' => 'error', 'error' => 'Sessao expirada.']);

            return;
        }

        @set_time_limit(0);

        $result = $this->migrator()->runNextPending();

        if ($result['done'] === true) {
            $_SESSION['install']['migrated'] = true;
        }

        echo json_encode($result);
    }

    public function admin(): void
    {
        if (!$this->requireStep('migrated', '/install/migrate')) {
            return;
        }

        $this->renderAdmin(['name' => '', 'email' => ''], null);
    }

    public function saveAdmin(): void
    {
        if (!$this->requireStep('migrated', '/install/migrate')) {
            return;
        }

        $values = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
        ];
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($values['name'] === '' || $values['email'] === '') {
            $this->renderAdmin($values, 'Informe o nome e o e-mail.');

            return;
        }

        if (filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
            $this->renderAdmin($values, 'Informe um e-mail valido.');

            return;
        }

        if (strlen($password) < 8) {
            $this->renderAdmin($values, 'A senha precisa de pelo menos 8 caracteres.');

            return;
        }

        if ($password !== $confirmation) {
            $this->renderAdmin($values, 'As senhas nao conferem.');

            return;
        }

        $users = new UserRepository();
        $existing = $users->findByEmail($values['email']);

        if ($existing !== null) {
            $this->renderAdmin($values, 'Este e-mail ja esta em uso.');

            return;
        }

        $users->create([
            'name' => $values['name'],
            'email' => $values['email'],
            'password' => $password,
        ]);

        $_SESSION['install']['admin_email'] = $values['email'];
        $_SESSION['install']['admin_created'] = true;
        $this->redirect('/install/done');
    }

    public function done(): void
    {
        if (!$this->requireStep('admin_created', '/install/admin')) {
            return;
        }

        $envSaved = (new EnvWriter($this->rootPath . '/.env'))->merge(['APP_INSTALLED' => 'true']);

        if (!$envSaved) {
            View::render('install/done', [
                'title' => 'Instalacao concluida',
                'step' => 5,
                'email' => (string) ($_SESSION['install']['admin_email'] ?? ''),
                'keyRemoved' => false,
                'envSaved' => false,
            ]);

            return;
        }

        $keyPath = $this->rootPath . '/.env.install';
        $keyRemoved = !is_file($keyPath) || @unlink($keyPath);

        $email = (string) ($_SESSION['install']['admin_email'] ?? '');
        unset($_SESSION['install']);

        View::render('install/done', [
            'title' => 'Instalacao concluida',
            'step' => 5,
            'email' => $email,
            'keyRemoved' => $keyRemoved,
            'envSaved' => true,
        ]);
    }

    private function renderAdmin(array $values, ?string $error): void
    {
        View::render('install/admin', [
            'title' => 'Instalacao — Administrador',
            'step' => 4,
            'values' => $values,
            'error' => $error,
        ]);
    }

    private function migrator(): Migrator
    {
        return new Migrator(new Database(), $this->rootPath . '/database/migrations');
    }

    private function finishDatabaseStep(): void
    {
        Env::load($this->rootPath . '/.env');
        $_SESSION['install']['db_ok'] = true;
        $this->redirect('/install/migrate');
    }

    private function renderDatabase(array $values, ?string $error): void
    {
        View::render('install/database', [
            'title' => 'Instalacao — Banco de dados',
            'step' => 2,
            'values' => $values,
            'error' => $error,
        ]);
    }

    private function currentValues(): array
    {
        $defaults = [
            'APP_NAME' => 'paulorb.dev',
            'APP_URL' => $this->guessUrl(),
            'DB_HOST' => 'localhost',
            'DB_PORT' => '3306',
            'DB_DATABASE' => '',
            'DB_USERNAME' => '',
            'DB_PASSWORD' => '',
        ];

        return array_merge($defaults, $_SESSION['install']['env'] ?? []);
    }

    private function postedValues(): array
    {
        $values = [];

        foreach (['APP_NAME', 'APP_URL', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $key) {
            $values[$key] = trim((string) ($_POST[$key] ?? ''));
        }

        return $values;
    }

    private function envPayload(array $values): array
    {
        return [
            'APP_NAME' => $values['APP_NAME'],
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => rtrim($values['APP_URL'], '/'),
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $values['DB_HOST'],
            'DB_PORT' => $values['DB_PORT'],
            'DB_DATABASE' => $values['DB_DATABASE'],
            'DB_USERNAME' => $values['DB_USERNAME'],
            'DB_PASSWORD' => $values['DB_PASSWORD'],
            'DB_CHARSET' => 'utf8mb4',
        ];
    }

    private function connectionError(array $values): ?string
    {
        if (!ctype_digit($values['DB_PORT'])) {
            return 'A porta do banco de dados deve conter apenas digitos.';
        }

        try {
            new PDO(
                sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $values['DB_HOST'],
                    $values['DB_PORT'],
                    $values['DB_DATABASE']
                ),
                $values['DB_USERNAME'],
                $values['DB_PASSWORD'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            return null;
        } catch (PDOException $exception) {
            return 'Nao foi possivel conectar: ' . $exception->getMessage();
        }
    }

    private function guessUrl(): string
    {
        $https = ($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return ($https ? 'https://' : 'http://') . $host;
    }

    private function renderRequirements(?string $error): void
    {
        $requirements = new Requirements($this->rootPath);

        View::render('install/requirements', [
            'title' => 'Instalacao — Requisitos',
            'step' => 1,
            'requirements' => $requirements->check(),
            'passes' => $requirements->passes(),
            'hasKey' => $this->state->hasInstallKey(),
            'error' => $error,
        ]);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
    }

    protected function requireStep(string $flag, string $fallback): bool
    {
        if (($_SESSION['install'][$flag] ?? false) === true) {
            return true;
        }

        $this->redirect($fallback);

        return false;
    }
}
