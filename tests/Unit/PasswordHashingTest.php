<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordHashingTest extends TestCase
{
    public function test_password_is_hashed_when_assigned_to_a_user(): void
    {
        $plainPassword = 'mot-de-passe-test';
        $user = new User(['password' => $plainPassword]);

        $this->assertNotSame($plainPassword, $user->password);
        $this->assertTrue(Hash::check($plainPassword, $user->password));
    }
}
