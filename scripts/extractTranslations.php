<?php

use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;

require __DIR__ . '/../vendor/autoload.php';

$isoMap = ['cs' => 'cs_CZ', 'en' => 'en_US'];

$appDir = __DIR__ . '/../app';
$translationConfig = Neon::decodeFile( $appDir . '/config/translation.neon');
$dirs = $translationConfig['translation']['dirs'];
$locales = $translationConfig['translation']['locales']['whitelist'];
$defaultLocale = $translationConfig['translation']['locales']['default'];
$json = [];

foreach ($dirs as $dir) {
    $path = str_replace('%appDir%', $appDir, $dir);
    $basename = basename(dirname($path));
    foreach ($locales as $locale) {
        $file = $path . "/$basename.$isoMap[$locale].neon";
        try {
            $json[$basename][$locale] = Neon::decodeFile($file) ?? (object) [];
        } catch (Exception) { // file not exist
            try { // fallback first to the default locale
                $json[$basename][$locale] = Neon::decodeFile($path . "/$basename.$isoMap[$defaultLocale].neon") ?? (object) [];
            } catch (Exception) { // empty object as a final fallback
                $json[$basename][$locale] = (object) [];
            }
        }
    }
}

FileSystem::write(__DIR__ . '/translations.json', Json::encode($json, Json::PRETTY));

