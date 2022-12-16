<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AccountsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//User Routes
Route::post("/auth/register", [UserController::class, "RegisterUser"]);
Route::post("/auth/login", [UserController::class, "Login"]);
Route::post("/auth/user-info", [UserController::class, "GetUserInfo"]);

//Bank Account Routes
Route::post("/bank/create-bank-account", [AccountsController::class, "CreateBankAccount"], ["middleware" => "auth:sanctum"]);
Route::get("/bank/get-details", [AccountsController::class, "GetAccountDetails"], ["middleware" => "auth:sanctum"]);
Route::post("/bank/deposit", [AccountsController::class, "Deposit"], ["middleware" => "auth:sanctum"]);
Route::post("/bank/make-transfer", [AccountsController::class, "MakeTransfers"], ["middleware" => "auth:sanctum"]);
Route::get("/bank/get-transactions", [AccountsController::class, "GetTransactions"], ["middleware" => "auth:sanctum"]);
Route::get("/bank/get-balance", [AccountsController::class, "GetBalance"], ["middleware" => "auth:sanctum"]);