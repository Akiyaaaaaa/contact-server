<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function testRegisterSuccess()
    {
        $response = $this->post('/api/users', [
            'username' => 'bento',
            'password' => 'rahasia',
            'name' => 'bento lokal'
        ]);
        $response->assertStatus(201)
            ->assertJson([
                "data" => [
                    'username' => 'bento',
                    'name' => 'bento lokal'
                ]
            ]);
    }


    public function testRegisterFailed()
    {
        $response = $this->post('/api/users', [
            'username' => '',
            'password' => '',
            'name' => ''
        ]);
        $response->assertStatus(400)
            ->assertJson([
                "errors" => [
                    'username' => [
                        "The username field is required"
                    ],
                    'password' => [
                        "The password field is required"
                    ],
                    'name' => [
                        "The name field is required"
                    ],
                ]
            ]);
    }


    public function testRegisterUsernameAlreadyExists()
    {
        $this->testRegisterSuccess();
        $response = $this->post('/api/users', [
            'username' => 'bento',
            'password' => 'rahasia',
            'name' => 'bento lokal'
        ]);
        $response->assertStatus(400)
            ->assertJson([
                "data" => [
                    'username' => 'bento',
                    'name' => 'bento lokal'
                ]
            ]);
    }

    public function testLoginSuccess()
    {
        $this->seed([UserSeeder::class]);
        $response = $this->post('/api/users/login', [
            'username' => 'test',
            'password' => 'test',
        ]);
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'username' => 'test',
                    'password' => 'test',
                ]
            ]);
        $user = User::where('username', 'test')->first();
        self::assertNotNull($user->token);
    }

    public function testLoginFailPasswordWrong()
    {
        $this->seed([UserSeeder::class]);
        $response = $this->post('/api/users/login', [
            'username' => 'test',
            'password' => 'salah',
        ]);
        $response->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => [
                        "username or password wrong"
                    ]
                ]
            ]);
    }

    public function testGetSuccess()
    {
        $this->seed([UserSeeder::class]);

        $this->get('/api/users/current', [
            'Authorization' => 'test'
        ])->assertStatus(200)
            ->assertJson([
                'data' => [
                    'username' => 'test',
                    'name' => 'test'
                ]
            ]);
    }

    public function testGetUnauthorized()
    {
        $this->seed([UserSeeder::class]);
        $this->get('/api/users/current')
            ->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => [
                        "unauthorized"
                    ]
                ]
            ]);
    }

    public function testGetInvalidToken()
    {
        $this->seed([UserSeeder::class]);

        $this->get('/api/users/current', [
            'Authorization' => 'salah'
        ])->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => [
                        "unauthorized"
                    ]
                ]
            ]);
    }

    public function testUpdateNameSuccess()
    {
        $this->seed([UserSeeder::class]);
        $oldUser = User::where('username', 'test')->first();

        $this->patch(
            '/api/users/current',
            [
                'name' => 'Bento'
            ],
            [
                'Authorization' => 'test'
            ]
        )->assertStatus(200)
            ->assertJson([
                'data' => [
                    'username' => 'test',
                    'name' => 'Bento'
                ]
            ]);

        $newUser = User::where('username', 'test')->first();
        self::assertNotEquals($oldUser->name, $newUser->name);
    }

    public function testUpdatePasswordSuccess()
    {
        $this->seed([UserSeeder::class]);
        $oldUser = User::where('username', 'test')->first();

        $this->patch(
            '/api/users/current',
            [
                'password' => 'baru'
            ],
            [
                'Authorization' => 'test'
            ]
        )->assertStatus(200)
            ->assertJson([
                'data' => [
                    'username' => 'test',
                    'name' => 'test'
                ]
            ]);

        $newUser = User::where('username', 'test')->first();
        self::assertNotEquals($oldUser->password, $newUser->password);
    }

    public function testUpdateFailed()
    {
        $this->seed([UserSeeder::class]);

        $this->patch(
            '/api/users/current',
            [
                'name' => 'baru'
            ],
            [
                'Authorization' => 'bento'
            ]
        )->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'unauthorized'
                    ]
                ]
            ]);
    }

    public function testLogoutSuccess()
    {
        $this->seed(UserSeeder::class);
        $this->delete(uri: '/api/users/logout', headers: [
            'Authorization' => 'test'
        ])->assertStatus(200)
            ->assertJson([
                'data' => true
            ]);

        $user = User::where('username', 'test')->first();
        self::assertNull($user->token);
    }

    public function testLogoutFailed()
    {
        $this->seed(UserSeeder::class);
        $this->delete(uri: '/api/users/logout', headers: [
            'Authorization' => 'salah'
        ])->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'unauthorized'
                    ]
                ]
            ]);
    }
}
