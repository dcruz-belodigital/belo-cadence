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
        'audience' => 'Público',
        'sample_list' => 'Lista de Exemplo',
        'sample_subject' => 'A sua linha de assunto aparece aqui',
        'sample_message' => "É aqui que aparece a mensagem que escrever no agendamento.\n\nAs linhas em branco tornam-se novos parágrafos.",
        'title' => 'Modelos de email',
        'description' => 'O email que os seus clientes recebem, exatamente como é enviado.',
        'source_notice' => 'Os modelos fazem parte da aplicação, por isso o seu texto é revisto como qualquer outra alteração e não pode ser editado aqui. Acrescentar um é uma pequena tarefa de desenvolvimento.',
        'subject' => 'Assunto',
        'preview' => 'Pré-visualizar',
        'dialog_title' => 'Pré-visualização do modelo',
        'sample_client' => 'Cliente de Exemplo',
        'sample_notice' => 'As pré-visualizações usam um cliente de exemplo e a data atual.',
    ],

    'recipient_count' => '{0} Sem destinatários|{1} 1 destinatário|[2,*] :count destinatários',

    'columns' => [
        'name' => 'Notificação',
        'recipients' => 'Destinatários',
        'target' => 'Envia para',
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
        'client' => 'Cliente',
        'message' => 'Mensagem',
        'name' => 'Nome da notificação',
        'recipients' => 'Emails dos destinatários',
        'subject' => 'Assunto',
        'target' => 'Envia para',
        'frequency' => 'Frequência',
        'is_enabled' => 'Ativo',
        'starts_at' => 'Data e hora do primeiro envio',
        'template' => 'Modelo de email',
    ],

    'hints' => [
        'client' => 'Só é possível enviar email a clientes ativos. Um cliente arquivado ou inativo não aparece na lista.',
        'message' => 'Texto simples. Deixe uma linha em branco entre parágrafos. É enviado exatamente como o escrever.',
        'name' => 'Como esta notificação se chama nas listas, e o nome que os modelos imprimem.',
        'recipients' => 'Um endereço de email por linha. Cada pessoa da lista recebe a sua própria cópia.',
        'subject' => 'A linha de assunto do email.',
        'target' => 'Um agendamento de cliente envia para esse cliente e fica registado nele. Uma lista de destinatários envia para os endereços que indicar.',
        'frequency' => 'Uma notificação de uma só vez é enviada uma vez e depois fecha-se sozinha.',
        'is_enabled' => 'Um agendamento desativado mantém as suas definições mas não envia nada.',
        'starts_at' => 'As horas são mostradas e introduzidas no seu fuso horário (:timezone).',
        'starts_at_anchor' => 'Esta data e hora são a âncora da recorrência. Um agendamento mensal ancorado no dia 31 recua para o último dia dos meses mais curtos.',
        'template' => 'Os modelos fazem parte da aplicação e não podem ser editados aqui.',
    ],

    'actions' => [
        'create_list' => 'Nova notificação',
        'send' => 'Enviar agora',
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
        'target' => 'Envia para',
        'frequency' => 'Frequência',
        'range' => 'Intervalo de tempo',
        'search' => 'Pesquisar nome ou cliente',
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
        'recipients' => 'Destinatários',
        'message' => 'Mensagem',
        'subject' => 'Assunto',
        'title' => 'Agendamento',
        'details' => 'Agendamento',
        'deliveries' => 'Envios deste agendamento',
        'deliveries_empty' => 'Este agendamento ainda não enviou nada',
    ],

    'send' => [
        'title' => 'Enviar esta notificação agora?',
        'message' => 'O email sai de imediato, para todos os destinatários desta notificação. O agendamento fica intacto: nada é reagendado e nenhuma ocorrência é gasta.',
        'confirm' => 'Enviar agora',
    ],

    'enable' => [
        'title' => 'Ativar este agendamento?',
        'message' => 'Volta a enviar a partir da próxima ocorrência. Não sai nada agora.',
        'confirm' => 'Ativar',
    ],

    'disable' => [
        'title' => 'Desativar este agendamento?',
        'message' => 'Não sai mais nada até voltar a ser ativado. O agendamento mantém o texto, as datas e tudo o que já enviou.',
        'confirm' => 'Desativar',
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
        'send_no_recipients' => 'Esta notificação não tem destinatários, por isso nada foi enviado.',
        'sent' => ':count email(s) enviado(s).',
        'sent_with_failures' => 'Não foi possível enviar :failed de :total emails. O histórico de envios tem o motivo de cada um.',
        'created' => 'O agendamento :template foi criado.',
        'deleted' => 'O agendamento foi eliminado.',
        'disabled' => 'O agendamento foi desativado.',
        'enabled' => 'O agendamento foi ativado.',
        'updated' => 'O agendamento foi atualizado.',
    ],

];
