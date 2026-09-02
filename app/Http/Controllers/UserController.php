<?php

namespace App\Http\Controllers;

use App\Models\Gtk;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->with(['roles', 'gtk'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pengguna.index', compact('users', 'q'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('pengguna.form', [
            'user' => new User(['is_aktif' => true]),
            'gtks' => $this->gtkPilihan(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $this->validated($request);
        $role = $data['role'];
        unset($data['role']);

        $user = User::query()->create($data);
        $user->syncRoles([$role]);

        return redirect()->route('pengguna.index')->with('status', 'Pengguna ditambahkan.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('pengguna.form', [
            'user' => $user,
            'gtks' => $this->gtkPilihan($user),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $this->validated($request, $user);
        $role = $data['role'];
        unset($data['role']);

        if (blank($data['password'])) {
            unset($data['password']);
        }

        if ($user->hasRole(Peran::SUPERADMIN) && User::role(Peran::SUPERADMIN)->count() <= 1) {
            if ($role !== Peran::SUPERADMIN) {
                return back()->with('status', 'Tidak dapat mengubah peran super admin terakhir.');
            }

            if (! ($data['is_aktif'] ?? true)) {
                return back()->with('status', 'Tidak dapat menonaktifkan super admin terakhir.');
            }
        }

        $user->update($data);
        $user->syncRoles([$role]);

        return redirect()->route('pengguna.index')->with('status', 'Pengguna diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->hasRole(Peran::SUPERADMIN) && User::role(Peran::SUPERADMIN)->count() <= 1) {
            return back()->with('status', 'Tidak dapat menghapus super admin terakhir.');
        }

        $user->delete();

        return redirect()->route('pengguna.index')->with('status', 'Pengguna dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in(array_keys(Peran::labels()))],
            'gtk_id' => ['nullable', 'exists:gtks,id', Rule::unique('users', 'gtk_id')->ignore($user?->id)],
            'is_aktif' => ['sometimes', 'boolean'],
        ]);

        $data['is_aktif'] = $request->boolean('is_aktif');

        if ($data['role'] === Peran::WALI_KELAS) {
            $request->validate([
                'gtk_id' => ['required', 'exists:gtks,id'],
            ], [
                'gtk_id.required' => 'Wali kelas harus terhubung ke data GTK.',
            ]);
        } else {
            $data['gtk_id'] = null;
        }

        Role::findOrCreate($data['role']);

        return $data;
    }

    /**
     * @return Collection<int, Gtk>
     */
    private function gtkPilihan(?User $user = null)
    {
        return Gtk::query()
            ->where('status', 'aktif')
            ->where(function ($query) use ($user) {
                $query->whereDoesntHave('akun')
                    ->when($user?->gtk_id, fn ($inner) => $inner->orWhereKey($user->gtk_id));
            })
            ->orderBy('nama')
            ->get();
    }
}
