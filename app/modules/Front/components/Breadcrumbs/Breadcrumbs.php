<?php

declare(strict_types=1);

namespace App\FrontModule\Components;

use Nette\Application\UI\Control;
use Nette\Application\LinkGenerator;
use Nette\Utils\ArrayHash;

/* @property-read BreadcrumbsTemplate $template */
class Breadcrumbs extends Control {

    public function __construct(
        private readonly LinkGenerator $linkGenerator,
    )
    {}

    public function render(): void
    {
        $this->template->items = ArrayHash::from([
            [
                'linkUrl' => $this->linkGenerator->link('Front:Homepage:default'),
                'linkText' => 'Homepage',
            ],
        ]);
        $this->template->setFile(__DIR__ . '/Breadcrumbs.latte');
        $this->template->render();
    }
}