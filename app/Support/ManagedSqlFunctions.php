<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManagedSqlFunctions
{
    public static function run(string $sql, string $label = 'SQL function statement'): bool
    {
        if (! self::functionsEnabledByConfig()) {
            return false;
        }

        try {
            DB::unprepared($sql);

            return true;
        } catch (QueryException $exception) {
            if (! self::shouldIgnore($exception)) {
                throw $exception;
            }

            Log::warning(
                "Skipping {$label} because this database does not allow SQL function management.",
                ['error' => $exception->getMessage()]
            );

            return false;
        }
    }

    private static function functionsEnabledByConfig(): bool
    {
        return filter_var((string) env('DB_ENABLE_SQL_FUNCTIONS', 'true'), FILTER_VALIDATE_BOOL);
    }

    private static function shouldIgnore(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        foreach ([
            'create function',
            'drop function',
            'super privilege',
            'execute command denied',
            'access denied; you need (at least one of) the super',
            'not allowed to create a stored function',
            'not allowed to drop a stored function',
        ] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }
}
