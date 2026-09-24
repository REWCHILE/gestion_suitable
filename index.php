<?php
/**
 * Forwarding root directory to public/ for Laragon subfolder environments
 */
$target = 'public/';
if (!empty($_SERVER['QUERY_STRING'])) {
    $target .= '?' . $_SERVER['QUERY_STRING'];
}
header('Location: ' . $target);
exit;
