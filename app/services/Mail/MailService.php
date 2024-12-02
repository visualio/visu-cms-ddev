<?php

namespace App\Services;

use Nette\Application\LinkGenerator;
use Nette\Bridges\ApplicationLatte\TemplateFactory;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use App\Services\LocaleService;

class MailService
{
    public function __construct(
        private $contact,
        private Mailer $mailer,
        private TemplateFactory $templateFactory,
        private LinkGenerator $linkGenerator,
        private LocaleService $localeService,
    ) {
        $this->contact = (object) $contact;
    }

    public function sendUserNewPassword(string $email, string $token): void
    {
        $subject = "{$this->contact->title} - Vytvoření hesla";
        $template = $this->createTemplate();
        $templateFile = __DIR__ . '/assets/templates/userNewPassword.latte';
        $templateParams = ['token' => $token, 'title' => $subject, 'contact' => $this->contact];
        $mail = new Message();
        $mail->setFrom($this->contact->email);
        $mail->addTo($email);
        $mail->setSubject($subject);
        $mail->setHtmlBody($template->renderToString($templateFile, $templateParams));
        $this->mailer->send($mail);
    }

    protected function createTemplate()
    {
        $template = $this->templateFactory->createTemplate();
        $template->getLatte()->addProvider('uiControl', $this->linkGenerator);
        $template->getLatte()->addFilter('translate', [$this->localeService, 'translate']);
        return $template;
    }
}