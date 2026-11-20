<?php

namespace App\Contracts;

use App\Models\Payment;

/** Something that can be paid for through Paystack (an order or an appointment request). */
interface Payable
{
    /** Amount still to pay, in pesewas, calculated from the database. */
    public function amountDueMinor(): int;

    public function paymentEmail(): string;

    /** Short description shown on Paystack's page and in the admin. */
    public function paymentDescription(): string;

    /** Called once, inside the payment's database transaction, when Paystack confirms payment. */
    public function markPaid(Payment $payment): void;

    /** Where to send the customer after paying. */
    public function paymentReturnUrl(): string;
}
