<?php

namespace App\Core;

require_once dirname(__DIR__, 2) . '/lib/fpdf/fpdf.php';

/**
 * Gera o PDF do curriculo a partir dos dados cadastrados no admin (nao
 * depende de upload manual). Usa FPDF (lib/fpdf, vendorizada — o projeto
 * nao usa Composer) com as fontes core (Helvetica), que so suportam
 * ISO-8859-1 — por isso todo texto passa por self::t() antes de ir pro PDF.
 */
class ResumePdf extends \FPDF
{
    private const ACCENT = [29, 95, 208];
    private const MUTED = [90, 98, 110];
    private const TEXT = [20, 26, 33];

    public static function generate(array $data): string
    {
        $pdf = new self();
        $pdf->render($data);

        return $pdf->Output('S');
    }

    protected function __construct()
    {
        parent::__construct('P', 'mm', 'A4');
        $this->SetMargins(18, 16, 18);
        $this->SetAutoPageBreak(true, 16);
        $this->SetTitle($this->t('Currículo'));
        $this->AddPage();
    }

    private function render(array $data): void
    {
        $settings = $data['settings'];

        $this->renderHeader($settings);
        $this->contactLine($settings);

        if (!empty($data['skills'])) {
            $this->skillsSection($data['skills']);
        }

        $this->listSection('Experiência profissional', $data['experience'], function (array $item): void {
            $this->entryTitle($item['role'], $item['period']);
            $this->entrySubtitle($item['company']);
            if (!empty($item['description'])) {
                $this->entryBody($item['description']);
            }
        });

        $this->listSection('Formação acadêmica', $data['education'], function (array $item): void {
            $this->entryTitle($item['course'], $item['period']);
            $this->entrySubtitle($item['institution']);
        });

        $this->listSection('Cursos', $data['courses'], function (array $item): void {
            $this->entryTitle($item['name'], $item['period']);
            $subtitle = $item['institution'];
            if (!empty($item['duration_text'])) {
                $subtitle .= '  -  ' . $item['duration_text'];
            }

            // A URL do certificado nunca entra como texto solto na
            // subtitle — links longos (comuns em certificados do
            // LinkedIn Learning etc.) vazam pra fora da margem porque
            // Cell()/MultiCell() nao quebram uma "palavra" sem espacos.
            // Em vez disso vira um link curto e clicavel (entrySubtitle
            // usa o parametro $link do FPDF, que anota a celula com o
            // hyperlink sem precisar imprimir a URL crua).
            $certificateUrl = null;
            if (!empty($item['certificate_url'])) {
                $certificateUrl = $item['certificate_url'];
            } elseif (!empty($item['certificate_media_url'])) {
                // certificate_media_url e um path relativo (arquivo da
                // nossa biblioteca de midia, nao uma URL externa como
                // credential_url) — precisa do dominio pra fazer sentido
                // como link num PDF.
                $certificateUrl = rtrim((string) Env::get('APP_URL', ''), '/') . $item['certificate_media_url'];
            }

            $this->entrySubtitle($subtitle, $certificateUrl, 'Ver certificado »');
        });

        $this->listSection('Certificações', $data['certifications'], function (array $item): void {
            $this->entryTitle($item['name'], $item['period']);
            $this->entrySubtitle($item['issuer'], $item['credential_url'] ?? null, 'Ver credencial »');
        });

        $this->listSection('Projetos', $data['projects'], function (array $item): void {
            $this->entryTitle($item['name'], $item['period']);
            if (!empty($item['technologies'])) {
                $this->entrySubtitle($item['technologies']);
            }
            if (!empty($item['resume_description_full'])) {
                $this->entryBody($item['resume_description_full']);
            }
        });
    }

    private function renderHeader(array $settings): void
    {
        $this->SetFont('Helvetica', 'B', 20);
        $this->SetTextColor(...self::TEXT);
        $this->Cell(0, 9, $this->t('Paulo Roberto Bolsanello'), 0, 1);

        if (!empty($settings['resume_tagline'])) {
            $this->SetFont('Helvetica', '', 10.5);
            $this->SetTextColor(...self::MUTED);
            $this->MultiCell(0, 5.2, $this->t($settings['resume_tagline']));
        }

        $this->Ln(1);
    }

    private function contactLine(array $settings): void
    {
        $parts = ['paulo.bolsanello@gmail.com'];

        if (!empty($settings['linkedin_url'])) {
            $parts[] = $settings['linkedin_url'];
        }

        if (!empty($settings['github_url'])) {
            $parts[] = $settings['github_url'];
        }

        $this->SetFont('Helvetica', '', 9.5);
        $this->SetTextColor(...self::ACCENT);
        $this->MultiCell(0, 5, $this->t(implode('   |   ', $parts)));

        $this->SetDrawColor(220, 224, 230);
        $this->Ln(2);
        $y = $this->GetY();
        $this->Line(18, $y, 210 - 18, $y);
        $this->Ln(5);
    }

    private function skillsSection(array $skills): void
    {
        $this->sectionTitle('Habilidades');
        $this->SetFont('Helvetica', '', 10);
        $this->SetTextColor(...self::TEXT);
        $this->MultiCell(0, 5.5, $this->t(implode('  ·  ', $skills)));
        $this->Ln(3);
    }

