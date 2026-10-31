<?php

namespace App\Services\Legacy;

use RuntimeException;

/** Reads literal INSERT rows only. SQL from the supplied dump is never executed. */
class SqlDumpReader
{
    public function read(string $path, array $tables): array
    {
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new RuntimeException('Cannot read SQL dump.');
        }
        $result = array_fill_keys($tables, []);
        preg_match_all('/^INSERT INTO `([^`]+)` \(([^\r\n]+)\) VALUES\s*/m', $sql, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $index => [$header, $offset]) {
            $table = $matches[1][$index][0];
            if (!array_key_exists($table, $result)) {
                continue;
            }
            preg_match_all('/`([^`]+)`/', $matches[2][$index][0], $columns);
            $pos = $offset + strlen($header);
            while (true) {
                $this->space($sql, $pos);
                if (($sql[$pos] ?? '') !== '(') {
                    throw new RuntimeException("Invalid literal row in $table.");
                }
                $pos++;
                $values = [];
                do {
                    $this->space($sql, $pos);
                    $values[] = $this->value($sql, $pos);
                    $this->space($sql, $pos);
                    $delimiter = $sql[$pos++] ?? '';
                } while ($delimiter === ',');
                if ($delimiter !== ')' || count($values) !== count($columns[1])) {
                    throw new RuntimeException("Column mismatch in $table.");
                }
                $row = array_combine($columns[1], $values);
                $key = $row['id'] ?? count($result[$table]);
                if (isset($result[$table][$key])) {
                    throw new RuntimeException("Duplicate legacy identifier in $table.");
                }
                $result[$table][$key] = $row;
                $this->space($sql, $pos);
                $delimiter = $sql[$pos++] ?? '';
                if ($delimiter === ';') {
                    break;
                }
                if ($delimiter !== ',') {
                    throw new RuntimeException("Invalid INSERT delimiter in $table.");
                }
            }
        }
        return $result;
    }

    private function space(string $sql, int &$pos): void
    {
        while (isset($sql[$pos]) && ctype_space($sql[$pos])) {
            $pos++;
        }
    }

    private function value(string $sql, int &$pos): ?string
    {
        if (($sql[$pos] ?? '') !== "'") {
            $start = $pos;
            while (isset($sql[$pos]) && !in_array($sql[$pos], [',', ')'], true)) {
                $pos++;
            }
            $value = trim(substr($sql, $start, $pos - $start));
            if (strtoupper($value) === 'NULL') {
                return null;
            }
            if (!preg_match('/^-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?$/D', $value)) {
                throw new RuntimeException('Non-literal SQL value is not supported.');
            }
            return $value;
        }
        $pos++;
        $value = '';
        $escapes = ['0' => "\0", 'n' => "\n", 'r' => "\r", 't' => "\t", 'b' => "\x08", 'Z' => "\x1a"];
        while (isset($sql[$pos])) {
            $char = $sql[$pos++];
            if ($char === '\\') {
                $next = $sql[$pos++] ?? throw new RuntimeException('Unterminated escape.');
                $value .= $escapes[$next] ?? (in_array($next, ['%', '_'], true) ? '\\'.$next : $next);
            } elseif ($char === "'") {
                if (($sql[$pos] ?? '') === "'") {
                    $pos++;
                    $value .= "'";
                } else {
                    return $value;
                }
            } else {
                $value .= $char;
            }
        }
        throw new RuntimeException('Unterminated quoted value.');
    }
}
