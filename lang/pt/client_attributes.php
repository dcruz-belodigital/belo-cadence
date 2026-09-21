<?php

declare(strict_types=1);

return [

    'title' => 'Atributos de cliente',
    'description' => 'Os campos adicionais que a sua equipa regista sobre um cliente. Os ativos aparecem no formulário de cliente.',

    'columns' => [
        'clients' => 'Usado por',
        'name' => 'Nome',
        'position' => 'Ordem',
        'required' => 'Obrigatório',
        'state' => 'Estado',
        'type' => 'Tipo',
    ],

    'fields' => [
        'field_name' => 'Nome do campo',
        'field_required' => 'Obrigatório',
        'field_type' => 'Tipo do campo',
        'fields' => 'Campos de cada linha',
        'hint' => 'Texto de ajuda',
        'is_active' => 'Ativo',
        'is_required' => 'Obrigatório',
        'key' => 'Identificador',
        'name' => 'Nome',
        'options' => 'Opções',
        'position' => 'Ordem',
        'type' => 'Tipo',
    ],

    'hints' => [
        'fields' => 'Cada linha deste atributo é composta por estes campos. Um campo pode, por sua vez, ser um conjunto de linhas repetíveis. Deixe um campo sem nome e os seus valores aparecem sozinhos, que é como uma linha se torna uma simples lista.',
        'hint' => 'Aparece por baixo do campo no formulário de cliente. Deixe vazio se o nome disser o suficiente.',
        'is_active' => 'Um atributo inativo desaparece do formulário de cliente, da página do cliente e de ambos os ficheiros CSV. Nada do que já foi registado se perde.',
        'is_required' => 'Um cliente não pode ser guardado sem ele. Os clientes criados antes de isto ser assinalado ficam como estão até alguém os editar.',
        'key' => 'Usado como nome da coluna nos ficheiros CSV. É definido a partir do nome quando o atributo é criado e nunca muda.',
        'name' => 'Como o campo se chama no formulário de cliente.',
        'options' => 'Uma opção por linha. Remover uma linha deixa de a oferecer, mas os clientes que já a usam mantêm a sua resposta.',
        'position' => 'Os números mais baixos aparecem primeiro, em todo o lado onde este atributo surge.',
        'type' => 'O tipo não pode ser alterado depois, porque todas as respostas já registadas estão guardadas nesse formato. Para o mudar, desative este atributo e crie outro.',
    ],

    'actions' => [
        'add_field' => 'Acrescentar campo',
        'add_row' => 'Acrescentar linha',
        'create' => 'Novo atributo',
        'delete' => 'Eliminar',
        'remove_field' => 'Remover',
        'remove_row' => 'Remover',
    ],

    'filters' => [
        'search' => 'Pesquisar nome',
        'state' => 'Estado',
        'type' => 'Tipo',
    ],

    'states' => [
        'active' => 'Ativo',
        'inactive' => 'Inativo',
    ],

    'create' => [
        'title' => 'Novo atributo de cliente',
        'description' => 'Defina um campo que a sua equipa preenche em todos os clientes.',
        'submit' => 'Criar atributo',
    ],

    'edit' => [
        'title' => 'Editar atributo',
        'description' => 'O nome, o texto de ajuda e as opções podem mudar. O identificador e o tipo não.',
        'submit' => 'Guardar atributo',
    ],

    'show' => [
        'details' => 'Atributo',
        'fields' => 'Campos de cada linha',
        'options' => 'Opções',
        'retired' => 'já não é oferecida',
        'unnamed' => 'Campo sem nome',
        'usage' => 'Registado em :count clientes.',
        'usage_none' => 'Ainda nenhum cliente registou um valor para este atributo.',
    ],

    'empty' => [
        'title' => 'Ainda não há atributos',
        'description' => 'Crie um e ele aparece imediatamente em todos os formulários de cliente.',
        'filtered_title' => 'Não há atributos que correspondam a estes filtros',
        'filtered_description' => 'Experimente outro termo de pesquisa ou limpe os filtros.',
    ],

    'delete' => [
        'title' => 'Eliminar este atributo?',
        'message' => 'Todas as respostas registadas nele são eliminadas com ele, em :count clientes, e isto não pode ser desfeito. Para deixar de o usar sem perder nada, desative-o.',
        'confirm' => 'Eliminar atributo',
    ],

    'client' => [
        'title' => 'Atributos',
        'description' => 'Os campos adicionais que a sua equipa regista sobre um cliente.',
        'empty' => 'Ainda não foram definidos atributos.',
        'rows_empty' => 'Ainda não há linhas.',
    ],

    'errors' => [
        'duplicate_field_key' => 'Dois campos da mesma linha não podem ter o mesmo nome.',
        'repeater_needs_field' => 'As linhas repetíveis precisam de pelo menos um campo.',
        'reserved_key' => 'Esse nome já é usado por um dos campos de cliente existentes.',
        'row' => 'Linha :row:',
        'select_needs_option' => 'Uma escolha de uma lista precisa de pelo menos uma opção.',
        'too_deep' => 'As linhas repetíveis não podem estar aninhadas mais do que :levels níveis.',
    ],

    'flash' => [
        'created' => ':name foi criado.',
        'deleted' => ':name foi eliminado.',
        'updated' => ':name foi atualizado.',
    ],

];
