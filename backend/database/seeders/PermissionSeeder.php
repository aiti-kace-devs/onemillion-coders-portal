<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Admin;
use Exception;

class PermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define resources based on menu items
        $resources = [
            'dashboard',
            'admin',
            'role',
            'batch',
            'user-admission',
            'student-verification',
            'filemanager',
            'branch',
            'centre',
            'programme',
            'course',
            'course-session',
            'course-category',
            'course-module',
            'course-certification',
            'course-match',
            'course-match-option',
            'attendance',
            'form',
            'category',
            'manage-exam',
            'qr-scanner',
            'manage-student',
            'student',
            'email-template',
            'sms-template',
            'app-config'
        ];

        // Define actions
        $actions = ['create', 'read', 'update', 'delete', 'status'];

        // Define scoped actions (actions that can be scoped)
        $scopedActions = ['read', 'update', 'delete'];

        // Define extra scopes
        $extraScopes = ['all', 'self'];

        // Define special student actions
        $specialStudentActions = ['shortlist', 'admit', 'bulk-sms', 'bulk-email', 'verify', 'assign-batch'];

        // Define special permissions
        $specialPermissions = ['monitor', 'config', 'page-editor', 'manager', 'permission', 'export', 'import'];

        // Define resource statuses for specific resources
        $resourceStatuses = [
            'student' => ['all', 'active', 'inactive', 'verified', 'unverified'],
            'user-admission' => ['all', 'pending', 'approved', 'rejected', 'shortlisted'],
            'course' => ['all', 'active', 'inactive', 'published', 'draft'],
            'attendance' => ['all', 'present', 'absent', 'late'],
            'manage-exam' => ['all', 'active', 'inactive', 'completed', 'pending'],
        ];

        // Define roles
        $roles = [
            'super-admin',
            'admission-officer',
            'notification-officer',
            'administrator',
            'app-administrator',
            'attendance-officer',
            'page-builder',
            'course-manager',
            'exam-manager',
            'student-manager',
            'centre-manager'
        ];

        // Create basic permissions for all resources
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                if (in_array($action, $scopedActions)) {
                    foreach ($extraScopes as $scope) {
                        $permissionName = "$resource.$action.$scope";
                        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'admin']);
                    }
                } else {
                    $permissionName = "$resource.$action";
                    Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'admin']);
                }
            }
        }

        // Create special student permissions
        foreach ($specialStudentActions as $action) {
            $permissionName = "user.$action";
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'admin']);
        }

        // Create special management permissions
        foreach ($specialPermissions as $action) {
            $permissionName = "manage.$action";
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'admin']);
        }

        // Create resource status permissions
        foreach ($resourceStatuses as $resource => $statuses) {
            foreach ($statuses as $status) {
                $permissionName = "$resource.status.$status";
                Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'admin']);
            }
        }

        // Create roles
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'admin']);
        }

        // SUPER ADMIN ROLE - All permissions
        $superAdminRole = Role::findByName('super-admin', 'admin');
        $superAdminRole->permissions()->sync(Permission::pluck('id')->toArray());

        // ADMISSION OFFICER ROLE
        $admissionOfficerRole = Role::findByName('admission-officer', 'admin');
        $admissionOfficerPermissions = $this->findResourcePermissions(
            ['student', 'user-admission', 'student-verification', 'batch'],
            ['create', 'read', 'update'],
            ['all', 'self'],
            ['student' => ['shortlist', 'admit', 'verify', 'assign-batch']]
        );
        $admissionOfficerRole->permissions()->sync($admissionOfficerPermissions->pluck('id')->toArray());

        // NOTIFICATION OFFICER ROLE
        $notificationOfficerRole = Role::findByName('notification-officer', 'admin');
        $notificationOfficerPermissions = $this->findResourcePermissions(
            ['sms-template', 'email-template', 'student'],
            ['create', 'read', 'update', 'delete'],
            ['all'],
            ['student' => ['bulk-sms', 'bulk-email']]
        );
        $notificationOfficerRole->permissions()->sync($notificationOfficerPermissions->pluck('id')->toArray());

        // ATTENDANCE OFFICER ROLE
        $attendanceOfficerRole = Role::findByName('attendance-officer', 'admin');
        $attendanceOfficerPermissions = $this->findResourcePermissions(
            ['attendance', 'qr-scanner', 'student-verification', 'student', 'course'],
            ['create', 'read', 'update'],
            ['all', 'self'],
            ['attendance' => ['present', 'absent', 'late'], 'qr-scanner' => ['scan', 'generate'], 'student-verification' => ['verify']]
        );
        $attendanceOfficerRole->permissions()->sync($attendanceOfficerPermissions->pluck('id')->toArray());

        // COURSE MANAGER ROLE
        $courseManagerRole = Role::findByName('course-manager', 'admin');
        $courseManagerPermissions = $this->findResourcePermissions(
            ['course', 'course-session', 'course-category', 'course-module', 'course-certification', 'course-match', 'course-match-option', 'programme'],
            ['create', 'read', 'update', 'delete'],
            ['all'],
            ['course' => ['active', 'inactive', 'published', 'draft']]
        );
        $courseManagerRole->permissions()->sync($courseManagerPermissions->pluck('id')->toArray());

        // EXAM MANAGER ROLE
        $examManagerRole = Role::findByName('exam-manager', 'admin');
        $examManagerPermissions = $this->findResourcePermissions(
            ['manage-exam', 'category', 'qr-scanner'],
            ['create', 'read', 'update', 'delete'],
            ['all'],
            ['manage-exam' => ['active', 'inactive', 'completed', 'pending']]
        );
        $examManagerRole->permissions()->sync($examManagerPermissions->pluck('id')->toArray());

        // STUDENT MANAGER ROLE
        $studentManagerRole = Role::findByName('student-manager', 'admin');
        $studentManagerPermissions = $this->findResourcePermissions(
            ['student', 'user-admission', 'form', 'manage-student'],
            ['create', 'read', 'update', 'delete'],
            ['all', 'self'],
            ['student' => ['shortlist', 'verify'], 'user-admission' => ['pending', 'approved', 'rejected', 'shortlisted']]
        );
        $studentManagerRole->permissions()->sync($studentManagerPermissions->pluck('id')->toArray());

        // CENTRE MANAGER ROLE
        $centreManagerRole = Role::findByName('centre-manager', 'admin');
        $centreManagerPermissions = $this->findResourcePermissions(
            ['centre'],
            ['read'],
            ['self']
        );
        $centreManagerPermissions = $centreManagerPermissions->merge(
            $this->findResourcePermissions(['dashboard'], ['read'], ['all'])
        );
        $centreManagerRole->permissions()->sync($centreManagerPermissions->pluck('id')->toArray());

        // ADMINISTRATOR ROLE
        $administratorRole = Role::findByName('administrator', 'admin');
        $administratorPermissions = $this->findResourcePermissions(
            ['student', 'user-admission', 'course', 'attendance', 'form', 'manage-exam', 'branch', 'centre', 'programme'],
            ['create', 'read', 'update', 'delete'],
            ['all'],
            ['student' => ['shortlist', 'admit', 'verify'], 'user-admission' => ['pending', 'approved', 'rejected', 'shortlisted']]
        );
        $administratorRole->permissions()->sync($administratorPermissions->pluck('id')->toArray());

        // APP ADMINISTRATOR ROLE
        $appAdministratorRole = Role::findByName('app-administrator', 'admin');
        $appAdministratorPermissions = $this->findResourcePermissions(
            ['app-config', 'email-template', 'sms-template'],
            ['create', 'read', 'update', 'delete'],
            ['all']
        );
        $appAdministratorPermissions = $appAdministratorPermissions->merge(
            $this->findResourcePermissions(['manage'], ['monitor', 'config', 'manager'], ['all'])
        );
        $appAdministratorRole->permissions()->sync($appAdministratorPermissions->pluck('id')->toArray());

        // PAGE BUILDER ROLE
        $pageBuilderRole = Role::findByName('page-builder', 'admin');
        $pageBuilderPermissions = $this->findResourcePermissions(
            ['manage'],
            ['page-editor'],
            ['all']
        );
        $pageBuilderRole->permissions()->sync($pageBuilderPermissions->pluck('id')->toArray());

        // Try granting super admin role to admin user from config
        try {
            $superAdminUser = Admin::where('email', config('app.super_admin_email'))->first();
            if ($superAdminUser) {
                $superAdminUser->syncRoles($superAdminRole);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    private function findResourcePermissions($resources, $actions, $scopes, $customStatuses = [])
    {
        $permissionNames = [];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                if (in_array($action, ['read', 'update', 'delete'])) {
                    foreach ($scopes as $scope) {
                        $permissionNames[] = "$resource.$action.$scope";
                    }
                } else {
                    $permissionNames[] = "$resource.$action";
                }
            }

            if (array_key_exists($resource, $customStatuses)) {
                foreach ($customStatuses[$resource] as $status) {
                    $permissionNames[] = "$resource.status.$status";
                }
            }
        }

        return Permission::whereIn('name', $permissionNames)->get();
    }
}
