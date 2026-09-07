<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallAutoEvolve extends Command
{
    protected $signature='autoevolve:install {--email=} {--password=}';
    protected $description='Run migrations and create or promote the initial AutoEvolve administrator.';

    public function handle(): int
    {
        if (config('database.default')==='sqlite') {
            $path=config('database.connections.sqlite.database');
            if ($path && !file_exists($path)) { @mkdir(dirname($path),0775,true); touch($path); }
        }
        Artisan::call('migrate',['--force'=>true]);
        $this->line(Artisan::output());
        $email=$this->option('email') ?: env('ADMIN_EMAIL');
        $password=$this->option('password') ?: env('ADMIN_PASSWORD');
        if ($email && $password) {
            $user=User::updateOrCreate(['email'=>$email],['name'=>'AutoEvolve Admin','password'=>$password,'is_admin'=>true]);
            $this->info('Administrator ready: '.$user->email);
        } else {
            $this->warn('Migrations complete. Set ADMIN_EMAIL and ADMIN_PASSWORD or rerun with --email and --password to create an administrator.');
        }
        return self::SUCCESS;
    }
}
