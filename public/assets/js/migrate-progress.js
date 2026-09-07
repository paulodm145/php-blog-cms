(function () {
    'use strict';

    function startMigrationProgress(options) {
        var list = document.getElementById('migration-list');
        var bar = document.getElementById('migration-bar');
        var counter = document.getElementById('migration-counter');
        var actions = document.getElementById('migration-actions');
        var total = parseInt(list.getAttribute('data-total'), 10);
        var done = 0;

        function itemFor(name) {
            return list.querySelector('[data-migration="' + name + '"]');
        }

        function markRunning() {
            var next = list.querySelector('[data-status="pending"]');

            if (next !== null) {
                next.setAttribute('data-status', 'running');
                next.querySelector('.migrations__mark').textContent = '▸';
                next.querySelector('.migrations__time').textContent = 'rodando';
            }
        }

        function paint(data) {
            var item = itemFor(data.migration);

            if (item === null) {
                return false;
            }

            var marks = { executed: '✓', skipped: '·', empty: '·', error: '✗' };
            item.setAttribute('data-status', data.status);
            item.querySelector('.migrations__mark').textContent = marks[data.status] || '·';
            item.querySelector('.migrations__time').textContent =
                data.status === 'error' ? 'falhou' : data.duration_ms + ' ms';

            if (data.status === 'error') {
                var box = document.createElement('p');
                box.className = 'migrations__error';
                box.textContent = data.error;
                item.parentNode.insertBefore(box, item.nextSibling);
            }

            return true;
        }

        function isValidResponse(data) {
            var validStatuses = { executed: true, skipped: true, empty: true, error: true };

            return typeof data.migration === 'string' && data.migration !== ''
                && typeof data.status === 'string' && validStatuses[data.status] === true;
        }

        function progress() {
            var percent = total === 0 ? 100 : Math.round((done / total) * 100);
            bar.style.width = percent + '%';
            counter.textContent = done + ' / ' + total;
        }

        var DDL_WARNING = 'Alteracoes de estrutura no MySQL nao sao transacionais: '
            + 'a migration que falhou pode ter aplicado parte das mudancas antes do erro. '
            + 'Repetir pode falhar de novo por objeto ja existente. '
            + 'O caminho seguro e restaurar o backup do banco, corrigir a causa e recomecar.';

        var UNKNOWN_STATE_WARNING = 'Nao foi possivel confirmar se a migration chegou a ser executada. '
            + 'Verifique o estado do banco antes de tentar de novo.';

        function fail(message, opts) {
            opts = opts || {};
            actions.innerHTML = '';

            var alert = document.createElement('p');
            alert.className = 'alert';
            alert.textContent = message;
            actions.appendChild(alert);

            if (opts.warning) {
                var warning = document.createElement('p');
                warning.className = 'alert alert--warn';
                warning.textContent = opts.warning;
                actions.appendChild(warning);
            }

            if (opts.action === 'reload') {
                var reload = document.createElement('button');
                reload.className = 'button';
                reload.type = 'button';
                reload.textContent = 'Recarregar a pagina';
                reload.addEventListener('click', function () {
                    window.location.reload();
                });
                actions.appendChild(reload);

                return;
            }

            if (opts.action === 'login') {
                var login = document.createElement('a');
                login.className = 'button';
                login.href = '/admin/login';
                login.textContent = 'Fazer login';
                actions.appendChild(login);

                return;
            }

            var retry = document.createElement('button');
            retry.className = 'button';
            retry.type = 'button';
            retry.textContent = 'Tentar novamente';
            retry.addEventListener('click', function () {
                actions.innerHTML = '';
                var errors = list.parentNode.querySelectorAll('.migrations__error');
                for (var i = 0; i < errors.length; i++) {
                    errors[i].parentNode.removeChild(errors[i]);
                }
                var stuck = list.querySelector('[data-status="error"]')
                    || list.querySelector('[data-status="running"]');
                if (stuck !== null) {
                    stuck.setAttribute('data-status', 'pending');
                    stuck.querySelector('.migrations__mark').textContent = '';
                    stuck.querySelector('.migrations__time').textContent = '';
                }
                step();
            });
            actions.appendChild(retry);
        }

        function step() {
            markRunning();

            fetch(options.endpoint, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) {
                if (response.status === 401) {
                    fail('A sessao expirou. Faca login novamente para continuar a atualizacao.', {
                        action: 'login'
                    });
                    return null;
                }

                if (response.status === 403) {
                    fail('A sessao de instalacao expirou. Recarregue a pagina para continuar.', {
                        action: 'reload'
                    });
                    return null;
                }

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            }).then(function (data) {
                if (data === null) {
                    return;
                }

                if (data.done === true) {
                    done = total;
                    progress();
                    window.location.href = options.nextUrl;
                    return;
                }

                if (!isValidResponse(data)) {
                    fail('O servidor devolveu uma resposta inesperada.', {
                        warning: UNKNOWN_STATE_WARNING,
                        action: 'retry'
                    });
                    return;
                }

                var painted = paint(data);

                if (!painted) {
                    fail('A lista de migrations mudou desde que a pagina foi carregada. Recarregue a pagina.', {
                        action: 'reload'
                    });
                    return;
                }

                if (data.status === 'error') {
                    fail('A migration ' + data.migration + ' falhou.', {
                        warning: DDL_WARNING,
                        action: 'retry'
                    });
                    return;
                }

                done++;
                progress();
                step();
            }).catch(function (error) {
                fail('A comunicacao com o servidor falhou: ' + error.message, {
                    warning: UNKNOWN_STATE_WARNING,
                    action: 'retry'
                });
            });
        }

        progress();
        step();
    }

    window.startMigrationProgress = startMigrationProgress;
})();
