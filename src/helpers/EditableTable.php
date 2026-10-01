<?php

namespace GlueAgency\LiteSpeed\helpers;

/**
 * Turns the rows an editable table posts into plain values.
 */
class EditableTable
{
    /**
     * Returns one column's non-blank values. A row that's already a plain string, as in a config file, is kept as-is.
     *
     * @return string[]
     */
    public static function column(mixed $rows, string $column): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $values = [];

        foreach ($rows as $row) {
            $value = is_array($row) ? ($row[$column] ?? '') : $row;

            if (! is_scalar($value)) {
                continue;
            }

            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            $values[] = $value;
        }

        return array_values(array_unique($values));
    }

    /**
     * Returns `[key => value]` integers from two columns. A map that's already keyed by integers, as in a config
     * file, is kept as-is.
     *
     * @return array<int, int>
     */
    public static function integerMap(mixed $rows, string $keyColumn, string $valueColumn): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $map = [];

        foreach ($rows as $key => $row) {
            if (! is_array($row)) {
                $map[(int) $key] = (int) $row;

                continue;
            }

            $rowKey = trim((string) ($row[$keyColumn] ?? ''));
            $rowValue = trim((string) ($row[$valueColumn] ?? ''));

            if ($rowKey === '' || $rowValue === '') {
                continue;
            }

            $map[(int) $rowKey] = (int) $rowValue;
        }

        return $map;
    }
}
