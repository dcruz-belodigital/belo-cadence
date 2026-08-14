<?php

declare(strict_types=1);

return [

    'title' => 'Painel',
    'description' => 'O que precisa de atenção hoje.',

    'metrics' => [
        'due_today' => 'A enviar hoje',
        'due_today_hint' => 'Incluindo o que estiver em atraso',
        'due_this_week' => 'A enviar nos próximos 7 dias',
        'sent_this_month' => 'Enviados este mês',
        'failed_this_month' => 'Falharam este mês',
    ],

    'panels' => [
        'upcoming' => 'Próximas notificações',
        'upcoming_all' => 'Ver todas as próximas',
        'upcoming_empty' => 'Não há nada agendado',
        'failures' => 'Falhas recentes',
        'failures_all' => 'Ver histórico de envios',
        'failures_empty' => 'Sem envios falhados',
        'failures_empty_description' => 'Todas as tentativas até agora foram entregues.',
        'recent' => 'Atividade recente',
        'recent_empty' => 'Ainda não foi enviado nada',
    ],

];
