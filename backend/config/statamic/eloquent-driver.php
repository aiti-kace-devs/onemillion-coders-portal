<?php

return [

    'connection' => env('STATAMIC_ELOQUENT_CONNECTION', 'eloquent'),
    'table_prefix' => env('STATAMIC_ELOQUENT_PREFIX', 'cms_'),

    'asset_containers' => [
        'driver' => env('STATAMIC_DATA_CONNECTION', 'file'),
        'model' => \Statamic\Eloquent\Assets\AssetContainerModel::class,
    ],

    'assets' => [
        'driver' => env('STATAMIC_DATA_CONNECTION', 'file'),
        'model' => \Statamic\Eloquent\Assets\AssetModel::class,
        'asset' => \Statamic\Eloquent\Assets\Asset::class,
    ],

    'blueprints' => [
        'driver' => env('STATAMIC_DATA_CONNECTION', 'file'),
        'model' => \Statamic\Eloquent\Fields\BlueprintModel::class,
        'namespaces' => 'all',
    ],

    'collections' => [
        'driver' => env('STATAMIC_DATA_CONNECTION', 'file'),
        'model' => \Statamic\Eloquent\Collections\CollectionModel::class,
        'update_entry_order_queue' => 'default',
        'update_entry_order_connection' => 'default',
    ],

    'collection_trees' => [
        'driver' => env('STATAMIC_DATA_CONNECTION', 'file'),
        'model' => \Statamic\Eloquent\Structures\TreeModel::class,
        'tree' => \Statamic\Eloquent\Structures\CollectionTree::class,
    ],

    'entries' => [
        'driver' => env('STATAMIC_DATA_CONNECTION', 'file'),
        'model' => \Statamic\Eloquent\Entries\UuidEntryModel::class,
        'entry' => \Statamic\Eloquent\Entries\Entry::class,
        'map_data_to_columns' => false,
    ],

    'fieldsets' => [
        'driver' => env('STATAMIC_DATA_CONNECTION', 'file'),
        'model' => \Statamic\Eloquent\Fields\FieldsetModel::class,
    ],

    'forms' => [
        'driver' => env('STATAMIC_DATA_CONNECTION', 'file'),
        'model'  => \Statamic\Eloquent\Forms\FormModel::class,
    ],

    'form_submissions' => [
        'driver' => env('STATAMIC_DATA_CONNECTION', 'file'),
        'model'  => \Statamic\Eloquent\Forms\SubmissionModel::class,
    ],

    'global_sets' => [
        'driver' => env('STATAMIC_DATA_CONNECTION', 'file'),
        'model' => \Statamic\Eloquent\Globals\GlobalSetModel::class,
    ],

    'global_set_variables' => [
        'driver' => env('STATAMIC_DATA_CONNECTION', 'file'),
        'model' => \Statamic\Eloquent\Globals\VariablesModel::class,
    ],

    'navigations' => [
        'driver' => env('STATAMIC_DATA_CONNECTION', 'file'),
        'model' => \Statamic\Eloquent\Structures\NavModel::class,
    ],

    'navigation_trees' => [
        'driver' => env('STATAMIC_DATA_CONNECTION', 'file'),
        'model' => \Statamic\Eloquent\Structures\TreeModel::class,
        'tree' => \Statamic\Eloquent\Structures\NavTree::class,
    ],

    'revisions' => [
        'driver' => 'file',
        'model' => \Statamic\Eloquent\Revisions\RevisionModel::class,
    ],

    'taxonomies' => [
        'driver' => env('STATAMIC_DATA_CONNECTION', 'file'),
        'model' => \Statamic\Eloquent\Taxonomies\TaxonomyModel::class,
    ],

    'terms' => [
        'driver' => env('STATAMIC_DATA_CONNECTION', 'file'),
        'model' => \Statamic\Eloquent\Taxonomies\TermModel::class,
    ],

    'tokens' => [
        'driver' => env('STATAMIC_DATA_CONNECTION', 'file'),
        'model' => \Statamic\Eloquent\Tokens\TokenModel::class,
    ],

    'sites' => [
        'driver' => env('STATAMIC_DATA_CONNECTION', 'file'),
        'model' => \Statamic\Eloquent\Sites\SiteModel::class,
    ],
    'users' => [
        'driver' => env('STATAMIC_DATA_CONNECTION', 'file'),
        'model' => App\Models\Admin::class,
        'map' => [
            'super' => 'is_super',
        ],
    ],
];
