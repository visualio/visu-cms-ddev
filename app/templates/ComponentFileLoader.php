<?php declare(strict_types = 1);

namespace App\Templates;

use Latte\Loaders\FileLoader as FileLoaderLatte;

class ComponentFileLoader extends FileLoaderLatte
{
    private static function getComponentName(string $name): ?string {
        preg_match('/^\.([a-z]*)/i', $name, $matches); // component name starts with a dot following a capital letter
        $id = $matches[1] ?? null;
        return $id ?: null;
    }

    public function getContent($fileName): string
    {
        $id = self::getComponentName($fileName);
        if ($id) {
            return parent::getContent(COMP_DIR . $id . "/" . $id . ".latte");
        }
        return parent::getContent($fileName);
    }

    public function getReferredName($file, $referringFile): string
    {
        if (self::getComponentName($file)) {
            return $file;
        }

        return parent::getReferredName($file, $referringFile);
    }

}