<?php

return [

    'connection' => env('STATAMIC_ELOQUENT_CONNECTION', 'eloquent'),
    'table_prefix' => env('STATAMIC_ELOQUENT_PREFIX', 'cms_'),

    'asset_containers' => [
        'driver' => 'connection',
        'model' => \Statamic\Eloquent\Assets\AssetContainerModel::class,
    ],

    'assets' => [
        'driver' => 'connection',
        'model' => \Statamic\Eloquent\Assets\AssetModel::class,
        'asset' => \Statamic\Eloquent\Assets\Asset::class,
    ],

    'blueprints' => [
        'driver' => 'connection',
        'model' => \Statamic\Eloquent\Fields\BlueprintModel::class,
        'namespaces' => 'all',
    ],

    'collections' => [
        'driver' => 'connection',
        'model' => \Statamic\Eloquent\Collections\CollectionModel::class,
        'update_entry_order_queue' => 'default',
        'update_entry_order_connection' => 'default',
    ],

    'collection_trees' => [
        'driver' => 'connection',
        'model' => \Statamic\Eloquent\Structures\TreeModel::class,
        'tree' => \Statamic\Eloquent\Structures\CollectionTree::class,
    ],

    'entries' => [
        'driver' => 'connection',
        'model' => \Statamic\Eloquent\Entries\UuidEntryModel::class,
        'entry' => \Statamic\Eloquent\Entries\Entry::class,
        'map_data_to_columns' => false,
    ],

    'fieldsets' => [
        'driver' => 'connection',
        'model' => \Statamic\Eloquent\Fields\FieldsetModel::class,
    ],

    'forms' => [
        'driver' => 'connection',
        'model'  => \Statamic\Eloquent\Forms\FormModel::class,
    ],

    'form_submissions' => [
        'driver' => 'connection',
        'model'  => \Statamic\Eloquent\Forms\SubmissionModel::class,
    ],

    'global_sets' => [
        'driver' => 'connection',
        'model' => \Statamic\Eloquent\Globals\GlobalSetModel::class,
    ],

    'global_set_variables' => [
        'driver' => 'connection',
        'model' => \Statamic\Eloquent\Globals\VariablesModel::class,
    ],

    'navigations' => [
        'driver' => 'connection',
        'model' => \Statamic\Eloquent\Structures\NavModel::class,
    ],

    'navigation_trees' => [
        'driver' => 'connection',
        'model' => \Statamic\Eloquent\Structures\TreeModel::class,
        'tree' => \Statamic\Eloquent\Structures\NavTree::class,
    ],

    'revisions' => [
        'driver' => 'file',
        'model' => \Statamic\Eloquent\Revisions\RevisionModel::class,
    ],

    'taxonomies' => [
        'driver' => 'connection',
        'model' => \Statamic\Eloquent\Taxonomies\TaxonomyModel::class,
    ],

    'terms' => [
        'driver' => 'connection',
        'model' => \Statamic\Eloquent\Taxonomies\TermModel::class,
    ],

    'tokens' => [
        'driver' => 'connection',
        'model' => \Statamic\Eloquent\Tokens\TokenModel::class,
    ],

    'sites' => [
        'driver' => 'connection',
        'model' => \Statamic\Eloquent\Sites\SiteModel::class,
    ],
    'users' => [
        'driver' => 'connection',
        'model' => App\Models\Admin::class,
        'map' => [
            'super' => 'is_super',
        ],
    ],
];
