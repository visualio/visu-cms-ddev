<?php

namespace App\FrontModule\Components;

use App\Services\LocaleService;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\UI\Control;

/* @property-read ChangeLocaleTemplate $template */
class ChangeLocale extends Control
{
    public function __construct(
        private LocaleService $localeService
    ){}

    public function render(): void
    {
        $this->template->locale = $this->localeService->getLocale();
        $this->template->localeNames = $this->localeService->getLocaleNames();
        $this->template->setFile(__DIR__ . '/ChangeLocale.latte');
        $this->template->render();
    }

    #[NoReturn] public function handleChangeLocale(string $locale): void
    {
        $this->getPresenter()->redirect('Homepage:default', ['locale' => $locale]);
    }
}