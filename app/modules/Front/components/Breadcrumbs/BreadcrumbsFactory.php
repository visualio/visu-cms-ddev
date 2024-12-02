<?php


namespace App\FrontModule\Factories;

use App\FrontModule\Components\Breadcrumbs;
use Nette\Application\LinkGenerator;

class BreadcrumbsFactory
{

    public function __construct(
        private readonly LinkGenerator $linkGenerator,
    )
    {}

    public function create(): Breadcrumbs
    {
        return new Breadcrumbs($this->linkGenerator);
    }
}