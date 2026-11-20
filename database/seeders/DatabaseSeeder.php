<?php
namespace Database\Seeders;
use App\Models\{User, Product, Customer};
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $username = config('pos.admin_username');
        if (!User::withTrashed()->where('username',$username)->exists()) {
            $password = config('pos.admin_password');
            if (!is_string($password) || strlen($password) < 12) {
                throw new \RuntimeException('Set ADMIN_PASSWORD to a unique password of at least 12 characters in .env before seeding.');
            }
            User::create(['username'=>$username,'full_name'=>config('pos.admin_name'),'password'=>$password]);
        }
        // Idempotent examples: re-seeding does not reset stock or prices.
        foreach (config('cafe.menu') as $item) {
            Product::firstOrCreate(['name'=>$item['name']], $item);
        }
        foreach ([['Ana Santos','ana@example.com','09171234567'],['Carlo Reyes','carlo@example.com','09181234567'],['Maria Cruz','maria@example.com',null]] as [$name,$email,$phone]) {
            if (!Customer::withTrashed()->where('email',$email)->exists()) Customer::create(['full_name'=>$name,'email'=>$email,'phone'=>$phone]);
        }
    }
}
