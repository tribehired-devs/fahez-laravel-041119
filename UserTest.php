<?php

namespace Test;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesTableSeeder']);
        $this->artisan('db:seed', ['--class' => 'UsersTableSeeder']);
    }

    public function testOnlySuperAdminCanViewUsers()
    {
        $inputs = [
            'api_token' => 'testuser',
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'password'  => bcrypt('123456'),
            'is_active' => 1,
            'roles' => ['staff']
        ];

        $user = User::create($inputs);

        $this->actingAs($user)->get(route('users.index'))
            ->assertStatus(403);
    }

    public function testOnlySuperAdminCanViewUserDetail()
    {
        $inputs = [
            'api_token' => 'testuser',
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'password'  => bcrypt('123456'),
            'is_active' => 1,
            'roles' => ['staff']
        ];

        $user = User::create($inputs);

        $this->actingAs($user)->get(route('users.show', $user))
            ->assertStatus(403);
    }

    public function testOnlySuperAdminCanViewCreateUser()
    {
        $inputs = [
            'api_token' => 'testuser',
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'password'  => bcrypt('123456'),
            'is_active' => 1,
            'roles' => ['staff']
        ];

        $user = User::create($inputs);

        $this->actingAs($user)->get(route('users.create'))
            ->assertStatus(403);
    }

    public function testOnlySuperAdminCanViewEditUser()
    {
        $inputs = [
            'api_token' => 'testuser',
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'password'  => bcrypt('123456'),
            'is_active' => 1,
            'roles' => ['staff']
        ];

        $user = User::create($inputs);

        $role = Role::first();

        $this->actingAs($user)->get(route('users.edit', $role))
            ->assertStatus(403);
    }

    public function testOnlySuperAdminCanDeleteUser()
    {
        $inputs = [
            'api_token' => 'testuser',
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'password'  => bcrypt('123456'),
            'is_active' => 1,
            'roles' => ['staff']
        ];

        $user = User::create($inputs);

        $role = Role::first();

        $this->actingAs($user)->get(route('users.destroy', $role))
            ->assertStatus(403);
    }

    public function testShowViewUsersPage()
    {
        $inputs = [
            'api_token' => 'testuser',
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'password'  => bcrypt('123456'),
            'is_active' => 1,
        ];

        $user = User::create($inputs);

        $user->assignRole(['superadmin']);

        $this->actingAs($user)->get(route('users.index'))
            ->assertStatus(200)
            ->assertSee('Available Users')
            ->assertSee('Create New User');
    }

    public function testShowCreateUserPage()
    {
        $inputs = [
            'api_token' => 'testuser',
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'password'  => bcrypt('123456'),
            'is_active' => 1,
        ];

        $user = User::create($inputs);

        $user->assignRole(['superadmin']);

        $this->actingAs($user)->get(route('users.create'))
            ->assertStatus(200)
            ->assertSee('Create New User');
    }

    public function testCreateUserShouldSuccess()
    {
        $user = User::first();

        $inputs = [
            'api_token' => 'testuser',
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'roles' => ['superadmin'],
            'is_active' => 'on',
            'phone' => '',
            'password' => 'password1234@@',
            'password_confirmation' => 'password1234@@'
        ];

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.create'))
            ->postJson(route('users.store'), $inputs)
            ->assertStatus(200)
            ->assertSee('Successfully created user ' . $inputs['username']);

        $newUser = User::where('username', $inputs['username'])->first();

        $this->assertSame($inputs['api_token'], $newUser->api_token);
        $this->assertSame($inputs['username'], $newUser->username);
        $this->assertSame($inputs['email'], $newUser->email);
        $this->assertNull($newUser->phone);
        $this->assertEquals(1, $newUser->is_active);
        $this->assertTrue(Hash::check($inputs['password'], $newUser->password));
        $this->assertTrue($newUser->hasAllRoles($inputs['roles']));
    }

    public function testRequiredFieldsDuringCreateUser()
    {
        $user = User::first();

        $inputs = [
            'username' => '',
            'email' => '',
            'phone' => '',
            'api_token' => '',
            'password' => '',
            'password_confirmation' => ''
        ];

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.create'))
            ->postJson(route('users.store'), $inputs)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'username' => 'The username field is required.',
                'email' => 'The email field is required.',
                'api_token' => 'The api token field is required.',
                'password' => 'The password field is required.',
                'roles' => 'The roles field is required.'
            ]);
    }

    public function testEmailAndUsernameShouldBeUniqueDuringCreateUser()
    {
        $user = User::first();

        $inputs = [
            'api_token' => 'admin',
            'username'  => 'admin',
            'email'  => 'admin@example.com',
            'roles' => ['admin'],
            'is_active' => 'on',
            'password' => 'password1234@@',
            'password_confirmation' => 'password1234@@'
        ];

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.create'))
            ->postJson(route('users.store'), $inputs)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'username' => 'The username has already been taken.',
                'email' => 'The email has already been taken.',
            ]);
    }

    public function testPasswordAndPasswordConfirmationMustMatchDuringCreateUser()
    {
        $user = User::first();

        $inputs = [
            'api_token' => 'testuser',
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'roles' => ['admin'],
            'is_active' => 'on',
            'password' => 'password1234@@',
            'password_confirmation' => 'password1234@@###'
        ];

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.create'))
            ->postJson(route('users.store'), $inputs)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'password' => 'The password confirmation does not match.',
            ]);
    }

    public function testPasswordMinimumLengthIs14DuringCreateUser()
    {
        $user = User::first();

        $inputs = [
            'api_token' => 'testuser',
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'roles' => ['admin'],
            'is_active' => 'on',
            'password' => 'Sales1@',
            'password_confirmation' => 'Sales1@'
        ];

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.create'))
            ->postJson(route('users.store'), $inputs)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'password' => 'The password must be at least 14 characters.',
            ]);
    }

    public function testPasswordMustContainOneSymbolDuringCreateUser()
    {
        $user = User::first();

        $inputs = [
            'api_token' => 'testuser',
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'roles' => ['admin'],
            'is_active' => 'on',
            'password' => 'password11111',
            'password_confirmation' => 'password11111'
        ];

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.create'))
            ->postJson(route('users.store'), $inputs)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'password' => 'The password must contain at least one digit, one lowercase character, one uppercase character and one special character.',
            ]);
    }

    public function testPasswordMustContainOneDigitDuringCreateUser()
    {
        $user = User::first();

        $inputs = [
            'api_token' => 'testuser',
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'roles' => ['admin'],
            'is_active' => 'on',
            'password' => 'password@@@@#####',
            'password_confirmation' => 'password@@@@#####'
        ];

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.create'))
            ->postJson(route('users.store'), $inputs)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'password' => 'The password must contain at least one digit, one lowercase character, one uppercase character and one special character.',
            ]);
    }

    public function testPasswordMustContainOneUppercaseDuringCreateUser()
    {
        $user = User::first();

        $inputs = [
            'api_token' => 'testuser',
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'roles' => ['admin'],
            'is_active' => 'on',
            'password' => 'password111@@',
            'password_confirmation' => 'password111@@'
        ];

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.create'))
            ->postJson(route('users.store'), $inputs)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'password' => 'The password must contain at least one digit, one lowercase character, one uppercase character and one special character.',
            ]);
    }

    public function testPasswordMustContainOneLowercaseDuringCreateUser()
    {
        $user = User::first();

        $inputs = [
            'api_token' => 'testuser',
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'roles' => ['admin'],
            'is_active' => 'on',
            'password' => 'PASSWORD1234@@',
            'password_confirmation' => 'PASSWORD1234@@'
        ];

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.create'))
            ->postJson(route('users.store'), $inputs)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'password' => 'The password must contain at least one digit, one lowercase character, one uppercase character and one special character.',
            ]);
    }

    public function testPasswordMinimumLengthIs14DuringUpdateUser()
    {
        $user = User::first();

        $inputs = [
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'roles' => ['apiuser'],
            'is_active' => 'on',
            'phone' => '123456',
            'password' => 'Sales1@',
            'password_confirmation' => 'Sales1@'
        ];

        $updateUser = User::find(5);

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.edit', $updateUser->id))
            ->putJson(route('users.update', $updateUser->id), $inputs)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'password' => 'The password must be at least 14 characters.',
            ]);
    }

    public function testPasswordMustContainOneSymbolDuringUpdateUser()
    {
        $user = User::first();

        $inputs = [
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'roles' => ['apiuser'],
            'is_active' => 'on',
            'phone' => '123456',
            'password' => 'Password11111',
            'password_confirmation' => 'Password11111'
        ];

        $updateUser = User::find(5);

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.edit', $updateUser->id))
            ->putJson(route('users.update', $updateUser->id), $inputs)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'password' => 'The password must contain at least one digit, one lowercase character, one uppercase character and one special character.',
            ]);
    }

    public function testPasswordMustContainOneDigitDuringUpdateUser()
    {
        $user = User::first();

        $inputs = [
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'roles' => ['apiuser'],
            'is_active' => 'on',
            'phone' => '123456',
            'password' => 'Password@@@@#####',
            'password_confirmation' => 'Password@@@@#####'
        ];

        $updateUser = User::find(5);

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.edit', $updateUser->id))
            ->putJson(route('users.update', $updateUser->id), $inputs)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'password' => 'The password must contain at least one digit, one lowercase character, one uppercase character and one special character.',
            ]);
    }

    public function testPasswordMustContainOneUppercaseDuringUpdateUser()
    {
        $user = User::first();

        $inputs = [
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'roles' => ['apiuser'],
            'is_active' => 'on',
            'phone' => '123456',
            'password' => 'password111@@',
            'password_confirmation' => 'password111@@'
        ];

        $updateUser = User::find(5);

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.edit', $updateUser->id))
            ->putJson(route('users.update', $updateUser->id), $inputs)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'password' => 'The password must contain at least one digit, one lowercase character, one uppercase character and one special character.',
            ]);
    }

    public function testPasswordMustContainOneLowercaseDuringUpdateUser()
    {
        $user = User::first();

        $inputs = [
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'roles' => ['apiuser'],
            'is_active' => 'on',
            'phone' => '123456',
            'password' => 'PASSWORD1234@@',
            'password_confirmation' => 'PASSWORD1234@@'
        ];

        $updateUser = User::find(5);

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.edit', $updateUser->id))
            ->putJson(route('users.update', $updateUser->id), $inputs)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'password' => 'The password must contain at least one digit, one lowercase character, one uppercase character and one special character.',
            ]);
    }

    public function testUpdateUserShouldSuccess()
    {
        $user = User::first();

        $updateUser = User::find(4);

        $inputs = [
            'phone' => '019555555555',
            'api_token' => $updateUser->api_token,
            'username'  => 'testuser',
            'email'  => 'testuser@gmail.com',
            'password'  => 'Password1234@@',
            'password_confirmation'  => 'Password1234@@',
            'is_active' => '',
            'roles' => ['superadmin', 'manager', 'apiuser']
        ];

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.edit', $updateUser))
            ->putJson(route('users.update', $updateUser), $inputs)
            ->assertStatus(200)
            ->assertSee('User has been updated.');

        $updatedUser = $updateUser->refresh();

        $this->assertEquals($inputs['phone'], $updatedUser->phone);
        $this->assertEquals($inputs['api_token'], $updatedUser->api_token);
        $this->assertEquals($inputs['username'], $updatedUser->username);
        $this->assertEquals($inputs['email'], $updatedUser->email);
        $this->assertEquals(0, $updatedUser->is_active);
        $this->assertTrue(Hash::check($inputs['password'], $updatedUser->password));
        $this->assertTrue($updateUser->hasAllRoles(['superadmin', 'manager', 'apiuser']));
    }

    public function testUpdateRolesShouldSuccessDuringUpdateUser()
    {
        $user = User::first();

        $updateUser = User::find(4);

        $inputs = [
            'phone' => $updateUser->phone,
            'api_token' => $updateUser->api_token,
            'username'  => $updateUser->username,
            'email'  => $updateUser->email,
            'roles' => ['superadmin', 'manager'],
        ];

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.edit', $updateUser))
            ->putJson(route('users.update', $updateUser), $inputs)
            ->assertStatus(200)
            ->assertSee('User has been updated.');

        $updatedUser = $updateUser->refresh();

        $updatedUser->hasAllRoles($inputs['roles']);
    }

    public function testRequiredFieldsDuringUpdateUser()
    {
        $user = User::first();

        $updateUser = User::find(4);

        $inputs = [
            'username' => '',
            'email' => '',
            'phone' => '',
            'password' => '',
            'password_confirmation' => ''
        ];

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.edit', $updateUser))
            ->putJson(route('users.update', $updateUser), $inputs)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'username' => 'The username field is required.',
                'email' => 'The email field is required.',
                'roles' => 'The roles field is required.'
            ]);
    }

    public function testEmailAndUsernameShouldBeUniqueDuringUpdateUser()
    {
        $user = User::first();

        $updateUser = User::find(4);

        $inputs = [
            'api_token' => 'admin',
            'username'  => 'admin',
            'email'  => 'admin@example.com',
            'roles' => ['admin'],
            'is_active' => 'on',
            'password' => 'password1234@@',
            'password_confirmation' => 'password1234@@'
        ];

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.edit', $updateUser))
            ->putJson(route('users.update', $updateUser), $inputs)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'username' => 'The username has already been taken.',
                'email' => 'The email has already been taken.',
            ]);
    }

    public function testPasswordAndPasswordConfirmationMustMatchDuringUpdateUser()
    {
        $user = User::first();

        $updateUser = User::find(4);

        $inputs = [
            'api_token' => 'testuser',
            'username'  => 'testuser',
            'email'  => 'testuser@test.com',
            'password'  => '123456',
            'roles' => ['admin'],
            'is_active' => 'on',
            'password' => 'password1234@@',
            'password_confirmation' => 'password1234@@$$$'
        ];

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.edit', $updateUser))
            ->putJson(route('users.update', $updateUser), $inputs)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'password' => 'The password confirmation does not match.',
            ]);
    }

    public function testOnlySuperadminCanUpdateApiKey()
    {
        $userWithManagerRole = User::find(5);

        $this->actingAs($userWithManagerRole)
            ->patchJson(route('users.update.api_key', $userWithManagerRole))
            ->assertStatus(401);
    }

    public function testUpdateApiKeyShouldSuccess()
    {
        $user = User::first();

        $updateUser = User::find(4);

        $response = $this->actingAs($user)
            ->patchJson(route('users.update.api_key', $updateUser));

        $oldApiKey = $updateUser->api_token;
        $newApiKey = $response->getContent();

        $this->assertNotSame($oldApiKey, $newApiKey);
        $this->assertEquals('200', $response->status());
    }

    public function testDeleteUserShouldSuccess()
    {
        $user = User::first();

        $deleteUser = User::find(4);

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('users.index'))
            ->delete(route('users.destroy', $deleteUser))
            ->assertStatus(200);

        $this->assertDatabaseMissing('users', ['username' => $deleteUser->username]);
    }
}
