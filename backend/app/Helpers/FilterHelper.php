<?php


namespace App\Helpers;
use App\Models\Course;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\Log;

class FilterHelper
{
    /**
     * Converts a GCS path to a media ID and updates the request.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $columnName
     */


    /**
     * Adds a generic "Has/No Value" dropdown filter for a nullable column.
     *
     * @param string $columnName
     * @param string|null $label
     * @return void
     */
    public static function addNullableColumnFilter(string $filterName, string $columnName, string $label = null)
    {
        $label = $label ?? ucwords(str_replace('_', ' ', $columnName));

        CRUD::filter($filterName)
            ->type('dropdown')
            ->label($label)
            ->values([
                '' => 'All Records',
                'has_value' => "Has $label",
                'no_value' => "Not $label"
            ])
            ->whenActive(function ($value) use ($columnName) {
                if ($value === 'has_value') {
                    CRUD::addClause('whereNotNull', $columnName);
                } elseif ($value === 'no_value') {
                    CRUD::addClause('whereNull', $columnName);
                }
            });
    }






    public static function addSelectFilter($columnName, $label = null, array $options = [], $type = 'select2_multiple', $callback = null)
    {
        $label = $label ?? ucwords(str_replace('_', ' ', $columnName));

        CRUD::filter($columnName)
            ->type($type)
            ->label($label)
            ->values($options)
            ->whenActive(function ($value) use ($columnName, $type, $callback) {
                if (in_array($type, ['select2_multiple', 'multiselect'])) {
                    $decoded = is_array($value) ? $value : json_decode($value, true);
                    if (is_array($decoded)) {
                        if ($callback) {
                            $callback($decoded);
                        } else {
                            CRUD::addClause('whereIn', $columnName, $decoded);
                        }
                    }
                } else {
                    if ($callback) {
                        $callback($value);
                    } else {
                        CRUD::addClause('where', $columnName, $value);
                    }
                }
            });
    }



    public static function addOngoingExamsFilter(string $label): void
{
    CRUD::filter('ongoing')
        ->type('simple')
        ->label($label)
        ->whenActive(function () {
            CRUD::addClause('whereDate', 'exam_date', '>=', now()->toDateString());
        });
}

    public static function addDateRangeFilter($columnName, $label = null, array $pickerOptions = [])
    {
        $label = $label ?? ucwords(str_replace('_', ' ', $columnName));

        CRUD::filter($columnName)
            ->type('date_range')
            ->label($label)
            ->date_range_options($pickerOptions)
            ->whenActive(function ($value) use ($columnName) {
                $dates = json_decode($value);

                if (!empty($dates->from)) {
                    CRUD::addClause('where', $columnName, '>=', $dates->from);
                }

                if (!empty($dates->to)) {
                    CRUD::addClause('where', $columnName, '<=', $dates->to);
                }
            });
    }



    public static function addBooleanColumn(string $columnName, string $permissionName, string $label = null)
    {
        $user = backpack_user();
        if (!$user) {
            return;
        }
        if (!$user->can($permissionName)) {
            return;
        }
        $label = $label ?? strtoupper(str_replace('_', ' ', $columnName));
        CRUD::addColumn([
            'name' => $columnName,
            'label' => $label,
            'type' => 'closure',
            'function' => function ($entry) use ($columnName) {
                if ($entry->{$columnName}) {
                    return '<span>	&#x2705;</span>';
                } else {
                    return '<span class="badge bg-danger">No</span>';
                }
            },
            'escaped' => false,
        ]);
    }




        public static function addGenericRelationshipColumn(string $methodName, string $label, string $pathName, string $columnName = null)
{
    CRUD::addColumn([
        'name' => $methodName,
        'label' => $label,
        'type' => 'closure',
        'function' => function($entry) use ($methodName, $pathName, $columnName) {
            if ($entry->$methodName) {
                $url = backpack_url($pathName . '/' . $entry->$methodName->id . '/show');
                return '<a href="' . $url . '">' . e($entry->$methodName->$columnName) . '</a>';
            }
            return '';
        },
        'escaped' => false,
    ]);
}




