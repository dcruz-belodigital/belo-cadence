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

    'notifications' => [

        'greeting' => 'Olá :name,',
        'closing' => 'Com os melhores cumprimentos,',
        'footer' => 'Está a receber esta mensagem porque a :application a envia no âmbito de um calendário acordado.',
        'scheduled_for' => 'Agendado para :date',

        'greeting_all' => 'Olá,',

        'status_update' => [
            'subject' => ':name — atualização de :application',
            'lines' => [
                'Esta é a atualização agendada de :name.',
                'Não é preciso responder, a não ser que algo lhe pareça errado.',
            ],
        ],

        'action_required' => [
            'subject' => ':name precisa de atenção — :application',
            'lines' => [
                'Esta é a nota agendada de :name e precisa que alguém a trate.',
                'Verifique, por favor, e responda quando estiver resolvido.',
            ],
        ],

        'blank' => [
            'empty' => 'Esta notificação foi enviada sem mensagem.',
        ],

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
            'due' => 'A data limite é :date.',
            'contacts' => 'Com quem falar sobre isto:',
            'slots' => [
                'due_date' => 'Data limite',
                'contacts' => 'Quem contactar',
            ],
            'subject' => 'O seu lembrete anual da :application',
            'lines' => [
                'Este é o seu lembrete anual da :application.',
                'É um bom momento para confirmar que tudo aquilo de que tratamos em seu nome continua como pretende.',
                'Responda a este email e marcamos uma revisão.',
            ],
        ],

    ],

];
