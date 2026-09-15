<?php

namespace App\Repositories;

use App\Database\Database;

class SettingRepository
{
    private $database;

    private $defaults = [
        'site_name' => 'paulorb.dev',
        'blog_description' => 'Programador. Escrevo sobre PHP, IA, LLMs, Go, TypeScript e arquitetura.',
        'github_url' => '',
        'linkedin_url' => '',
        'recaptcha_site_key' => '',
        'recaptcha_secret_key' => '',
        'resume_tagline' => '',
        'resume_skills' => '',
        'resume_photo' => '',
        'google_analytics_id' => '',
    ];

    public function __construct(?Database $database = null)
    {
        $this->database = $database ?: new Database();
    }

    public function all(): array
    {
        $settings = $this->defaults;
        $rows = $this->database->fetchAll('SELECT setting_key, setting_value FROM settings');

        foreach ($rows as $row) {
            if (array_key_exists($row['setting_key'], $settings)) {
                $settings[$row['setting_key']] = (string) $row['setting_value'];
            }
        }

        return $settings;
    }

    public function update(array $data): void
    {
        $settings = [
            'site_name' => $this->requiredText($data['site_name'] ?? '', $this->defaults['site_name']),
            'blog_description' => trim((string) ($data['blog_description'] ?? '')),
            'github_url' => $this->url($data['github_url'] ?? ''),
            'linkedin_url' => $this->url($data['linkedin_url'] ?? ''),
            'recaptcha_site_key' => trim((string) ($data['recaptcha_site_key'] ?? '')),
            'recaptcha_secret_key' => $this->keepIfBlank('recaptcha_secret_key', $data['recaptcha_secret_key'] ?? ''),
            'google_analytics_id' => $this->gaMeasurementId($data['google_analytics_id'] ?? ''),
        ];

        foreach ($settings as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Atualiza so as chaves do curriculo (tagline, resumo em texto corrido,
     * habilidades) — usado pelo AdminResumeController, separado do form
     * principal de configuracoes.
     */
    public function updateResume(array $data): void
    {
        $this->set('resume_tagline', trim((string) ($data['resume_tagline'] ?? '')));
        $this->set('resume_skills', trim((string) ($data['resume_skills'] ?? '')));
    }

    public function setResumePhoto(string $path): void
    {
        $this->set('resume_photo', $path);
    }

    private function set(string $key, string $value): void
    {
        $this->database->execute(
            'INSERT INTO settings (setting_key, setting_value) VALUES (:setting_key, :setting_value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            ['setting_key' => $key, 'setting_value' => $value]
        );
    }

    private function requiredText(string $value, string $fallback): string
    {
        $value = trim($value);

        return $value !== '' ? $value : $fallback;
    }

    private function url(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        return preg_match('#^https?://#i', $value) ? $value : '';
    }

    /**
     * O Measurement ID vai direto num <script> inline (gtag.js), fora de
     * um atributo HTML — htmlspecialchars() na view nao protege contra
     * um valor que "escape" do JS ali. Por isso valida o formato aqui
     * (G-XXXXXXXXXX) antes de gravar; qualquer coisa fora do padrao vira
     * string vazia (GA simplesmente nao carrega, em vez de quebrar a pagina
     * ou abrir brecha de injecao de script).
     */
    private function gaMeasurementId(string $value): string
    {
        $value = trim($value);

        return preg_match('/^G-[A-Z0-9]+$/i', $value) === 1 ? strtoupper($value) : '';
    }

    private function keepIfBlank(string $key, $value): string
    {
        $value = trim((string) $value);

        if ($value !== '') {
            return $value;
        }

        $current = $this->database->fetch(
            'SELECT setting_value FROM settings WHERE setting_key = :setting_key LIMIT 1',
            ['setting_key' => $key]
        );

        return (string) ($current['setting_value'] ?? '');
    }
}
