<?php

declare(strict_types=1);

return [

    'title' => 'Perfis de acesso',
    'description' => 'Conjuntos de permissões que atribui às pessoas.',

    'columns' => [
        'name' => 'Perfil de acesso',
        'permissions' => 'Permissões',
        'users' => 'Utilizadores',
    ],

    'fields' => [
        'name' => 'Nome do perfil de acesso',
        'permissions' => 'Permissões',
    ],

    'hints' => [
        'permissions' => 'As permissões fazem parte da aplicação. Escolhe apenas quais delas este perfil concede.',
    ],

    'actions' => [
        'create' => 'Novo perfil de acesso',
        'delete' => 'Eliminar perfil de acesso',
    ],

    'create' => [
        'title' => 'Novo perfil de acesso',
        'description' => 'Dê um nome ao perfil e escolha o que ele pode fazer.',
        'submit' => 'Criar perfil de acesso',
    ],

    'edit' => [
        'title' => 'Editar perfil de acesso',
        'submit' => 'Guardar perfil de acesso',
    ],

    'show' => [
        'details' => 'Perfil de acesso',
        'permissions' => 'Permissões',
        'no_permissions' => 'Este perfil de acesso ainda não concede permissões.',
        'users' => 'Pessoas com este perfil de acesso',
        'no_users' => 'Ainda ninguém tem este perfil de acesso.',
        'in_use_notice' => 'Este perfil de acesso está atribuído a :count utilizador(es), pelo que não pode ser eliminado enquanto não forem movidos para outro perfil.',
    ],

    'delete' => [
        'title' => 'Eliminar este perfil de acesso?',
        'message' => 'O perfil de acesso desaparece. Ninguém o tem atualmente, por isso não muda nenhuma permissão a ninguém.',
        'confirm' => 'Eliminar perfil de acesso',
    ],

    'empty' => [
        'title' => 'Ainda não há perfis de acesso',
        'description' => 'Crie um perfil de acesso para começar a conceder permissões.',
    ],

    'errors' => [
        'last_administrator' => 'Esta alteração deixaria ninguém com possibilidade de gerir utilizadores e perfis de acesso.',
    ],

    'flash' => [
        'created' => 'O perfil de acesso :name foi criado.',
        'deleted' => 'O perfil de acesso :name foi eliminado.',
        'updated' => 'O perfil de acesso :name foi atualizado.',
    ],

];
