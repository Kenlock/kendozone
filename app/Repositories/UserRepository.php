<?php
namespace App\Repositories;

use App\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use GeoIP;

class UserRepository
{
    public function findByUserNameOrCreate($userData, $provider)
    {
        $user = User::where('provider_id', '=', $userData->id)->first();
        if (!$user) {
            // Check if there is no other user with same email
            $user = User::where('email', '=', $userData->email)->first();
//            dd($user);
            if (!$user){


                $location = GeoIP::getLocation("189.209.75.100"); // Simulating IP in Mexico DF
                $user = User::create([
                    'provider' => $provider,
                    'provider_id' => $userData->id,
                    'name' => $userData->name,
                    'username' => $userData->nickname,
                    'email' => $userData->email,
                    'country' => $location['country'],
                    'countryCode' => $location['isoCode'],
                    'city' => $location['city'],
                    'latitude' => $location['lat'],
                    'longitude' => $location['lon'],
                    'avatar' => $userData->avatar,
                    'roleId' => Config::get('constants.ROLE_ADMIN'),
                    'verified' => 1,
                ]);
            }

            else{
                return null;
            }

//                dd("ya hay otro we");
        }

        $this->checkIfUserNeedsUpdating($userData, $user);
        return $user;
    }

    public function checkIfUserNeedsUpdating($userData, $user)
    {

        $socialData = [
            'avatar' => $userData->avatar,
            'email' => $userData->email,
            'firstname' => $userData->name,
            'name' => $userData->nickname,
        ];
        $dbData = [
            'avatar' => $user->avatar,
            'email' => $user->email,
            'firstname' => $user->name,
            'name' => $user->username,
        ];

        if (!empty(array_diff($socialData, $dbData))) {
            $user->avatar = $userData->avatar;
            $user->email = $userData->email;
            $user->firstname = $userData->name;
            $user->name = $userData->nickname;
            $user->save();
        }
    }
}