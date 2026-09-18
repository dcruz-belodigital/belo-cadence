<?php

declare(strict_types=1);

return [

    'title' => 'Histórico de envios',
    'description' => 'Todos os emails que esta aplicação tentou enviar, exatamente como foram produzidos.',

    'columns' => [
        'attempted_at' => 'Tentativa',
        'client' => 'Cliente',
        'failure' => 'Falha',
        'recipient' => 'Destinatário',
        'scheduled_for' => 'Agendado para',
        'sender' => 'Remetente',
        'sent_at' => 'Enviado',
        'sent_by' => 'Enviado por',
        'target' => 'Enviado para',
        'source' => 'Origem',
        'status' => 'Estado',
        'subject' => 'Assunto',
        'template' => 'Modelo',
    ],

    'actions' => [
        'send' => 'Enviar agora',
    ],

    'fields' => [
        'client' => 'Cliente',
        'template' => 'Modelo de email',
    ],

    'hints' => [
        'client' => 'Só é possível enviar email a clientes ativos. Um cliente arquivado ou inativo não aparece na lista.',
        'template' => 'Os modelos fazem parte da aplicação e não podem ser editados aqui.',
    ],

    'send' => [
        'title' => 'Enviar uma notificação agora',
        'description' => 'Envie um dos modelos de email de imediato — a um cliente ou para endereços que escreva aqui — fora de qualquer agendamento. Sai assim que confirmar e fica registado no histórico de envios como envio manual.',
        'submit' => 'Enviar agora',
        'choose_client' => 'Escolha um cliente',
        'no_clients' => 'Não há nenhum cliente para contactar',
        'no_clients_description' => 'Só clientes ativos podem receber email. Crie um, ou reative um cliente, e depois envie.',
    ],

    'filters' => [
        'client' => 'Cliente',
        'from' => 'Data inicial',
        'search' => 'Pesquisar assunto ou destinatário',
        'source' => 'Origem',
        'status' => 'Estado',
        'template' => 'Modelo',
        'to' => 'Data final',
    ],

    'show' => [
        'title' => 'Envio',
        'details' => 'Envio',
        'snapshot' => 'Mensagem tal como foi enviada',
        'snapshot_notice' => 'Esta é a mensagem exata que foi produzida para esta ocorrência. Nunca muda, mesmo que o cliente, o remetente ou o modelo mudem.',
        'failure' => 'Detalhes da falha',
        'schedule' => 'Agendamento',
        'schedule_deleted' => 'O agendamento que originou este envio foi entretanto eliminado.',
        'no_schedule' => 'Enviado manualmente, sem agendamento que o tenha originado.',
        'manual_notice' => 'Este email foi enviado manualmente e não por um agendamento. Nada mudou nos agendamentos do cliente.',
        'sent_by_system' => 'O agendador',
        'no_body' => 'Não foi produzido conteúdo de mensagem para esta tentativa.',
    ],

    'flash' => [
        'sent' => ':count email(s) para :target enviado(s).',
        'failed' => 'Não foi possível enviar :failed de :total emails para :target. Todas as tentativas e os motivos ficam registados no histórico.',
    ],

    'empty' => [
        'title' => 'Ainda não foi enviado nada',
        'description' => 'Os envios aparecem aqui assim que o agendador processar uma notificação em prazo.',
        'filtered_title' => 'Não há envios que correspondam a estes filtros',
        'filtered_description' => 'Experimente outro intervalo de datas ou limpe os filtros.',
    ],

];
