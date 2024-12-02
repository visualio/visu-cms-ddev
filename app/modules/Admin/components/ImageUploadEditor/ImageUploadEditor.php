<?php

namespace App\Components;

use Nette\Application\UI\Control;
use Nette\ComponentModel\IComponent;

class ImageUploadEditor extends Control
{
    private ?string $source;
    private ?string $label;
    private string $inputControl;
    private string $id;

    public function __construct(
        string $html,
        IComponent $presenter,
        string $id,
        string $label = null,
        string $source = null
    )
    {
        $this->setParent($presenter);
        $this->source = $source;
        $this->label = $label;
        $this->inputControl = $html;
        $this->id = $id;
    }

    public function render()
    {
        $this->template->id = $this->id;
        $this->template->source = $this->source;
        $this->template->inputControl = $this->inputControl;
        return $this->template->renderToString(__DIR__ . '/ImageUploadEditor.latte');
    }

}