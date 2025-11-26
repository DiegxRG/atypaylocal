<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestRoleLoad extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-role-load';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (class_exists(\App\Models\Role::class)) {
            $this->info('Role class found!');
        } else {
            $this->error('Role class NOT found!');
        }
    }

}
