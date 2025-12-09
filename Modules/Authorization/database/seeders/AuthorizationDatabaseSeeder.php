<?php

namespace Modules\Authorization\Database\Seeders;

use Modules\Authentication\Entities\User;
use Illuminate\Database\Seeder;
use Modules\Authorization\Entities\AuthObject;
use Modules\Authorization\Entities\AuthObjectField;
use Modules\Authorization\Entities\Role;
use Modules\Authorization\Entities\RoleAuthorization;
use Modules\Authorization\Entities\RoleAuthorizationField;

class AuthorizationDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create SuperAdmin role (bypasses all authorization checks)
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'SuperAdmin'],
            [
                'description' => 'Super Administrator - Has full access to everything. Bypasses all authorization checks.',
            ]
        );

        // Create SuperReadOnly role (bypasses authorization for read-only operations)
        $superReadOnlyRole = Role::firstOrCreate(
            ['name' => 'SuperReadOnly'],
            [
                'description' => 'Super Read-Only Administrator - Has read-only access to everything. Bypasses authorization for display/view operations (ACTVT = 03).',
            ]
        );

        // Create roles
        $salesClerk = Role::firstOrCreate(
            ['name' => 'SALES_CLERK'],
            [
                'description' => 'Sales clerk with limited order access',
            ]
        );

        $apManager = Role::firstOrCreate(
            ['name' => 'AP_MANAGER'],
            [
                'description' => 'Accounts Payable Manager with financial document access',
            ]
        );

        $salesManager = Role::firstOrCreate(
            ['name' => 'SALES_MANAGER'],
            [
                'description' => 'Sales Manager with full sales order access',
            ]
        );

        $customerService = Role::firstOrCreate(
            ['name' => 'CUSTOMER_SERVICE'],
            [
                'description' => 'Customer service representative with customer master access',
            ]
        );

        // Create Auth Objects
        $salesOrderHeader = AuthObject::firstOrCreate(
            ['code' => 'SALES_ORDER_HEADER'],
            ['description' => 'Sales Order Header Authorization']
        );

        $finDocument = AuthObject::firstOrCreate(
            ['code' => 'FIN_DOCUMENT'],
            ['description' => 'Financial Document Authorization']
        );

        $customerMaster = AuthObject::firstOrCreate(
            ['code' => 'CUSTOMER_MASTER'],
            ['description' => 'Customer Master Data Authorization']
        );

        // Create Authorization Module access object
        $authorizationModule = AuthObject::firstOrCreate(
            ['code' => 'AUTHORIZATION_MODULE'],
            ['description' => 'Authorization Module Access - Controls access to the Authorization administration module']
        );

        // Create ACTVT field for AUTHORIZATION_MODULE if it doesn't exist
        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $authorizationModule->id,
                'code' => 'ACTVT',
            ],
            [
                'label' => 'Activity',
                'is_org_level' => false,
                'sort' => 1,
            ]
        );

        // Create Authentication Settings access object
        $authenticationSettings = AuthObject::firstOrCreate(
            ['code' => 'AUTHENTICATION_SETTINGS'],
            ['description' => 'Authentication Settings - Controls access to manage authentication settings (email verification, 2FA, registration, etc.)']
        );

        // Create Dashboard Access object
        $dashboardAccess = AuthObject::firstOrCreate(
            ['code' => 'DASHBOARD_ACCESS'],
            ['description' => 'Dashboard Access - Controls access to the main application dashboard']
        );

        // Create Tenant Management object
        $tenantManagement = AuthObject::firstOrCreate(
            ['code' => 'TENANT_MANAGEMENT'],
            ['description' => 'Tenant Management - Controls access to manage tenants (create, update, delete)']
        );

        // Create User Management object
        $userManagement = AuthObject::firstOrCreate(
            ['code' => 'USER_MANAGEMENT'],
            ['description' => 'User Management - Controls access to create and manage users']
        );

        // Create Profile Management object
        $profileManagement = AuthObject::firstOrCreate(
            ['code' => 'PROFILE_MANAGEMENT'],
            ['description' => 'Profile Management - Controls access to view and edit own profile']
        );

        // Create Authorization Debug object
        $authorizationDebug = AuthObject::firstOrCreate(
            ['code' => 'AUTHORIZATION_DEBUG'],
            ['description' => 'Authorization Debug - Controls access to authorization debug tools and SU53 views']
        );

        // Create Transport Management object
        $transportManagement = AuthObject::firstOrCreate(
            ['code' => 'TRANSPORT_MANAGEMENT'],
            ['description' => 'Transport Management - Controls access to manage transport requests (CTS)']
        );

        // Create Todo Management object
        $todoManagement = AuthObject::firstOrCreate(
            ['code' => 'TODO_MANAGEMENT'],
            ['description' => 'Todo Management - Controls access to manage todos']
        );

        // Create UI Management object
        $uiManagement = AuthObject::firstOrCreate(
            ['code' => 'UI_MANAGEMENT'],
            ['description' => 'UI Management - Controls access to manage UI resources']
        );

        // Create Company Management object
        $companyManagement = AuthObject::firstOrCreate(
            ['code' => 'COMPANY_MANAGEMENT'],
            ['description' => 'Company Management - Controls access to manage companies']
        );

        // Create Clipboard Management object
        $clipboardManagement = AuthObject::firstOrCreate(
            ['code' => 'CLIPBOARD_MANAGEMENT'],
            ['description' => 'Clipboard Management - Controls access to manage clipboard items']
        );

        // Create Auth Object Fields for CLIPBOARD_MANAGEMENT
        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $clipboardManagement->id,
                'code' => 'ACTVT',
            ],
            [
                'label' => 'Activity',
                'is_org_level' => false,
                'sort' => 1,
            ]
        );

        // Create Auth Object Fields for SALES_ORDER_HEADER
        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $salesOrderHeader->id,
                'code' => 'ACTVT',
            ],
            [
                'label' => 'Activity',
                'is_org_level' => false,
                'sort' => 1,
            ]
        );

        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $salesOrderHeader->id,
                'code' => 'COMP_CODE',
            ],
            [
                'label' => 'Company Code',
                'is_org_level' => true,
                'sort' => 2,
            ]
        );

        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $salesOrderHeader->id,
                'code' => 'SALES_ORG',
            ],
            [
                'label' => 'Sales Organization',
                'is_org_level' => true,
                'sort' => 3,
            ]
        );

        // Create Auth Object Fields for FIN_DOCUMENT
        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $finDocument->id,
                'code' => 'ACTVT',
            ],
            [
                'label' => 'Activity',
                'is_org_level' => false,
                'sort' => 1,
            ]
        );

        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $finDocument->id,
                'code' => 'COMP_CODE',
            ],
            [
                'label' => 'Company Code',
                'is_org_level' => true,
                'sort' => 2,
            ]
        );

        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $finDocument->id,
                'code' => 'DOC_TYPE',
            ],
            [
                'label' => 'Document Type',
                'is_org_level' => false,
                'sort' => 3,
            ]
        );

        // Create Auth Object Fields for CUSTOMER_MASTER
        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $customerMaster->id,
                'code' => 'ACTVT',
            ],
            [
                'label' => 'Activity',
                'is_org_level' => false,
                'sort' => 1,
            ]
        );

        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $customerMaster->id,
                'code' => 'CUSTOMER_GROUP',
            ],
            [
                'label' => 'Customer Group',
                'is_org_level' => false,
                'sort' => 2,
            ]
        );

        // Create Auth Object Fields for AUTHENTICATION_SETTINGS
        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $authenticationSettings->id,
                'code' => 'ACTVT',
            ],
            [
                'label' => 'Activity',
                'is_org_level' => false,
                'sort' => 1,
            ]
        );

        // Create Role Authorizations for SALES_CLERK
        $salesClerkAuth1 = RoleAuthorization::create([
            'role_id' => $salesClerk->id,
            'auth_object_id' => $salesOrderHeader->id,
            'label' => 'Sales Clerk - Display Orders for Company 1000',
        ]);

        RoleAuthorizationField::create([
            'role_authorization_id' => $salesClerkAuth1->id,
            'field_code' => 'ACTVT',
            'operator' => '=',
            'value_from' => '03',
        ]);

        RoleAuthorizationField::create([
            'role_authorization_id' => $salesClerkAuth1->id,
            'field_code' => 'COMP_CODE',
            'operator' => '=',
            'value_from' => '1000',
        ]);

        RoleAuthorizationField::create([
            'role_authorization_id' => $salesClerkAuth1->id,
            'field_code' => 'SALES_ORG',
            'operator' => 'in',
            'value_from' => '2000,2001,2002',
        ]);

        // Create Role Authorizations for AP_MANAGER
        $apManagerAuth1 = RoleAuthorization::create([
            'role_id' => $apManager->id,
            'auth_object_id' => $finDocument->id,
            'label' => 'AP Manager - Full Access to Financial Documents',
        ]);

        RoleAuthorizationField::create([
            'role_authorization_id' => $apManagerAuth1->id,
            'field_code' => 'ACTVT',
            'operator' => '*',
        ]);

        RoleAuthorizationField::create([
            'role_authorization_id' => $apManagerAuth1->id,
            'field_code' => 'COMP_CODE',
            'operator' => 'in',
            'value_from' => '1000,2000,3000',
        ]);

        RoleAuthorizationField::create([
            'role_authorization_id' => $apManagerAuth1->id,
            'field_code' => 'DOC_TYPE',
            'operator' => 'in',
            'value_from' => 'KR,DR,AB',
        ]);

        // Create Role Authorizations for SALES_MANAGER
        $salesManagerAuth1 = RoleAuthorization::create([
            'role_id' => $salesManager->id,
            'auth_object_id' => $salesOrderHeader->id,
            'label' => 'Sales Manager - Full Access to Sales Orders',
        ]);

        RoleAuthorizationField::create([
            'role_authorization_id' => $salesManagerAuth1->id,
            'field_code' => 'ACTVT',
            'operator' => '*',
        ]);

        RoleAuthorizationField::create([
            'role_authorization_id' => $salesManagerAuth1->id,
            'field_code' => 'COMP_CODE',
            'operator' => '*',
        ]);

        RoleAuthorizationField::create([
            'role_authorization_id' => $salesManagerAuth1->id,
            'field_code' => 'SALES_ORG',
            'operator' => '*',
        ]);

        // Create Role Authorizations for CUSTOMER_SERVICE
        $customerServiceAuth1 = RoleAuthorization::create([
            'role_id' => $customerService->id,
            'auth_object_id' => $customerMaster->id,
            'label' => 'Customer Service - Display and Change Customer Data',
        ]);

        RoleAuthorizationField::create([
            'role_authorization_id' => $customerServiceAuth1->id,
            'field_code' => 'ACTVT',
            'operator' => 'in',
            'value_from' => '02,03',
        ]);

        RoleAuthorizationField::create([
            'role_authorization_id' => $customerServiceAuth1->id,
            'field_code' => 'CUSTOMER_GROUP',
            'operator' => 'between',
            'value_from' => '01',
            'value_to' => '10',
        ]);

        // Create Role Authorization for SuperAdmin to access Authorization Module
        // Note: SuperAdmin completely bypasses authorization system, so these are optional
        // They are created for consistency and potential future use, but SuperAdmin doesn't need them
        $superAdminModuleAuth = RoleAuthorization::firstOrCreate(
            [
                'role_id' => $superAdminRole->id,
                'auth_object_id' => $authorizationModule->id,
            ],
            [
                'label' => 'SuperAdmin - Full Access to Authorization Module',
            ]
        );

        RoleAuthorizationField::firstOrCreate(
            [
                'role_authorization_id' => $superAdminModuleAuth->id,
                'field_code' => 'ACTVT',
            ],
            [
                'operator' => '*',
            ]
        );

        // Grant SuperAdmin access to all business objects (SALES_ORDER_HEADER, FIN_DOCUMENT, CUSTOMER_MASTER)
        $superAdminSalesOrderAuth = RoleAuthorization::firstOrCreate(
            [
                'role_id' => $superAdminRole->id,
                'auth_object_id' => $salesOrderHeader->id,
            ],
            [
                'label' => 'SuperAdmin - Full Access to Sales Orders',
            ]
        );

        RoleAuthorizationField::firstOrCreate(
            [
                'role_authorization_id' => $superAdminSalesOrderAuth->id,
                'field_code' => 'ACTVT',
            ],
            [
                'operator' => '*',
            ]
        );

        $superAdminFinDocAuth = RoleAuthorization::firstOrCreate(
            [
                'role_id' => $superAdminRole->id,
                'auth_object_id' => $finDocument->id,
            ],
            [
                'label' => 'SuperAdmin - Full Access to Financial Documents',
            ]
        );

        RoleAuthorizationField::firstOrCreate(
            [
                'role_authorization_id' => $superAdminFinDocAuth->id,
                'field_code' => 'ACTVT',
            ],
            [
                'operator' => '*',
            ]
        );

        $superAdminCustomerAuth = RoleAuthorization::firstOrCreate(
            [
                'role_id' => $superAdminRole->id,
                'auth_object_id' => $customerMaster->id,
            ],
            [
                'label' => 'SuperAdmin - Full Access to Customer Master',
            ]
        );

        RoleAuthorizationField::firstOrCreate(
            [
                'role_authorization_id' => $superAdminCustomerAuth->id,
                'field_code' => 'ACTVT',
            ],
            [
                'operator' => '*',
            ]
        );

        // Create Role Authorization for SuperAdmin to access Authentication Settings
        $superAdminAuthSettingsAuth = RoleAuthorization::firstOrCreate(
            [
                'role_id' => $superAdminRole->id,
                'auth_object_id' => $authenticationSettings->id,
            ],
            [
                'label' => 'SuperAdmin - Full Access to Authentication Settings',
            ]
        );

        RoleAuthorizationField::firstOrCreate(
            [
                'role_authorization_id' => $superAdminAuthSettingsAuth->id,
                'field_code' => 'ACTVT',
            ],
            [
                'operator' => '*',
            ]
        );

        // Create ACTVT field for all new authorization objects
        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $dashboardAccess->id,
                'code' => 'ACTVT',
            ],
            [
                'label' => 'Activity',
                'is_org_level' => false,
                'sort' => 1,
            ]
        );

        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $tenantManagement->id,
                'code' => 'ACTVT',
            ],
            [
                'label' => 'Activity',
                'is_org_level' => false,
                'sort' => 1,
            ]
        );

        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $userManagement->id,
                'code' => 'ACTVT',
            ],
            [
                'label' => 'Activity',
                'is_org_level' => false,
                'sort' => 1,
            ]
        );

        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $profileManagement->id,
                'code' => 'ACTVT',
            ],
            [
                'label' => 'Activity',
                'is_org_level' => false,
                'sort' => 1,
            ]
        );

        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $authorizationDebug->id,
                'code' => 'ACTVT',
            ],
            [
                'label' => 'Activity',
                'is_org_level' => false,
                'sort' => 1,
            ]
        );

        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $transportManagement->id,
                'code' => 'ACTVT',
            ],
            [
                'label' => 'Activity',
                'is_org_level' => false,
                'sort' => 1,
            ]
        );

        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $todoManagement->id,
                'code' => 'ACTVT',
            ],
            [
                'label' => 'Activity',
                'is_org_level' => false,
                'sort' => 1,
            ]
        );

        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $uiManagement->id,
                'code' => 'ACTVT',
            ],
            [
                'label' => 'Activity',
                'is_org_level' => false,
                'sort' => 1,
            ]
        );

        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $companyManagement->id,
                'code' => 'ACTVT',
            ],
            [
                'label' => 'Activity',
                'is_org_level' => false,
                'sort' => 1,
            ]
        );

        // Create Role Authorizations for SuperAdmin for all new objects
        $superAdminDashboardAuth = RoleAuthorization::firstOrCreate(
            [
                'role_id' => $superAdminRole->id,
                'auth_object_id' => $dashboardAccess->id,
            ],
            [
                'label' => 'SuperAdmin - Full Access to Dashboard',
            ]
        );

        RoleAuthorizationField::firstOrCreate(
            [
                'role_authorization_id' => $superAdminDashboardAuth->id,
                'field_code' => 'ACTVT',
            ],
            [
                'operator' => '*',
            ]
        );

        $superAdminTenantAuth = RoleAuthorization::firstOrCreate(
            [
                'role_id' => $superAdminRole->id,
                'auth_object_id' => $tenantManagement->id,
            ],
            [
                'label' => 'SuperAdmin - Full Access to Tenant Management',
            ]
        );

        RoleAuthorizationField::firstOrCreate(
            [
                'role_authorization_id' => $superAdminTenantAuth->id,
                'field_code' => 'ACTVT',
            ],
            [
                'operator' => '*',
            ]
        );

        $superAdminUserAuth = RoleAuthorization::firstOrCreate(
            [
                'role_id' => $superAdminRole->id,
                'auth_object_id' => $userManagement->id,
            ],
            [
                'label' => 'SuperAdmin - Full Access to User Management',
            ]
        );

        RoleAuthorizationField::firstOrCreate(
            [
                'role_authorization_id' => $superAdminUserAuth->id,
                'field_code' => 'ACTVT',
            ],
            [
                'operator' => '*',
            ]
        );

        $superAdminProfileAuth = RoleAuthorization::firstOrCreate(
            [
                'role_id' => $superAdminRole->id,
                'auth_object_id' => $profileManagement->id,
            ],
            [
                'label' => 'SuperAdmin - Full Access to Profile Management',
            ]
        );

        RoleAuthorizationField::firstOrCreate(
            [
                'role_authorization_id' => $superAdminProfileAuth->id,
                'field_code' => 'ACTVT',
            ],
            [
                'operator' => '*',
            ]
        );

        $superAdminDebugAuth = RoleAuthorization::firstOrCreate(
            [
                'role_id' => $superAdminRole->id,
                'auth_object_id' => $authorizationDebug->id,
            ],
            [
                'label' => 'SuperAdmin - Full Access to Authorization Debug',
            ]
        );

        RoleAuthorizationField::firstOrCreate(
            [
                'role_authorization_id' => $superAdminDebugAuth->id,
                'field_code' => 'ACTVT',
            ],
            [
                'operator' => '*',
            ]
        );

        $superAdminTransportAuth = RoleAuthorization::firstOrCreate(
            [
                'role_id' => $superAdminRole->id,
                'auth_object_id' => $transportManagement->id,
            ],
            [
                'label' => 'SuperAdmin - Full Access to Transport Management',
            ]
        );

        RoleAuthorizationField::firstOrCreate(
            [
                'role_authorization_id' => $superAdminTransportAuth->id,
                'field_code' => 'ACTVT',
            ],
            [
                'operator' => '*',
            ]
        );

        $superAdminTodoAuth = RoleAuthorization::firstOrCreate(
            [
                'role_id' => $superAdminRole->id,
                'auth_object_id' => $todoManagement->id,
            ],
            [
                'label' => 'SuperAdmin - Full Access to Todo Management',
            ]
        );

        RoleAuthorizationField::firstOrCreate(
            [
                'role_authorization_id' => $superAdminTodoAuth->id,
                'field_code' => 'ACTVT',
            ],
            [
                'operator' => '*',
            ]
        );

        $superAdminUIAuth = RoleAuthorization::firstOrCreate(
            [
                'role_id' => $superAdminRole->id,
                'auth_object_id' => $uiManagement->id,
            ],
            [
                'label' => 'SuperAdmin - Full Access to UI Management',
            ]
        );

        RoleAuthorizationField::firstOrCreate(
            [
                'role_authorization_id' => $superAdminUIAuth->id,
                'field_code' => 'ACTVT',
            ],
            [
                'operator' => '*',
            ]
        );

        $superAdminCompanyAuth = RoleAuthorization::firstOrCreate(
            [
                'role_id' => $superAdminRole->id,
                'auth_object_id' => $companyManagement->id,
            ],
            [
                'label' => 'SuperAdmin - Full Access to Company Management',
            ]
        );

        RoleAuthorizationField::firstOrCreate(
            [
                'role_authorization_id' => $superAdminCompanyAuth->id,
                'field_code' => 'ACTVT',
            ],
            [
                'operator' => '*',
            ]
        );

        $superAdminClipboardAuth = RoleAuthorization::firstOrCreate(
            [
                'role_id' => $superAdminRole->id,
                'auth_object_id' => $clipboardManagement->id,
            ],
            [
                'label' => 'SuperAdmin - Full Access to Clipboard Management',
            ]
        );

        RoleAuthorizationField::firstOrCreate(
            [
                'role_authorization_id' => $superAdminClipboardAuth->id,
                'field_code' => 'ACTVT',
            ],
            [
                'operator' => '*',
            ]
        );

        // Create additional authorization with between operator for SALES_CLERK
        $salesClerkAuth2 = RoleAuthorization::create([
            'role_id' => $salesClerk->id,
            'auth_object_id' => $salesOrderHeader->id,
            'label' => 'Sales Clerk - Change Orders for Specific Range',
        ]);

        RoleAuthorizationField::create([
            'role_authorization_id' => $salesClerkAuth2->id,
            'field_code' => 'ACTVT',
            'operator' => '=',
            'value_from' => '02',
        ]);

        RoleAuthorizationField::create([
            'role_authorization_id' => $salesClerkAuth2->id,
            'field_code' => 'COMP_CODE',
            'operator' => '=',
            'value_from' => '1000',
        ]);

        RoleAuthorizationField::create([
            'role_authorization_id' => $salesClerkAuth2->id,
            'field_code' => 'SALES_ORG',
            'operator' => 'between',
            'value_from' => '2000',
            'value_to' => '2005',
        ]);

        // Assign roles to users if they exist
        $users = User::all();

        if ($users->isNotEmpty()) {
            // Assign SALES_CLERK to first user
            $users->first()->roles()->syncWithoutDetaching([$salesClerk->id]);

            // Assign AP_MANAGER to second user if exists
            if ($users->count() > 1) {
                $users->get(1)->roles()->syncWithoutDetaching([$apManager->id]);
            }

            // Assign SALES_MANAGER to third user if exists
            if ($users->count() > 2) {
                $users->get(2)->roles()->syncWithoutDetaching([$salesManager->id]);
            }

            // Assign CUSTOMER_SERVICE to fourth user if exists
            if ($users->count() > 3) {
                $users->get(3)->roles()->syncWithoutDetaching([$customerService->id]);
            }
        }

        $this->command->info('Authorization seeder completed successfully!');
        $this->command->info('Created:');
        $this->command->info('- 5 Roles (including SuperAdmin and SuperReadOnly)');
        $this->command->info('- 14 Auth Objects (including AUTHORIZATION_MODULE, DASHBOARD_ACCESS, TENANT_MANAGEMENT, USER_MANAGEMENT, PROFILE_MANAGEMENT, AUTHORIZATION_DEBUG, TRANSPORT_MANAGEMENT, TODO_MANAGEMENT, UI_MANAGEMENT, COMPANY_MANAGEMENT, CLIPBOARD_MANAGEMENT)');
        $this->command->info('- Multiple Auth Object Fields');
        $this->command->info('- Multiple Role Authorizations with various field rules');
        $this->command->info('- All operator types (*, =, in, between)');
    }
}
