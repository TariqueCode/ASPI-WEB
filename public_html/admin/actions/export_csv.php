<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="aspi-export.csv"');

echo "id,name\n";
echo "1,ASPI\n";
