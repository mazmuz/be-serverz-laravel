<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Rules\Password;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function register(Request $request)
    {
        try {
            $input = $request->all();

            $validator = Validator::make($input, [
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users',
                'email' => 'required|string|email:dns|max:255|unique:users',
                'phone' => 'required|string|max:255',
                'password' => ['required', 'string', new Password],
            ]);

            if ($validator->fails()) {
                return ResponseFormatter::error($validator->errors(), 'Validation error!');
            }

            User::create([
                'name' => $input['name'],
                'username' => $input['username'],
                'email' => $input['email'],
                'phone' => $input['phone'],
                'password' => Hash::make($input['password']),
            ]);

            $user = User::where('email', $input["email"])->first();

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'User Registered');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage()
            ], 'Failed to register', 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $input = $request->all();

            $validator = Validator::make($input, [
                'email' => 'email:dns|required',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return ResponseFormatter::error($validator->errors(), 'Validation error!');
            }

            $credentials = request(['email', 'password']);

            if (!Auth::attempt($credentials)) {
                return ResponseFormatter::error([
                    'message' => 'Unauthenticated user'
                ], 'Authentication failed', 401);
            }

            $user = User::where('email', $input['email'])->first();

            if (!Hash::check($input['password'], $user->password, [])) {
                throw new \Exception('Invalid credentials');
            }

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'Authenticated');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage()
            ], 'Authentication failed', 500);
        }
    }

    public function fetch(Request $request)
    {
        return ResponseFormatter::success($request->user(), 'Data profile user berhasil diambil');
    }

    public function updateProfile(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'email' => 'required|string|email:dns|max:255',
            'phone' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error($validator->errors(), 'Validation error!');
        }

        $user = Auth::user();
        $redudance = User::where('id', '!=', $user['id'])
            ->where('username', $data['username'])
            ->orWhere('id', '!=', $user['id'])
            ->where('email', $data['email'])
            ->first();

        if ($redudance) {
            return ResponseFormatter::error([
                'error' => 'Username or email already exist!',
            ], 'Failed to update', 500);
        }

        $user->update($data);

        return ResponseFormatter::success($user, 'Profile updated successfully!');
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken()->delete();
        return ResponseFormatter::success($token, 'Token Revoked!');
    }
}
