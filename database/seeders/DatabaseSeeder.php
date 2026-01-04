<?php

 namespace Database\Seeders;

 use App\Models\User;
 use Illuminate\Database\Console\Seeds\WithoutModelEvents;
 use Illuminate\Database\Seeder;
 use Illuminate\Support\Facades\Hash;
 use Spatie\Permission\Models\Role;

 class DatabaseSeeder extends Seeder
 {
     use WithoutModelEvents;

     /**
      * Seed the application's database.
      */
     public function run(): void
     {
         $superAdminRole = Role::firstOrCreate([
             'name' => 'super_admin',
             'guard_name' => 'web',
         ]);

         foreach (['owner', 'admin', 'editor'] as $roleName) {
             Role::firstOrCreate([
                 'name' => $roleName,
                 'guard_name' => 'web',
             ]);
         }

         $superAdmin = User::firstOrCreate(
             ['email' => 'superadmin@example.com'],
             [
                 'name' => 'Super Admin',
                 'password' => Hash::make('password'),
             ],
         );

         if (! $superAdmin->hasRole($superAdminRole)) {
             $superAdmin->assignRole($superAdminRole);
         }
     }
 }
