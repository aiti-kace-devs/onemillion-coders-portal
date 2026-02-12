<?php

namespace App\Helpers;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\FilterHelper;
use App\Models\Course;
use App\Helpers\CourseFieldHelpers;
trait UserFieldHelpers
{

    use FormHelper;
    use CourseFieldHelpers;

    protected string $accountInfoTab = 'Account Info';
    protected string $rolesPermissionsTab = 'Roles & Permissions';

    protected string $profileTab = 'Profile';

    /**
     * Setup all user fields with tabs
     */
    public function setupUserFields($showPassword = true): void
    {
        $this->setFirstNameField();
        // $this->setLastNameField();
        $this->assignedCourses();
        $this->setEmailField();
        if ($showPassword) {
            $this->setPasswordField();
        }
        $this->setIsActiveField();
        // $this->setIsVerifiedField();
        $this->setRolesField();
        $this->setPermissionsField();
        $this->addPermissionsSyncScript();

        $this->twoColumnFields();
        $this->oneColumnFields(['email', 'roles', 'permissions']);
    }

    /**
     * Setup all user columns for list view
     */
    public function setupUserColumns(): void
    {
        // $this->addFirstNameColumn();
        // $this->addLastNameColumn();
        $this->addFullNameColumn();
        $this->addEmailColumn();
        // $this->addIsActiveColumn();
        // $this->addIsVerifiedColumn();
        $this->addEmailVerifiedColumn();
        $this->addRolesColumn();
        $this->addUserLastLoginColumn();
        $this->addPermissionsColumn();
    }


    public function setupStudentColumns(): void
    {
        $this->addTitleColumn();
        $this->addGenderColumn();
        $this->addAgeColumn();
        $this->addCourseField();
        $this->addConfirmedAdmissionColumn();
        FilterHelper::addBooleanColumn('shortlist', 'Shortlist');
        $this->addPhoneColumn();
    }


    public function setupShowStudentColumns(): void
    {
        $this->addFullNameColumn();
        $this->addEmailColumn();
        $this->addGenderColumn();
        $this->addPhoneColumn();
        $this->addAgeColumn();
        CRUD::column('ghcard')->label('Ghana Card Number');
        $this->addCourseField();
        $this->addConfirmedAdmissionColumn();
        FilterHelper::addBooleanColumn('shortlist', 'Shortlist');
        CRUD::column('created_at');

    }

    /**
     * Setup show columns for Manage Student preview page with full student info and actions.
     */
    public function setupManageStudentShowColumns(): void
    {
        CRUD::column('name')->label('Name');
        CRUD::column('email')->label('Email');
        CRUD::column('age')->label('Age');
        $this->addCourseField();
        CRUD::addColumn([
            'name' => 'admission_location',
            'label' => 'Location',
            'type' => 'closure',
            'function' => function ($entry) {
                $admission = $entry->admission;
                return $admission?->location ?? '-';
            },
        ]);
        CRUD::column('mobile_no')->label('Mobile Number');
        CRUD::column('ghcard')->label('Ghana Card Number');
        CRUD::addColumn([
            'name' => 'session',
            'label' => 'Session',
            'type' => 'closure',
            'function' => function ($entry) {
                $admission = $entry->admission;
                if (!$admission?->session) {
                    return '-';
                }
                $courseSession = \App\Models\CourseSession::find($admission->session);
                return $courseSession?->name ?? $admission->session;
            },
        ]);
        CRUD::column('gender')->label('Gender');
        CRUD::column('created_at')->label('Date Registered')->type('datetime');
        $this->addConfirmedAdmissionColumn('Admitted');
        CRUD::addColumn([
            'name' => 'shortlist_display',
            'label' => 'Shortlisted',
            'type' => 'closure',
            'function' => function ($entry) {
                return $entry->shortlist
                    ? '<span class="badge bg-success">Yes</span>'
                    : '<span class="badge bg-secondary">No</span>';
            },
            'escaped' => false,
        ]);
        CRUD::addColumn([
            'name' => 'score',
            'label' => 'Score',
            'type' => 'closure',
            'function' => function ($entry) {
                $latestResult = $entry->examResults()->latest()->first();
                if (!$latestResult) {
                    return '-';
                }
                $total = $latestResult->yes_ans + $latestResult->no_ans;
                $total = $total > 0 ? $total : 30;
                return round(($latestResult->yes_ans / $total) * 100) . '%';
            },
        ]);
        CRUD::addColumn([
            'name' => 'exam_status',
            'label' => 'Status',
            'type' => 'closure',
            'function' => function ($entry) {
                $latestResult = $entry->examResults()->latest()->first();
                if (!$latestResult) {
                    return '<span class="badge bg-secondary">Not Taken</span>';
                }
                $exam = $latestResult->exam;
                $passmark = $exam ? (int) $exam->passmark : 0;
                $passed = $latestResult->yes_ans >= $passmark;
                return $passed
                    ? '<span class="badge bg-success">Pass</span>'
                    : '<span class="badge bg-danger">Fail</span>';
            },
            'escaped' => false,
        ]);
    }

