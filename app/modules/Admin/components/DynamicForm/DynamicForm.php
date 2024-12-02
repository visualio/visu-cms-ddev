<?php

namespace App\Components;

use App\AdminModule\Forms\Controls\ImageUploadEditorControl;
use App\AdminModule\Forms\Controls\UppyControl;
use App\Model\Utils;
use App\Services\FileStorage;
use App\Services\LocaleService;
use App\Services\TusService;
use Latte\Essential\Filters;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\ComponentModel\IComponent;
use Nette\Forms\Container;
use Nette\Forms\ControlGroup;
use Nette\Forms\Controls\Button;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\HiddenField;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Forms\Controls\RadioList;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\TextArea;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Controls\UploadControl;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Utils\Html;
use Nette\Utils\Image;
use Nette\Utils\Strings;

class DynamicForm extends Control
{
    public const
        DELIMITER = ';',
        FIELD_ID = "id",
        NAMESPACE_WYSIWYG = "wysiwyg",
        FLAG_HAS_TRANSLATION = "has_translation",
        ORIGINAL_POST = "original_post",
        MARKER_MULTIPLIER_START = "multiplier_start",
        MARKER_MULTIPLIER_END = "multiplier_end",
        MARKER_MULTIPLIER_ITEM_START = "multiplier_item_start",
        MARKER_MULTIPLIER_ITEM_END = "multiplier_item_end",
        MARKER_TRANSLATION_START = "translation_start",
        MARKER_TRANSLATION_END = "translation_end",
        SESSION_FORM_DATA = "formData";

    private LocaleService $localeService;
    private $onRender;
    private $onSubmit;
    private FileStorage $fileStorage;
    private Form $form;
    private ?array $defaults;
    private TusService $tusService;
    private string $cta;
    private SessionSection $session;
    private bool $useTempFormData = false;
    private bool $isCompact;

    public function __construct(
        callable $onRender,
        callable $onSubmit,
        LocaleService $localeService,
        FileStorage $fileStorage,
        TusService $tusService,
        Session $session,
        ?array $defaults = null,
        $caption = null,
        bool $isCompact = false
    ) {
        $this->localeService = $localeService;
        $this->onRender = $onRender;
        $this->onSubmit = $onSubmit;
        $this->fileStorage = $fileStorage;
        $this->form = new Form();
        [$heading, $cta] = is_array($caption) ? $caption : [
            ($defaults ? "Upravit" : "Vytvořit") . " " . $caption,
            ($defaults ? "Upravit" : "Vytvořit") . " " . $caption
        ];
        $this->form->addGroup($heading);
        $this->defaults = $defaults;
        $this->tusService = $tusService;
        $this->cta = $cta;
        $this->session = $session->getSection("dynamicForm");
        $this->isCompact = $isCompact;
    }

    public function getTitle(): string
    {
        return $this->cta;
    }

    public function handleUploadWysiwygImage()
    {
        $file = $this->presenter->getHttpRequest()->getFile('upload');
        if ($file->isImage()) {
            $this->fileStorage->setNamespace(self::NAMESPACE_WYSIWYG);
            $this->presenter->sendJson($this->fileStorage->uploadWysiwygImage($file));
        }
    }

    public function handleAddMultiplierItem(string $name)
    {
        $path = explode("-", $name);
        $formData = $this->presenter->getHttpRequest()->getPost();
        Utils::setArrayValue(
            $path,
            $formData,
            function ($items) {
                $items[uniqid()] = [];
                return $items;
            }
        );
        $this->redraw($formData);
    }

    public function handleRemoveMultiplierItem(string $name, string $key)
    {
        $path = explode("-", $name);
        $formData = $this->presenter->getHttpRequest()->getPost();
        Utils::setArrayValue(
            $path,
            $formData,
            function ($items) use ($key) {
                unset($items[$key]);
                unset($items[self::MARKER_MULTIPLIER_START]);
                return $items;
            }
        );
        $this->redraw($formData);
    }


    public function handleFileUpload(string $htmlId)
    {
        $recentFiles = $this->tusService->getRecentInputFiles($htmlId);
        $formData = $this->presenter->getHttpRequest()->getPost();
        $path = explode("-", $htmlId);
        array_shift($path);
        Utils::setArrayValue(
            $path,
            $formData,
            function ($data) use ($recentFiles) {
                return join(
                    self::DELIMITER,
                    array_unique(
                        array_merge(
                            $data ? explode(self::DELIMITER, $data) : [],
                            $recentFiles
                        )
                    )
                );
            }
        );
        $this->redraw($formData);
    }

