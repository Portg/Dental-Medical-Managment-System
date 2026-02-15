<?php

namespace App\Services;

use App\BookAppointment;

class BookAppointmentService
{
    /**
     * Create a new appointment booking request.
     */
    public function create(string $fullName, string $phoneNumber, ?string $email, string $message): ?BookAppointment
    {
        return BookAppointment::create([
            'full_name' => $fullName,
            'phone_number' => $phoneNumber,
            'email' => $email,
            'message' => $message,
        ]);
    }
}
