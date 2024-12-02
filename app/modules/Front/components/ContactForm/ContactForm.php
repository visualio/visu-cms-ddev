<?php

declare(strict_types=1);

namespace App\FrontModule\Components;

use App\Services\RecaptchaException;
use App\Services\RecaptchaService;
use Nette\Application\UI\Control;
use App\Services\LocaleService;
use Nette\Application\UI\Form;

/* @property-read ContactFormTemplate $template*/
class ContactForm extends Control {

    public const FIELD_NAME = "name";
    public const FIELD_PHONE = "phone";
    public const FIELD_EMAIL = "email";
    public const FIELD_MESSAGE = "message";
    public const FIELD_GDPR = "gdpr";
    public const FIELD_CONSENT = "consent";
    public const FIELD_RECAPTCHA = "recaptcha";
    public const FIELD_SUBMIT = "submit";

    /* @var callable */
    public $onSuccess = null;
    /* @var callable */
    public $onError = null;

    public function __construct(
        private readonly LocaleService $localeService,
        private readonly RecaptchaService $recaptchaService,
        $onSuccess,
        $onError
    )
    {
        $this->onSuccess = $onSuccess;
        $this->onError = $onError;
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/ContactForm.latte');
        $this->template->render();
    }

    public function createComponentForm(): Form
    {
        $gdprConsent = $this->presenter->getHttpRequest()->getUrl()->getBasePath() . 'downloads/gdpr_file.pdf';

        $form = new Form();

        $this->recaptchaService->init($form);

        $form->addText(self::FIELD_NAME, $this->localeService->translate('form.field.fullname'))
            ->setRequired($this->localeService->translate('form.message.required', ['requiredItem' => '"%label"']));

        $form->addText(self::FIELD_PHONE, $this->localeService->translate('form.field.phone'));

        $form->addEmail(self::FIELD_EMAIL, $this->localeService->translate('form.field.email'))
            ->addRule(Form::EMAIL, $this->localeService->translate('form.message.invalidMail'))
            ->setRequired($this->localeService->translate('form.message.required', ['requiredItem' => '"%label"']));

        $form->addTextarea(self::FIELD_MESSAGE, $this->localeService->translate('form.field.message'))
            ->setRequired($this->localeService->translate('form.message.required', ['requiredItem' => '"%label"']));

        $form->addCheckbox(self::FIELD_GDPR, $this->localeService->translate('form.field.gdprConsent', ['link' => $gdprConsent]))
            ->setRequired($this->localeService->translate('form.message.requiredGdprConsent'));

        $form->addCheckbox(self::FIELD_CONSENT, $this->localeService->translate('form.field.newsletterConsent', ['link' => $gdprConsent]))
            ->setRequired($this->localeService->translate('form.message.requiredNewsletterConsent'));

        $form->addSubmit(self::FIELD_SUBMIT, $this->localeService->translate('form.field.submit'));

        $form->onSuccess[] = function (Form $form, array $values) {
            try {
                $this->recaptchaService->verify($form);
            } catch (RecaptchaException) {
                $form->addError($this->localeService->translate("form.error.recaptcha"));
            }
            if ($this->onSuccess)
                ($this->onSuccess)($values);
        };
        
        return $form;
    }

}