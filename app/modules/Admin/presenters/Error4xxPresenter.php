<?php

declare(strict_types=1);

namespace App\AdminModule\Presenters;

use Nette;


final class Error4xxPresenter extends BasePresenter
{
  
  public function __construct()
	{
    parent::__construct();
	}

	public function startup(): void
	{
		parent::startup();
		if (!$this->getRequest()->isMethod(Nette\Application\Request::FORWARD)) {
			$this->error();
		}
	}
  
  public function renderDefault(Nette\Application\BadRequestException $exception): void
	{
    $this->template->errorCode = $exception->getCode();
	}

}
