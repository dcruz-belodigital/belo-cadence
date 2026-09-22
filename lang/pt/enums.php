<?php

declare(strict_types=1);

return [

    'client_status' => [
        'active' => 'Ativo',
        'inactive' => 'Inativo',
    ],

    'client_attribute_type' => [
        'text' => 'Texto',
        'long_text' => 'Texto longo',
        'number' => 'Número',
        'date' => 'Data',
        'boolean' => 'Sim ou não',
        'select' => 'Escolha de uma lista',
        'url' => 'Endereço web',
        'email' => 'Endereço de email',
        'repeater' => 'Linhas repetíveis',
        'file' => 'Ficheiro',
    ],

    'notification_frequency' => [
        'one_time' => 'Uma vez',
        'monthly' => 'Mensal',
        'yearly' => 'Anual',
    ],

    'notification_delivery_status' => [
        'pending' => 'Pendente',
        'sent' => 'Enviado',
        'failed' => 'Falhou',
    ],

    'notification_delivery_source' => [
        'scheduled' => 'Agendado',
        'manual' => 'Manual',
    ],

    'notification_target' => [
        'client' => 'Cliente',
        'recipients' => 'Lista de destinatários',
    ],

    'email_template_audience' => [
        'client' => 'Para um cliente',
        'recipients' => 'Para uma lista de destinatários',
        'any' => 'Para qualquer agendamento',
    ],

    'email_template' => [
        'general_reminder' => 'Lembrete geral',
        'monthly_reminder' => 'Lembrete mensal',
        'annual_reminder' => 'Lembrete anual',
        'status_update' => 'Atualização de estado',
        'action_required' => 'Ação necessária',
        'blank' => 'Em branco',
    ],

    /*
    | Os nomes dos temas são evocativos e não descritivos, à maneira dos temas de
    | editores e terminais: são recordados e pedidos pelo nome, por isso não se
    | traduzem. Só a descrição é que muda de idioma.
    */
    'theme' => [
        'iris' => [
            'label' => 'Iris',
            'description' => 'Minimalista e moderno, em violeta suave',
        ],
        'cappuccino' => [
            'label' => 'Cappuccino',
            'description' => 'Clássico e de cantos direitos, papel quente e café',
        ],
        'bubblegum' => [
            'label' => 'Bubblegum',
            'description' => 'Vistoso, redondo e bem-disposto',
        ],
        'graphite' => [
            'label' => 'Graphite',
            'description' => 'Sóbrio e quase monocromático, tinta sobre papel',
        ],
        'cathode' => [
            'label' => 'Cathode',
            'description' => 'Um terminal: monoespaçado, direito, verde de fósforo',
        ],
        'meridian' => [
            'label' => 'Meridian',
            'description' => 'Frio e preciso, em azul-marinho profundo',
        ],
        'ember' => [
            'label' => 'Ember',
            'description' => 'Quente e vistoso, brasa sobre carvão',
        ],
        'folio' => [
            'label' => 'Folio',
            'description' => 'Livresco e serifado, vinho sobre marfim',
        ],
        'manuscript' => [
            'label' => 'Manuscript',
            'description' => 'Manuscrito e exagerado, tinta azul-preta sobre creme',
        ],
    ],

    'color_scheme' => [
        'system' => 'Sistema',
        'light' => 'Claro',
        'dark' => 'Escuro',
    ],

    'locale' => [
        'en' => 'English',
        'pt' => 'Português',
    ],

    /*
    | As ações de auditoria são valores com ponto, como "client.created". O tradutor lê
    | o ponto como aninhamento, por isso estas etiquetas estão aninhadas para
    | corresponderem exatamente ao valor — uma chave plana 'client.created' nunca seria
    | encontrada.
    */
    'audit_action' => [

        'client' => [
            'created' => 'Cliente criado',
            'updated' => 'Cliente atualizado',
            'archived' => 'Cliente arquivado',
            'restored' => 'Cliente restaurado',
        ],

        'clients' => [
            'imported' => 'Clientes importados',
        ],

        'client_attribute' => [
            'created' => 'Atributo de cliente criado',
            'updated' => 'Atributo de cliente atualizado',
            'deleted' => 'Atributo de cliente eliminado',
        ],

        'notification_schedule' => [
            'created' => 'Agendamento criado',
            'updated' => 'Agendamento atualizado',
            'enabled' => 'Agendamento ativado',
            'disabled' => 'Agendamento desativado',
            'deleted' => 'Agendamento eliminado',
        ],

        'user' => [
            'created' => 'Utilizador criado',
            'updated' => 'Utilizador atualizado',
            'activated' => 'Utilizador ativado',
            'deactivated' => 'Utilizador desativado',
        ],

        'users' => [
            'imported' => 'Utilizadores importados',
        ],

        'role' => [
            'created' => 'Perfil de acesso criado',
            'updated' => 'Perfil de acesso atualizado',
            'deleted' => 'Perfil de acesso eliminado',
        ],

        'application_settings' => [
            'updated' => 'Definições da aplicação atualizadas',
        ],

        'default_client_notifications' => [
            'updated' => 'Notificações predefinidas atualizadas',
        ],

    ],

];
