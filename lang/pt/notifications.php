<?php

declare(strict_types=1);

return [

    'title' => 'Notificações',
    'description' => 'O que a aplicação lhe quer dar a conhecer.',

    'empty' => 'Nada a comunicar',
    'empty_description' => 'As notificações sobre emails que falharam aparecem aqui.',
    'fallback_title' => 'Notificação',
    'mark_all_read' => 'Marcar todas como lidas',
    'unread' => 'Por ler',
    'unread_count' => ':count notificação(ões) por ler',
    'view_all' => 'Todas as notificações',

    'columns' => [
        'received' => 'Recebida',
    ],

    'flash' => [
        'all_read' => 'Todas as notificações foram marcadas como lidas.',
    ],

    'delivery_failed' => [
        'title' => 'Falhou o envio de um email',
        'message' => 'Não foi possível entregar :template para :target.',
    ],

];
