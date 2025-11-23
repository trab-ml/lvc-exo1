<?php
function handle_error(string $msg, PDOException $ex): void {
    echo"<div class='col-red'><span>{$msg} </span><p class='xl'>{$ex}</p></div>";
}
