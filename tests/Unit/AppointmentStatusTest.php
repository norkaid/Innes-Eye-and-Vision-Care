<?php

namespace Tests\Unit;

use App\Enums\AppointmentStatus;
use PHPUnit\Framework\TestCase;

class AppointmentStatusTest extends TestCase
{
    public function test_transitions(): void
    {
        $this->assertTrue(AppointmentStatus::Pending->canTransitionTo(AppointmentStatus::Confirmed));
        $this->assertFalse(AppointmentStatus::Pending->canTransitionTo(AppointmentStatus::Completed));
        $this->assertTrue(AppointmentStatus::Confirmed->canTransitionTo(AppointmentStatus::Completed));
        $this->assertSame([], AppointmentStatus::Cancelled->staffTransitions());
        $this->assertSame([], AppointmentStatus::Completed->staffTransitions());
    }

    public function test_every_status_has_a_text_label_and_icon(): void
    {
        foreach (AppointmentStatus::cases() as $status) {
            $this->assertNotEmpty($status->label());
            $this->assertNotEmpty($status->icon());
        }
    }

    public function test_customer_rules(): void
    {
        $this->assertTrue(AppointmentStatus::Pending->customerCanCancel());
        $this->assertFalse(AppointmentStatus::Completed->customerCanCancel());
        $this->assertFalse(AppointmentStatus::RescheduleRequested->customerCanRequestReschedule());
    }
}
