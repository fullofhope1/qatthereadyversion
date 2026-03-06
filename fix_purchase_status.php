<?php
require 'config/db.php';
$pdo->exec("UPDATE purchases SET status = 'Fresh' WHERE status = '' OR status IS NULL");
echo "Updated empty statuses to 'Fresh'. Done.";
