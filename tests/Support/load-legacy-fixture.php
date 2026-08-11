<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$sql = file_get_contents(dirname(__DIR__).'/Fixtures/legacy-contract.sql');
if ($sql === false) {
    fwrite(STDERR, 'Unable to read legacy fixture.'.PHP_EOL);
    exit(1);
}

foreach (preg_split('/;\s*(?:\R|$)/', $sql) ?: [] as $statement) {
    $statement = trim($statement);
    if ($statement !== '') {
        DB::unprepared($statement);
    }
}

echo 'legacy fixture loaded'.PHP_EOL;
