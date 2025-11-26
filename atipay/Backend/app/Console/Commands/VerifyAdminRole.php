<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifyAdminRole extends Command
{
    protected $signature = 'admin:verify {email?}';
    protected $description = 'Verify and fix admin role for a user';

    public function handle()
    {
        $email = $this->argument('email') ?? 'milenco@globaldifgmail.com';

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("❌ Usuario no encontrado con email: $email");
            return 1;
        }

        $this->line("👤 Usuario encontrado: {$user->username} ({$user->email})");
        $this->line("   Rol ID: {$user->role_id}");
        $this->line("   Role object: " . ($user->role ? $user->role->name : 'NULL'));

        if ($user->role && $user->role->name === 'admin') {
            $this->info("✅ El usuario ya es admin");
            return 0;
        }

        // Buscar o crear el rol de admin
        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        
        if (!$adminRole) {
            $this->warn("⚠️  No existe rol 'admin' en la base de datos. Creándolo...");
            $adminRole = \App\Models\Role::create([
                'name' => 'admin',
                'description' => 'Administrador del sistema'
            ]);
            $this->info("✅ Rol 'admin' creado");
        }

        $user->role_id = $adminRole->id;
        $user->save();

        $this->info("✅ Rol de admin asignado correctamente al usuario {$user->username}");
        return 0;
    }
}
