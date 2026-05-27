<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('role')
                     ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('username', 'like', "%{$s}%"))
                     ->orderBy('name')
                     ->paginate(15)
                     ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::orderBy('nama_role')->get();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username',
            'email'    => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id'  => 'required|exists:roles,id',
            'status'   => 'required|in:aktif,nonaktif',
        ]);

        User::create([
            'name'     => $request->name,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => bcrypt($request->password),
            'role_id'  => $request->role_id,
            'status'   => $request->status,
        ]);

        LogAktivitas::catat("Menambahkan user: {$request->username}", 'User');

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('nama_role')->get();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username,' . $user->id,
            'email'    => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role_id'  => 'required|exists:roles,id',
            'status'   => 'required|in:aktif,nonaktif',
        ]);

        $data = $request->only(['name', 'username', 'email', 'role_id', 'status']);
        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);

        LogAktivitas::catat("Mengubah user: {$user->username}", 'User');

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        $username = $user->username;
        $user->delete();

        LogAktivitas::catat("Menghapus user: {$username}", 'User');

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
