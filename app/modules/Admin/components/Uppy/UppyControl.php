<?php

namespace App\AdminModule\Forms\Controls;

use App\Components\DynamicForm;
use App\Components\Uppy;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Forms\Controls\TextArea;
use Nette\Utils\Html;

class UppyControl extends TextArea
{
    private string $uploadEndpoint;
    private string $successEndpoint;
    private string $removeEndpoint;
    private string $sortEndpoint;
    private bool $isMultiple;
    private array $restrictions;
    private Presenter $presenter;
    /** @var callable */
    private $onThumbnailRender = null;

    public function __construct(
        Presenter $presenter,
        $caption = null,
        $isMultiple = false,
        private string $adapter = Uppy::ADAPTER_XHR
    ) {
        parent::__construct($caption);
        $this->isMultiple = $isMultiple;
        $this->restrictions = [];
        $this->presenter = $presenter;
    }


    public function getControl(): Html
    {
        $value = $this->getRenderedValue();
        $editor = new Uppy(
            (string) parent::getControl(),
            $this->htmlId,
            $this->uploadEndpoint,
            $this->onThumbnailRender ?: fn($filename) => $filename,
            $this->successEndpoint,
            $this->removeEndpoint,
            $this->sortEndpoint,
            $value ? explode(DynamicForm::DELIMITER, $value) : [],
            $this->isMultiple,
            $this->restrictions,
            $this->adapter
        );
        $editor->setParent($this->presenter);
        return Html::fromHtml($editor->renderToString())->setName('div');
    }

    public function setUploadEndpoint(string $url): self
    {
        $this->uploadEndpoint = $url;
        return $this;
    }

    public function setSuccessEndpoint(string $url): self
    {
        $this->successEndpoint = $url;
        return $this;
    }

    public function setRemoveEndpoint(string $url): self
    {
        $this->removeEndpoint = $url;
        return $this;
    }

    public function setSortEndpoint(string $url): self
    {
        $this->sortEndpoint = $url;
        return $this;
    }

    public function setThumbnailRenderHandler(callable $callback)
    {
        $this->onThumbnailRender = $callback;
    }

    public function setRestrictions(array $restrictions)
    {
        // https://uppy.io/docs/uppy/#restrictions
        $this->restrictions = $restrictions;
        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->isMultiple;
    }

}