    public function handleFileRemove()
    {
        [$formData, $originalPost] = Utils::extractValues(
            $this->presenter->getHttpRequest()->getPost(),
            self::ORIGINAL_POST
        );
        $name = json_decode($originalPost);
        $this->tusService->removeFile($name);
        array_walk_recursive(
            $formData,
            function (&$value) use ($name) {
                if (Strings::contains($value, $name)) {
                    $files = explode(self::DELIMITER, $value);
                    $value = join(
                        self::DELIMITER,
                        array_filter($files, fn($file) => $file !== $name)
                    );
                }
            }
        );
        $this->redraw($formData);
    }

    public function handleFileSort(string $htmlId)
    {
        $path = explode("-", $htmlId);
        array_shift($path);
        [$formData, $originalPost] = Utils::extractValues(
            $this->presenter->getHttpRequest()->getPost(),
            self::ORIGINAL_POST
        );
        $body = json_decode($originalPost);
        Utils::setArrayValue(
            $path,
            $formData,
            fn() => join(self::DELIMITER, $body->idList)
        );
        $this->redraw($formData);
    }

    public function createComponentForm(): Form
    {
        ($this->onRender)($this);
        // handle submit
        $this->form->addSubmit("submit", $this->cta)->setOption('isCompact', $this->isCompact);
        $this->form->onSubmit[] = function (Form $form) {
            $unsafeValues = $this->presenter->getHttpRequest()->getPost();
            $files = $this->presenter->getHttpRequest()->getFiles();
            // repopulate form with post data for better values format
            $uppyMap = [];
            foreach ($this->form->getComponents() as $component) {
                if ($component instanceof UppyControl)
                    $uppyMap[$component->getName()] = $component->isMultiple();
                $this->form->removeComponent($component);
            }
            $id = $this->defaults[self::FIELD_ID] ?? null;
            $this->defaults = $unsafeValues;
            ($this->onRender)($this);
            $values = $this->form->getValues('array');
            // remove multiplier_start flag
            $values = Utils::unsetRecursive($values, self::MARKER_MULTIPLIER_START);
            // process uppy values
            $values = Utils::mapArrayKeys(
                function($key) use ($uppyMap, $values) {
                    $value = $values[$key];
                    if (!isset($uppyMap[$key]))
                        return $value;
                    $isMultiple = $uppyMap[$key];
                    if ($isMultiple) {
                        return array_map(fn($item) => $this->tusService->createFileUpload($item), explode(self::DELIMITER, $value));
                    }
                    return $this->tusService->createFileUpload($value);
                },
                $values
            );
            // trigger
            ($this->onSubmit)(array_replace_recursive($values, $files), $id ?? null, $form);
        };
        $this->form->setDefaults($this->getWorkFormData());
        return $this->form;
    }

    public function setAjax()
    {
        $this->form->setHtmlAttribute('data-ajax');
    }

    public function addText(string $name, string $label): TextInput
    {
        return $this->form->addText($name, $label);
    }

    public function addInteger(string $name, string $label): TextInput
    {
        return $this->form->addInteger($name, $label);
    }

    public function addDate(string $name, string $label): TextInput
    {
        return $this->form
            ->addText($name, $label)
            ->setHtmlType("date")
            ->setHtmlAttribute("class", "js-date uk-input uk-display-block");
    }

    public function addPassword(string $name, string $label): TextInput
    {
        return $this->form->addPassword($name, $label);
    }

    public function addColorPicker(string $name, string $label): TextInput
    {
        return $this->form->addText($name, $label)->setHtmlType("color");
    }

    public function addCheckbox(string $name, $label): Checkbox
    {
        return $this->form->addCheckbox($name, $label);
    }

    public function addUpload(string $name, string $label): UploadControl
    {
        return $this->form->addUpload($name, $label);
    }

    public function addEmail(string $name, string $label): TextInput
    {
        return $this->form->addEmail($name, $label);
    }

    public function addTextArea(string $name, string $label): TextArea
    {
        return $this->form->addTextArea($name, $label)
            ->setHtmlAttribute('class', 'uk-textarea');
    }

    public function addRadioList(string $name, string $label, array $items): RadioList
    {
        return $this->form->addRadioList($name, $label, $items);
    }

    public function addWysiwyg(string $name, string $label, Container $container = null): TextArea
    {
        if ($container) {
            $control = $container->addTextArea($name, $label);
        } else {
            $control = $this->form->addTextArea($name, $label);
        }
        return $this->textAreaToWysiwyg($control);
    }

    public function textAreaToWysiwyg(TextArea $textArea): TextArea
    {
        return $textArea
            ->setOption('class', 'wysiwyg-wrapper')
            ->setHtmlAttribute('class', 'js-wysiwyg uk-input')
            ->setHtmlAttribute('data-upload-url', $this->link('uploadWysiwygImage!'));
    }

