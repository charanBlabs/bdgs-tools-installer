<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Root redirects to admin login, so we follow the redirect
        $response = $this->get('/');
        $response->assertStatus(302); // Redirects to admin login
        
        // Test the admin login page directly
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }
}
