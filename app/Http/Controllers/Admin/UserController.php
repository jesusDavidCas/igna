<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'user' => new User([
                'preferred_language' => 'es',
                'role' => UserRole::CLIENT,
                'is_active' => true,
            ]),
            'roles' => UserRole::cases(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        User::query()->create($this->payload($request));

        return redirect()->route('admin.users.index')->with('success', __('site.user_created'));
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $payload = $this->payload($request);

        if ($this->wouldRemoveLastSuperAdmin($user, $payload)) {
            return back()
                ->withInput($request->safe()->except('password'))
                ->withErrors(['role' => __('site.last_super_admin_guard')]);
        }

        if ($user->is($request->user())) {
            $payload['role'] = UserRole::SUPER_ADMIN;
            $payload['is_active'] = true;
        }

        if (empty($payload['password'])) {
            unset($payload['password']);
        }

        $user->update($payload);

        return redirect()->route('admin.users.edit', $user)->with('success', __('site.user_updated'));
    }

    private function payload(UserRequest $request): array
    {
        return [
            'first_name' => $request->validated('first_name'),
            'last_name' => $request->validated('last_name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'preferred_language' => $request->validated('preferred_language'),
            'role' => UserRole::from($request->validated('role')),
            'is_active' => $request->boolean('is_active'),
            'password' => $request->validated('password'),
        ];
    }

    private function wouldRemoveLastSuperAdmin(User $user, array $payload): bool
    {
        if (! $user->isSuperAdmin()) {
            return false;
        }

        $activeSuperAdmins = User::query()
            ->where('role', UserRole::SUPER_ADMIN)
            ->where('is_active', true)
            ->count();

        return $activeSuperAdmins <= 1
            && ($payload['role'] !== UserRole::SUPER_ADMIN || ! $payload['is_active']);
    }
}