    public function addImageUpload(
        string $name,
        string $label = null,
        callable $callback = null,
        Container $container = null
    ): ImageUploadEditorControl {
        $control = new ImageUploadEditorControl($this->presenter, $label);
        if ($container) {
            $container->addComponent($control, $name);
        } else {
            $this->form->addComponent($control, $name);
        }
        return $callback ? $callback($control) : $control;
    }

    public function addFileUpload(
        string $name,
        string $label = null,
        bool $isMultiple = false,
        string $uploadDestination = 'File:uploadFile',
        string $adapter = Uppy::ADAPTER_TUS,
        Container $container = null
    ): UppyControl {
        $control = new UppyControl($this->presenter, $label, $isMultiple, $adapter);
        if ($container) {
            $container->addComponent($control, $name);
        } else {
            $this->form->addComponent($control, $name);
        }
        $control->setUploadEndpoint($this->presenter->link($uploadDestination));
        $control->setSuccessEndpoint($this->link('fileUpload!', $control->getHtmlId()));
        $control->setRemoveEndpoint($this->link('fileRemove!'));
        $control->setSortEndpoint($this->link('fileSort!', $control->getHtmlId()));
        $control->setThumbnailRenderHandler(
            function (string $filename): Html {
                $tusFilePath = $this->tusService->getFilePath($filename);
                try {
                    if (file_exists($tusFilePath)) {
                        $image = Image::fromFile($tusFilePath)->resize(48, 48, Image::EXACT)->toString(Image::JPEG, 50);
                        return Html::el("img")->setAttribute("src", Filters::dataStream($image));
                    } else {
                        return Html::el("img")->setAttribute(
                            "src",
                            $this->fileStorage->getRelativeUrl($filename)
                        );
                    }
                } catch (\Exception $e) {
                    return Html::el("span")->setAttribute("uk-icon", "file");
                }
            }
        );
        return $control;
    }

    public function addGroup(?string $caption = null, int $numberOfColumns = 1, string $align = 'start'): ControlGroup
    {
        $control = $this->form->addGroup($caption);
        $control->setOption(
            'attributes',
            [
                'uk-grid' => true,
                'class' => "uk-flex-$align uk-grid uk-grid-small uk-child-width-1-$numberOfColumns@m",
            ]
        );
        return $control;
    }

    public function addTranslation(
        string $name,
        callable $onRender,
        $caption = null,
        string $className = '',
        Container $container = null
    ): Container {
        $this->form->setHtmlAttribute(self::FLAG_HAS_TRANSLATION, true);
        $subContainer = $container ? $container->addContainer($name) : $this->addContainer($name);
        $this->addMarker($subContainer, self::MARKER_TRANSLATION_START);
        foreach ($this->localeService->getLocalesTranslation() as $key => $locale) {
            $localeCaption = "$caption ($locale)";
            $input = $onRender($localeCaption, $className, $key);
            $input->setOption('class', $className);
            $subContainer->addComponent($input, $key);
        }
        $this->addMarker($subContainer, self::MARKER_TRANSLATION_END);
        return $subContainer;
    }

//    public function addSubmit(string $name, string $caption): SubmitButton
//    {
//        return $this->form->addSubmit($name, $caption);
//    }

    public function addMultiselect(string $name, string $label, array $items): MultiSelectBox
    {
        return $this->form->addMultiselect($name, $label, $items);
    }

    public function addHidden(string $name): HiddenField
    {
        return $this->form->addHidden($name);
    }

    public function addSelect(string $name, string $label, array $items): SelectBox
    {
        return $this->form->addSelect($name, $label, $items);
    }

    public function addButton(string $name, string $label): Button
    {
        return $this->form->addButton($name, $label);
    }

