<?php

namespace App\Http\Controllers\Crm;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        $users = User::whereIn('role', [Role::Admin, Role::Staff])->orderBy('name')->get();

        return view('crm.staff.index', ['users' => $users]);
    }

    public function create(): View
    {
        return view('crm.staff.create');
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'role' => Role::Staff,
            'active' => true,
        ]);

        return redirect()->route('crm.staff.index')->with('status', 'Funcionário criado.');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $this->abortUnlessStaffOrAdmin($user);

        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Não pode desativar a sua própria conta.']);
        }

        $user->update(['active' => false]);

        return back()->with('status', 'Funcionário desativado.');
    }

    public function activate(User $user): RedirectResponse
    {
        $this->abortUnlessStaffOrAdmin($user);

        $user->update(['active' => true]);

        return back()->with('status', 'Funcionário ativado.');
    }

    private function abortUnlessStaffOrAdmin(User $user): void
    {
        abort_unless(in_array($user->role, [Role::Admin, Role::Staff], true), 404);
    }
}
