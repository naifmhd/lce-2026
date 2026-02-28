<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $search = trim((string) request()->query('search', ''));

        $users = User::query()
            ->when(
                $search !== '',
                fn ($query) => $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roleKeys(),
                'created_at' => $user->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
            'roleOptions' => UserRole::options(),
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'roles' => $validated['roles'],
        ]);

        return redirect()->route('users.index', [
            'search' => $request->query('search'),
        ]);
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'roles' => $validated['roles'],
        ];

        if (isset($validated['password']) && is_string($validated['password']) && $validated['password'] !== '') {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);

        return redirect()->route('users.index', [
            'search' => $request->query('search'),
        ]);
    }
}
