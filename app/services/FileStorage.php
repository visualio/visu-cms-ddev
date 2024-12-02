<?php

namespace App\Services;

use App\Model\Utils;
use Nette\Http\FileUpload;
use Nette\Http\Request as HttpRequest;
use Nette\Utils\FileSystem;
use Nette\Utils\Image;
use Nette\Utils\UnknownImageFileException;

class FileStorage
{
    private string $basePath;
    private string $namespace;
    private string $wwwDir;
    private string $assetDir;
    private array $srcset;
    private array $aliases;
    private array $sizes;

    public function __construct(
        string $wwwDir,
        array $options,
        HttpRequest $httpRequest
    ) {
        $this->basePath = $httpRequest->getUrl()->getBasePath();
        $this->wwwDir = $wwwDir;
        $this->assetDir = $options['dir'];
        $this->srcset = $options['srcset'];
        $this->aliases = $options['aliases'];
        $this->sizes = $options['sizes'];
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function uploadFormFiles(array $values, ?array $uploadGroups = []): array
    {
        $uploadGroup = [];
        foreach ($values as $key => $value) {
            if ($value instanceof FileUpload) {
                $id = $this->uploadFileUpload($value);
                if ($id) {
                    $values[$key] = $id;
                } else {
                    unset($values[$key]);
                }
            }
            if ($value instanceof Image) {
                $values[$key] = $this->uploadImage($value, $key);
            }
            if (is_string($value) && Utils::isBase64Image($value)) {
                $values[$key] = $this->uploadBase64Image($value, $key);
            }
            if (array_search($key, $uploadGroups) !== false && is_array($value)) {
                $uploadGroup[$key] = $this->uploadFormFiles($value)[0];
                unset($values[$key]);
            }
            if (is_array($value)) {
                [$nested] = $this->uploadFormFiles($value);
                $values[$key] = $nested;
            }
        }
        return [$values, (object)$uploadGroup];
    }

    public static function explode(string $id): array
    {
        [$namespace, $filename] = explode('/', $id) + [null, null];
        return [$namespace, $filename];
    }

    public function uploadImage(Image $image, string $key): string
    {
        if (!$this->namespace) {
            throw new \Exception('Namespace has to be specified');
        }
        $dirName = $this->wwwDir . '/' . $this->assetDir . '/' . $this->namespace . '/original';
        FileSystem::createDir($dirName);
        $uniqueName = $this->getRandomName($key . '.png');
        $image->save($dirName . '/' . $uniqueName);
        return $this->namespace . '/' . $uniqueName;
    }

    public function uploadBase64Image(string $string, string $key): ?string
    {
        if (!$this->namespace) {
            throw new \Exception('Namespace has to be specified');
        }
        $data = explode(',', $string);
        if (!isset($data[1])) {
            return null;
        }
        return $this->uploadImage(Image::fromString(base64_decode($data[1])), $key);
    }

    public function uploadFileUpload(FileUpload $upload): ?string
    {
        if (!$this->namespace) {
            throw new \Exception('Namespace has to be specified');
        }
        if (!$upload->isOk()) {
            return $upload->getUntrustedName() ?: null;
        }
        $dirName = $this->wwwDir . '/' . $this->assetDir . '/' . $this->namespace . '/original';
        FileSystem::createDir($dirName);
        $uniqueName = $this->getRandomName($upload->name);
        $upload->move($dirName . '/' . $uniqueName);
        return $this->namespace . '/' . $uniqueName;
    }

    public function getRelativePath(string $id): string
    {
        [$namespace, $filename] = self::explode($id);
        return $this->assetDir . '/' . $namespace . '/original/' . $filename;
    }

    public function getRelativeUrl(string $id): string
    {
        return $this->basePath . $this->getRelativePath($id);
    }

    public function getAliasDirectory(string $namespace, string $alias): string
    {
        return $this->assetDir . "/" . $namespace . "/" . $alias . "/";
    }

    public function getAliasRelativePath(string $id, string $alias): string
    {
        [$namespace, $filename] = self::explode($id);
        return self::getAliasDirectory($namespace, $alias) . $filename . ".webp";
    }

    public function createAlias(string $id, ?string $alias = null): string
    {
        if (!$id)
            $id = "unknown/empty.jpg";

        if (!$alias) {
            return $this->getRelativeUrl($id);
        }

        if (!isset($this->aliases[$alias])) {
            throw new \Exception("Alias '$alias' is not registered in config");
        }

        $path = $this->getAliasRelativePath($id, $alias);

        if (file_exists(WWW_DIR . "/" . $path)) {
            return $this->basePath . $path;
        }

        try {
            $image = Image::fromFile(WWW_DIR . "/" . $this->getRelativePath($id));
        } catch (UnknownImageFileException) {
            $image = Image::fromBlank(800, 600);
        }

        foreach ($this->aliases[$alias] as $method => $args) {
            $image->{$method}(...$args ?: []);
        }

        [$namespace, $filename] = self::explode($id);
        $directory = $this->getAliasDirectory($namespace, $alias);
        FileSystem::createDir(WWW_DIR . "/" . $directory);
        $image->save($path, 90, Image::WEBP);
        return $this->basePath . $directory . $filename . ".webp";
    }

    public function getAliasWidth(string $alias): int
    {
        return $this->sizes[$alias][0];
    }

    public function getAliasHeight(string $alias): int
    {
        return $this->sizes[$alias][1];
    }

    public function uploadWysiwygImage(FileUpload $upload): ?array
    {
        if (!$upload->isOk()) {
            return null;
        }
        $id = $this->uploadFileUpload($upload);

        return [
            "urls" =>
                ["default" => $this->getRelativeUrl($id)] +
                array_combine(
                    array_values($this->srcset),
                    array_map(fn($alias) => $this->createAlias($id, $alias), $this->srcset)
                )
        ];
    }

    public function removeFile(?string $id = null): void
    {
        if ($id) {
            FileSystem::delete(WWW_DIR . "/" . $this->getRelativePath($id));
            foreach (array_keys($this->aliases) as $alias) {
                FileSystem::delete(WWW_DIR . "/" . $this->getAliasRelativePath($id, $alias));
            }
        }
    }

    private function getRandomName(string $fileName): string
    {
        $ext = explode('.', $fileName);
        $ext = '.' . $ext[count($ext) - 1];
        return md5(time() . rand()) . $ext;
    }
}

