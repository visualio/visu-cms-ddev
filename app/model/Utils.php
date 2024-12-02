<?php


namespace App\Model;


use Nette\Database\Table\Selection;
use Nette\Utils\AssertionException;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

class Utils
{
    public static function extractValues(iterable $array, ...$keys): array
    {
        $values = [];
        foreach ($keys as $key) {
            $values[] = $array[$key] ?? null;
            unset($array[$key]);
        }
        return [$array, ...$values];
    }

    public static function createAssocPath(...$keys): string
    {
        return join("|", $keys);
    }

    public static function createPath(...$colDefs): string
    {
        return join(
            ",",
            array_map(
                function ($colDef) {
                    [$column, $table, $isForeign, $alias] = is_array($colDef) ? array_pad($colDef, 4, null) : [
                        $colDef,
                        null,
                        null,
                        null
                    ];
                    return ($isForeign ? ':' : '') . ($table ? $table . '.' : '') . $column . ($alias ? " AS $alias" : "");
                },
                $colDefs,
            )
        );
    }

    public static function insertRelations(
        Selection $table,
        array $entityIds,
        array $part,
        string $key,
        ?string $sortCol = null
    ): void {
        if (count($entityIds) > 0) {
            $table->insert(
                array_map(
                    fn($id, $sort) => array_merge($part, [$key => $id], $sortCol ? [$sortCol => $sort] : []),
                    $entityIds,
                    range(1, count($entityIds))
                )
            );
        }
    }

    public static function updateRelations(
        Selection $selection,
        array $parentPair,
        array $entityIds,
        string $key,
        ?string $sortCol = null
    ): void {
        $existingIds = $selection->where($parentPair)->fetchPairs(null, $key);
        // delete
        $idsToDelete = array_values(array_diff($existingIds, $entityIds));
        if (count($idsToDelete) > 0) {
            $selection
                ->where($parentPair)
                ->where("$key IN ?", $idsToDelete)
                ->delete();
        }
        // create
        $idsToCreate = array_values(array_diff($entityIds, $existingIds));
        if (count($idsToCreate) > 0) {
            $selection
                ->insert(
                    array_map(
                        fn($id) => array_merge($parentPair, [$key => $id]),
                        $idsToCreate
                    )
                );
        }
        // update order (TODO: try to improve performance)
        if ($sortCol) {
            foreach ($entityIds as $index => $id) {
                (clone $selection)->where($key, $id)->update([$sortCol => $index + 1]);
            }
        }
    }

    public static function filterArrayKeys(array $array, array $allowed): array
    {
        return array_filter(
            $array,
            fn($key) => in_array($key, $allowed),
            ARRAY_FILTER_USE_KEY
        );
    }

    public static function filterArrayRecursive( array $array, callable $callback = null ) {
        $array = is_callable( $callback ) ? array_filter( $array, $callback ) : array_filter( $array );
        foreach ( $array as &$value ) {
            if ( is_array( $value ) ) {
                $value = self::filterArrayRecursive($value, $callback);
            }
        }

        return $array;
    }

    public static function mapArrayKeys(callable $callback, array $array): array
    {
        return array_combine(
            array_keys($array),
            array_map(
                fn($key) => $callback($key),
                array_keys($array)
            )
        );
    }

    public static function groupConcatToArray(?string $value): array
    {
        return $value ? array_unique(explode(",", $value)) : [];
    }

    public static function invertLocaleValuesMap(array $map): array
    {
        $masterLocale = array_key_first($map);
        $locales = array_keys($map);
        $keys = array_keys($map[$masterLocale]);
        return array_map(
            fn($key) => array_map(
                fn($locale) => $map[$locale][$key],
                array_combine($locales, $locales)
            ),
            array_combine($keys, $keys)
        );
    }

    public static function createTranslationFields(Selection $selection, array $fields): array
    {
        $data = $selection->fetchAssoc('locale');
        return array_combine(
            $fields,
            array_map(
                function ($field) use ($data) {
                    return array_map(fn($i) => $i[$field], $data);
                },
                $fields
            )
        );
    }

    public static function extractTranslationFields(array $formData, array $fields): array
    {
        $result = self::extractValues($formData, ...$fields);
        $translations = [];
        foreach (array_slice($result, 1) as $index => $item) {
            foreach ($item as $locale => $value) {
                $translations[$locale][$fields[$index]] = $value;
            }
        }
        return [$result[0], $translations];
    }

    public static function createFulltextQuery(string $query): string
    {
        $arr = array_map(function ($word) {
            // https://dev.mysql.com/doc/refman/8.0/en/fulltext-boolean.html
            // (+) A leading or trailing plus sign indicates that this word must be present in each row that is returned
            // (*) The asterisk serves as the truncation (or wildcard) operator. Unlike the other operators, it is appended to the word to be affected. Words match if they begin with the word preceding the * operator
            return '+' . trim($word) . '*';
        }, explode(' ', $query));
        return join(' ', $arr);
    }


    public static function setArrayValue(array $path, array &$array, callable $modifier)
    {
        $temp =& $array;

        foreach ($path as $key) {
            $temp =& $temp[$key];
        }

        $temp = $modifier($temp);
    }

    public static function isBase64Image(string $string): bool
    {
        return Strings::startsWith($string, "data:image");
    }

    public static function unsetRecursive($haystack, $needle)
    {
        if (is_array($haystack)) {
            unset($haystack[$needle]);
            foreach ($haystack as $k => $value) {
                $haystack[$k] = self::unsetRecursive($value, $needle);
            }
        }
        return $haystack;
    }


    public static function validateValues(array $values, array $validators): void
    {
        Validators::assert($values, "array");

        foreach ($validators as $col => $validator) {
            if (is_array($validator)) {
                if ($values[$col] !== null) { // allow null values for arrays
                    if (is_array($values[$col])) {
                        self::validateValues($values[$col], $validator);
                    } else {
                        throw new AssertionException("key '$col' has incorrect shape");
                    }
                }
            } else {
                Validators::assertField($values, $col, $validator);
            }
        }

        foreach (array_keys($values) as $key) {
            if (!in_array($key, array_keys($validators))) {
                throw new AssertionException("key '$key' is not defined");
            }
        }
    }

    public static function snakeToCamel(string $snake): string
    {
        return str_replace("_", '', lcfirst(ucwords($snake, "_")));
    }

    public static function camelToSnake(string $camel): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $camel));
    }

    public static function createGroupwiseMaxQuery(string $table, string $groupColumn, string $valueColumn, ?string $condition = "NULL IS NULL"): string
    {
        // http://mysql.rjweb.org/doc.php/groupwise_max#using_windowing_functions_
        return sprintf(
            "
        SELECT *
            FROM (
                SELECT  %s,
                        ROW_NUMBER() OVER(PARTITION BY %s ORDER BY %s DESC) AS n, id
                    FROM %s WHERE %s
                 ) x
            WHERE n <= 1
       ", $groupColumn, $groupColumn, $valueColumn, $table, $condition
        );
    }

    public static function sortArray(array $arr, callable $indexer): array
    {
        usort($arr, function ($a, $b) use ($indexer) {
            $a = $indexer($a);
            $b = $indexer($b);
            if ($a === false && $b === false) {
                return 0;
            } elseif ($a === false) {
                return 1;
            } elseif ($b === false) {
                return -1;
            } else {
                return $a - $b;
            }
        });
        return $arr;
    }

    public static function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('', 'KB', 'MB', 'GB', 'TB');   

        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }

}