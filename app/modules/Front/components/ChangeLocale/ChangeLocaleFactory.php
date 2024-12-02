<?php


namespace App\FrontModule\Factories;

use App\Services\LocaleService;
use App\FrontModule\Components\ChangeLocale;

class ChangeLocaleFactory
{
    public function __construct(
        private LocaleService $localeService
    ){}

    public function create(): ChangeLocale
    {
        return new ChangeLocale($this->localeService);
    }
}