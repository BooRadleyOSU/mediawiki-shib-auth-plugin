<?php
$target = isset($_GET['target']) ? $_GET['target'] : '/wiki';
header('Location: '.$target);
die();
?>
