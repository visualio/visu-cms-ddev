<?php

namespace App\Services;

use Nette\Http\FileUpload;
use Nette\Utils\FileSystem;
use TusPhp\Tus\Server;

class TusService
{
    const TUS_DIRNAME = 'tus';
    const RECENCY_THRESHOLD = 60 * 10; // ten minutes

    private Server $server;
    private string $tempDir;

    public function __construct(
        string $tempDir
    ) {
        $this->server = new Server();
        $this->tempDir = $tempDir;
    }

    public function getDirName(): string
    {
        return $this->tempDir . '/' . self::TUS_DIRNAME;
    }

    public function getFiles(): array
    {
        return array_diff(scandir($this->getDirName()), ['..', '.']);
    }

    public function getRecentInputFiles(string $inputId): array
    {
        $files = array_reduce(
            $this->getFiles(),
            function ($acc, $fileName) {
                $parts = explode("_", $fileName);
                $id = array_shift($parts);
                $time = filemtime($this->getFilePath($fileName));
                if ($time > (time() - self::RECENCY_THRESHOLD))
                    $acc[$id][] = $fileName;
                return $acc;
            },
            []
        );
        return $files[$inputId] ?: [];
    }

    public function getFilePath(string $fileName): string
    {
        return $this->getDirName() . '/' . $fileName;
    }

    public function createFileUpload(string $fileName): FileUpload
    {
        $file = $this->getFilePath($fileName);
        if (file_exists($file) && is_file($file))
            return new FileUpload(
                [
                    'tmp_name' => $file,
                    'size' => filesize($file),
                    'name' => $fileName,
                    'error' => UPLOAD_ERR_OK,
                ]
            );
        return new FileUpload(
            [
                'tmp_name' => $file,
                'size' => 0,
                'name' => $fileName,
                'error' => UPLOAD_ERR_NO_FILE,
            ]
        );
    }

    public function removeFile(string $fileName): bool
    {
        $filePath = $this->getFilePath($fileName);
        if (file_exists($filePath)) {
            FileSystem::delete($this->getFilePath($fileName));
            return true;
        } else {
            return false;
        }
    }

    public function sendResponse(string $apiPath)
    {
        $dirName = $this->getDirName();
        FileSystem::createDir($dirName);
        $this->server->setUploadDir($dirName);
        $this->server->setApiPath($apiPath);
        $this->server->serve()->send();
        exit(0);
    }
}