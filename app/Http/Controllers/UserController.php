<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use AuthorizesRequests;

    protected const CHAMPS_TRIABLES = ['name', 'email', 'role', 'is_active', 'created_at'];

    public function __construct(
        protected UserService $userService,
        protected UserRepositoryInterface $users
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        [$champ, $direction] = $this->resoudreTri($request);
        $recherche = $request->input('search');

        $utilisateurs = $this->users->paginateSorted($champ, $direction, $recherche);

        if ($request->ajax()) {
            return view('users._table', compact('utilisateurs', 'champ', 'direction'))->render();
        }

        return view('users.index', compact('utilisateurs', 'champ', 'direction', 'recherche'));
    }

    protected function resoudreTri(Request $request): array
    {
        $champ = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');

        if (! in_array($champ, self::CHAMPS_TRIABLES, true)) {
            $champ = 'name';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        return [$champ, $direction];
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,mentor,stagiaire'],
        ]);

        $this->userService->creer($data);

        return redirect()
            ->route('users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        return view('users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $this->userService->modifier($user, $data);

        return redirect()
            ->route('users.index')
            ->with('success', 'Utilisateur modifié avec succès.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $this->userService->desactiver(auth()->user(), $user);

        return redirect()
            ->route('users.index')
            ->with('success', 'Utilisateur désactivé.');
    }
}
