<?php

declare(strict_types=1);

namespace App\FrontModule\Presenters;

use App\FrontModule\Components\Breadcrumbs;
use App\FrontModule\Components\ChangeLocale;
use App\FrontModule\Components\ContactForm;
use App\FrontModule\Types\BaseTemplate;
use App\Services\ParamService;
use App\Services\LocaleService;
use App\FrontModule\Factories\BreadcrumbsFactory;
use App\FrontModule\Factories\ContactFormFactory;
use App\FrontModule\Factories\ChangeLocaleFactory;

/* @property-read BaseTemplate $template */
class BasePresenter extends \App\BaseModule\Presenters\BasePresenter
{
    private ParamService $paramService;
    private LocaleService $localeService;
    private BreadcrumbsFactory $breadcrumbsFactory;
    private ContactFormFactory $contactFormFactory;
    private ChangeLocaleFactory $changeLocaleFactory;

    public function injectRepository(
        ParamService $paramService,
        LocaleService $localeService,
        BreadcrumbsFactory $breadcrumbsFactory,
        ContactFormFactory $contactFormFactory,
        ChangeLocaleFactory $changeLocaleFactory
    ) {
        $this->paramService = $paramService;
        $this->localeService = $localeService;
        $this->breadcrumbsFactory = $breadcrumbsFactory;
        $this->contactFormFactory = $contactFormFactory;
        $this->changeLocaleFactory = $changeLocaleFactory;
    }

    protected function beforeRender()
    {
        parent::beforeRender();
        $this->template->contact = $this->paramService->getContact();
        $this->template->socialLinks = $this->paramService->getSocial();
    }

    public function createComponentBreadcrumbs(): Breadcrumbs
    {
        return $this->breadcrumbsFactory->create();
    }

    public function createComponentContactForm(): ContactForm
    {
        return $this->contactFormFactory->create();
    }

    public function createComponentChangeLocale(): ChangeLocale
    {
        return $this->changeLocaleFactory->create();
    }

    public function translate(string $message, ...$args): string
    {
        return $this->localeService->translate($message, ...$args);
    }
}