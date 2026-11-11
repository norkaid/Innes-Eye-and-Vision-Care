<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Validation\Validator;

/**
 * Basic spam protection for public forms: a hidden honeypot field and a minimum
 * fill time (encrypted timestamp, so it cannot be forged). Combined with rate limiting.
 */
trait ChecksSpam
{
    protected int $minimumSeconds = 3;

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (filled($this->input('website_url'))) {
                $v->errors()->add('form', 'Your submission could not be processed. Please try again.');

                return;
            }
            try {
                $started = (int) decrypt((string) $this->input('form_started_at'));
            } catch (DecryptException) {
                $v->errors()->add('form', 'Your form session expired. Please refresh the page and try again.');

                return;
            }
            if (now()->timestamp - $started < $this->minimumSeconds) {
                $v->errors()->add('form', 'That was very quick! Please check your details and submit again.');
            }
        });
    }
}
