<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('admin.default_password', env('ADMIN_DEFAULT_PASSWORD', 'ChangeMe123!'));

        $user = User::query()->firstOrNew(['email' => 'admin@evalio.de']);

        $user->name = 'Evalio Admin';
        $user->is_admin = true;
        $user->password = Hash::make($password);
        $user->email_verified_at = now();

        if (! $user->remember_token) {
            $user->remember_token = Str::random(10);
        }

        $user->save();
    }
}
