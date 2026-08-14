<?php

declare(strict_types=1);

return [

    'title' => 'Definições da aplicação',
    'description' => 'Como o Belo Cadence se apresenta e de quem vem o email para clientes.',

    'fields' => [
        'application_name' => 'Nome da aplicação',
        'client_email_sender_email' => 'Endereço de remetente do email a clientes',
        'client_email_sender_name' => 'Nome de remetente do email a clientes',
        'default_locale' => 'Idioma predefinido',
        'default_theme' => 'Tema predefinido',
        'default_timezone' => 'Fuso horário predefinido',
    ],

    'hints' => [
        'application_name' => 'Mostrado na interface e usado no texto do email a clientes.',
        'client_email_sender' => 'Usado no email agendado para clientes de agora em diante. Os envios já registados mantêm o remetente com que foram enviados.',
        'default_locale' => 'Usado no email a clientes e para quem não tiver escolhido um idioma.',
        'default_theme' => 'O aspeto da aplicação para todos os que não tiverem escolhido um tema próprio.',
        'default_timezone' => 'As datas de envio recorrente são calculadas neste fuso horário, que é também o recurso para quem não tiver escolhido um.',
        'infrastructure' => 'As credenciais do servidor de email não são editáveis aqui de propósito: pertencem à configuração do ambiente.',
    ],

    'sections' => [
        'application' => 'Aplicação',
        'client_email' => 'Email a clientes',
    ],

    'color_scheme' => [
        'label' => 'Esquema de cores',
    ],

    'submit' => 'Guardar definições',

    'flash' => [
        'updated' => 'As definições da aplicação foram atualizadas.',
    ],

    'defaults' => [
        'title' => 'Notificações predefinidas',
        'description' => 'Os agendamentos de notificação oferecidos quando se cria um cliente.',
        'explanation' => 'Aplicar uma destas a um cliente cria uma cópia. Alterar esta lista mais tarde nunca altera agendamentos que já existam.',
        'columns' => [
            'template' => 'Modelo',
            'frequency' => 'Frequência',
            'enabled' => 'Ativa por predefinição',
        ],
        'add' => 'Adicionar predefinição',
        'remove' => 'Remover',
        'empty' => 'Sem predefinições configuradas',
        'empty_description' => 'Os novos clientes começam sem agendamentos até acrescentar um aqui.',
        'submit' => 'Guardar predefinições',
        'flash' => [
            'updated' => 'As notificações predefinidas foram atualizadas.',
        ],
    ],

];
