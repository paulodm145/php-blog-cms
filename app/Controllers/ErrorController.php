<?php

namespace App\Controllers;

use App\Core\ErrorPage;

/**
 * So existe pra dar um alvo de rota ao ErrorDocument 403 do .htaccess.
 * Quando alguem acessa direto uma pasta real dentro de public/ (ex:
 * /uploads/media/2026/09/), o Apache barra a listagem (Options -Indexes)
 * e devolve um 403 cru antes mesmo do PHP entrar em cena — isso acontece
 * fora do Router normal. O ErrorDocument reenvia essa resposta pra essa
 * rota, que so troca o 403 cru do Apache pela mesma pagina de erro
 * estilizada usada em qualquer outro lugar do site.
 */
class ErrorController
{
    public function forbidden(): void
    {
        ErrorPage::forbidden();
    }
}
