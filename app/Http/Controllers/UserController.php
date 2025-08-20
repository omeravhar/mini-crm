<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{
     public function login (Request $request){
        $credentials = $request->only('email', 'password');

        $result = User::checkLoginUser($credentials);

        if (is_array($result) && isset($result['error'])) {
            return redirect()->route('login')->withErrors([
                $result['error'] => $result['message']
            ])->withInput($request->only('email'));
        }

        
        Auth::login($result);

        // $user = User::getData(Auth::user()->email);
        return view("crm",["user"=>$result]);
    }



    public function showMain(){
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        return view("crm");
    }

   
}
