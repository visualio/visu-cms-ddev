<?php

namespace App\Filter;


use App\Services\FileStorage;

class FileFilter
{
    private array $srcset;
    private FileStorage $fileStorage;

    public function __construct(
        array $options,
        FileStorage $fileStorage
    )
    {
        $this->srcset = $options['srcset'];
        $this->fileStorage = $fileStorage;
    }

    public function srcset(string $id)
    {
        return join(
            ",\n",
            array_map(
                fn($size) => $this->fileStorage->createAlias($id, $size) . " $size" .  "w",
                $this->srcset
            )
        );
    }

    public function image(string $id, ?string $alias = null): string
    {
        return $this->fileStorage->createAlias($id, $alias);
    }

    public function width(string $alias): string
    {
        return $this->fileStorage->getAliasWidth($alias);
    }

    public function height(string $alias): string
    {
        return $this->fileStorage->getAliasHeight($alias);
    }

    public function file(string $id): string
    {
        return $this->fileStorage->getRelativeUrl($id);
    }
}