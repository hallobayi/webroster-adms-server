<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Oficina;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockCommTest extends TestCase
{
    use RefreshDatabase;

    private function office(): Oficina
    {
        return Oficina::create([
            'idempresa' => 1,
            'idoficina' => 2,
            'ubicacion' => 'Cancun',
            'public_url' => 'https://station.test',
            'token' => 'token',
            'iatacode' => 'CUN',
            'timezone' => 'Asia/Jakarta',
        ]);
    }

    private function device(string $sn = 'SN-1'): Device
    {
        return Device::create([
            'serial_number' => $sn,
            'idempresa' => 1,
            'idoficina' => 2,
            'idreloj' => '1',
            'name' => 'Test device',
        ]);
    }

    /**
     * test the clock/test
     */
    public function test_iclock_test_returns_200(): void
    {
        $response = $this->get('/iclock/test');

        $response->assertStatus(200);
    }


    /**
     * test the clock/cdata
     */
    public function test_iclock_getrequest_returns_200(): void
    {
        $response = $this->get('/iclock/getrequest');

        $response->assertStatus(200);
    }

    /**
     * The handshake is what keeps the "Online" column in the device list
     * truthful. If this regresses the list silently shows "Unknown" forever,
     * which is hard to tell apart from a device that is genuinely offline.
     */
    public function test_handshake_marks_the_device_online(): void
    {
        $this->office();
        $device = $this->device();

        $this->assertNull($device->online);

        $this->get('/iclock/cdata?SN=SN-1&options=all')->assertOk();

        $this->assertNotNull($device->fresh()->online, 'handshake must set devices.online');
    }

    /**
     * A terminal that has not been registered yet still handshakes. updateOrInsert
     * creates a row for it — which is how a serial number typo shows up as a
     * nameless duplicate device instead of an error.
     */
    public function test_handshake_for_an_unregistered_serial_number_creates_a_row(): void
    {
        $this->office();

        $this->get('/iclock/cdata?SN=UNKNOWN-SN&options=all')->assertOk();

        $this->assertNotNull(
            Device::where('serial_number', 'UNKNOWN-SN')->first(),
            'updateOrInsert registers an unknown serial number'
        );
    }

    /**
     * getrequest is the terminal's frequent keepalive, so it also refreshes
     * online. Unlike the handshake it does NOT create a row — an unknown serial
     * number gets an error string instead.
     */
    public function test_getrequest_refreshes_online_for_a_known_device(): void
    {
        $this->office();
        $device = $this->device();

        $this->get('/iclock/getrequest?SN=SN-1')->assertOk();

        $this->assertNotNull($device->fresh()->online);
    }

    public function test_getrequest_rejects_an_unknown_serial_number(): void
    {
        $this->office();

        $response = $this->get('/iclock/getrequest?SN=UNKNOWN-SN');

        $this->assertStringContainsString('Device not found', $response->getContent());
        $this->assertNull(Device::where('serial_number', 'UNKNOWN-SN')->first());
    }

    /**
     * OpStamp used to be sent as "Y-m-d H:i:s" and TimeZone as an IANA name.
     * Both are the wrong type for the protocol, and a terminal that cannot
     * parse the options block never settles into a normal polling rhythm.
     */
    public function test_handshake_response_uses_protocol_correct_types(): void
    {
        $this->office();
        $this->device();

        $body = $this->get('/iclock/cdata?SN=SN-1&options=all')->getContent();

        // OpStamp is a unix timestamp in seconds. Lines end with CRLF, so the
        // pattern has to tolerate the trailing \r.
        $this->assertSame(1, preg_match('/^OpStamp=(\d+)\r?$/m', $body, $m), 'OpStamp must be an integer');
        $this->assertGreaterThan(1_600_000_000, (int) $m[1]);
        $this->assertLessThan(2_000_000_000, (int) $m[1]);

        // TimeZone is an hour offset, never an IANA identifier. The working
        // deployments omit it entirely.
        $this->assertStringNotContainsString('TimeZone=Asia/Jakarta', $body);
        $this->assertStringNotContainsString('TimeZone=', $body);

        $this->assertStringContainsString('TransTimes=00:00;14:05', $body);
        $this->assertStringContainsString('Stamp=9999', $body);
        $this->assertStringContainsString('TransFlag=' . config('adms.trans_flag'), $body);
    }
}
