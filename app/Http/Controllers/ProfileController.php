<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function edit(string $id)
    {
        $user = User::findOrFail($id);
        return view('admin.profile.edit', compact('user'));
    }

    public function update(Request $request, string $id)
    {
        //dd($request->all());
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$id.',id',
            'old_password' => 'nullable|string|min:8',
            'password' => 'nullable|string|min:8',
            'bio' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $user = User::where('id',$id)->first();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->bio = $request->bio;
        $user->phone = $request->phone;

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $avatarPath;
        }

        $user->save();

        return redirect()->route('profile.edit', $id)->with('success', 'Se ha actualizado correctamente el perfil.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        if ($request->filled('current_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['old_password' => 'La contraseña actual no es correcta.']);
            }

            if ($request->filled('new_password')) {
                $user->password = Hash::make($request->new_password);
            }
        }
        $user->save();
        return redirect()->route('profile.edit', $user->id)->with('success', 'Se ha actualizado correctamente la contraseña.');
    }

    public function destroy(string $id)
    {
        //
    }
}
