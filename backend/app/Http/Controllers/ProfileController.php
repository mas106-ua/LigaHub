<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role,
            'avatar_url' => $user->avatar_url,
            'created_at' => $user->created_at,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'   => ['required', 'string', 'max:80'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // 2MB
        ]);

        // Subida de avatar (opcional)
        if ($request->hasFile('avatar')) {
            // borrar avatar anterior si estaba en /storage
            if ($user->avatar_url && Str::startsWith($user->avatar_url, '/storage/')) {
                $relative = 'public/' . Str::after($user->avatar_url, '/storage/');
                Storage::delete($relative);
            }

            $path = $request->file('avatar')->store('avatars', 'public'); // storage/app/public/avatars/...
            $user->avatar_url = Storage::url($path); // /storage/avatars/xxx.webp
        }

        $user->name = $data['name'];
        $user->save();

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'user'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role,
                'avatar_url' => $user->avatar_url,
            ],
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password'      => ['required', 'current_password'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            // requiere campo password_confirmation
        ]);

        $user = $request->user();
        $user->password = Hash::make($request->input('password'));
        $user->save();

        // Opcional: invalidar otras sesiones / tokens si usas tokens personales
        // $user->tokens()->delete();

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }
}
