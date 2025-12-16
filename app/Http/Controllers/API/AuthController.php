<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class AuthController extends Controller
{
    /**
     * Register a new user
     *
     * @param RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        try {
            $profileImagePath = null;
            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image');
                $filename = time() . '.' . $image->getClientOriginalExtension();
                $profileImagePath = 'users/' . $filename;
                
                // Store the image
                Storage::disk('public')->makeDirectory('users', 0755, true, true);
                Image::read($image)->resize(300, 300)->save(storage_path('app/public/' . $profileImagePath));
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'profile_image' => $profileImagePath,
            ]);

            $user->assignRole('user');

            // Login the user to create an authenticated session
            Auth::login($user);

            // Load roles efficiently and append roles_array
            try {
                $user->load('roles');
                $user->roles_array = $user->roles->pluck('name')->toArray();
            } catch (\Exception $e) {
                // If roles fail to load, set empty array
                $user->roles_array = [];
            }

            return response()->json([
                'status' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $user,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to register user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user and create token
     *
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        try {
            // Log login attempt for debugging
            Log::info('Login attempt', ['email' => $request->email]);
            
            if (!Auth::attempt($request->only('email', 'password'))) {
                Log::warning('Login failed: Invalid credentials', ['email' => $request->email]);
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid login credentials'
                ], 401);
            }

            /** @var User $user */
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid login credentials'
                ], 401);
            }
            
            // Check if user is restricted
            if ($user->is_restricted) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account is restricted due to overdue items. Please return all overdue items to regain access.'
                ], 403);
            }

            session()->regenerate();

            Log::info('User authenticated, session regenerated', ['user_id' => $user->id]);

            // Skip role loading during login to avoid timeout - load it in /user endpoint instead
            // This makes login fast and reliable
            $user->roles_array = [];

            Log::info('Login successful, returning response', ['user_id' => $user->id]);
            
            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Failed to login: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout user (invalidate session)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'status' => true,
                'message' => 'Successfully logged out'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to logout: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user profile
     *
     * @param UpdateProfileRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        try {
            $user = $request->user();

            if ($request->hasFile('profile_image')) {
                // Delete old image if exists
                if ($user->profile_image) {
                    Storage::disk('public')->delete($user->profile_image);
                }

                $image = $request->file('profile_image');
                $filename = time() . '.' . $image->getClientOriginalExtension();
                $profileImagePath = 'users/' . $filename;
                
                // Store the image
                Storage::disk('public')->makeDirectory('users', 0755, true, true);
                Image::read($image)->resize(300, 300)->save(storage_path('app/public/' . $profileImagePath));
                
                $user->profile_image = $profileImagePath;
            }

            $user->name = $request->name;
            $user->email = $request->email;
            $user->save();

            // Load roles efficiently and append roles_array for frontend
            try {
                $user->load('roles');
                $user->roles_array = $user->roles->pluck('name')->toArray();
            } catch (\Exception $e) {
                // If roles fail to load, set empty array
                $user->roles_array = [];
            }

            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change user password
     *
     * @param ChangePasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $user = $request->user();

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Current password is incorrect'
                ], 401);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Password changed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to change password: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        try {
            $user = $request->user();
            
            // Load roles efficiently for the user
            try {
                $user->load('roles');
                $user->roles_array = $user->roles->pluck('name')->toArray();
            } catch (\Exception $e) {
                // If roles fail to load, set empty array
                $user->roles_array = [];
            }
            
            return response()->json([
                'status' => true,
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch user: ' . $e->getMessage()
            ], 500);
        }
    }
}
