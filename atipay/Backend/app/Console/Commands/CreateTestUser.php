<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateTestUser extends Command
{
    protected $signature = 'create:test-user {username} {email} {password} {--role=partner}';
    protected $description = 'Create a test user for development';

    public function handle()
    {
        $username = $this->argument('username');
        $email = $this->argument('email');
        $password = $this->argument('password');
        $roleName = $this->option('role');

        $this->info("📝 Creando usuario de prueba...");
        $this->info("---");

        // Verificar que el usuario no exista
        $existingUser = User::where('username', $username)->orWhere('email', $email)->first();
        if ($existingUser) {
            $this->error("❌ El usuario o email ya existe");
            return;
        }

        // Obtener el rol
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            $this->error("❌ Rol '{$roleName}' no existe");
            $this->info("Roles disponibles:");
            Role::all()->each(fn($r) => $this->line("  - " . $r->name));
            return;
        }

        // Crear el usuario
        try {
            $user = User::create([
                'username' => $username,
                'email' => $email,
                'phone_number' => '999999999',
                'password' => $password, // Se encriptará automáticamente
                'role_id' => $role->id,
                'status' => 'active',
                'reference_code' => Str::random(8),
                'reference_code_valid' => true,
                'login_inactive' => false,
                'registration_date' => now('America/Lima')->toDateString(),
                'registration_time' => now('America/Lima')->format('h:i:s A'),
            ]);

            $this->info("✅ Usuario creado exitosamente!");
            $this->info("---");
            $this->line("ID: {$user->id}");
            $this->line("Usuario: {$user->username}");
            $this->line("Email: {$user->email}");
            $this->line("Rol: {$role->name}");
            $this->line("Contraseña: {$password}");
            $this->line("Código de referencia: {$user->reference_code}");
            $this->info("---");
            $this->info("Ahora puedes hacer login con:");
            $this->line("  Usuario: {$username}");
            $this->line("  Contraseña: {$password}");

        } catch (\Exception $e) {
            $this->error("❌ Error al crear el usuario: " . $e->getMessage());
        }
    }
}