    public function addMultiplier(
        string $name,
        callable $onRender,
        ?array $labels = ["Položka", "položku"],
        ?string $id = null,
        ?array $attributes = null,
        Container $container = null
    ) {
        [$entityName, $inflection] = $labels;
        $formData = $this->getWorkFormData();
        if ($container) {
            $defaults = $formData[$container->getParent()->getName()][$container->getName()][$name] ?? [];
            $key = join("-", [$container->getParent()->getName(), $container->getName(), $name]);
        } else {
            $defaults = $formData[$name] ?? [];
            $key = $name;
        }

        $subContainer = $this->addContainer($name, $container);

        unset($defaults[self::MARKER_MULTIPLIER_START]);
        $copyCount = count($defaults);
        $subContainer->addRadioList(
            self::MARKER_MULTIPLIER_START,
            "Přidat " . $inflection,
            array_combine(
                array_keys($defaults),
                array_map(
                    fn($i) => $entityName . " " . ($i + 1),
                    array_keys(array_fill(0, $copyCount, '')),
                )
            )
        )
            ->setDefaultValue($copyCount > 0 ? array_key_first($defaults) : null)
            ->setOption("link", $this->link("addMultiplierItem!", $key))
            ->setOption("attributes", $attributes)
            ->setOption("id", $id);

        foreach (array_keys($defaults) as $id) {
            $subSubContainer = $subContainer->addContainer($id);
            $this->addMarker($subSubContainer, self::MARKER_MULTIPLIER_ITEM_START)->setOption("entityId", $id);
            $onRender($subSubContainer, $id);
            $subSubContainer
                ->addButton('remove', 'Odstranit ' . $inflection)
                ->setHtmlAttribute("data-ajax", $this->link('removeMultiplierItem!', $key, $id))
                ->setOption("class", "uk-margin-top")
                ->setOmitted();
            $this->addMarker($subSubContainer, self::MARKER_MULTIPLIER_ITEM_END);
        }

        $this->addMarker($subContainer, self::MARKER_MULTIPLIER_END);
        return $subContainer;
    }

    public function getInput(string $name): ?IComponent
    {
        // use temp data to access new components
        $this->useTempFormData = true;
        // template has to be rendered first to ensure correct html ids of components
        $this->template->locales = [];
        $this->template->renderToString(__DIR__ . '/DynamicForm.latte');
        return $this->form->getComponent($name);
    }

    private function addMarker(Container $container, string $name): Button
    {
        // markers are necessary for correct grouping in the template
        return $container
            ->addButton($name, "")
            ->setHtmlAttribute("class", "uk-hidden")
            ->setOmitted();
    }

    public function addContainer(string $name, Container $subContainer = null): Container
    {
        $container = $subContainer ? $subContainer->addContainer($name) : $this->form->addContainer($name);
        $container::extensionMethod(
            "addTranslation",
            function (
                Container $container,
                string $containerName,
                callable $onRender,
                $caption = null,
                string $className = ''
            ) {
                return $this->addTranslation($containerName, $onRender, $caption, $className, $container);
            }
        );
        $container::extensionMethod(
            "addMultiplier",
            function (
                Container $container,
                string $containerName,
                callable $onRender,
                ?array $labels = ["Sekce", "sekci"],
                ?string $id = null,
                ?array $attributes = null
            ) {
                return $this->addMultiplier($containerName, $onRender, $labels, $id, $attributes, $container);
            }
        );
        $container::extensionMethod(
            "addImageUpload",
            function (
                Container $container,
                string $containerName,
                string $label = null,
                callable $callback = null
            ) {
                return $this->addImageUpload($containerName, $label, $callback, $container);
            }
        );
        $container::extensionMethod(
            "addFileUpload",
            function (
                Container $container,
                string $containerName,
                string $label = null,
                bool $isMultiple = false,
                string $uploadDestination = 'File:uploadFile',
                string $adapter = Uppy::ADAPTER_TUS
            ) {
                return $this->addFileUpload($containerName, $label, $isMultiple, $uploadDestination, $adapter, $container);
            }
        );
        $container::extensionMethod(
            "addWysiwyg",
            function (
                Container $container,
                string $containerName,
                string $label = null
            ) {
                return $this->addWysiwyg($containerName, $label, $container);
            }
        );

        return $container;
    }

    public function render(): void
    {
        $this->template->locales = $this->localeService->getLocalesTranslation();
        $this->template->render(__DIR__ . '/DynamicForm.latte');
    }

    public function redraw(array $formData)
    {
        // repopulate data, ignore empty
        $this->defaults = array_filter($formData);
        $this->setTempFormData($formData);

        // redraw snippet
        $this->redrawControl("form");
    }

    private function setTempFormData(array $formData): void
    {
        $this->session->setExpiration('30 minutes');
        $this->session->{self::SESSION_FORM_DATA} = $this->cleanFormData($formData);
    }

    private function getWorkFormData(): array
    {
        return ($this->useTempFormData ? $this->session->{self::SESSION_FORM_DATA} : $this->defaults) ?: [];
    }

    private function cleanFormData(array $formData): array
    {
        // remove private fields
        $values = array_filter(
            $formData,
            function ($key) {
                return !Strings::startsWith($key, '_');
            },
            ARRAY_FILTER_USE_KEY
        );
        // empty values to NULL
        return array_map(
            fn($value) => $value === "" ? null : $value,
            $values
        );
    }
}