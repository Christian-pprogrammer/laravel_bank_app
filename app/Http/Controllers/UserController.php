<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Exception;
use App\Http\Controllers\Token;

class UserController extends Controller
{


    public function Login(Request $request)
    {
        try {
            $request->validate([
                "email" => "required",
                "password" => "required"
            ]);

            $userInfo = User::where('email', "=", $request->email)->first();

            if (!$userInfo) {
                return response()->json(["message" => "Incorrect email or password"], 403);
            } else {

                if (Hash::check($request->password, $userInfo->password)) {

                    $headers = array('alg' => 'HS256', 'typ' => 'JWT');
                    $payload = array('id' => $userInfo->id, "email" => $userInfo->email, 'exp' => (time() + 1024*24*60*60));

                    $generateToken = new Token();
                    $token = $generateToken->generate_jwt($headers, $payload);

                    return response()->json(["token" => $token, "message" => "authentication successful"], 200);
                } else {
                    return response()->json(["message" => "Incorrect email or password"], 403);
                }
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            if (stripos($message, 'The given data was invalid') !== false) {
                $message = print_r($e->validator->failed(), true);
                return response()->json([
                    'message' => __($e->getMessage()),
                    'errors' => $e->validator->getMessageBag()
                ], 422);
            } else {
                return response()->json([
                    'error' => $e->getMessage()
                ], 400);
            }
        }
    }

    public function RegisterUser(Request $request)
    {

        try {
            $request->validate([
                "email" => "required|unique:users|max:150|min:3",
                "username" => "required|max:40|min:3",
                "phone_number" => "required|max:10|min:10|unique:users",
                "password" => "required|max:25|min:6",
                "national_id" => "required|max:30|min:7",
                "nationality" => "required|max:10|min:1"
            ]);

            $register = new User();
            $register->username = $request->username;
            $register->email = $request->email;
            $register->password = Hash::make($request->password);
            $register->phone_number = $request->phone_number;
            $register->national_id = $request->national_id;
            $register->nationality = $request->nationality;

            $saved = $register->save();

            if ($saved)
                return response()->json(["message" => "User Registered Successfully!"], 200);
            else
                return response()->json(["message" => "Something went wrong"], 400);
        } catch (Exception $e) {;

            $message = $e->getMessage();
            if (stripos($message, 'The given data was invalid') !== false) {
                $message = print_r($e->validator->failed(), true);
                return response()->json([
                    'message' => __($e->getMessage()),
                    'errors' => $e->validator->getMessageBag()
                ], 422);
            } else {
                return response()->json([
                    'error' => $e->getMessage()
                ], 400);
            }
        }
    }

    public function GetUserInfo(Request $request)
    {
        try {
            $user = User::where('email', "=", $request->email)->first();
            if ($user) {
                return response()->json(["user" => [
                    "id" => $user->id,
                    "email" => $user->email,
                    "username" => $user->username,
                    "phone_number" => $user->phone_number
                ]], 200);
            } else {
                return response()->json(["message" => "User not found"], 404);
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            if (stripos($message, 'The given data was invalid') !== false) {
                $message = print_r($e->validator->failed(), true);
                return response()->json([
                    'message' => __($e->getMessage()),
                    'errors' => $e->validator->getMessageBag()
                ], 422);
            } else {
                return response()->json([
                    'error' => $e->getMessage()
                ], 400);
            }
        }
    }
}
