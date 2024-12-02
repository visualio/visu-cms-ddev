<?php

namespace App\Services;

use Nette\Forms\Form;

class RecaptchaService
{
    public const FIELD_RECAPTCHA = "recaptcha";

    public function __construct(
        private readonly ParamService $paramService
    ) {
    }

    public function init(Form $form): void
    {
        $form
            ->addHidden(self::FIELD_RECAPTCHA)
            ->setHtmlAttribute('data-key', $this->paramService->getRecaptchaPublicKey());
    }

    public function verify(Form $form): void
    {
        $token = $form->getValues('array')[self::FIELD_RECAPTCHA] ?? throw new \Exception('Form must contain recaptcha value');

        $post_data = http_build_query(
            [
                'secret' => $this->paramService->getRecaptchaSecretKey(),
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR'],
            ]
        );

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $post_data,
            ]
        ];

        $context = stream_context_create($opts);
        $response = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
        $result = json_decode($response);

        if (!$result->success || $result->score < 0.5) {
            throw new RecaptchaException('Recaptcha test failed');
        }
    }
}