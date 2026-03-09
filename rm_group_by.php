<?php
$c = file_get_contents('app/Http/Controllers/SaleController.php');
$c = str_replace("->groupBy('sales.id')", '', $c);
file_put_contents('app/Http/Controllers/SaleController.php', $c);
echo "Replaced groupBy";
