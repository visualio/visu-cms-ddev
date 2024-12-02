# How to run the project

There are two basic modules:

* front (aka front-office)
* admin (aka back-office)

## Prerequisites

* [nodejs](https://nodejs.org/) with [npm](https://www.npmjs.com/)
* [Docker](https://www.docker.com/)
* [MySQL Workbench](https://www.mysql.com/products/workbench/)

## DDEV Development

- start: $`ddev start`

# Database

### [Adminer](http://127.0.0.1:8081/)

- server: `db`
- Uživatel: `db`
- Heslo: `db`

## SSH

* connect to ddev container: `ddev ssh`
* run `npm install` to install frontend dependencies
* run `docker compose up -d` to start the PHP server
* chmod 777 for `./temp` and `./log`
* run `npm start` or `npm start:admin` to start the ViteJS server
* open http://localhost/ to see the resulting website (any file changes reloads the browser for fast local development)
* open http://localhost:8080/ for database administration
* if you need to run a composer command, run `docker run -it --rm -v $(pwd):/app composer <command>`

## Production

run `npm run build` if you want to create production build of the front module. For the admin module
use `npm run build:admin` command.

# Coding standard

Run `npm run lint:css` and `npm run lint:js` before you commit to check
if your code adheres to the coding standard. It automatically fixes
problems with formatting. Issues which cannot be solved automatically
are displayed to the console.

## GIT

All commit messages must be written in English in present tense.

## Javascript

Formatting is handled by [Prettier](https://prettier.io/). Standard
is enforced by [ESLint](https://eslint.org/) rules (see `eslintConfig.rules` in `package.json` for a reference).

## CSS

Whenever possible use [Tailwind](https://tailwindcss.com/docs) utility classes. For custom CSS, formatting is handled
by [Prettier](https://prettier.io/) and standard
is enforced by [Stylelint](https://stylelint.io/) rules (see `stylelint.rules` in `package.json` for a reference).

## PHP

Stick to Clean Code concepts:
https://github.com/jupeter/clean-code-php

## HTML

Learn to use all HTML tags. [MDN](https://developer.mozilla.org/en-US/docs/Web/HTML/Element) is your friend. `<div>` is
the last resort.

# Frontend assets

Use `./dev` and its respective subfolders to create or edit front-end assets.
Here is an example of the folder structure:

```
/dev
|-- admin
|   |-- (same structure as front)
`-- front
    |-- images
    |   |-- photo.jpg
    |   `-- logo.svg
    |-- icons
    |   |-- mail.svg
    |   `-- arrow.svg
    |-- css
    |   |-- index.css
    |   `-- contact.css
    |-- js
    |   |-- index.js
    |   `-- contact.js
    
```

All assets are compiled into `/www/dist/<modlue>` folder.

## Usage in the templates

Because of cache busting, the only way of using your assets is
by means of `ViteAssets` service. It's a dependency of `BasePresenter` and you can use it in your
templates.

Examples:

```latte

<head>
    {$viteAssets->printFrontTags("js/index.js")}
</head>
```

### Images

Static images are generated into `www/dist/<module>/images` folder:

```html
<img src="{$basePath}/dist/front/images/logo.svg" alt="Logo">
```

For each image the build system attempts to create several file types (jpg, webp, png and avif). You can take advantage
of this and use them in `<picture>` element.

```html

<picture>
    <source
            type="image/avif"
            src="{$basePath}/dist/front/images/pomodoro.avif"
    />
    <source
            type="image/webp"
            src="{$basePath}/dist/front/images/pomodoro.webp"
    />
    <img
            src="{$basePath}/dist/front/images/pomodoro.jpg"
            alt="Some great alternative text"
            loading="lazy"
            decoding="async"
            width="2032"
            height="1076"
    />
</picture>
```

Images from the CMS should be processed using the `image` or `srcset` Latte filter:

```html
<img src="{$user->avatar|image:thumbnail}" alt="Logo">
```

The `image` filter accepts one parameter which is the configuration key:

```neon
# config.neon
fileStorageOptions:
    aliases:
        thumbnail: # this is the key
            resize: [150, 150, Nette\Utils\Image::EXACT]
            shapren: null
            crop: ['100%', '50%', '80%', '80%']
    srcset: [640, 768, 1024, 1366, 1600, 1920]
```

Every alias is an object where the key is a method name for [Nette\Utils\Image](https://doc.nette.org/cs/3.1/images) and
the value is an array of arguments for the method.

The `srcset` filter can be used for responsive images:

```html
<img src="{$user->avatar|image}" srcset="{$user->avatar|srcset}" alt="Logo">
<!--becomes-->
<img src="avatar.jpg" srcset="avatar-640.jpg 640w, ..." alt="Logo">
```

# Customizing the Admin

This repo holds only the most basic entities which are common to most clients content management systems (
eg. `Article`, `Tag` and `User`). Probably, you will need to adjust them to meet specification of your new project. If
so, here is the recommended workflow:

## Change the database schema

Open the `database_source.mwb` with [MySQL Workbench](https://www.mysql.com/products/workbench/) and change the tables
and its columns. When ready export the SQL script (File → Export → SQL Script) and save it to `init-db.sql`.

## Adjust the code

Use model classes in `app/model` and presenters in `app/modules/Admin/presenters` as examples to integrate a new entity.

## DynamicForm

For developers convenience there is `DynamicForm` class for entity management.

### Usage

Include DynamicFormFactory with DI. Here is a basic example:

```php
use App\Components\DynamicForm;
use Nette\Forms\Controls\TextArea;
use App\AdminModule\Factories\DynamicFormFactory;

class Presenter extends \Nette\Application\UI\Presenter {

    public DynamicFormFactory $dynamicFormFactory;
    
    public function __construct(
        DynamicFormFactory $dynamicFormFactory 
    ) {
        parent::__construct();
        $this->dynamicFormFactory = $dynamicFormFactory;
    }
    
    public function createComponentForm(): DynamicForm {
     return $this->dynamicFormFactory->create(
        // definition callback
        function (DynamicForm $form) {
            $form->addImageUpload("image", "Obrázek");
        },
        // submit handler
        function (array $values, ?int $id) {
            $this->model->upsert($values, $id);
        },
        // caption (string or array)
        "položku",
        // default values
        $this->model->getData()     
     );
    }
}
```

### First parameter $onRender

This callback describes what inputs should be rendered. Submit input is automatically inserted at
the end of the form. It provides you the instance of DynamicForm which
monkey-patches [Nette/Form input methods](https://doc.nette.org/cs/3.1/form-controls) to play nicely
with [UIKit frontend framework](https://getuikit.com/). It also provides some convenience methods listed below:

#### setAjax

This method adds `data-ajax` attribute to the form, and it enables ajax behaviour. See `initNetteAjax()`
in `dev/admin/js/imports/initilaizers.js`.

#### addMultiplier(...$args)

Often times you need to manage sub-entities inside one form. For example an article may have many sections. This is a
perfect case for the multiplier which provides you the instance
of [Nette\Forms\Container](https://api.nette.org/2.4/Nette.Forms.Container.html) for sub-entity description. It is
extended with methods listed below. Here is an example of the multiplier with argument description:

```php
$form->addMultiplier(
    'sections', // name
    function(Container $container) {
        $container->addText('slug', 'Slug');
        $container->addTranslation(
          'label',
          fn($label) => new TextInput($label),
          'Popisek'
        );
        $container->addMultiplier(
            'icons',
            function(Container $container) {
                $container->addImageUpload('icon', 'Ikona');
                $container->addText('caption', 'Název');
            }
        );
      );
    },
    ["Sekce", "sekci"], // labels [normative case, genitive case] (optional),
    "article-sections", // html id (optional)
    ["data-text" => 42] // html attributes (optional)
);
```

#### addColorPicker(string $name, string $label)

This method sets attribute `type` to `color`.
See [MDN](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/color).

#### addWysiwyg(string $name, string $label)

This method renders [CKEditor](https://ckeditor.com/) WYSIWYG component. In translation, use *textAreaToWysiwyg* public
method.

#### addTranslation(...$args)

This method renders input for all project's locales. They are then displayed and hidden
by [UIKit Tab](https://getuikit.com/docs/tab) component. Here is an example of the translation with argument
description:

```php
$form->addTranslation(
    // name
    'content',
    // definition callback (this is how to render wysiwyg)
    fn($caption) => $form->textAreaToWysiwyg(new TextArea($caption)), 
    // caption (it's rendered with locales name in the brackets)
    'Obsah',
    // optional className
    'uk-width-1/2'
);
```

#### addImageUpload(string $name, string $label)

This method renders [Toast UI ImageEditor](https://github.com/nhn/tui.image-editor).

#### addFileUpload(string $name, string $label, bool $isMultiple = false)

This method renders [Uppy](https://uppy.io/).

#### addGroup(string $caption, int $numberOfColumns = 1, string $align)

This method groups inputs into a fieldset grid. You can specify caption,
number of columns and alignment of the grid.

### Second parameter $onSubmit

User's save action invokes this function. It provides you `array $formData`, `?int $id` of the record (derived
from `$defaults`) to easily distinguish between update and insert, and `Form $form` for form manipulation.

### Third parameter $caption

This can be either a string or an array made of two strings. In the first case, the form generates form's title and
submit text by prepending `Uložit / Upravit` keywords. If you provide an array, you have direct control on the title,
and the submit text. For example: `['Hello buddy', 'Sign up']`.

### Fourth parameter $defaults (optional)

Provide an associative array with the same shape as you've defined in the `$onRender` parameter to prefill the form. If
the `$defaults` contains a key named `id`, it will be passed as a second parameter to the `$onSubmit` callback.

### Fifth parameter $isCompact (optional)

This optional parameter forces the form to render as compact as possible to save vertical space.