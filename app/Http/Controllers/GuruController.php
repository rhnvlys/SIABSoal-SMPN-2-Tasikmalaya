<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\User;
use App\Models\Role;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;

class GuruController extends Controller
{
    public function index(Request $request)
    {
        $guru = Guru::with('user')
                    ->when($request->search, fn($q, $s) => $q->where('nama_guru', 'like', "%{$s}%")->orWhere('nip', 'like', "%{$s}%"))
                    ->orderBy('nama_guru')
                    ->paginate(15)
                    ->withQueryString();

        return view('guru.index', compact('guru'));
    }

    public function create()
    {
        return view('guru.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_guru'     => 'required|string|max:255',
            'nip'           => 'nullable|string|max:30|unique:guru,nip',
            'jenis_kelamin' => 'nullable|in:L,P',
            'no_hp'         => 'nullable|string|max:20',
            'alamat'        => 'nullable|string',
            'status'        => 'required|in:aktif,nonaktif',
            'buat_akun'     => 'nullable|boolean',
            'username'      => 'nullable|required_if:buat_akun,1|string|max:100|unique:users,username',
            'password'      => 'nullable|required_if:buat_akun,1|string|min:6',
        ]);

        $userId = null;
        if ($request->buat_akun) {
            $guruRole = Role::where('nama_role', 'Guru')->first();
            $user = User::create([
                'name'     => $request->nama_guru,
                'username' => $request->username,
                'password' => bcrypt($request->password),
                'role_id'  => $guruRole->id,
                'status'   => $request->status,
            ]);
            $userId = $user->id;
        }

        Guru::create([
            'user_id'       => $userId,
            'nama_guru'     => $request->nama_guru,
            'nip'           => $request->nip,
            'jenis_kelamin' => $request->jenis_kelamin,
            'no_hp'         => $request->no_hp,
            'alamat'        => $request->alamat,
            'status'        => $request->status,
        ]);

        LogAktivitas::catat("Menambahkan guru: {$request->nama_guru}", 'Guru');

        return redirect()->route('guru.index')->with('success', 'Data guru berhasil ditambahkan.');
    }

    public function edit(Guru $guru)
    {
        return view('guru.edit', compact('guru'));
    }

    public function update(Request $request, Guru $guru)
    {
        $request->validate([
            'nama_guru'     => 'required|string|max:255',
            'nip'           => 'nullable|string|max:30|unique:guru,nip,' . $guru->id,
            'jenis_kelamin' => 'nullable|in:L,P',
            'no_hp'         => 'nullable|string|max:20',
            'alamat'        => 'nullable|string',
            'status'        => 'required|in:aktif,nonaktif',
        ]);

        $guru->update($request->only(['nama_guru', 'nip', 'jenis_kelamin', 'no_hp', 'alamat', 'status']));

        LogAktivitas::catat("Mengubah guru: {$guru->nama_guru}", 'Guru');

        return redirect()->route('guru.index')->with('success', 'Data guru berhasil diperbarui.');
    }

    public function destroy(Guru $guru)
    {
        if ($guru->ujian()->exists()) {
            return back()->with('error', 'Guru tidak dapat dihapus karena memiliki data ujian.');
        }

        $nama = $guru->nama_guru;
        $guru->delete();

        LogAktivitas::catat("Menghapus guru: {$nama}", 'Guru');

        return redirect()->route('guru.index')->with('success', 'Data guru berhasil dihapus.');
    }
}
