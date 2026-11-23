<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StaffRouteAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->string('role', 20)->default('CASHIER');
            $table->string('status', 20)->default('ACTIVE');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    private function createUser(string $name, string $username, string $role): User
    {
        $user = new User;
        $user->name = $name;
        $user->username = $username;
        $user->password = Hash::make('password123');
        $user->role = $role;
        $user->status = 'ACTIVE';
        $user->save();

        return $user;
    }

    public function test_guest_is_redirected_to_login_for_staff_routes(): void
    {
        $this->get('/staff')->assertRedirect(route('login'));

        $this->post('/staff', [
            'name' => 'Test Cashier',
            'username' => 'cashier_guest',
            'password' => 'secret123',
            'role' => 'CASHIER',
        ])->assertRedirect(route('login'));

        $this->delete('/staff/1')->assertRedirect(route('login'));
    }

    public function test_cashier_is_redirected_to_pos_for_staff_routes(): void
    {
        $cashier = $this->createUser('Cashier One', 'cashier1', 'CASHIER');
        $otherUser = $this->createUser('Cashier Two', 'cashier2', 'CASHIER');

        $this->actingAs($cashier)
            ->get('/staff')
            ->assertRedirect(route('pos'))
            ->assertSessionHas('error');

        $this->actingAs($cashier)
            ->post('/staff', [
                'name' => 'Unauthorized Staff',
                'username' => 'unauth_staff',
                'password' => 'secret123',
                'role' => 'CASHIER',
            ])
            ->assertRedirect(route('pos'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('users', ['username' => 'unauth_staff']);

        $this->actingAs($cashier)
            ->delete("/staff/{$otherUser->id}")
            ->assertRedirect(route('pos'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $otherUser->id]);
    }

    public function test_owner_can_manage_staff_but_cannot_delete_own_account(): void
    {
        $owner = $this->createUser('Shop Owner', 'owner1', 'OWNER');

        $this->actingAs($owner)
            ->get('/staff')
            ->assertOk()
            ->assertSee('Staff & Cashier Accounts', false)
            ->assertSee('owner1');

        $this->actingAs($owner)
            ->post('/staff', [
                'name' => 'New Cashier',
                'username' => 'cashier_new',
                'password' => 'secret123',
                'role' => 'CASHIER',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'username' => 'cashier_new',
            'role' => 'CASHIER',
        ]);

        $newCashier = User::where('username', 'cashier_new')->firstOrFail();

        $this->actingAs($owner)
            ->delete("/staff/{$owner->id}")
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('users', ['id' => $owner->id]);

        $this->actingAs($owner)
            ->delete("/staff/{$newCashier->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $newCashier->id]);
    }
}
