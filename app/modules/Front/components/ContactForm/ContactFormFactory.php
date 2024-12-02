<?php


namespace App\FrontModule\Factories;

use App\FrontModule\Components\ContactForm;
use App\Services\LocaleService;
use App\Services\RecaptchaService;

class ContactFormFactory
{
    public function __construct(
        private readonly LocaleService $localeService,
        private readonly RecaptchaService $recaptchaService
    ) {
    }

    public function create(?callable $onSuccess = null, ?callable $onError = null): ContactForm
    {
        return new ContactForm(
            $this->localeService, $this->recaptchaService, $onSuccess, $onError
        );
    }
}