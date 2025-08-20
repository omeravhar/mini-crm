<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class DataController extends Controller
{
    public $user;

    public function __construct(){
        if (Auth::check()) {
        $this->user = User::where('email', Auth::user()->email)->first()->toArray();
        } else {
            $this->user = null;
        }
    }

    public function createNewLead(){
        return view("newLead",["user"=> $this->user]);
    }

    public function saveNewLead(Request $request){
        echo "<pre>";
        print_r($_POST);
        echo "<pre>";
    }


    public function showAllUsers(){
        return view("showAllUsers");
    }
}
