<?php

namespace App\FrontModule\Types;

use App\FrontModule\Presenters\BasePresenter;
use App\Services\Params\Contact;

class BaseTemplate extends \App\BaseModule\Templates\BaseTemplate
{
	public BasePresenter $presenter;
	public Contact $contact;
	public array $socialLinks;
}