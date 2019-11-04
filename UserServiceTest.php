<?php

namespace Tests;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class UserServiceTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesTableSeeder']);
        $this->artisan('db:seed', ['--class' => 'UsersTableSeeder']);
    }

    public function testGetAllUserShouldSuccess()
    {
        $userService = new UserService();

        $data = $userService->getAllUser()->getData(true);

        $this->assertCount(5, $data['data']);
        $this->assertCount(5, Arr::pluck($data['data'], 'action'));
        $this->assertCount(5, Arr::pluck($data['data'], 'roles'));

        $expectedRoles = implode(',', User::pluck('username')->toArray());
        $actualRoles = implode(',', Arr::pluck($data['data'], 'username'));

        $this->assertSame($expectedRoles, $actualRoles);
    }

    public function testCreateNewUserShouldSuccess()
    {
        $inputs = [
            'api_token' => 'testuser',
            'username'  => 'testuser',
            'email'  => 'testuser@example.com',
            'password' => '123456',
            'is_active' => 1,
            'roles' => ['superadmin']
        ];

        $userService = new UserService();

        $newUser = $userService->createNewUser($inputs);

        // Check password is correct.
        $this->assertTrue(Hash::check($inputs['password'], $newUser->password));
        $this->assertSame($inputs['api_token'], $newUser->api_token);
        $this->assertSame($inputs['username'], $newUser->username);
        $this->assertSame($inputs['email'], $newUser->email);
        $this->assertSame($inputs['is_active'], $newUser->is_active);
        $this->assertTrue($newUser->hasRole($inputs['roles'][0]));
    }

    public function testUpdateUserWithPasswordShouldSuccess()
    {
        $user = User::create([
            'api_token' => 'testuser',
            'username'  => 'testuser',
            'email'  => 'testuser@example.com',
            'password'  => bcrypt('123456'),
            'is_active' => 1,
        ]);

        $inputs = [
            'username'  => 'updateuser',
            'email'  => 'updateuser@example.com',
            'roles' => ['manager'],
            'phone' => '',
            'password' => 'password1234@@',
            'password_confirmation' => 'password1234@@'
        ];

        $userService = new UserService();

        $userService->updateUser($user, $inputs);

        $updatedUser = $user->refresh();

        // Check password is correct.
        $this->assertTrue(Hash::check($inputs['password'], $updatedUser->password));
        $this->assertSame($user['api_token'], $updatedUser->api_token);
        $this->assertSame($inputs['username'], $updatedUser->username);
        $this->assertSame($inputs['email'], $updatedUser->email);
        $this->assertSame($inputs['phone'], $updatedUser->phone);
        $this->assertSame(0, $updatedUser->is_active);
        $this->assertTrue($updatedUser->hasRole($inputs['roles'][0]));
    }

    public function testUpdateUserWithoutPasswordShouldSuccess()
    {
        $password = '123456';

        $user = User::create([
            'api_token' => 'testuser',
            'username'  => 'testuser',
            'email'  => 'testuser@example.com',
            'password'  => bcrypt($password),
            'is_active' => 1,
        ]);

        $inputs = [
            'username' => $user->username,
            'email'  => 'updateuser@example.com',
            'roles' => ['superadmin'],
            'api_token' => $user->api_token,
            'phone' => $user->phone
        ];

        $userService = new UserService();

        $updatedUser = $userService->updateUser($user, $inputs);

        // Check password must not change..
        $this->assertTrue(Hash::check($password, $updatedUser->password));
        $this->assertSame($user->api_token, $updatedUser->api_token);
        $this->assertSame($user->username, $updatedUser->username);
        $this->assertSame($inputs['email'], $updatedUser->email);
        $this->assertSame($user->phone, $updatedUser->phone);
        $this->assertSame($user->is_active, $updatedUser->is_active);
        $this->assertTrue($updatedUser->hasAllRoles($inputs['roles']));
    }

    public function testUpdateApiKeyShouldSuccess()
    {
        $password = '123456';

        $user = User::create([
            'api_token' => 'testuser',
            'username'  => 'testuser',
            'email'  => 'testuser@example.com',
            'password'  => bcrypt($password),
            'is_active' => 1,
        ]);

        $userService = new UserService();

        $newApiKey = $userService->updateApiKey($user);

        $updatedUser = $user->refresh();

        // Check password must not change..
        $this->assertTrue(Hash::check($password, $updatedUser->password));
        $this->assertSame($user->api_token, $newApiKey);
        $this->assertSame($user->username, $updatedUser->username);
        $this->assertSame($user->email, $updatedUser->email);
        $this->assertSame($user->is_active, $updatedUser->is_active);
    }

    public function testDeleteUserShouldSuccess()
    {
        $userService = new UserService();

        $user = User::first();

        $result = $userService->deleteUser($user);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('users', ['username' => $user->username]);
    }
}
