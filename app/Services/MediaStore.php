<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PDO;

/**
 * Writes image bytes to media_contents on every supported database.
 * PostgreSQL (e.g. Supabase) needs the bytes bound as binary (bytea); a normal
 * string parameter is rejected as invalid UTF-8.
 */
class MediaStore
{
    public static function put(int $mediaId, string $bytes): void
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'pgsql') {
            DB::table('media_contents')->insert(['media_id' => $mediaId, 'data' => $bytes]);

            return;
        }

        $statement = $connection->getPdo()->prepare('insert into media_contents (media_id, data) values (?, ?)');
        $statement->bindValue(1, $mediaId, PDO::PARAM_INT);
        $statement->bindValue(2, $bytes, PDO::PARAM_LOB);
        $statement->execute();
    }

    /** Reads a value from a binary column: PostgreSQL returns a stream, other databases a string. */
    public static function bytes(mixed $value): ?string
    {
        if (is_resource($value)) {
            rewind($value);

            return (string) stream_get_contents($value);
        }

        return $value === null ? null : (string) $value;
    }
}
