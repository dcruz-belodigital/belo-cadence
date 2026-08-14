<?php

declare(strict_types=1);

return [

    'fields' => [
        'file' => 'Ficheiro CSV',
    ],

    'guide_link' => 'Como funciona a importação',

    /*
    | O título do manual do utilizador para onde esta ligação aponta. É o slug desse
    | título, por isso muda com ele; o `LocaleTest` falha se os dois se separarem.
    */
    'guide_anchor' => 'importar-e-exportar',

    'upload' => [
        'hint' => 'Um ficheiro CSV. A correspondência das colunas é feita no passo seguinte, por isso a ordem das suas colunas não importa.',
        'starting_point' => 'Precisa de um ponto de partida?',
        'template_link' => 'Modelo CSV',
    ],

    'available_columns' => 'Colunas que esta importação entende',
    'available_columns_hint' => 'O seu ficheiro não precisa de as ter todas nem de usar estes nomes.',

    'identifier_hint' => 'Deixe :column vazio para criar um novo registo. Preencha com um :column existente para atualizar esse registo.',

    'columns' => [
        'column' => 'Coluna',
        'example' => 'Exemplo',
        'id' => 'Identificador',
        'optional' => 'Opcional',
        'required' => 'Obrigatório',
        'requirement' => 'Obrigatório?',
    ],

    'mapping' => [
        'title' => 'Fazer corresponder as colunas',
        'description' => 'Escolha que coluna do seu ficheiro fornece cada informação. As colunas deixadas como "Não importado" são ignoradas.',
        'not_imported' => 'Não importado',
        'preview' => 'As primeiras linhas do seu ficheiro',
        'preview_hint' => 'Mostradas exatamente como foram lidas, para poder confirmar que o ficheiro está alinhado antes de importar.',
        'row_count' => '{0} Nenhuma linha a importar|{1} :count linha a importar|[2,*] :count linhas a importar',
        'start_over' => 'Carregar outro ficheiro',
        'back_to_upload' => 'Carregar',
        'submit' => 'Importar estas linhas',
    ],

    'errors' => [
        'duplicate_heading' => 'A coluna ":heading" está associada a mais do que um campo.',
        'duplicate_in_file' => 'O valor :value aparece também na linha :line deste ficheiro.',
        'expired' => 'O ficheiro carregado já não está disponível. Carregue-o novamente.',
        'missing_header_row' => 'O ficheiro não começa com uma linha de cabeçalho.',
        'no_rows' => 'O ficheiro não contém linhas para importar.',
        'row' => 'Linha :line: :message',
        'unmapped_required' => ':column é obrigatório, por isso precisa de uma coluna do seu ficheiro.',
        'unreadable' => 'Não foi possível ler o ficheiro carregado.',
    ],

    'flash' => [
        'clients' => 'Importação concluída: :created clientes criados, :updated atualizados.',
        'users' => 'Importação concluída: :created utilizadores criados, :updated atualizados.',
    ],

    'clients' => [
        'title' => 'Importar clientes',
        'description' => 'Criar ou atualizar clientes a partir de um ficheiro CSV.',
        'submit' => 'Continuar',
    ],

    'users' => [
        'title' => 'Importar utilizadores',
        'description' => 'Criar ou atualizar contas de utilizador a partir de um ficheiro CSV.',
        'submit' => 'Continuar',
        'password_hint' => 'Os novos utilizadores precisam de uma palavra-passe com pelo menos 12 caracteres, contendo letras e números. Deixe a palavra-passe vazia ao atualizar um utilizador existente para manter a atual.',
        'roles_hint' => 'Indique os perfis de acesso separados por ponto e vírgula, por exemplo "Administrator;Viewer". Os perfis têm de já existir.',
    ],

];
