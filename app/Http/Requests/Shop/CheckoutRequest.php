<?php

namespace App\Http\Requests\Shop;

use App\Http\Requests\Concerns\ChecksSpam;
use App\Services\ShopService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    use ChecksSpam;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $options = array_keys(app(ShopService::class)->fulfilmentOptions());

        return [
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_email' => ['required', 'email:rfc', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{7,20}$/'],
            'fulfilment' => ['required', Rule::in($options)],
            'pickup_branch_id' => ['nullable', 'required_if:fulfilment,pickup', 'integer', Rule::exists('branches', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'delivery_address' => ['nullable', 'required_if:fulfilment,delivery', 'string', 'max:500'],
            'customer_note' => ['nullable', 'string', 'max:500'],
            'accept_terms' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_name' => 'name',
            'customer_email' => 'email address',
            'customer_phone' => 'phone number',
            'fulfilment' => 'collection or delivery',
            'pickup_branch_id' => 'pickup branch',
            'delivery_address' => 'delivery address',
            'customer_note' => 'note',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_phone.regex' => 'Enter a valid phone number, e.g. +233 24 123 4567.',
            'accept_terms.accepted' => 'Please confirm that your order details are correct.',
            'pickup_branch_id.required_if' => 'Choose the branch where you will collect your order.',
            'delivery_address.required_if' => 'Enter the delivery address.',
        ];
    }
}
