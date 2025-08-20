<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DataController;


Route::get('/', function () {return view('login');});
Route::get('login', function () {return view('login');});

Route::post('login', [UserController::class, 'login']);
Route::get('main', [UserController::class, 'showMain']);
route::get('myLeads',function(){
    return view('myLeads');
});




Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('newLead', [DataController::class, 'createNewLead']);
    Route::post('newLead', [DataController::class, 'saveNewLead'])->name('admin.saveNewLead');
    Route::get('showUsers',[DataController::class,'showAllUsers']);
    Route::get('showAllLeads',function(){
        return view("showAllLeads");
    });
});