<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authorization\Entities\AuthObject;
use Modules\Authorization\Entities\AuthObjectField;

class TransportManagementAuthObjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $authObject = AuthObject::firstOrCreate(
            ['code' => 'TRANSPORT_MGMT'],
            ['description' => 'Transport Management - Controls access to transport request management']
        );

        // Create fields for ADMIN and OPERATOR
        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $authObject->id,
                'code' => 'ADMIN',
            ],
            [
                'label' => 'Transport Administrator',
                'is_org_level' => false,
                'sort' => 1,
            ]
        );

        AuthObjectField::firstOrCreate(
            [
                'auth_object_id' => $authObject->id,
                'code' => 'OPERATOR',
            ],
            [
                'label' => 'Transport Operator',
                'is_org_level' => false,
                'sort' => 2,
            ]
        );
    }
}
