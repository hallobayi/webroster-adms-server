<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Columns the application reads but that no migration ever created.
 *
 * Both of these were found by reading the migrations rather than by a failure:
 * the code that uses them degrades quietly (a page shows "N/A", a delivery goes
 * out unsigned) instead of erroring.
 */
class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Device::$fillable lists modelo and devices/monitor.blade.php renders
     * `$device->modelo ?? 'N/A'`, so a missing column silently means "N/A" for
     * every device. 2024_11_21_013744_add_oficina_data_to_devices was supposed
     * to create it and never did - 2026_09_23_000020 does.
     */
    public function test_devices_has_a_modelo_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('devices', 'modelo'),
            'devices.modelo must exist - the monitor page renders it'
        );
    }

    public function test_webhooks_has_a_secret_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('webhooks', 'secret'),
            'webhooks.secret must exist - deliveries are signed with it'
        );
    }
}
