<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeUser;
use App\Models\Role;
use App\Services\Contacts;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('admin.user.index', compact('users'));
    }

    public function edit($id)
    {
        $user = User::where('id', $id)->first();
        $roles = Role::all();
        return view('admin.user.edit', compact('roles','user'));
    }

    public function store(Request $request)
    {
        if($request->generate_password){
            $validated = $request->validate([
                'name' => 'required',
                'last_name' => 'required',
                'email' => 'required|email|unique:users',
            ]);

            $password = Str::random(8);
            $validated['password'] = $password;

        } else {
            $validated = $request->validate([
                'name' => 'required',
                'last_name' => 'required',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:6'
            ]);
        }

        $validated['password'] = Hash::make($validated['password']);
        
        $user = User::create($validated);
        
        Mail::to($user->email)->send(new WelcomeUser($user,$validated['password']));
        
        return redirect()->route('user.index')->with('success', 'Se ha creado el usuario correctamente');
    }

    public function create()
    {
        $roles = Role::all();
        return view('admin.user.create', compact('roles'));
    }

    public function show(User $user)
    {
        return $user;
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required',
            'last_name' => 'sometimes|required',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|min:6'
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        return $user;
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}
