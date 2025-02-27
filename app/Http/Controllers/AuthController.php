<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request) {
        // Validation data
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'birthday' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:3', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/'],
        ]);
        // 422 error, if fails validation
        if($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }
        $validate = $validator->validate();
        // registration new user
        $user = User::create($validate);
        return response()->json([
            "success" => true,
            "message" => "Success",
            "token" => $user->createToken('auth_token')->plainTextToken,
        ], 200);
    }
    public function login(Request $request) {
        // Validation data
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        // 422 error, if fails validation
        if($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }
        $validate = $validator->validate();
        $user = User::where('users.email', $validate['email'])->first();
        if(!$user || !Hash::check($validate['password'], $user->password)) {
            return response()->json([
                "success" => false,
                "message" => "Login failed",
            ], 401);
        }
        return response()->json([
            "success" => true,
            "message" => "Success",
            "token" => $user->createToken('auth_token')->plainTextToken,
        ], 200);
    }

    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            "success" => true,
            "message" => "Logout",
        ], 200);
    }
}
