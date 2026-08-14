<?php

declare(strict_types=1);

return [

    'title' => 'Clientes',
    'description' => 'As organizações a quem o Belo Cadence envia email recorrente.',

    'columns' => [
        'created' => 'Criado',
        'email' => 'Email',
        'name' => 'Nome',
        'schedules' => 'Agendamentos',
        'status' => 'Estado',
    ],

    'fields' => [
        'email' => 'Endereço de email',
        'name' => 'Nome do cliente',
        'notes' => 'Notas internas',
        'status' => 'Estado',
    ],

    'hints' => [
        'email' => 'Os emails agendados para o cliente são enviados para este endereço.',
        'notes' => 'Visíveis apenas dentro do Belo Cadence. Nunca são enviadas ao cliente.',
    ],

    'actions' => [
        'archive' => 'Arquivar',
        'create' => 'Novo cliente',
        'restore' => 'Restaurar',
    ],

    'filters' => [
        'archived' => 'Incluir arquivados',
        'search' => 'Pesquisar nome ou email',
        'status' => 'Estado',
    ],

    'create' => [
        'title' => 'Novo cliente',
        'description' => 'Acrescente um cliente e, se quiser, comece já o seu email recorrente.',
        'details' => 'Dados do cliente',
        'defaults' => 'Notificações predefinidas',
        'defaults_description' => 'Estes são os agendamentos de notificação que a sua equipa aplica a novos clientes. Assinale os que este cliente deve receber e defina a primeira data de envio de cada um.',
        'defaults_empty' => 'Não há notificações predefinidas configuradas, por isso este cliente começa sem agendamentos. Pode acrescentá-los depois na página do cliente.',
        'apply' => 'Aplicar a este cliente',
        'enabled' => 'Começar a enviar de imediato',
        'submit' => 'Criar cliente',
    ],

    'edit' => [
        'title' => 'Editar cliente',
        'description' => 'Alterar estes dados nunca altera emails que já tenham sido enviados.',
        'submit' => 'Guardar cliente',
    ],

    'show' => [
        'archived_notice' => 'Este cliente está arquivado. Os seus agendamentos estão desligados e não é enviado email em seu nome.',
        'inactive_notice' => 'Este cliente está inativo, por isso não lhe é enviado email agendado.',
        'details' => 'Dados do cliente',
        'schedules' => 'Agendamentos de notificação',
        'schedules_description' => 'O que este cliente recebe, e quando.',
        'schedules_empty' => 'Ainda não há agendamentos',
        'schedules_empty_description' => 'Crie um agendamento para começar a enviar email recorrente a este cliente.',
        'deliveries' => 'Envios recentes',
        'deliveries_description' => 'As tentativas de envio mais recentes para este cliente.',
        'deliveries_empty' => 'Ainda não foi enviado nada a este cliente',
        'notes' => 'Notas',
    ],

    'empty' => [
        'title' => 'Ainda não há clientes',
        'description' => 'Acrescente o seu primeiro cliente para começar a agendar email recorrente.',
        'filtered_title' => 'Não há clientes que correspondam a estes filtros',
        'filtered_description' => 'Experimente outro termo de pesquisa ou limpe os filtros.',
    ],

    'archive' => [
        'title' => 'Arquivar este cliente?',
        'message' => 'O cliente deixa de aparecer na lista de trabalho e os seus agendamentos são desligados. O histórico de envios é mantido tal como está e o cliente pode ser restaurado mais tarde.',
        'confirm' => 'Arquivar cliente',
    ],

    'restore' => [
        'title' => 'Restaurar este cliente?',
        'message' => 'O cliente volta à lista de trabalho. Os seus agendamentos ficam desligados até os voltar a ativar.',
        'confirm' => 'Restaurar cliente',
    ],

    'flash' => [
        'archived' => ':name foi arquivado.',
        'created' => ':name foi criado.',
        'restored' => ':name foi restaurado.',
        'updated' => ':name foi atualizado.',
    ],

];
