<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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
        $roles = $this->finalRoles();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username',
            'email'    => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role_id'  => ['required', $this->roleExistsRule()],
            'status'   => 'required|in:aktif,nonaktif',
        ]);

        User::create([
            'name'     => $request->name,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role_id'  => $request->role_id,
            'status'   => $request->status,
        ]);

        LogAktivitas::catat("Menambahkan user: {$request->username}", 'User');

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $roles = $this->finalRoles();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username,' . $user->id,
            'email'    => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role_id'  => ['required', $this->roleExistsRule()],
            'status'   => 'required|in:aktif,nonaktif',
        ]);

        $data = $request->only(['name', 'username', 'email', 'role_id', 'status']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        LogAktivitas::catat("Mengubah user: {$user->username}", 'User');

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menonaktifkan akun sendiri.');
        }

        $statusBaru = $user->status === 'aktif' ? 'nonaktif' : 'aktif';
        $user->update(['status' => $statusBaru]);

        LogAktivitas::catat("Mengubah status user {$user->username} menjadi {$statusBaru}", 'User');

        return back()->with('success', "Status user berhasil diubah menjadi {$statusBaru}.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak sama.',
        ]);

        $user->update(['password' => Hash::make($request->password)]);

        LogAktivitas::catat("Reset password user: {$user->username}", 'User');

        return redirect()->route('users.edit', $user)->with('success', 'Password user berhasil direset.');
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

    private function finalRoles()
    {
        foreach (User::FINAL_ROLES as $roleName) {
            Role::firstOrCreate(['nama_role' => $roleName]);
        }

        return Role::whereIn('nama_role', User::FINAL_ROLES)
            ->orderByRaw("FIELD(nama_role, 'Admin', 'Guru', 'Kepala Sekolah')")
            ->get();
    }

    private function roleExistsRule()
    {
        return Rule::exists('roles', 'id')
            ->where(fn($query) => $query->whereIn('nama_role', User::FINAL_ROLES));
    }
}
