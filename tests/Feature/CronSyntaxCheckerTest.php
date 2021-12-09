<?php

namespace Tests\Feature;

use Tests\TestCase;

class CronSyntaxCheckerTest extends TestCase
{
    public array $success = ['message' => 'date matches'];
    public array $dateError = ['error' => 'date does not match'];
    public array $offLimitError = ['error' => 'template is off the limits'];

    /** @test */
    public function matchingDate(): void
    {
        $response = $this->post('/api/check', ['template'=> '* * * * *', 'date' => '2021-12-4 15:03:00',]);

        $response->assertJson($this->success);
    }

    /** @test */
    public function invalidDate(): void
    {
        $response = $this->post('/api/check', ['template'=> '* * * * *', 'date' => '2021-14-4 15:03:00',]);

        $response->assertJson($this->dateError);
    }

    /** @test */
    public function mismatchingDate(): void
    {
        $response = $this->post('/api/check', ['template'=> '* * */2 * *', 'date' => '2021-12-3 15:03:00',]);

        $response->assertJson($this->dateError);
    }

    /** @test */
    public function templateInvalid(): void
    {
        $response = $this->post('/api/check', ['template'=> '* 99 * * *', 'date' => '2021-12-4 15:03:00',]);

        $response->assertJson($this->offLimitError);
    }

    /** @test */
    public function rangeValid(): void
    {
        $response = $this->post('/api/check', ['template'=> '15-25 * * * *', 'date' => '2021-12-3 15:20:00',]);

        $response->assertJson($this->success);
    }

    /** @test */
    public function commasValid(): void
    {
        $response = $this->post('/api/check', ['template'=> '15,16,17,18,19,20 * * * *', 'date' => '2021-12-3 15:18:00',]);

        $response->assertJson($this->success);
    }

    /** @test */
    public function slashValid(): void
    {
        $response = $this->post('/api/check', ['template'=> '* * */2 * *', 'date' => '2021-12-4 15:03:00',]);

        $response->assertJson($this->success);
    }
}
