<?php

namespace App\Helpers;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

trait GeneralFieldsAndColumns
{
    // Default configuration
    protected int $defaultStringLimit = 200;
    protected string $defaultDateFormat = 'YYYY-MM-DD HH:mm';
    protected string $defaultBooleanLabelTrue = 'Active';
    protected string $defaultBooleanLabelFalse = 'Inactive';

    // FIELD METHODS =====================================================

    /**
     * Add a created_on date field
     */
    public function addCreatedOnField(string $tab = '', string $label = 'Created At', string $format = ''): void
    {
        CRUD::field('created_on')
            ->type('datetime')
            ->label($label)
            ->format($format ?? $this->defaultDateFormat)
            ->when(!is_null($tab), fn($field) => $field->tab($tab));
    }

    /**
     * Add an updated_on date field
     */
    public function addUpdatedOnField(string $tab = '', string $label = 'Updated At', string $format = ''): void
    {
        CRUD::field('updated_on')
            ->type('datetime')
            ->label($label)
            ->format($format ?? $this->defaultDateFormat)
            ->when(!is_null($tab), fn($field) => $field->tab($tab));
    }

    /**
     * Add an is_active switch field
     */
    public function addIsActiveField(array $options = [], string $label = 'Status', string $name = 'is_active', string $tab = ''): void
    {
        CRUD::field($name)
            ->type('switch')
            ->label($label)
            ->options($options ?? [
                0 => $this->defaultBooleanLabelFalse,
                1 => $this->defaultBooleanLabelTrue,
            ])
            ->wrapper(['class' => 'form-group col-6'])
            ->when(!is_null($tab), fn($field) => $field->tab($tab));
    }

    /**
     * Add a name text field
     */
    public function addNameField(string $tab = '', string $label = 'Name', int $limit = 0): void
    {
        CRUD::field('name')
            ->type('text')
            ->label($label)
            ->attributes([
                'maxlength' => $limit ?? $this->defaultStringLimit,
                'placeholder' => 'Enter ' . strtolower($label),
            ])
            ->when(!is_null($tab), fn($field) => $field->tab($tab));
    }

    /**
     * Add a title text field
     */
    public function addTitleField(string $tab = '', string $label = 'Title', int $limit = 0): void
    {
        CRUD::field('title')
            ->type('text')
            ->label($label)
            ->attributes([
                'maxlength' => $limit ?? $this->defaultStringLimit,
                'placeholder' => 'Enter ' . strtolower($label),
            ])
            ->when(!is_null($tab), fn($field) => $field->tab($tab));
    }

    /**
     * Add a relationship count field
     */
    public function addRelationshipCountField(
        string $relationName,
        string $label = '',
        string $tab = '',
        string $type = 'text'
    ): void {
        CRUD::field($relationName . '_count')
            ->type($type)
            ->label($label ?? ucfirst($relationName) . ' Count')
            ->value(fn($entry) => $entry->$relationName()->count())
            ->when(!is_null($tab), fn($field) => $field->tab($tab));
    }

    /**
     * Add a relationship field
     */
    public function addRelationshipField(
        string $relationName,
        string $label = '',
        string $tab = '',
        string $attribute = 'name',
        string $model = '',
        string $type = 'select2'
    ): void {
        CRUD::field($relationName)
            ->type($type . (str_contains($type, '_multiple') ? '' : '_multiple'))
            ->label($label ?? ucfirst($relationName))
            ->entity($relationName)
            ->attribute($attribute)
            ->model($model ?? 'App\Models\\' . ucfirst($relationName))
            ->when(!is_null($tab), fn($field) => $field->tab($tab));
    }

    /**
     * Add a tinymce field
     */
    public function addContentField(string $name = 'content', string $label = 'Content', string $tab = '', int $limit = 0): void
    {
        $attributes = [
            'placeholder' => 'Enter ' . strtolower($label),
        ];

        if ($limit != 0) {
            $attributes['maxlength'] = $limit;
        }

        CRUD::field($name)
            ->type('tinymce')
            ->label($label)
            ->attributes($attributes)
            ->when(!is_null($tab), fn($field) => $field->tab($tab));
    }

    /**
     * Add a select2_multiple field for many-to-many relationships
     */
    public function addSelectMultipleField(
        string $name,
        string $label,
        string $entity,
        string $attribute = 'name',
        string $model = '',
        string $tab = '',
        $options = null
    ): void {
        $model = $model ?? 'App\Models\\' . ucfirst($entity);

        CRUD::field($name)
            ->type('select2_multiple')
            ->label($label)
            ->entity($entity)
            ->attribute($attribute)
            ->model($model)
            ->pivot(true)
            ->when(!is_null($options), fn($field) => $field->options($options))
            ->when(!is_null($tab), fn($field) => $field->tab($tab));
    }

    /**
     * Add a description text area field
     */
    public function addDescriptionField(string $name = 'description', string $label = 'Description', string $tab = '', int $limit = 0, int $row = 2): void
    {
        $attributes = [
            'placeholder' => 'Enter ' . strtolower($label),
            'rows' => $row
        ];

        if ($limit != 0) {
            $attributes['maxlength'] = $limit;
        }

        CRUD::field($name)
            ->type('textarea')
            ->label($label)
            ->attributes($attributes)
            ->when(!is_null($tab), fn($field) => $field->tab($tab));
    }

