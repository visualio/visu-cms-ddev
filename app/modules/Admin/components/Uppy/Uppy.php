<?php

namespace App\Components;

use Nette\Application\UI\Control;

class Uppy extends Control
{
    public const ADAPTER_TUS = "tus";
    public const ADAPTER_XHR = "xhr";

    private string $inputControl;
    private string $id;
    private string $uploadEndpoint;
    private bool $isMultiple;
    private array $restrictions;
    private ?string $successEndpoint;
    private array $files;
    private ?string $removeEndpoint;
    /** @var callable */
    private $onThumbnailRender;
    private ?string $sortEndpoint;
    private string $adapter;

    public function __construct(
        string $html,
        string $id,
        string $uploadEndpoint,
        callable $onThumbnailRender,
        string $successEndpoint = null,
        string $removeEndpoint = null,
        string $sortEndpoint = null,
        array $files = [],
        bool $isMultiple = false,
        array $restrictions = [],
        string $adapter = self::ADAPTER_XHR
    )
    {
        $this->inputControl = $html;
        $this->id = $id;
        $this->uploadEndpoint = $uploadEndpoint;
        $this->isMultiple = $isMultiple;
        $this->restrictions = $restrictions;
        $this->successEndpoint = $successEndpoint;
        $this->files = $files;
        $this->removeEndpoint = $removeEndpoint;
        $this->onThumbnailRender = $onThumbnailRender;
        $this->sortEndpoint = $sortEndpoint;
        $this->adapter = $adapter;
    }

    public function createTemplateParams()
    {
        return [
            'id' => $this->id,
            'files' => $this->files,
            'onThumbnailRender' => $this->onThumbnailRender,
            'inputControl' => $this->inputControl,
            'uploadEndpoint' => $this->uploadEndpoint,
            'successEndpoint' => $this->successEndpoint,
            'sortEndpoint' => $this->sortEndpoint,
            'removeEndpoint' => $this->removeEndpoint,
            'isMultiple' => $this->isMultiple,
            'restrictions' => $this->restrictions,
            'adapter' => $this->adapter
        ];
    }

    public function render(): void
    {
        $this->template->render(__DIR__ . '/Uppy.latte', $this->createTemplateParams());
    }

    public function renderToString(): string
    {
        return $this->template->renderToString(__DIR__ . '/Uppy.latte', $this->createTemplateParams());
    }
}