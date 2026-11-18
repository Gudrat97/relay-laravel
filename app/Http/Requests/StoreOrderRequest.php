<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'pickup_address' => ['required', 'string', 'max:255'],
            'dropoff_address' => ['required', 'string', 'max:255', 'different:pickup_address'],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'distance_km' => ['required', 'numeric', 'min:0.1', 'max:80'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_phone.regex' => 'Enter a phone number with 7 to 20 digits.',
            'dropoff_address.different' => 'The drop-off address must differ from the pick-up address.',
        ];
    }
}
