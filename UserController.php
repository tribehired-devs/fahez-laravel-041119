<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateRequest;
use App\Http\Requests\User\EditRequest;
use App\Models\User;
use App\Services\UserService;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\Role;

class UserController extends Controller
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * UserController constructor.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display list of user available.
     *
     * @return Factory|View
     */
    public function index()
    {
        return view('pages.user.index');
    }

    /**
     * Get all user.
     *
     * @return mixed
     * @throws Exception
     */
    public function get()
    {
        return $this->userService->getAllUser();
    }

    /**
     * Show details information of user.
     *
     * @param User $user
     *
     * @return Factory|View
     */
    public function show(User $user)
    {
        $userRoles = generateListElement($user->roles->toArray(), ['name']);

        return view('pages.user.show', compact('user', 'userRoles'));
    }

    /**
     * Form to create new user.
     *
     * @return Factory|View
     */
    public function create()
    {
        $roles = Role::select('name')->get();

        return view('pages.user.create', compact('roles'));
    }

    /**
     * Store new user in database.
     *
     * @param CreateRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateRequest $request)
    {
        $result = $this->userService->createNewUser($request->except('_token'));

        if ($result) {
            return redirect()->route('users.index')->with('message', 'Successfully created user ' . $request->username . '.');
        }

        return redirect()->back()->with('error', 'Failed to create user ' . $request->username . '.');
    }

    /**
     * Display form to edit user.
     *
     * @param User $user
     *
     * @return Factory|View
     */
    public function edit(User $user)
    {
        $roles = Role::select('name')->get();

        $userRoles = $user->roles->pluck('name')->toArray();

        return view('pages.user.edit', compact('user', 'roles', 'userRoles'));
    }

    /**
     * Update user information.
     *
     * @param User $user
     * @param EditRequest $request
     *
     * @return void
     */
    public function update(User $user, EditRequest $request)
    {
        $result = $this->userService->updateUser($user, $request->except('_token'));

        if ($result) {
            return redirect()->back()->with('message', 'User has been updated.');
        }

        return redirect()->back()->with('error', 'Failed to update ' . $user->username  . ' user');
    }

    /**
     * Update user API Key using user ID.
     *
     * @param User $user
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function updateApiKey(User $user)
    {
        $result = $this->userService->updateApiKey($user);

        return response()->json($result);
    }

    /**
     * Delete user in database using user ID.
     *
     * @param User $user
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(User $user)
    {
        $result = $this->userService->deleteUser($user);

        return response()->json($result);
    }
}
