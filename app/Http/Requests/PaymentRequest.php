<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_on' => ['required', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $invoice = $this->route('invoice');

            if (! $invoice) {
                return;
            }

            $invoice->load('items', 'payments');

            $amountDue = bcsub(
                (string) $invoice->grand_total,
                (string) $invoice->amount_paid,
                2
            );

            if (bccomp((string) $this->input('amount'), $amountDue, 2) > 0) {
                $validator->errors()->add(
                    'amount',
                    "Payment amount exceeds the amount due ({$amountDue})."
                );
            }
        });
    }
}
