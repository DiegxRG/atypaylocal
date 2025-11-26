<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ListUsers extends Command
{
    protected $signature = 'list:users';
    protected $description = 'List all users in the database';

    public function handle()
    {
        $users = User::with('role')->get();

        if ($users->isEmpty()) {
            $this->warn("⚠️  No hay usuarios en la base de datos");
            return;
        }

        $this->info("📋 Usuarios en la base de datos:");
        $this->info("---");

        $headers = ['ID', 'Usuario', 'Email', 'Teléfono', 'Rol', 'Estado', 'Inactivo'];
        $rows = [];

        foreach ($users as $user) {
            $rows[] = [
                $user->id,
                $user->username,
                $user->email,
                $user->phone_number ?? 'N/A',
                $user->role ? $user->role->name : 'Sin rol',
                $user->status,
                $user->login_inactive ? 'SÍ' : 'NO'
            ];
        }

        $this->table($headers, $rows);

        $this->info("---");
        $this->info("Total: {$users->count()} usuarios");
        $this->info("");
        $this->info("💡 Para hacer login usa:");
        $this->line("  - Usuario o Email de la tabla anterior");
        $this->line("  - La contraseña que fue asignada (no se puede ver)");
        $this->info("");
        $this->info("Si no sabes la contraseña, crea un usuario de prueba:");
        $this->line("  php artisan create:test-user juan juan@example.com password123");
    }
}
