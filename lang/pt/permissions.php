<?php

declare(strict_types=1);

/*
| Os nomes das permissões são valores com ponto, como "clients.viewAny". O tradutor lê
| o ponto como aninhamento, por isso as etiquetas abaixo estão aninhadas para
| corresponderem exatamente ao valor — uma chave plana 'clients.viewAny' nunca seria
| encontrada.
*/

return [

    'groups' => [
        'application-settings' => 'Definições da aplicação',
        'audit-log' => 'Registo de auditoria',
        'notifications' => 'Agendamentos de notificação',
        'clients' => 'Clientes',
        'client-attributes' => 'Atributos de cliente',
        'dashboard' => 'Painel',
        'default-client-notifications' => 'Notificações predefinidas',
        'notification-deliveries' => 'Histórico de envios',
        'roles' => 'Perfis de acesso',
        'users' => 'Utilizadores',
    ],

    'names' => [

        'dashboard' => [
            'view' => 'Ver o painel',
        ],

        'client-attributes' => [
            'viewAny' => 'Listar atributos de cliente',
            'view' => 'Ver um atributo de cliente',
            'create' => 'Criar atributos de cliente',
            'update' => 'Atualizar atributos de cliente',
            'delete' => 'Eliminar atributos de cliente',
        ],

        'clients' => [
            'viewAny' => 'Listar clientes',
            'view' => 'Ver um cliente',
            'create' => 'Criar clientes',
            'update' => 'Atualizar clientes',
            'delete' => 'Arquivar e restaurar clientes',
            'import' => 'Importar clientes de CSV',
            'export' => 'Exportar clientes para CSV',
        ],

        'notifications' => [
            'viewAny' => 'Listar agendamentos de notificação',
            'view' => 'Ver um agendamento de notificação',
            'create' => 'Criar agendamentos de notificação',
            'update' => 'Atualizar, ativar e desativar agendamentos',
            'delete' => 'Eliminar agendamentos de notificação',
            'send' => 'Enviar um email manualmente',
            'export' => 'Exportar agendamentos de notificação para CSV',
        ],

        'notification-deliveries' => [
            'viewAny' => 'Listar o histórico de envios',
            'view' => 'Ver um envio',
            'export' => 'Exportar o histórico de envios para CSV',
        ],

        'users' => [
            'viewAny' => 'Listar utilizadores',
            'view' => 'Ver um utilizador',
            'create' => 'Criar utilizadores',
            'update' => 'Atualizar utilizadores, perfis de acesso e ativação',
            'import' => 'Importar utilizadores de CSV',
            'export' => 'Exportar utilizadores para CSV',
        ],

        'roles' => [
            'viewAny' => 'Listar perfis de acesso',
            'view' => 'Ver um perfil de acesso',
            'create' => 'Criar perfis de acesso',
            'update' => 'Atualizar perfis de acesso e as suas permissões',
            'delete' => 'Eliminar perfis de acesso',
            'export' => 'Exportar perfis de acesso para CSV',
        ],

        'audit-log' => [
            'viewAny' => 'Listar entradas de auditoria',
            'view' => 'Ver uma entrada de auditoria',
            'export' => 'Exportar o registo de auditoria para CSV',
        ],

        'application-settings' => [
            'view' => 'Ver as definições da aplicação',
            'update' => 'Atualizar as definições da aplicação',
        ],

        'default-client-notifications' => [
            'view' => 'Ver as notificações predefinidas',
            'update' => 'Atualizar as notificações predefinidas',
        ],

    ],

];
