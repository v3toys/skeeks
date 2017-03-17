<?php
return [

    'components' =>
    [
        'dbV3project' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'pgsql:host=db.v3project.ru;port=5432;dbname=v3toys_ru',
            'username' => 'username',
            'password' => 'password',
            'charset' => 'utf8',
        ],

        'v3toysApi' => 
        [
            'class' => 'v3toys\skeeks\V3toysApi'
        ],

        'v3projectApi' =>
        [
            'class' => 'v3toys\skeeks\V3projectApi'
        ],

        'v3toysSettings' =>
        [
            'class' => 'v3toys\skeeks\components\V3toysSettings'
        ],

        'v3toys' =>
        [
            'class' => 'v3toys\skeeks\components\V3toysComponent'
        ],

        'i18n' => 
        [
            'translations' =>
            [
                'v3toys/skeeks' => 
                [
                    'class'             => 'yii\i18n\PhpMessageSource',
                    'basePath'          => '@v3toys/skeeks/messages',
                    'fileMap' => [
                        'v3toys/skeeks' => 'main.php',
                    ],
                ]
            ]
        ],

        'urlManager' => [
            'rules' => [
                '~child-<_a:(checkout|finish)>'          => 'v3toys/cart/<_a>',
                '~child-order/<_a>'                      => 'v3toys/order/<_a>',
            ]
        ],
    ],
    
    'modules' =>
    [
        'v3toys' => 
        [
            'class'                 => 'v3toys\skeeks\V3toysModule',
        ]
    ]
];