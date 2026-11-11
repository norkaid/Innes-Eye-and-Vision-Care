<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Customer-facing ownership rules. Staff access is governed by permission gates instead.
 */
class AppointmentPolicy
{
    /** Non-owners get a 404 so the existence of other people's references is not revealed. */
    public function view(User $user, Appointment $appointment): Response
    {
        return $this->owns($user, $appointment) ? Response::allow() : Response::denyAsNotFound();
    }

    public function cancel(User $user, Appointment $appointment): Response
    {
        if (! $this->owns($user, $appointment)) {
            return Response::denyAsNotFound();
        }

        return $appointment->status->customerCanCancel() ? Response::allow() : Response::deny('This appointment can no longer be cancelled online.');
    }

    public function reschedule(User $user, Appointment $appointment): Response
    {
        if (! $this->owns($user, $appointment)) {
            return Response::denyAsNotFound();
        }

        return $appointment->status->customerCanRequestReschedule() ? Response::allow() : Response::deny('This appointment can no longer be rescheduled online.');
    }

    private function owns(User $user, Appointment $appointment): bool
    {
        return $user->isCustomer() && $user->is_active && $appointment->user_id !== null && $appointment->user_id === $user->id;
    }
}
