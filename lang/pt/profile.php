<?php

declare(strict_types=1);

return [

    'title' => 'Perfil',
    'description' => 'A sua conta e o aspeto que a aplicação tem para si.',

    'sections' => [
        'account' => 'Conta',
        'account_description' => 'O seu nome tal como as outras pessoas o veem.',
        'password' => 'Palavra-passe',
        'password_description' => 'Escolha algo que não use em mais lado nenhum.',
        'preferences' => 'Preferências',
        'preferences_description' => 'Estas definições afetam apenas a sua própria vista do Belo Cadence.',
        'roles' => 'Os seus perfis de acesso',
        'roles_description' => 'Os perfis de acesso e a ativação são geridos por um administrador.',
    ],

    'fields' => [
        'current_password' => 'Palavra-passe atual',
        'email' => 'Endereço de email',
        'locale' => 'Idioma',
        'name' => 'Nome completo',
        'password' => 'Nova palavra-passe',
        'password_confirmation' => 'Confirmar nova palavra-passe',
        'theme' => 'Tema',
        'timezone' => 'Fuso horário',
    ],

    'hints' => [
        'email' => 'Peça a um administrador para alterar o seu endereço de email.',
        'theme' => 'Só você vê isto. Deixe por definir para seguir a aplicação, que está atualmente em :theme.',
        'timezone' => 'Todas as datas e horas da aplicação são mostradas neste fuso horário.',
    ],

    'theme_default' => 'Usar o tema da aplicação',

    'submit' => [
        'account' => 'Guardar nome',
        'password' => 'Alterar palavra-passe',
        'preferences' => 'Guardar preferências',
    ],

    'no_roles' => 'Ainda não tem perfis de acesso, por isso a maior parte da aplicação não lhe está disponível.',

    'flash' => [
        'password_updated' => 'A sua palavra-passe foi alterada.',
        'preferences_updated' => 'As suas preferências foram guardadas.',
        'updated' => 'O seu perfil foi guardado.',
    ],

];
