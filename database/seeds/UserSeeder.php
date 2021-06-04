<?php

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        for ($i=0; $i < 20; $i++) { 
            // rend city
            $cityarr = ['New York','Los Angeles','Chicago','Houston'];

            $key = array_rand($cityarr);

            $city = $cityarr[$key];
            
            // rend password
            $pass_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            
            $pass =  substr(str_shuffle($pass_chars), 0, 8);

            
            User::create([
                'name'      => Str::random(10),
                'username'  => Str::random(10),
                'email'     => Str::random(10).'@gmail.com',
                'street'    => Str::random(40),
                'city'      => $city,
                'password'  => Hash::make($pass),
           ]);
       }

    }
    
}
