<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guest_is_redirected_to_login_from_the_dashboard(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }
}
