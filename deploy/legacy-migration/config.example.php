<?php
declare(strict_types=1);
// Copy to config.local.php OUTSIDE the public web directory. Never commit credentials.
// Credentials: MIGRATION_DB_USER / MIGRATION_DB_PASS environment variables, or database.local.php.
return [
    'source_database' => 'vengg',
    'target_database' => 'vengg_db', // Use a separate staging DB for the first rehearsal.
    'host' => '127.0.0.1',
    'port' => 3306,
    // Key = legacy user.id; value = an explicitly reviewed, unique new username.
    'username_overrides' => [],
    // Confirm actual working periods before applying.
    'shift_periods' => [
        'กลางวัน' => '08.30-16.30',
        'กลางคืน' => '16.30-08.30',
        'nightCourt' => '16.30-20.30',
    ],
    // Recover missing duties ONLY from verified backups or reviewed historical data.
    // Example shape (replace every value with verified data):
    // ['id'=>106, 'ven_name_id'=>24, 'name'=>'VERIFIED NAME', 'price'=>0,
    //  'color'=>'#000000', 'srt'=>99, 'st'=>1],
    'restore_subs' => [],
    // Explicit reviewed corrections for this import; NEVER updates the legacy source.
    // Originals are still archived. Unknown records/fields and primary-key changes are rejected.
    // Example: 'user' => ['LEGACY_ID' => ['created_at' => null]],
    // Correct inconsistent change references/statuses here only after reviewing the original.
    'row_overrides' => [],
    // Optional legacy ven.id => reviewed reason. Keeps status=2 LOCKED without inventing
    // an approval request. These duties require manual review after migration.
    'preserve_locked_pending' => [],
    // Copy verified image files into backend/public/uploads/avatars first.
    // Key = legacy user.id, value = destination filename (not the legacy path).
    'avatar_overrides' => [],
    // Optional explicit replacement of agency_settings id=1, using the NEW UI JSON shape.
    // Omit to keep existing agency/signers unchanged and archive legacy sign_name only.
    // 'agency_settings' => ['agency_name'=>'...', 'directors'=>[], 'admins'=>[], 'finances'=>[]],
];