        public static function addCategoryColumn()
{
    CRUD::addColumn([
        'name' => 'categoryRelation',
        'label' => 'Category',
        'type' => 'closure',
        'function' => function($entry) {
            if ($entry->categoryRelation) {
                $url = backpack_url( 'category/' . $entry->categoryRelation->id . '/show');
                return '<a href="' . $url . '">' . e($entry->categoryRelation->name) . '</a>';
            }
            return '';
        },
        'escaped' => false,
    ]);
}



public static function addBooleanFilter(string $columnName, ?string $permissionName = null, ?string $label = null)
{
    $user = backpack_user();

    if (!$user) {
        return;
    }

    if (!$user->can($permissionName)) {
        return;
    }

    $label = $label ?? ucwords(str_replace('_', ' ', $columnName));

    CRUD::filter($columnName)
        ->type('dropdown')
        ->label($label)
        ->values([
            'true' => 'Yes',
            'false' => 'No',
        ])
        ->whenActive(function ($value) use ($columnName) {
            $booleanValue = $value === 'true';
            CRUD::addClause('where', $columnName, $booleanValue);
        });
}




    /**
     * Add a date range filter for created_on field
     */
    public static function addCreatedOnDateRangeFilter(): void
    {
        CRUD::filter('created_on')
            ->type('date_range')
            ->date_range_options([
                'timePicker' => true
            ])
            ->label('CREATED AT')
            ->whenActive(function ($value) {
                $dates = json_decode($value);
                CRUD::addClause('where', 'created_on', '>=', $dates->from);
                CRUD::addClause('where', 'created_on', '<=', $dates->to);
            });
    }



    public static function addLoggedInTodayFilter(): void
    {
        if (!backpack_user()->hasRole('super-admin')) {
            return;
        }
        CRUD::filter('last_login')
            ->type('date_range')
            ->label('Last Login Range')
            ->date_range_options([
                'timePicker' => false
            ])
            ->whenActive(function ($value) {
                $dates = json_decode($value);
                CRUD::addClause('where', 'last_login', '>=', $dates->from);
                CRUD::addClause('where', 'last_login', '<=', $dates->to);
            });
    }


    /**
     * Add a boolean dropdown filter for is_verified field
     */
    public static function addIsVerifiedFilter(): void
    {
        if (!backpack_user()->hasRole('super-admin')) {
            return;
        }

        CRUD::filter('is_verified')
            ->type('simple')
            ->whenActive(function () {
                CRUD::addClause('whereNotNull', 'email_verified_at');
            });
    }

    public static function addIsActiveFilter(): void
    {
        
        CRUD::filter('is_active')
            ->type('simple')
            ->whenActive(function () {
                CRUD::addClause('where', 'is_active', true);
            });
    }



    public static function addExpiringSoonFilter(): void
    {
        CRUD::addFilter([
            'name'  => 'expires_soon',
            'type'  => 'simple',
            'label' => 'Expiring Soon (7 days)',
        ], 
        false, 
        function () {
            $from = \Carbon\Carbon::now();
            $to = \Carbon\Carbon::now()->addDays(7);

            CRUD::addClause('whereBetween', 'expires', [$from, $to]);
        });
    }








    public static function addAgeRangeFilter(string $label = 'Age Group')
{
    CRUD::addFilter([
        'name'  => 'age_range',
        'type'  => 'dropdown',
        'label' => $label,
    ], [
        '15-19' => '15 - 19 years',
        '20-24' => '20 - 24 years',
        '25-35' => '25 - 35 years',
        '36-45' => '36 - 45 years',
        '45+'   => '45+ years',
    ],
    function ($value) {
        CRUD::addClause('where', 'age', 'LIKE', '%' . $value . '%');
    });
}





    public static function addGenderFilter(string $label = 'Gender')
{
    CRUD::addFilter([
        'name'  => 'gender',
        'type'  => 'dropdown',
        'label' => $label,
    ], [
        'male' => 'Male',
        'female' => 'Female',
    ], function ($value) {
        CRUD::addClause('where', 'gender', $value);
    });
}









}
