<?php
use App\Exceptions\EnvFileExtractionException;

function handle_error(string $msg, PDOException $ex): void {
    echo"<div class='col-red'><span>{$msg} </span><p class='xl'>{$ex}</p></div>";
}

function loadEnv($path) {
    try {
        $env = fopen($path, "r");
        while (!feof($env)) {
            $line = trim(fgets($env));

            if ($line != "") {
                $temp = explode("=", $line, 2);
                $key = trim((string) $temp[0]);
                $val = trim((string) $temp[1]);
                $_ENV[$key] = $val;
            }
        }
    } catch (Exception $e) {
        throw new EnvFileExtractionException($e->getMessage());
    } finally {
        fclose($env);
    }
}
