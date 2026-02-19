<?php

namespace App\Services;

use App\Appointment;
use App\InsuranceCompany;
use App\OnlineBooking;
use App\Patient;
use App\Http\Helper\FunctionsHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OnlineBookingService
{
    /**
     * Get all insurance providers for the frontend form.
     */
    public function getInsuranceProviders(): Collection
    {
        return InsuranceCompany::all();
    }

    /**
     * Get filtered online bookings list for DataTables.
     */
    public function getBookingList(array $filters): Collection
    {
        $query = DB::table('online_bookings')
            ->whereNull('online_bookings.deleted_at')
            ->select(
                'online_bookings.*',
                DB::raw('DATE_FORMAT(online_bookings.start_date, "%d-%b-%Y") as start_date'),
                DB::raw('DATE_FORMAT(online_bookings.created_at, "%d-%b-%Y") as booking_date')
            );

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('online_bookings.full_name', 'like', '%' . $search . '%')
                  ->orWhere('online_bookings.email', 'like', '%' . $search . '%')
                  ->orWhere('online_bookings.phone_no', 'like', '%' . $search . '%');
            });
        } elseif (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween(DB::raw('DATE_FORMAT(sort_by, \'%Y-%m-%d\')'), [
                $filters['start_date'], $filters['end_date'],
            ]);
        } else {
            // Default: query without deleted appointments reference (fix original bug)
            $query->whereNull('online_bookings.deleted_at');
        }

        return $query->orderBy('sort_by', 'desc')->get();
    }

    /**
     * Create a new online booking.
     */
    public function createBooking(array $data): ?OnlineBooking
    {
        return OnlineBooking::create([
            'full_name' => $data['full_name'],
            'phone_no' => $data['phone_number'],
            'email' => $data['email'] ?? null,
            'start_date' => FunctionsHelper::convert_date($data['appointment_date']),
            'end_date' => FunctionsHelper::convert_date($data['appointment_date']),
            'start_time' => $data['appointment_time'],
            'message' => $data['visit_reason'],
            'insurance_company_id' => $data['insurance_provider'] ?? null,
            'visit_history' => $data['visit_history'],
        ]);
    }

    /**
     * Get a single booking with insurance company details.
     */
    public function getBookingDetail(int $id): ?object
    {
        return DB::table('online_bookings')
            ->leftJoin('insurance_companies', 'insurance_companies.id', 'online_bookings.insurance_company_id')
            ->whereNull('online_bookings.deleted_at')
            ->where('online_bookings.id', '=', $id)
            ->select('online_bookings.*', 'insurance_companies.name')
            ->first();
    }

    /**
     * Accept an online booking: update status, find/create patient, generate appointment.
     *
     * @return array{success: bool, message: string|null, phone: string|null}
     */
    public function acceptBooking(array $data, int $id, int $userId, int $branchId): array
    {
        $success = OnlineBooking::where('id', $id)->update(['status' => OnlineBooking::STATUS_ACCEPTED]);

        if (!$success) {
            return ['success' => false, 'message' => null, 'phone' => null];
        }

        $patientId = $this->findOrCreatePatient($data, $userId);

        $appointment = $this->generateAppointment($data, $patientId, $userId, $branchId);

        if (!$appointment) {
            return ['success' => false, 'message' => null, 'phone' => null];
        }

        $message = __('sms.appointment_scheduled', [
            'name' => $data['full_name'],
            'company' => config('app.company_name'),
            'date' => $data['appointment_date'],
            'time' => $data['appointment_time'],
        ]);

        return [
            'success' => true,
            'message' => $message,
            'phone' => $data['phone_number'] ?? null,
        ];
    }

    /**
     * Reject an online booking.
     */
    public function rejectBooking(int $id): bool
    {
        return (bool) OnlineBooking::where('id', $id)->update(['status' => OnlineBooking::STATUS_REJECTED]);
    }

    /**
     * Find existing patient or create a new one.
     */
    private function findOrCreatePatient(array $data, int $userId): int
    {
        $patient = Patient::where('phone_no', $data['phone_number'])
            ->orWhere('email', $data['email'] ?? '')
            ->first();

        if ($patient) {
            return $patient->id;
        }

        $hasInsurance = false;
        if (!empty($data['insurance_company_id'])) {
            $hasInsurance = true;
        }

        $newPatient = Patient::create([
            'patient_no' => Patient::PatientNumber(),
            'surname' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'phone_no' => $data['phone_number'],
            'has_insurance' => $hasInsurance,
            'insurance_company_id' => $data['insurance_company_id'] ?? null,
            '_who_added' => $userId,
        ]);

        return $newPatient->id;
    }

    /**
     * Generate an appointment from booking data.
     */
    private function generateAppointment(array $data, int $patientId, int $userId, int $branchId): ?Appointment
    {
        $time24 = date('H:i:s', strtotime($data['appointment_time']));

        return Appointment::create([
            'appointment_no' => Appointment::AppointmentNo(),
            'patient_id' => $patientId,
            'doctor_id' => $data['doctor_id'],
            'start_date' => $data['appointment_date'],
            'end_date' => $data['appointment_date'],
            'start_time' => $data['appointment_time'],
            'branch_id' => $branchId,
            'sort_by' => $data['appointment_date'] . ' ' . $time24,
            '_who_added' => $userId,
        ]);
    }
}
