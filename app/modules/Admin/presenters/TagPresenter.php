<?php

declare(strict_types=1);

namespace App\AdminModule\Presenters;

use App\AdminModule\Factories\TagManagerFactory;
use App\Components\TagManager;

final class TagPresenter extends BasePresenter
{
    private TagManagerFactory $tagManagerFactory;

    public function __construct(
        TagManagerFactory $tagManagerFactory
    )
    {
        parent::__construct();
        $this->tagManagerFactory = $tagManagerFactory;
    }

    public function createComponentTagManager(): TagManager
    {
        return $this->tagManagerFactory->create();
    }

}
