<?php

use BasePackage\Shared\ModuleGenerator\Activator\AllModulesActivator;
use BasePackage\Shared\ModuleGenerator\Console\Command\ControllerMakeCommand;
use BasePackage\Shared\ModuleGenerator\Console\Command\ModelMakeCommand;
use BasePackage\Shared\ModuleGenerator\Console\Command\ModuleListCommand;
use BasePackage\Shared\ModuleGenerator\Console\Command\ModuleMakeCommand;
use Nwidart\Modules\Activators\FileActivator;
use Nwidart\Modules\Commands;

$replacementList = [
    'CLEAN_MODULE_NAMESPACE',
    'MODULE_NAME',
    'LOWER_NAME',
    'STUDLY_NAME',
    'SNAKE_MODULE_NAME',
    'CAMEL_MODULE_NAME',
    'SNAKE_PLURAL_MODULE_NAME',
];

return [

    /*
    |--------------------------------------------------------------------------
    | Module Namespace
    |--------------------------------------------------------------------------
    |
    | Default module namespace.
    |
    */

    'namespace' => 'Modules',

    /*
    |--------------------------------------------------------------------------
    | Module Stubs
    |--------------------------------------------------------------------------
    |
    | Default module stubs.
    |
    */

    'stubs' => [
        'enabled' => false,
        'path' => base_path('stubs/modules/'),
        'gitkeep' => false,
        'files' => [
            'auto-generate-model' => 'Models/$STUDLY_NAME$.php',
            'database/factory' => 'Database/factories/$STUDLY_NAME$Factory.php',
            'dto/create' => 'DTO/Create$STUDLY_NAME$DTO.php',
            'commands/update' => 'Commands/Update$STUDLY_NAME$Command.php',
            'controllers/controller' => 'Controllers/$STUDLY_NAME$Controller.php',
            'handler/delete' => 'Handlers/Delete$STUDLY_NAME$Handler.php',
            'handler/update' => 'Handlers/Update$STUDLY_NAME$Handler.php',
            'presenter/presenter' => 'Presenters/$STUDLY_NAME$Presenter.php',
            'repository/repository' => 'Repositories/$STUDLY_NAME$Repository.php',
            'requests/create' => 'Requests/Create$STUDLY_NAME$Request.php',
            'requests/getOne' => 'Requests/Get$STUDLY_NAME$Request.php',
            'requests/getList' => 'Requests/Get$STUDLY_NAME$ListRequest.php',
            'requests/update' => 'Requests/Update$STUDLY_NAME$Request.php',
            'requests/delete' => 'Requests/Delete$STUDLY_NAME$Request.php',
            'routes/api' => 'Resources/routes/api.php',
            //'scaffold/config' => 'Resources/config/config.php',
            'scaffold/provider' => 'Providers/$STUDLY_NAME$ServiceProvider.php',
            'service/service' => 'Services/$STUDLY_NAME$CRUDService.php',
            'json' => 'module.json',
            'filters/filter' => 'Filters/$STUDLY_NAME$Filter.php',
//            'views/index' => 'Resources/views/index.blade.php',
//            'views/master' => 'Resources/views/layouts/master.blade.php',
        ],
        'replacements' => [
            'database/factory' => $replacementList,
            'auto-generate-model' => $replacementList,
            'commands/update' => $replacementList,
            'dto/create' => $replacementList,
            'filters/filter' => $replacementList,
            'controllers/controller' => $replacementList,
            'handler/delete' => $replacementList,
            'handler/update' => $replacementList,
            'json' => ['LOWER_NAME', 'STUDLY_NAME', 'MODULE_NAMESPACE', 'PROVIDER_NAMESPACE'],
            'model' => [
                'LOWER_NAME',
                'STUDLY_NAME',
                'VENDOR',
                'AUTHOR_NAME',
                'AUTHOR_EMAIL',
                'MODULE_NAMESPACE',
                'PROVIDER_NAMESPACE',
            ],
            'presenter/presenter' => $replacementList,
            'repository/repository' => $replacementList,
            'requests/create' => $replacementList,
            'requests/getOne' => $replacementList,
            'requests/getList' => $replacementList,
            'requests/update' => $replacementList,
            'requests/delete' => $replacementList,
            'routes/api' => $replacementList,
            'scaffold/config' => ['STUDLY_NAME'],
            'scaffold/provider' => $replacementList,
            'service/service' => $replacementList,
            'views/index' => ['LOWER_NAME'],
            'views/master' => ['LOWER_NAME', 'STUDLY_NAME'],
        ],
    ],

    'paths' => [

        /*
        |--------------------------------------------------------------------------
        | Modules path
        |--------------------------------------------------------------------------
        |
        | This path used for save the generated module. This path also will be added
        | automatically to list of scanned folders.
        |
        */

        'modules' => base_path('modules'),

        /*
        |--------------------------------------------------------------------------
        | Modules assets path
        |--------------------------------------------------------------------------
        |
        | Here you may update the modules assets path.
        |
        */

        'assets' => public_path('modules'),

        /*
        |--------------------------------------------------------------------------
        | The migrations path
        |--------------------------------------------------------------------------
        |
        | Where you run 'module:publish-migration' command, where do you publish the
        | the migration files?
        |
        */

        'migration' => base_path('database/migrations'),

        /*
        |--------------------------------------------------------------------------
        | Generator path
        |--------------------------------------------------------------------------
        | Customise the paths where the folders will be generated.
        | Set the generate key to false to not generate that folder
        */

        'generator' => [
            'config' => ['path' => 'Resources/config', 'generate' => false],
            'command' => ['path' => 'Console', 'generate' => true],
            'migration' => ['path' => 'Database/Migrations', 'generate' => true],
            'seeder' => ['path' => 'Database/Seeders', 'generate' => false],
            'factory' => ['path' => 'Database/factories', 'generate' => true],
            'model' => ['path' => 'Models', 'generate' => true],
            'routes' => ['path' => 'Resources/routes', 'generate' => true],
            'controller' => ['path' => 'Controllers', 'generate' => false],
            'commands' => ['path' => 'Commands', 'generate' => true],
            'dto' => ['path' => 'DTO', 'generate' => true],
            'handlers' => ['path' => 'Handlers', 'generate' => true],
            'service' => ['path' => 'Services', 'generate' => true],
            'module-filters' => ['path' => 'ModuleFilters', 'generate' => false],
            'presenters' => ['path' => 'Presenters', 'generate' => true],
            'filter' => ['path' => 'Middleware', 'generate' => false],
            'request' => ['path' => 'Requests', 'generate' => true],
            'provider' => ['path' => 'Providers', 'generate' => false],
//            'lang' => ['path' => 'Resources/lang', 'generate' => true],
//            'views' => ['path' => 'Resources/views', 'generate' => false],
//            'test' => ['path' => 'Tests/Unit', 'generate' => false],
//            'test-feature' => ['path' => 'Tests/Feature', 'generate' => false],
            'repository' => ['path' => 'Repositories', 'generate' => true],
            'event' => ['path' => 'Events', 'generate' => false],
            'listener' => ['path' => 'Listeners', 'generate' => false],
            'policies' => ['path' => 'Policies', 'generate' => false],
            'rules' => ['path' => 'Rules', 'generate' => false],
            'jobs' => ['path' => 'Jobs', 'generate' => false],
            'emails' => ['path' => 'Emails', 'generate' => false],
            'notifications' => ['path' => 'Notifications', 'generate' => false],
//            'resource' => ['path' => 'Presenters', 'generate' => false],
//            'component-view' => ['path' => 'Resources/views/components', 'generate' => false],
//            'component-class' => ['path' => 'View/Component', 'generate' => false],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Package commands
    |--------------------------------------------------------------------------
    |
    | Here you can define which commands will be visible and used in your
    | application. If for example you don't use some of the commands provided
    | you can simply comment them out.
    |
    */
    'commands' => [
        Commands\CommandMakeCommand::class,
//        Commands\ComponentClassMakeCommand::class,
//        Commands\ComponentViewMakeCommand::class,
          ControllerMakeCommand::class,
//        Commands\DisableCommand::class,
//        Commands\DumpCommand::class,
//        Commands\EnableCommand::class,
        Commands\EventMakeCommand::class,
        Commands\JobMakeCommand::class,
        Commands\ListenerMakeCommand::class,
        Commands\MailMakeCommand::class,
        Commands\MiddlewareMakeCommand::class,
        Commands\NotificationMakeCommand::class,
        Commands\ProviderMakeCommand::class,
//        RouteProviderMakeCommand::class,
//        Commands\InstallCommand::class,
        ModuleListCommand::class,
//        Commands\ModuleDeleteCommand::class,
        ModuleMakeCommand::class,
        Commands\FactoryMakeCommand::class,
        Commands\PolicyMakeCommand::class,
        Commands\RequestMakeCommand::class,
        Commands\RuleMakeCommand::class,
        Commands\MigrateCommand::class,
        Commands\MigrateFreshCommand::class,
        Commands\MigrateRefreshCommand::class,
        Commands\MigrateResetCommand::class,
        Commands\MigrateRollbackCommand::class,
        Commands\MigrateStatusCommand::class,
        Commands\MigrationMakeCommand::class,
        ModelMakeCommand::class,
//        Commands\PublishCommand::class,
//        Commands\PublishConfigurationCommand::class,
//        Commands\PublishMigrationCommand::class,
//        Commands\PublishTranslationCommand::class,
        Commands\SeedCommand::class,
        Commands\SeedMakeCommand::class,
//        Commands\SetupCommand::class,
        Commands\UnUseCommand::class,
//        Commands\UpdateCommand::class,
        Commands\UseCommand::class,
        Commands\ResourceMakeCommand::class,
//        Commands\TestMakeCommand::class,
//        Commands\LaravelModulesV6Migrator::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Scan Path
    |--------------------------------------------------------------------------
    |
    | Here you define which folder will be scanned. By default will scan vendor
    | directory. This is useful if you host the package in packagist website.
    |
    */

    'scan' => [
        'enabled' => true,
        'paths' => [
            //base_path('vendor/*/*'),
            base_path('modules/*/*'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Composer File Template
    |--------------------------------------------------------------------------
    |
    | Here is the config for composer.json file, generated by this package
    |
    */

    'composer' => [
        'vendor' => 'qhub',
        'author' => [
            'name' => 'Qhub Module Generator',
            'email' => 'developers@queueshub.com',
        ],
        'composer-output' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Here is the config for setting up caching feature.
    |
    */

    'cache' => [
        'enabled' => env('MODULE_CACHE_ENABLED', env('APP_ENV') !== 'local'),
        'driver' => 'file',
        'key' => 'laravel-modules',
        'lifetime' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Choose what laravel-modules will register as custom namespaces.
    | Setting one to false will require you to register that part
    | in your own Service Provider class.
    |--------------------------------------------------------------------------
    */

    'register' => [
        'translations' => true,
        /**
         * load files on boot or register method
         *
         * Note: boot not compatible with asgardcms
         *
         * @example boot|register
         */
        'files' => 'register',
    ],

    /*
    |--------------------------------------------------------------------------
    | Activators
    |--------------------------------------------------------------------------
    |
    | You can define new types of activators here, file, database etc. The only
    | required parameter is 'class'.
    | The file activator will store the activation status in storage/installed_modules
    */

    'activators' => [
        'all-modules' => [
            'class' => AllModulesActivator::class,
        ],
        'file' => [
            'class' => FileActivator::class,
            'statuses-file' => base_path('modules_statuses.json'),
            'cache-key' => 'activator.installed',
            'cache-lifetime' => null,
        ],
    ],

    'activator' => 'all-modules',
];
