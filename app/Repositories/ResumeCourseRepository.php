<?php

namespace App\Repositories;

use App\Core\Text;
use App\Database\Database;

class ResumeCourseRepository
{
    private $database;

    public function __construct(?Database $database = null)
    {
        $this->database = $database ?: new Database();
    }

    /**
     * So os cursos visiveis, em ordem cronologica pela data de conclusao
     * (end_date; cursos sem data de termino usam start_date), do mais
     * recente pro mais antigo — usado pela pagina publica /curriculo e
     * pelo PDF gerado.
     */
    public function all(): array
    {
        return array_map(
            [$this, 'withComputedFields'],
            $this->database->fetchAll(
                $this->selectSql() . ' WHERE resume_courses.visible = 1
                 ORDER BY COALESCE(resume_courses.end_date, resume_courses.start_date) DESC, resume_courses.id DESC'
            )
        );
    }

    /**
     * Lista completa (sem paginacao, visiveis e ocultos) pro admin — usada
     * pela tabela com busca/ordenacao/paginacao em JS de /admin/curriculo
     * (aba Cursos), que filtra e ordena no navegador em vez de ir ao banco
     * a cada interacao. Mesmo padrao de PostRepository::allForAdmin().
     */
    public function allForAdmin(): array
    {
        return array_map(
            [$this, 'withComputedFields'],
            $this->database->fetchAll(
                $this->selectSql() . '
                 ORDER BY COALESCE(resume_courses.end_date, resume_courses.start_date) DESC, resume_courses.id DESC'
            )
        );
    }

    /**
     * Soma da carga horaria de TODOS os cursos cadastrados (visiveis ou
     * nao, ignora busca) — estatistica fixa mostrada acima da tabela.
     */
    public function totalDurationMinutes(): int
    {
        $row = $this->database->fetch('SELECT COALESCE(SUM(duration_minutes), 0) AS total FROM resume_courses');

        return (int) ($row['total'] ?? 0);
    }

    public function setVisible(int $id, bool $visible): void
    {
        $this->database->execute(
            'UPDATE resume_courses SET visible = :visible WHERE id = :id',
            ['visible' => $visible ? 1 : 0, 'id' => $id]
        );
    }

    public function find(int $id): ?array
    {
        $row = $this->database->fetch(
            $this->selectSql() . ' WHERE resume_courses.id = :id LIMIT 1',
            ['id' => $id]
        );

        return $row === null ? null : $this->withComputedFields($row);
    }

    public function create(array $data): void
    {
        $values = $this->normalize($data);
        $values['sort_order'] = $this->nextSortOrder();

        $this->database->execute(
            'INSERT INTO resume_courses (
                name, institution, start_date, end_date, duration_minutes,
                certificate_media_id, certificate_url, sort_order
             ) VALUES (
                :name, :institution, :start_date, :end_date, :duration_minutes,
                :certificate_media_id, :certificate_url, :sort_order
             )',
            $values
        );
    }

    public function update(int $id, array $data): void
    {
        $values = $this->normalize($data);
        unset($values['sort_order']);
        $values['id'] = $id;

        $this->database->execute(
            'UPDATE resume_courses
             SET name = :name, institution = :institution, start_date = :start_date,
                 end_date = :end_date, duration_minutes = :duration_minutes,
                 certificate_media_id = :certificate_media_id, certificate_url = :certificate_url
             WHERE id = :id',
            $values
        );
    }

    public function delete(int $id): void
    {
        $this->database->execute('DELETE FROM resume_courses WHERE id = :id', ['id' => $id]);
    }

    /**
     * sort_order nao controla mais a ordem de exibicao dos cursos (que
     * agora e sempre cronologica pela data de conclusao — ver all() e
     * allForAdmin()); a coluna so continua existindo pra manter o
     * schema/insercao estaveis, sem reordenamento manual (setas) na UI.
     */
    private function nextSortOrder(): int
    {
        $row = $this->database->fetch('SELECT COALESCE(MAX(sort_order), -1) AS max_order FROM resume_courses');

        return (int) $row['max_order'] + 1;
    }

    private function selectSql(): string
    {
        return 'SELECT resume_courses.*, media.path AS certificate_media_url,
                        media.original_name AS certificate_media_name
                 FROM resume_courses
                 LEFT JOIN media ON media.id = resume_courses.certificate_media_id
                    AND media.deleted_at IS NULL';
    }

    /**
     * Injeta campos derivados pra quem consome o registro nao precisar
     * saber os detalhes de armazenamento: "period" formatado (dd/mm/aaaa)
     * a partir de start_date/end_date, e "hours"/"minutes" (partes
     * separadas, pra pre-preencher o form) + "duration_text" (formatado,
     * "1h29min") a partir de duration_minutes.
     */
    private function withComputedFields(array $row): array
    {
        $row['period'] = Text::dateRange($row['start_date'], $row['end_date']);

        $totalMinutes = $row['duration_minutes'] !== null ? (int) $row['duration_minutes'] : null;
        $row['hours'] = $totalMinutes !== null ? intdiv($totalMinutes, 60) : '';
        $row['minutes'] = $totalMinutes !== null ? $totalMinutes % 60 : '';
        $row['duration_text'] = Text::duration($totalMinutes);
        $row['visible'] = (bool) $row['visible'];

        return $row;
    }

    private function normalize(array $data): array
    {
        $startDate = trim((string) ($data['start_date'] ?? ''));
        $endDate = trim((string) ($data['end_date'] ?? ''));
        $hours = max(0, (int) ($data['hours'] ?? 0));
        $minutes = max(0, min(59, (int) ($data['minutes'] ?? 0)));
        $totalMinutes = $hours * 60 + $minutes;
        $certificateMediaId = (int) ($data['certificate_media_id'] ?? 0);
        $certificateUrl = trim((string) ($data['certificate_url'] ?? ''));

        return [
            'name' => trim((string) $data['name']),
            'institution' => trim((string) $data['institution']),
            'start_date' => $startDate !== '' ? $startDate : null,
            'end_date' => $endDate !== '' ? $endDate : null,
            'duration_minutes' => $totalMinutes > 0 ? $totalMinutes : null,
            'certificate_media_id' => $certificateMediaId > 0 ? $certificateMediaId : null,
            'certificate_url' => $certificateUrl !== '' ? $certificateUrl : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
