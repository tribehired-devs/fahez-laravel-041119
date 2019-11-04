<?php

namespace App\Services;

use Exception;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class UserService
{
    /**
     * Retrieve all user using data table.
     *
     * @return mixed
     * @throws Exception
     */
    public function getAllUser()
    {
        $model = User::with('roles')->get();

        return DataTables::of($model)
            ->addColumn('roles', function ($user) {
                return generateListElement($user->roles->toArray(), ['name']);
            })
            ->addColumn('api_token', function ($user) {
                return '<div class="d-flex justify-content-between align-items-center"><div class="api-key-' . $user->id . '">' . $user->api_token .
                    '</div><button type="button" class="generate-api-key-btn btn btn-sm btn-primary" 
                    data-id="' . $user->id . '" data-username="' . $user->username . '">Generate</button>';
            })
            ->addColumn('action', function ($user) {
                if ($user->name === 'admin') {
                    return;
                }

                return '
                    <a href="' . route("users.show", $user->id) . '"data-toggle="tooltip" data-placement="left" title="" data-original-title="View"' . ' class="btn btn-xs btn-primary" style="width: 40px; padding: 5px; margin-bottom: 3px;"><i class="far fa-eye"></i></a>
                    <a href="' . route("users.edit", $user->id) . '"data-toggle="tooltip" data-placement="left" title="" data-original-title="Edit"' . ' class="btn btn-xs btn-info" style="width: 40px; padding: 5px; margin-bottom: 3px;"><i class="far fa-edit"></i></a>
                    <button type="button" class="delete-btn btn btn-xs btn-danger" style="width: 40px; padding: 5px; margin-bottom: 3px;"
                    data-id="' . $user->id . '" data-title="' . $user->username . '"><i class="far fa-trash-alt"></i></button>';
            })->rawColumns(['action', 'roles', 'api_token'])->toJson();
    }

    /**
     * Store new user in database,
     *
     * @param array $inputs
     *
     * @return bool|Model|User
     */
    public function createNewUser(array $inputs)
    {
        $inputs['is_active'] = isset($inputs['is_active']) ? 1 : 0;

        // Encrypt the password.
        $inputs['password'] = bcrypt($inputs['password']);

        DB::beginTransaction();

        if ($user = User::create($inputs)) {
            // Attach roles to the user.
            $user->assignRole($inputs['roles']);

            DB::commit();

            return $user;
        }

        DB::rollback();

        return false;
    }

    /**
     * Update existing user information in database,
     *
     * @param User $user
     * @param array $inputs
     *
     * @return bool|Model|User
     */
    public function updateUser(User $user, array $inputs)
    {
        $inputs['is_active'] = isset($inputs['is_active']) ? 1 : 0;

        $password = empty($inputs['password']) ? $user->password : bcrypt($inputs['password']);

        DB::beginTransaction();

        $user->username = $inputs['username'];
        $user->email = $inputs['email'];
        $user->phone = $inputs['phone'];
        $user->is_active = $inputs['is_active'];
        $user->password = $password;

        if ($user->save()) {
            $user = $user->refresh();
            $user->syncRoles($inputs['roles']);

            DB::commit();

            return $user;
        }

        DB::rollback();

        return false;
    }

    /**
     * Update User API key by user ID.
     *
     * @param User $user
     *
     * @return int
     * @throws Exception
     */
    public function updateApiKey(User $user)
    {
        $newApiKey = (string) Str::uuid();

        $user->api_token = $newApiKey;

        if ($user->save()) {
            return $newApiKey;
        }

        return false;
    }

    /**
     * Delete user from database by user ID.
     *
     * @param User $user
     *
     * @return int
     * @throws Exception
     */
    public function deleteUser(User $user)
    {
        if ($user->delete()) {
            return true;
        }

        return false;
    }
}
