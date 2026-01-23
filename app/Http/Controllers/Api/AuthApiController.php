<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AuthApiController extends Controller
{
     public function login(Request $request)
    {
        // Validate the request
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Get the credentials from the request
        $credentials = $request->only('email', 'password');

        // Attempt to authenticate the user
        if (!auth()->attempt($credentials)) {
            return response()->json([
                'message' => 'The given data was invalid',
                'errors' => [
                    'password' => ['Invalid credentials']
                ]
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        // Revoke all previous tokens
        $user->tokens()->delete();

        // Create a new token
        $authToken = $user->createToken('Personal Access Token')->plainTextToken;

        return response()->json(['token' => $authToken], 200);
    }
 

}
