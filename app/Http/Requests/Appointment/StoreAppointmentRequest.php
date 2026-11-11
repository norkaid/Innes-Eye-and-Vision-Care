<?php

namespace App\Http\Requests\Appointment;

use App\Http\Requests\Concerns\ChecksSpam;
use App\Models\Branch;
use App\Models\Service;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAppointmentRequest extends FormRequest
{
    use ChecksSpam {
        withValidator as spamValidator;
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_name' => trim((string) $this->input('customer_name')),
            'customer_email' => strtolower(trim((string) $this->input('customer_email'))),
            'customer_phone' => trim((string) $this->input('customer_phone')),
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:2', 'max:150'],
            'customer_email' => ['required', 'string', 'email:rfc', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{7,20}$/'],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('is_published', true)->whereNull('deleted_at')],
            'requested_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'requested_time' => ['required', 'date_format:H:i'],
            'customer_note' => ['nullable', 'string', 'max:500'],
            'privacy_consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_phone.regex' => 'Please enter a valid phone number, e.g. +233 24 000 0000.',
            'requested_date.after_or_equal' => 'Please choose a date in the future.',
            'requested_time.required' => 'Please choose one of the available times.',
            'privacy_consent.accepted' => 'Please confirm you agree to us using your details to handle this request.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_name' => 'name', 'customer_email' => 'email address', 'customer_phone' => 'phone number',
            'branch_id' => 'branch', 'service_id' => 'service', 'requested_date' => 'preferred date', 'requested_time' => 'preferred time',
            'customer_note' => 'note',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->spamValidator($validator);

        $validator->after(function (Validator $v) {
            if ($v->errors()->hasAny(['branch_id', 'service_id', 'requested_date', 'requested_time'])) {
                return;
            }
            $branch = Branch::with('openingHours')->find($this->integer('branch_id'));
            $service = Service::find($this->integer('service_id'));

            if (! $branch->services()->whereKey($service->id)->exists()) {
                $v->errors()->add('service_id', 'This service is not offered at the selected branch. Please choose another service or branch.');

                return;
            }

            $availability = app(AvailabilityService::class);
            $date = Carbon::parse($this->input('requested_date'));
            if (! $availability->isBookableDate($branch, $date)) {
                $v->errors()->add('requested_date', 'The branch is not taking requests for this date. Please choose another date.');

                return;
            }
            if (! $availability->isSlotAvailable($branch, $date, $this->input('requested_time'))) {
                $v->errors()->add('requested_time', 'This time is no longer available. Please choose another time.');
            }
        });
    }

    /** Only the fields a customer is allowed to supply. */
    public function appointmentData(): array
    {
        return $this->safe()->only(['customer_name', 'customer_email', 'customer_phone', 'branch_id', 'service_id', 'requested_date', 'requested_time', 'customer_note']);
    }
}
