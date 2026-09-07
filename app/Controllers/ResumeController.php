<?php

namespace App\Controllers;

use App\Core\Env;
use App\Core\ResumePdf;
use App\Core\Sidebar;
use App\Core\View;
use App\Repositories\ProjectRepository;
use App\Repositories\ResumeCertificationRepository;
use App\Repositories\ResumeCourseRepository;
use App\Repositories\ResumeEducationRepository;
use App\Repositories\ResumeExperienceRepository;
use App\Repositories\SettingRepository;

class ResumeController
{
    public function show(): void
    {
        $data = $this->resumeData();
        $settings = $data['settings'];
        $appUrl = rtrim((string) Env::get('APP_URL', ''), '/');

        View::render('site/curriculo', array_merge($data, [
            'title' => 'Currículo — Paulo Roberto Bolsanello | ' . $settings['site_name'],
            'description' => 'Currículo de Paulo Roberto Bolsanello: desenvolvedor fullstack PHP/Laravel, pós-graduado em IA e Ciência de Dados.',
            'active' => 'curriculo',
            'canonical' => $appUrl !== '' ? $appUrl . '/curriculo' : null,
            'ogType' => 'profile',
            'image' => $settings['resume_photo'] ?: null,
            'sidebar' => Sidebar::data(),
            'jsonLd' => [
                '@context' => 'https://schema.org',
                '@type' => 'Person',
                'name' => 'Paulo Roberto Bolsanello',
                'jobTitle' => 'Desenvolvedor Fullstack PHP',
                'url' => $appUrl !== '' ? $appUrl . '/curriculo' : null,
                'sameAs' => array_values(array_filter([
                    $settings['github_url'] ?? null,
                    $settings['linkedin_url'] ?? null,
                ])),
            ],
        ]));
    }

    /**
     * PDF gerado na hora com FPDF a partir dos mesmos dados da pagina
     * publica — evita ter um PDF enviado manualmente que fica desatualizado
     * assim que o curriculo muda no admin.
     */
    public function pdf(): void
    {
        $pdfContent = ResumePdf::generate($this->resumeData());

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="curriculo-paulo-bolsanello.pdf"');
        header('Content-Length: ' . strlen($pdfContent));
        echo $pdfContent;
    }

    private function resumeData(): array
    {
        $settings = (new SettingRepository())->all();
        $skills = array_filter(array_map('trim', explode(',', $settings['resume_skills'])));

        return [
            'settings' => $settings,
            'experience' => (new ResumeExperienceRepository())->all(),
            'education' => (new ResumeEducationRepository())->all(),
            'courses' => (new ResumeCourseRepository())->all(),
            'certifications' => (new ResumeCertificationRepository())->all(),
            'projects' => (new ProjectRepository())->featuredForResume(),
            'skills' => $skills,
        ];
    }
}
