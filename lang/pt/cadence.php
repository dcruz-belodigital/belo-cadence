<?php

declare(strict_types=1);

return [

    'title' => 'Agendamentos de notificação',
    'description' => 'Todo o email recorrente a clientes de que esta aplicação tem conhecimento.',

    'upcoming' => [
        'title' => 'Próximas notificações',
        'description' => 'O que sai a seguir, do mais próximo para o mais distante.',
        'empty' => 'Não há nada agendado',
        'empty_description' => 'Os emails a clientes aparecem aqui assim que um agendamento tiver uma data de envio futura.',
        'filtered_empty' => 'Não há nada que corresponda a estes filtros',
        'filtered_empty_description' => 'Experimente um intervalo de tempo mais largo ou limpe os filtros.',
    ],

    'templates' => [
        'title' => 'Modelos de email',
        'description' => 'O email que os seus clientes recebem, exatamente como é enviado.',
        'source_notice' => 'Os modelos fazem parte da aplicação, por isso o seu texto é revisto como qualquer outra alteração e não pode ser editado aqui. Acrescentar um é uma pequena tarefa de desenvolvimento.',
        'subject' => 'Assunto',
        'preview' => 'Pré-visualizar',
        'dialog_title' => 'Pré-visualização do modelo',
        'sample_client' => 'Cliente de Exemplo',
        'sample_notice' => 'As pré-visualizações usam um cliente de exemplo e a data atual.',
    ],

    'columns' => [
        'client' => 'Cliente',
        'frequency' => 'Frequência',
        'last_sent_at' => 'Último envio',
        'next_send_at' => 'Próximo envio',
        'recipient' => 'Destinatário',
        'starts_at' => 'Ancorado em',
        'state' => 'Estado',
        'template' => 'Modelo',
    ],

    'fields' => [
        'frequency' => 'Frequência',
        'is_enabled' => 'Ativo',
        'starts_at' => 'Data e hora do primeiro envio',
        'template' => 'Modelo de email',
    ],

    'hints' => [
        'frequency' => 'Uma notificação de uma só vez é enviada uma vez e depois fecha-se sozinha.',
        'is_enabled' => 'Um agendamento desativado mantém as suas definições mas não envia nada.',
        'starts_at' => 'As horas são mostradas e introduzidas no seu fuso horário (:timezone).',
        'starts_at_anchor' => 'Esta data e hora são a âncora da recorrência. Um agendamento mensal ancorado no dia 31 recua para o último dia dos meses mais curtos.',
        'template' => 'Os modelos fazem parte da aplicação e não podem ser editados aqui.',
    ],

    'actions' => [
        'create' => 'Novo agendamento',
        'delete' => 'Eliminar agendamento',
        'disable' => 'Desativar',
        'enable' => 'Ativar',
    ],

    'states' => [
        'completed' => 'Concluído',
        'disabled' => 'Desativado',
        'enabled' => 'Ativo',
        'no_next_occurrence' => 'Sem próxima ocorrência',
    ],

    'state_hints' => [
        'completed' => 'Esta notificação de uma só vez já foi enviada.',
        'no_next_occurrence' => 'Este agendamento está ativo mas não tem data futura. Edite-o para definir uma nova.',
    ],

    'filters' => [
        'frequency' => 'Frequência',
        'range' => 'Intervalo de tempo',
        'search' => 'Pesquisar cliente',
        'state' => 'Estado',
        'template' => 'Modelo',
    ],

    'ranges' => [
        'next_30_days' => 'Próximos 30 dias',
        'next_7_days' => 'Próximos 7 dias',
        'today' => 'Hoje',
    ],

    'create' => [
        'title' => 'Novo agendamento de notificação',
        'description' => 'Escolha um dos modelos de email disponíveis e indique quando :client o deve receber pela primeira vez.',
        'submit' => 'Criar agendamento',
    ],

    'edit' => [
        'title' => 'Editar agendamento',
        'description' => 'Alterar a âncora ou a frequência recalcula a próxima data de envio.',
        'submit' => 'Guardar agendamento',
    ],

    'show' => [
        'title' => 'Agendamento',
        'details' => 'Agendamento',
        'deliveries' => 'Envios deste agendamento',
        'deliveries_empty' => 'Este agendamento ainda não enviou nada',
    ],

    'delete' => [
        'title' => 'Eliminar este agendamento?',
        'message' => 'O agendamento deixa de enviar e desaparece das listas de trabalho. Tudo o que já enviou permanece no histórico de envios.',
        'confirm' => 'Eliminar agendamento',
    ],

    'empty' => [
        'title' => 'Ainda não há agendamentos',
        'description' => 'Abra um cliente para criar o seu primeiro email recorrente.',
        'filtered_title' => 'Não há agendamentos que correspondam a estes filtros',
        'filtered_description' => 'Experimente outro termo de pesquisa ou limpe os filtros.',
    ],

    'flash' => [
        'created' => 'O agendamento :template foi criado.',
        'deleted' => 'O agendamento foi eliminado.',
        'disabled' => 'O agendamento foi desativado.',
        'enabled' => 'O agendamento foi ativado.',
        'updated' => 'O agendamento foi atualizado.',
    ],

];