    private function listSection(string $title, array $items, callable $renderItem): void
    {
        if (count($items) === 0) {
            return;
        }

        $this->sectionTitle($title);

        foreach ($items as $item) {
            $renderItem($item);
            $this->Ln(3);
        }
    }

    private function sectionTitle(string $title): void
    {
        // Evita "titulo orfao": se sobra menos espaco na pagina do que o
        // titulo + um primeiro item minimo precisam, pula pra proxima
        // pagina em vez de deixar o cabecalho da secao sozinho no rodape
        // com o conteudo comecando so na pagina seguinte.
        if ($this->GetY() + 24 > $this->PageBreakTrigger) {
            $this->AddPage();
        } else {
            $this->Ln(2);
        }

        $this->SetFont('Helvetica', 'B', 12.5);
        $this->SetTextColor(...self::TEXT);
        $this->Cell(0, 7.5, $this->t($title), 0, 1);

        // Tracinho de destaque logo abaixo do titulo — precisa ficar
        // abaixo da linha de base do texto (nao no meio dela) senao corta
        // os proprios caracteres.
        $this->SetDrawColor(...self::ACCENT);
        $this->SetLineWidth(0.6);
        $y = $this->GetY() + 0.3;
        $this->Line($this->lMargin, $y, $this->lMargin + 16, $y);
        $this->SetLineWidth(0.2);
        $this->Ln(2.5);
    }

    /**
     * Titulo do item (cargo, nome do curso etc.) + periodo. Quando o
     * titulo cabe ao lado do periodo, os dois ficam na mesma linha
     * (layout compacto). Quando nao cabe, o FPDF nao teria como avisar —
     * Cell() nao quebra texto, so deixa o titulo invadir visualmente o
     * espaco do periodo — entao aqui e medido antes: se nao couber, o
     * titulo quebra em varias linhas (MultiCell) e o periodo desce pra
     * uma linha propria, alinhado a direita.
     */
    private function entryTitle(string $title, string $period): void
    {
        $titleText = $this->t($title);
        $periodText = $this->t($period);
        $availableWidth = $this->w - $this->lMargin - $this->rMargin;

        $this->SetFont('Helvetica', '', 9);
        $periodWidth = $periodText !== '' ? $this->GetStringWidth($periodText) + 2 : 0;

        $this->SetFont('Helvetica', 'B', 10.5);
        $titleFits = $periodText === '' || $this->GetStringWidth($titleText) <= ($availableWidth - $periodWidth - 2);

        if ($titleFits) {
            $this->SetTextColor(...self::TEXT);
            $this->Cell($availableWidth - $periodWidth, 5.6, $titleText, 0, 0);

            $this->SetFont('Helvetica', '', 9);
            $this->SetTextColor(...self::MUTED);
            $this->Cell($periodWidth, 5.6, $periodText, 0, 1, 'R');

            return;
        }

        $this->SetTextColor(...self::TEXT);
        $this->MultiCell($availableWidth, 5.6, $titleText);

        if ($periodText !== '') {
            $this->SetFont('Helvetica', '', 9);
            $this->SetTextColor(...self::MUTED);
            $this->Cell($availableWidth, 4.6, $periodText, 0, 1, 'R');
        }
    }

    /**
     * $linkUrl, quando informado, vira um link curto e clicavel (ex.:
     * "Ver certificado »") em vez de imprimir a URL crua — evita que
     * links longos vazem pra fora da margem, ja que nem Cell() nem
     * MultiCell() quebram uma "palavra" sem espacos.
     */
    private function entrySubtitle(string $subtitle, ?string $linkUrl = null, string $linkLabel = 'Ver mais »'): void
    {
        $this->SetFont('Helvetica', 'B', 9.5);
        $this->SetTextColor(...self::ACCENT);
        $this->MultiCell(0, 5.2, $this->t($subtitle));

        if ($linkUrl !== null && $linkUrl !== '') {
            $this->SetFont('Helvetica', 'BI', 8.5);
            $this->SetTextColor(...self::ACCENT);
            $this->Cell(0, 5, $this->t($linkLabel), 0, 1, 'L', false, $linkUrl);
        }
    }

    private function entryBody(string $body): void
    {
        $this->SetFont('Helvetica', '', 9.5);
        $this->SetTextColor(...self::TEXT);
        $this->MultiCell(0, 5, $this->t($body));
    }

    /**
     * FPDF com fontes core so entende ISO-8859-1 (Latin-1) — cobre acentos
     * do portugues (a, e, c-cedilha etc). Sem essa conversao, texto UTF-8
     * vindo do banco sai corrompido no PDF.
     */
    private function t(string $text): string
    {
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);

        return $converted !== false ? $converted : $text;
    }

    public function Header(): void
    {
        // Sem cabecalho repetido por pagina — o cabecalho com nome/contato
        // e desenhado uma unica vez em self::header().
    }

    public function Footer(): void
    {
        $this->SetY(-12);
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(...self::MUTED);
        $this->Cell(0, 8, $this->t('Página ' . $this->PageNo()), 0, 0, 'C');
    }
}
