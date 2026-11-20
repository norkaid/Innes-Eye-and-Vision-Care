<?php

namespace App\Exceptions;

use RuntimeException;

/** Paystack could not be reached or refused the request. The message is safe to show. */
class PaymentGatewayException extends RuntimeException {}
