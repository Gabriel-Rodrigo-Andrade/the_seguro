<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('users.index', ['users' => User::orderBy('name')->paginate(15)]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('users.create', ['roles' => UserRole::cases()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = new User;
        $user->forceFill($request->validated())->save();

        return to_route('users.show', $user)->with('status', 'Usuário criado.');
    }

    public function show(User $user): View
    {
        Gate::authorize('view', $user);

        return view('users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('users.edit', ['user' => $user, 'roles' => UserRole::cases()]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->saveChanges($request, $user, $request->validated(), 'update');

        if ($request->user()->is($user)) {
            return to_route('dashboard')->with('status', 'Perfil atualizado.');
        }

        return to_route('users.show', $user)->with('status', 'Usuário atualizado.');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $this->saveChanges($request, $user, ['is_active' => false], 'deactivate');

        return to_route('users.index')->with('status', 'Usuário desativado.');
    }

    private function saveChanges(Request $request, User $user, array $attributes, string $ability): void
    {
        DB::transaction(function () use ($request, $user, $attributes, $ability) {
            // Serializa alterações administrativas para preservar o último administrador ativo.
            DB::statement('LOCK TABLE users IN SHARE ROW EXCLUSIVE MODE');
            $user->refresh();
            Gate::forUser($request->user()->fresh())->authorize($ability, $user);
            $user->forceFill($attributes);
            $this->ensureActiveAdministratorRemains($user);
            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }
            $user->save();
        });
    }

    private function ensureActiveAdministratorRemains(User $user): void
    {
        if ($user->getOriginal('role') !== UserRole::Administrator || ! $user->getOriginal('is_active')) {
            return;
        }
        if ($user->is_active && $user->role === UserRole::Administrator) {
            return;
        }
        if (User::where('role', UserRole::Administrator)->where('is_active', true)->whereKeyNot($user->id)->exists()) {
            return;
        }
        throw ValidationException::withMessages(['is_active' => 'É necessário manter pelo menos um administrador ativo.']);
    }
}
