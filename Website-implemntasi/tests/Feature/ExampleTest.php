<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Jangan bergantung pada service Python saat pengujian.
        Http::fake([
            '*/health' => Http::response(['status' => 'ok'], 200),
        ]);
    }

    public function test_dashboard_returns_a_successful_response(): void
    {
        $this->get('/')->assertOk()->assertSee('Analisis Paket');
    }

    public function test_riwayat_page_returns_a_successful_response(): void
    {
        $this->get('/riwayat')->assertOk()->assertSee('Riwayat Prediksi');
    }

    public function test_api_model_returns_information(): void
    {
        $this->getJson('/api/model')
            ->assertOk()
            ->assertJsonPath('algoritma', 'SVM RBF (C=5) + StandardScaler')
            ->assertJsonStructure(['kelas', 'metrik', 'endpoint']);
    }
}
