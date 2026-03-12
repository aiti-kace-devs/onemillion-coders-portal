<?php

namespace App\Helpers;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Helper for configuring the List operation in Backpack CRUD controllers.
 */
class CrudListHelper
{
    /**
     * Put all line action buttons (edit, show, delete) under a dropdown.
     *
     * Replaces the default edit, preview, and delete buttons with a single
     * "Actions" dropdown containing all available actions. Use this instead
     * of Backpack's built-in lineButtonsAsDropdown when you need edit
     * inside the dropdown (Backpack's JS has a bug where 0 defaults to 1).
     *
     * @param array $extraDropdownButtons View names of additional buttons to include in the dropdown
     *                                    (e.g. ['crud::buttons.custom_action'])
     * @return void
     *
     * @example
     * protected function setupListOperation()
     * {
     *     CrudListHelper::editInDropdown();
     *     // ... columns, filters, etc.
     * }
     *
     * @example With custom action in dropdown
     * CrudListHelper::editInDropdown(['crud::buttons.custom_action']);
     */
    public static function editInDropdown(array $extraDropdownButtons = []): void
    {
        CRUD::removeButton('update', 'line');
        CRUD::removeButton('show', 'line');
        CRUD::removeButton('delete', 'line');

        CRUD::setOperationSetting('rowActionsDropdownExtraButtons', $extraDropdownButtons);
        CRUD::addButtonFromView('line', 'row_actions_dropdown', 'view', 'crud::buttons.row_actions_dropdown', 'end');

        // Disable Backpack's dropdown logic since we provide our own
        CRUD::setOperationSetting('lineButtonsAsDropdown', false);
    }
}
