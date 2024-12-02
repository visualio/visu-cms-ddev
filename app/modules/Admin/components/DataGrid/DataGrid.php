<?php


namespace App\Components;


use Nette\ComponentModel\IContainer;
use Ublaboo\DataGrid\Column\Action;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;
use Ublaboo\DataGrid\Filter\FilterSelect;
use Ublaboo\DataGrid\Filter\FilterText;
use Ublaboo\DataGrid\Localization\SimpleTranslator;

class DataGrid extends \Ublaboo\DataGrid\DataGrid
{
    public function __construct(
        ?IContainer $parent = null,
        ?string $name = null,
        ?string $sortableHandle = null
    )
    {
        parent::__construct($parent, $name);
        parent::$iconPrefix = 'data-grid-icon ';
        $this->setTemplateFile(__DIR__ . '/DataGrid.latte');
        $this->setTranslator($this->createTranslator());
        $this->useHappyComponents(false);
        $this->setPagination(true);
        $this->setCustomPaginatorTemplate(__DIR__ . '/DataGridPaginator.latte');
        $this->setItemsPerPageList([50, 100, 250, 500, 'all']);
        if ($sortableHandle) {
            $this->setSortable();
            $this->addAction('sort', '')
                ->addAttributes(['uk-icon' => 'menu', 'title' => 'Serřadit', 'href' => '#'])
                ->setClass('uk-icon-button uk-sortable-handle js-sortable-handle');
            $this->setSortableHandler($sortableHandle);
        }
    }

    public function addColumnThumbnail(string $key, string $name, string $href)
    {
        return parent::addCOlumnText($key, $name, $href)
            ->setTemplate(__DIR__ . '/Thumbnail.latte', ['key' => $key, 'href' => $href]);
    }

    public function addEditAction()
    {
        $this->addIconAction('edit', 'Upravit', 'pencil');
    }

    public function addFilterText(
        string $key,
        string $name,
        $columns = null
    ): FilterText
    {
        $component = parent::addFilterText($key, $name, $columns);
        $component->setAttribute('class', 'uk-input');
        return $component;
    }
    public function addFilterSelect(
        string $key,
        string $name,
        array $options,
        ?string $column = null
    ): FilterSelect
    {
        $component = parent::addFilterSelect($key, $name, $options, $column);
        $component->setAttribute('class', 'uk-select');
        $component->setPrompt('');
        return $component;
    }

    public function addDeleteAction(string $col, string $key = 'delete', ?bool $isAjax = false)
    {
        $action = $this->addIconAction($key, 'Smazat', 'trash');
        if ($isAjax) {
            $action->setDataAttribute('ajax', 'true');
        } else {
            $action->setConfirmation(
                new StringConfirmation('Opravdu chcete smazat položku %s?', $col) // Second parameter is optional
            );
        }
    }

    /**
     * @param string|callable $title
     * @param string|callable $icon
     * @return Action
     */
    public function addIconAction(
        string $key,
        $title,
        $icon,
        ?string $href = null,
        ?array $params = null,
        ?callable $class = null
    ): Action
    {
        $this->addActionCheck($key);

        $href = $href ?: $key;

        if ($params === null) {
            $params = [$this->primaryKey];
        }

        $action = new Action($this, $key, $href, '', $params);
        $action->setClass(
            $class ? (fn(...$args) => $class(...$args) . ' uk-icon-button') : 'uk-icon-button'
        );
        $action->setTitle($title);
        $action->setIcon($icon);

        return $this->actions[$key] = $action;
    }

    public function createTranslator(): SimpleTranslator
    {
        return new SimpleTranslator(
            [
                'ublaboo_datagrid.no_item_found_reset' => 'Žádné položky nenalezeny. Filtr můžete vynulovat',
                'ublaboo_datagrid.no_item_found' => 'Žádné položky nenalezeny.',
                'ublaboo_datagrid.here' => 'zde',
                'ublaboo_datagrid.items' => 'Položky',
                'ublaboo_datagrid.all' => 'všechny',
                'ublaboo_datagrid.from' => 'z',
                'ublaboo_datagrid.reset_filter' => 'Resetovat filtr',
                'ublaboo_datagrid.group_actions' => 'Hromadné akce',
                'ublaboo_datagrid.show_all_columns' => 'Zobrazit všechny sloupce',
                'ublaboo_datagrid.hide_column' => 'Skrýt sloupec',
                'ublaboo_datagrid.action' => '',
                'ublaboo_datagrid.previous' => 'Předchozí',
                'ublaboo_datagrid.next' => 'Další',
                'ublaboo_datagrid.choose' => 'Vyberte',
                'ublaboo_datagrid.execute' => 'Provést',
                'Name' => 'Jméno',
                'Inserted' => 'Vloženo'
            ]
        );
    }
}