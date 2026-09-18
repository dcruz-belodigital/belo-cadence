<?php

declare(strict_types=1);

return [

    'title' => 'Registo de auditoria',
    'description' => 'Quem alterou o quê. Apenas de leitura, por princípio.',

    'columns' => [
        'action' => 'Ação',
        'actor' => 'Autor',
        'record' => 'Registo',
        'when' => 'Quando',
    ],

    'filters' => [
        'action' => 'Ação',
        'from' => 'Data inicial',
        'search' => 'Pesquisar autor',
        'to' => 'Data final',
        'type' => 'Tipo de registo',
    ],

    'system_actor' => 'Sistema',
    'system_actor_hint' => 'Executado pela própria aplicação e não por uma pessoa.',

    'show' => [
        'title' => 'Entrada de auditoria',
        'details' => 'Entrada',
        'old_values' => 'Antes',
        'new_values' => 'Depois',
        'metadata' => 'Informação adicional',
        'no_values' => 'Não foram registados valores para esta entrada.',
        'record_missing' => 'O registo a que esta entrada se refere já não existe.',
    ],

    'empty' => [
        'title' => 'Ainda não há entradas de auditoria',
        'description' => 'As entradas aparecem assim que alguém alterar algo que valha a pena registar.',
        'filtered_title' => 'Não há entradas que correspondam a estes filtros',
        'filtered_description' => 'Experimente outro intervalo de datas ou limpe os filtros.',
    ],

    'auditable_types' => [
        'application_settings' => 'Definições da aplicação',
        'audit' => 'Entrada de auditoria',
        'client' => 'Cliente',
        'client_attribute' => 'Atributo de cliente',
        'client_attribute_value' => 'Valor de atributo de cliente',
        'notification_delivery' => 'Envio',
        'notification_schedule' => 'Agendamento de notificação',
        'default_client_notification' => 'Notificação predefinida',
        'role' => 'Perfil de acesso',
        'user' => 'Utilizador',
    ],

];
