<?php

declare(strict_types=1);

return [

    'title' => 'Histórico de envios',
    'description' => 'Todos os emails a clientes que esta aplicação tentou enviar, exatamente como foram produzidos.',

    'columns' => [
        'attempted_at' => 'Tentativa',
        'client' => 'Cliente',
        'failure' => 'Falha',
        'recipient' => 'Destinatário',
        'scheduled_for' => 'Agendado para',
        'sender' => 'Remetente',
        'sent_at' => 'Enviado',
        'status' => 'Estado',
        'subject' => 'Assunto',
        'template' => 'Modelo',
    ],

    'filters' => [
        'client' => 'Cliente',
        'from' => 'Data inicial',
        'search' => 'Pesquisar assunto ou destinatário',
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
        'no_body' => 'Não foi produzido conteúdo de mensagem para esta tentativa.',
    ],

    'empty' => [
        'title' => 'Ainda não foi enviado nada',
        'description' => 'Os envios aparecem aqui assim que o agendador processar uma notificação em prazo.',
        'filtered_title' => 'Não há envios que correspondam a estes filtros',
        'filtered_description' => 'Experimente outro intervalo de datas ou limpe os filtros.',
    ],

];
