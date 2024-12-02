<?php

namespace App\AdminModule\Forms\Controls;


use App\Components\ImageUploadEditor;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Html;

class ImageUploadEditorControl extends TextInput
{
    private ?string $caption;
    private Presenter $presenter;


    public function __construct(
        Presenter $presenter,
        $caption = null
    ) {
        parent::__construct($caption);
        $this->caption = $caption;
        $this->presenter = $presenter;
    }

    public function getControl(): Html
    {
        $value = $this->getRenderedValue();
        $html = parent::getControl();
        $editor = new ImageUploadEditor(
            $html,
            $this->presenter,
            $this->htmlId,
            $this->caption,
            $value
        );
        return Html::fromHtml($editor->render())->setName('div');
    }

    public function getHtmlName(): string
    {
        return parent::getHtmlName();
    }
}
