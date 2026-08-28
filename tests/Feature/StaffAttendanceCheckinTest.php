<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Livewire\Staff\AttendanceCheckin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class StaffAttendanceCheckinTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private AttendanceLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->create([
            'name' => 'KINANTHY NAJMI SUHOYO',
            'email' => 'kinantii1519@gmail.com',
            'role' => 'staff_accounting',
            'is_active' => true,
        ]);

        $this->location = AttendanceLocation::create([
            'name' => 'Kantor Pusat M2B',
            'type' => 'office',
            'latitude' => 3.6193041,
            'longitude' => 98.655271,
            'radius_meters' => 1000,
            'is_active' => true,
        ]);
    }

    public function test_staff_can_view_attendance_page()
    {
        $this->actingAs($this->staff);

        $this->get(route('staff.attendance'))
            ->assertOk()
            ->assertSee('Presensi & Absensi Harian M2B')
            ->assertSee('KINANTHY NAJMI SUHOYO');
    }

    public function test_staff_can_checkin_successfully()
    {
        $this->actingAs($this->staff);

        Livewire::test(AttendanceCheckin::class)
            ->call('setCoordinates', 3.6193041, 98.655271)
            ->set('notes', 'Checkin dari browser kantor')
            ->call('submitCheckin')
            ->assertHasNoErrors()
            ->assertSee('Check-In Masuk berhasil dicatat');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->staff->id,
            'type' => 'checkin',
            'location_id' => $this->location->id,
        ]);
    }

    public function test_staff_can_checkout_successfully_after_checkin()
    {
        $this->actingAs($this->staff);

        // Checkin first
        Attendance::create([
            'user_id' => $this->staff->id,
            'location_id' => $this->location->id,
            'type' => 'checkin',
            'latitude' => 3.6193041,
            'longitude' => 98.655271,
            'created_at' => now()->subHours(8),
        ]);

        Livewire::test(AttendanceCheckin::class)
            ->call('setCoordinates', 3.6193041, 98.655271)
            ->set('notes', 'Check-out selesai kerja')
            ->call('submitCheckout')
            ->assertHasNoErrors()
            ->assertSee('Check-Out Pulang berhasil dicatat');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->staff->id,
            'type' => 'checkout',
            'location_id' => $this->location->id,
        ]);
    }
}