    public function setupProfileColumns()
    {
        // show bio, image, address
        CRUD::column('bio')
            ->value(function ($entry) {
                return $entry->userProfile->bio;
            })
            ->tab($this->profileTab);

        CRUD::column('address')
            ->value(function ($entry) {
                return $entry->userProfile->address;
            })
            ->tab($this->profileTab);

        CRUD::column('avatar')
            ->type('image')
            ->wrapper([
                'class' => 'avatar'
            ])
            ->value(function ($entry) {
                return  config('filesystems.cdn_url') . '/' . $entry->userProfile->avatar;
            })
            ->tab($this->profileTab);

        CRUD::column('gender')
            ->value(function ($entry) {
                return $entry->userProfile->gender;
            })
            ->tab($this->profileTab);

        CRUD::column('date_of_birth')
            ->type('date')
            ->value(function ($entry) {
                return $entry->userProfile->date_of_birth;
            })
            ->tab($this->profileTab);
    }

    public function setupUserFilters()
    {
        FilterHelper::addLoggedInTodayFilter();
        FilterHelper::addIsVerifiedFilter();
        $roleModel = backpack_user()->hasRole('super-admin') ? new \Spatie\Permission\Models\Role() : \Spatie\Permission\Models\Role::whereNot('name', 'super-admin');
        $roles = $roleModel->select('name', 'id')->get()->map(function ($role) {
            return [$role->id => $role->name];
        })->all();
        $rolesArray = array_reduce($roles, function ($result, $subArray) {
            return $result + $subArray; // Preserves keys
        }, []);

        $roles = array_merge(...array_values($roles));
        FilterHelper::addSelectFilter(columnName: 'roles', label: 'Roles', options: $rolesArray, callback: function ($values) {
            CRUD::addClause('whereHas', 'roles', function ($query) use ($values) {
                $query->whereIn('roles.id', array_values($values));
            });
        });
        // FilterHelper::addIsActiveFilter();
        FilterHelper::addCreatedOnDateRangeFilter();
    }

    // Field Methods
    public function setFirstNameField(): void
    {
        CRUD::field('name')
            ->type('text')
            ->label('Full Name')
            ->tab($this->accountInfoTab);
    }

    public function setLastNameField(): void
    {
        CRUD::field('last_name')
            ->type('text')
            ->label('Last Name')
            ->tab($this->accountInfoTab);
    }

    public function setEmailField(): void
    {
        CRUD::field('email')
            ->type('email')
            ->label(trans('backpack::permissionmanager.email'))
            ->wrapper(['class' => 'form-group col-6'])
            ->tab($this->accountInfoTab);
    }



    public function setPasswordField(): void
    {
        CRUD::field('password')
            ->type('password')
            ->label('Password')
            ->tab($this->accountInfoTab);

        CRUD::field('password_confirmation')
            ->type('password')
            ->label('Confirm Password')
            ->tab($this->accountInfoTab);
    }

    public function setIsActiveField(): void
    {
        CRUD::field('is_super')
            ->type('switch')
            ->label('Is Super')
            ->tab($this->accountInfoTab);
    }

    public function setIsVerifiedField(): void
    {
        CRUD::field('email_verified_at')
            ->type('switch')
            ->label('Verify Email')
            ->tab($this->accountInfoTab);
    }