    /**
     * Add a text field
     */
    public function addTextField(string $name, string $label = 'Title', int $limit = 0, string $tab = ''): void
    {
        CRUD::field($name)
            ->type('text')
            ->label($label)
            ->attributes([
                'maxlength' => $limit && $limit > 0 ? $limit : $this->defaultStringLimit,
                'placeholder' => 'Enter ' . strtolower($label),
            ])
            ->when(!is_null($tab), fn($field) => $field->tab($tab));
    }

    /**
     * Add enum field
     */
    public function addEnumField(
        string $name = 'status',
        string $label = 'Status',
        array $options = [],
        string $tab = ''
    ): void {
        CRUD::field($name)
            ->type('enum')
            ->label($label)
            ->options($options ?? [
                'active' => $this->defaultBooleanLabelTrue,
                'inactive' => $this->defaultBooleanLabelFalse,
            ])
            ->when(!is_null($tab), fn($field) => $field->tab($tab));
    }



    // COLUMN METHODS ====================================================

    /**
     * Add a created_on date column
     */
    public function addCreatedOnColumn(string $label = 'Created At', string $format = ''): void
    {
        CRUD::column('created_on')
            ->type('datetime')
            ->label($label)
            ->format($format ?? $this->defaultDateFormat);
    }

    /**
     * Add an updated_on date column
     */
    public function addUpdatedOnColumn(string $label = 'Updated At', string $format = ''): void
    {
        CRUD::column('updated_on')
            ->type('datetime')
            ->label($label)
            ->format($format ?? $this->defaultDateFormat);
    }

    /**
     * Add an is_active boolean column
     */
    public function addIsActiveColumn(string $label = 'Status', array $options = []): void
    {
        CRUD::column('is_active')
            ->type('boolean')
            ->label($label)
            ->options($options ?? [
                0 => $this->defaultBooleanLabelFalse,
                1 => $this->defaultBooleanLabelTrue,
            ]);
    }

    /**
     * Add a name text column
     */
    public function addNameColumn(string $label = 'Name', bool $searchable = true, bool $orderable = true): void
    {
        CRUD::column('name')
            ->type('text')
            ->label($label)
            ->searchLogic($searchable ? fn($query, $column, $term) =>
            $query->orWhere('name', 'like', "%{$term}%") : false)
            ->orderLogic($orderable ? fn($query, $column, $direction) =>
            $query->orderBy('name', $direction) : false);
    }

    /**
     * Add a title text column
     */
    public function addTitleColumn(string $label = 'Title', bool $searchable = true, bool $orderable = true): void
    {
        CRUD::column('title')
            ->type('text')
            ->label($label)
            ->searchLogic($searchable ? fn($query, $column, $term) =>
            $query->orWhere('title', 'like', "%{$term}%") : false)
            ->orderLogic($orderable ? fn($query, $column, $direction) =>
            $query->orderBy('title', $direction) : false);
    }

    /**
     * Add a relationship count column
     */
    public function addRelationshipCountColumn(
        string $relationName,
        string $label = '',
        bool $searchable = false,
        bool $orderable = true
    ): void {
        CRUD::column($relationName . '_count')
            ->type('text')
            ->label($label ?? ucfirst($relationName) . ' Count')
            ->value(fn($entry) => $entry->$relationName()->count())
            ->searchLogic($searchable ? fn($query, $column, $term) =>
            $query->orWhereHas($relationName, fn($q) => $q->where('name', 'like', "%{$term}%")) : false)
            ->orderLogic($orderable ? fn($query, $column, $direction) =>
            $query->withCount($relationName)->orderBy($relationName . '_count', $direction) : false);
    }

    /**
     * Add a relationship column
     */
    public function addRelationshipColumn(
        string $relationName,
        string $label = '',
        string $attribute = 'name',
        bool $searchable = true,
        bool $orderable = true
    ): void {
        CRUD::column($relationName)
            ->type('select')
            ->label($label ?? ucfirst($relationName))
            ->entity($relationName)
            ->attribute($attribute)
            ->searchLogic($searchable ? fn($query, $column, $term) =>
            $query->orWhereHas($relationName, fn($q) => $q->where($attribute, 'like', "%{$term}%")) : false)
            ->orderLogic($orderable ? fn($query, $column, $direction) =>
            $query->leftJoinRelationship($relationName)
                ->orderBy($relationName . '.' . $attribute, $direction) : false);
    }

    // CONFIGURATION METHODS ============================================

    /**
     * Set default string length limit
     */
    public function setDefaultStringLimit(int $limit): void
    {
        $this->defaultStringLimit = $limit;
    }

    /**
     * Set default date format
     */
    public function setDefaultDateFormat(string $format): void
    {
        $this->defaultDateFormat = $format;
    }

    /**
     * Set default boolean labels
     */
    public function setDefaultBooleanLabels(string $trueLabel, string $falseLabel): void
    {
        $this->defaultBooleanLabelTrue = $trueLabel;
        $this->defaultBooleanLabelFalse = $falseLabel;
    }
}
