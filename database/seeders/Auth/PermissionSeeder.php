<?php

namespace Database\Seeders\Auth;

use Illuminate\Database\Seeder;
use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Reader;
use League\Csv\UnavailableStream;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * @throws UnavailableStream
     * @throws InvalidArgument
     * @throws Exception
     */
    public function run(): void
    {
        $rows = Reader::createFromPath(database_path('import/permissions.csv'))
            ->setHeaderOffset(0)
            ->setDelimiter(';');

        $permissions = [];

        /**
         * ```php
         *
         * $column = 'super_admin'; // super_admin,user
         * ```
         */
        foreach ($rows as $row) {
            // Simpan atau ambil permission
            $permission = Permission::firstOrCreate(['name' => $row['name']]);

            // Simpan ke array berdasarkan role
            foreach ($row as $column => $value) {
                if ($column !== 'name' && strtoupper($value) === 'YES') {
                    $permissions[$column][] = $permission->id;
                }
            }
        }

        // Ambil semua role dari DB
        $roles = Role::whereIn('name', array_keys($permissions))
            ->get();

        // Attach permission ke masing-masing role
        foreach ($roles as $role) {
            $role->permissions()->syncWithoutDetaching($permissions[$role->name] ?? []);
        }
    }
}
