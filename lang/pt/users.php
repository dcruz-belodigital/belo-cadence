<?php

declare(strict_types=1);

return [

    'title' => 'Utilizadores',
    'description' => 'As pessoas que podem iniciar sessão no Belo Cadence.',

    'columns' => [
        'created' => 'Criado',
        'email' => 'Email',
        'name' => 'Nome',
        'roles' => 'Perfis de acesso',
        'state' => 'Estado',
    ],

    'fields' => [
        'email' => 'Endereço de email',
        'is_active' => 'Ativo',
        'name' => 'Nome completo',
        'password' => 'Palavra-passe',
        'password_confirmation' => 'Confirmar palavra-passe',
        'roles' => 'Perfis de acesso',
    ],

    'hints' => [
        'is_active' => 'Só as contas ativas podem iniciar sessão.',
        'password_optional' => 'Deixe vazio para manter a palavra-passe atual.',
        'roles' => 'As permissões vêm dos perfis de acesso. Um utilizador pode ter mais do que um.',
    ],

    'states' => [
        'active' => 'Ativo',
        'inactive' => 'Inativo',
    ],

    'actions' => [
        'activate' => 'Ativar',
        'create' => 'Novo utilizador',
        'deactivate' => 'Desativar',
    ],

    'filters' => [
        'role' => 'Perfil de acesso',
        'search' => 'Pesquisar nome ou email',
        'state' => 'Estado',
    ],

    'create' => [
        'title' => 'Novo utilizador',
        'description' => 'Não há registo público: as contas são criadas aqui.',
        'submit' => 'Criar utilizador',
    ],

    'edit' => [
        'title' => 'Editar utilizador',
        'description' => 'A ativação é alterada na página do utilizador.',
        'submit' => 'Guardar utilizador',
    ],

    'show' => [
        'details' => 'Conta',
        'roles' => 'Perfis de acesso',
        'no_roles' => 'Este utilizador não tem perfis de acesso, por isso só consegue chegar ao seu próprio perfil.',
        'inactive_notice' => 'Esta conta está desativada e não pode iniciar sessão.',
    ],

    'deactivate' => [
        'title' => 'Desativar esta conta?',
        'message' => 'A pessoa deixa de conseguir iniciar sessão. Nada do que fez é removido e a conta pode ser reativada mais tarde.',
        'confirm' => 'Desativar',
    ],

    'empty' => [
        'title' => 'Não há utilizadores que correspondam a estes filtros',
        'description' => 'Experimente outro termo de pesquisa ou limpe os filtros.',
    ],

    'errors' => [
        'last_administrator' => 'Esta alteração deixaria ninguém com possibilidade de gerir utilizadores e perfis de acesso.',
    ],

    'flash' => [
        'activated' => ':name pode voltar a iniciar sessão.',
        'created' => ':name foi criado.',
        'deactivated' => ':name foi desativado.',
        'updated' => ':name foi atualizado.',
    ],

];
