<?php

namespace App\Filter;

class StaticFilters
{
    public static function common($filter, $value)
    {
        if (method_exists(__CLASS__, $filter)) {
            $args = func_get_args();
            array_shift($args);
            return call_user_func_array(array(__CLASS__, $filter), $args);
        }
        return null;
    }

    public static function join(array $arr): string
    {
        $filter = array_filter(
            $arr,
            function ($i) {
                return $i === '' ? false : true;
            }
        );
        return join(' ', $filter);
    }

    public static function nbsp(string $text): string
    {
        // One letter words
        $result = preg_replace('/(\s|&nbsp;)([a-z])\s/i', '$1$2&nbsp;', $text);

        // NBSP units to their number
        $result = preg_replace('/(\d+)\s(km|kč)(?:\s|$|\.|,|;)/i', '$1&nbsp;$2', $result);

        return $result;
    }

    public static function nbspAll(string $text): string
    {
        return preg_replace('/(\s)/i', '&nbsp;', $text);
    }

    public static function ytThumbnail(string $link): string
    {
        $id = StaticFilters::getYouTubeId($link);
        return "http://img.youtube.com/vi/$id/maxresdefault.jpg";
    }

    public static function ytEmbed(string $link): string
    {
        $id = StaticFilters::getYouTubeId($link);
        return "https://www.youtube-nocookie.com/embed/$id";
    }

    public static function getYouTubeId(string $url): ?string
    {
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
        return $match[1] ?? null;
    }

}