    public function assignedCourses(): void
    {
        CRUD::addField([
            'name' => 'courses',
            'type' => 'select2_multiple',
            'label' => 'Assign Course',
            'entity' => 'assignedCourses',
            'model' => 'App\\Models\\Course',
            'attribute' => 'course_name',
            'pivot' => true,
            'tab' => 'Account Info',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);
    }



    public function setRolesField(): void
    {
        CRUD::field('roles')
            ->type('select2_multiple')
            ->label(trans('backpack::permissionmanager.roles'))
            ->entity('roles')
            ->attribute('name')
            ->model(config('permission.models.role'))
            ->pivot(true)
            ->tab($this->rolesPermissionsTab)
            ->wrapper(['class' => 'form-group col-md-6']);
    }

    public function setPermissionsField(): void
    {
        CRUD::field('permissions')
            ->type('select2_multiple')
            ->label(mb_ucfirst(trans('backpack::permissionmanager.permission_plural')))
            ->entity('permissions')
            ->attribute('name')
            ->model(config('permission.models.permission'))
            ->pivot(true)
            ->tab($this->rolesPermissionsTab)
            ->wrapper(['class' => 'form-group col-md-6']);
    }

    // Column Methods
    public function addFullNameColumn(): void
    {
        CRUD::column('name')
            ->label('Full Name')
            ->searchLogic(function ($query, $column, $searchTerm) {
                $query->orWhere('name', 'like', '%' . $searchTerm . '%');
            })->tab($this->accountInfoTab);
    }


    public function addFirstNameColumn(): void
    {
        CRUD::column('first_name')
            ->label('First Name')
            ->searchLogic(function ($query, $column, $searchTerm) {
                $query->orWhere('first_name', 'like', '%' . $searchTerm . '%');
            })->tab($this->accountInfoTab);
    }



    public function addGenderColumn(): void
    {
        CRUD::column('gender')
            ->label('Gender')
            ->searchLogic(function ($query, $column, $searchTerm) {
                $query->orWhere('gender', 'like', '%' . $searchTerm . '%');
            })->tab($this->accountInfoTab);
    }



    public function addPhoneColumn(): void
    {
        CRUD::column('mobile_no')
            ->label('Mobile No')
            ->searchLogic(function ($query, $column, $searchTerm) {
                $query->orWhere('mobile_no', 'like', '%' . $searchTerm . '%');
            })->tab($this->accountInfoTab);
    }



    public function addAgeColumn(): void
    {
        CRUD::column('age')
            ->label('Age')
            ->searchLogic(function ($query, $column, $searchTerm) {
                $query->orWhere('age', 'like', '%' . $searchTerm . '%');
            })->tab($this->accountInfoTab);
    }

public function addTitleColumn(): void
{
    CRUD::addColumn([
        'name' => 'name_email',
        'label' => 'Fullname (email)',
        'type' => 'model_function',
        'function_name' => 'getNameWithEmail',
        'tab' => $this->accountInfoTab,
        'searchLogic' => function ($query, $column, $searchTerm) {
            $query->orWhere('name', 'like', '%' . $searchTerm . '%')
                ->orWhere('email', 'like', '%' . $searchTerm . '%');
        },
    ]);
}



    public function addLastNameColumn(): void
    {
        CRUD::column('last_name')
            ->label('Last Name')
            ->searchLogic(function ($query, $column, $searchTerm) {
                $query->orWhere('last_name', 'like', '%' . $searchTerm . '%');
            })->tab($this->accountInfoTab);
    }

    public function addEmailColumn(): void
    {
        CRUD::column('email')
            ->label(trans('backpack::permissionmanager.email'))
            ->searchLogic(function ($query, $column, $searchTerm) {
                $query->orWhere('email', 'like', '%' . $searchTerm . '%');
            })->tab($this->accountInfoTab);
    }


    public function addIsActiveColumn(): void
    {
        CRUD::column('is_active')
            ->label('Active')
            ->type('text')
            ->limit(200)
            ->value(function ($entry) {
                if ($entry->is_active) {
                    return '<span class="badge bg-success">Yes</span>';
                } else {
                    return '<span class="badge bg-danger">No</span>';
                }
            })
            ->tab($this->accountInfoTab)
            ->escaped(false);
    }


    public function addEmailVerifiedColumn(): void
    {
        CRUD::addColumn([
            'name' => 'email_verified_at',
            'label' => 'Email Verified',
            'type' => 'text',
            'limit' => 200,
            'value' => function ($entry) {
                if ($entry->email_verified_at) {
                    return '<span>	&#x2705;</span>';
                } else {
                    return '<span class="badge bg-danger">No</span>';
                }
            },
            'escaped' => false,
            'tab' => $this->accountInfoTab,
        ]);
    }



    public function addUserLastLoginColumn(): void
    {
        CRUD::column('last_login')
            ->label('Last Login')
            ->type('text')
            ->value(function ($entry) {
                if (!$entry->last_login) {
                    return '-';
                }
                return \Carbon\Carbon::parse($entry->last_login)->format('Y-m-d H:i:s');
            })->tab($this->accountInfoTab);
    }




    public function addIsVerifiedColumn(): void
    {
        CRUD::addColumn([
            'name' => 'email_verified_at',
            'label' => 'Verified',
            'type' => 'text',
            'limit' => 200,
            'value' => function ($entry) {
                if ($entry->email_verified_at) {
                    return '<span>	&#x2705;</span>';
                } else {
                    return '<span class="badge bg-danger">No</span>';
                }
            },
            'escaped' => false,
            'tab' => $this->accountInfoTab,
        ]);
    }

    public function addRolesColumn(): void
    {
        CRUD::column('roles')
            ->label(trans('backpack::permissionmanager.roles'))
            ->type('select_multiple')
            ->entity('roles')
            ->attribute('name')
            ->model(config('permission.models.role'))
            ->tab($this->rolesPermissionsTab);
    }



    public function addPermissionsColumn(): void
    {
        CRUD::column('permissions')
            ->label(mb_ucfirst(trans('backpack::permissionmanager.permission_plural')))
            ->type('select_multiple')
            ->entity('permissions')
            ->attribute('name')
            ->model(config('permission.models.permission'))
            ->tab($this->rolesPermissionsTab);
    }

    public function addPermissionsSyncScript(): void
    {
        CRUD::field('custom-sync-permission')
            ->type('view')
            ->view('admin.js.sync-permission')
            ->tab($this->rolesPermissionsTab);
    }

    // public function removeRolesAndPermission(){
    //     CRUD::removeColumn()
    // }


    /**
     * Override default tab names
     */
    public function setUserFieldTabs(string $accountInfoTab, string $rolesPermissionsTab): void
    {
        $this->accountInfoTab = $accountInfoTab;
        $this->rolesPermissionsTab = $rolesPermissionsTab;
    }
}
