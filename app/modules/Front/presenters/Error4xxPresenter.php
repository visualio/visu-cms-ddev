<?php

declare(strict_types=1);

namespace App\FrontModule\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\Request;


final class Error4xxPresenter extends BasePresenter
{
  
    public function __construct()
    {
        parent::__construct();
    }

    public function startup(): void
    {
        parent::startup();
        if (!$this->getRequest()->isMethod(Request::FORWARD)) {
            $this->error();
        }
    }

    public function renderDefault(BadRequestException $exception): void
    {
        $this->template->errorCode = $exception->getCode();
    }

}
