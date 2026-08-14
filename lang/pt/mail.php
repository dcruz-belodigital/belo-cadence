<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Texto do Email para Clientes
    |--------------------------------------------------------------------------
    |
    | O texto dos modelos de email para clientes, mantidos em código. Acrescentar
    | um modelo implica acrescentar aqui uma secção também.
    |
    */

    'client_notifications' => [

        'greeting' => 'Olá :name,',
        'closing' => 'Com os melhores cumprimentos,',
        'footer' => 'Está a receber esta mensagem porque a :application a envia no âmbito de um calendário acordado.',
        'scheduled_for' => 'Agendado para :date',

        'general_reminder' => [
            'subject' => 'Um lembrete da :application',
            'lines' => [
                'Este é um lembrete agendado da :application.',
                'Se houver algo que precise da sua atenção, basta responder a este email e nós tratamos do assunto.',
            ],
        ],

        'monthly_reminder' => [
            'subject' => 'A sua atualização mensal da :application',
            'lines' => [
                'Aqui fica a sua nota mensal da :application.',
                'Não é necessária qualquer ação da sua parte neste momento. Responda a este email se quiser rever algum assunto connosco.',
            ],
        ],

        'annual_reminder' => [
            'subject' => 'O seu lembrete anual da :application',
            'lines' => [
                'Este é o seu lembrete anual da :application.',
                'É um bom momento para confirmar que tudo aquilo de que tratamos em seu nome continua como pretende.',
                'Responda a este email e marcamos uma revisão.',
            ],
        ],

    ],

];
