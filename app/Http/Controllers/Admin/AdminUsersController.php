<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;

class AdminUsersController extends Controller
{
    public function index()
    {
        $users = User::select('id', 'name', 'email', 'email_verified_at', 'created_at')
            ->latest()
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'Gebruiker',
                'type' => 'user',
                'verified' => $user->email_verified_at !== null,
                'created_at' => $user->created_at->format('d-m-Y H:i'),
            ]);

        $admins = Admin::select('id', 'name', 'email', 'email_verified_at', 'created_at')
            ->latest()
            ->get()
            ->map(fn($admin) => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'Beheerder',
                'type' => 'admin',
                'verified' => $admin->email_verified_at !== null,
                'created_at' => $admin->created_at->format('d-m-Y H:i'),
            ]);

        $allUsers = $users->concat($admins)->sortByDesc('created_at')->values();

        return Inertia::render('admin/users/Index', [
            'users' => $allUsers,
            'stats' => [
                'total' => $allUsers->count(),
                'regular_users' => $users->count(),
                'admins' => $admins->count(),
                'verified' => $allUsers->where('verified', true)->count(),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/users/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
        ]);

        // Aanmaken als gewone gebruiker (kan je aanpassen voor admin!)
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Gebruiker aangemaakt!');
    }

    public function update(Request $request, $type, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'nullable|string|min:8',
        ]);

        $model = $type === 'admin' ? Admin::class : User::class;
        $user = $model::findOrFail($id);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Gebruiker succesvol bijgewerkt!');
    }

    public function destroy($type, $id)
    {
        $model = $type === 'admin' ? Admin::class : User::class;
        $user = $model::findOrFail($id);

        if ($type === 'admin' && $user->id === auth()->guard('admin')->id()) {
            return back()->withErrors(['error' => 'Je kunt jezelf niet verwijderen!']);
        }

        $user->delete();

        return back()->with('success', 'Gebruiker succesvol verwijderd!');
    }
}
