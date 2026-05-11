<?php

if (!function_exists('delete_directory_safely')) {
    /**
     * Recursively deletes a directory and its contents.
     * Built as a global function so it survives PHP fatal crashes.
     * * @param string $dir The path to the directory
     */
    function delete_directory_safely($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object)) {
                    delete_directory_safely($dir . DIRECTORY_SEPARATOR . $object);
                } else {
                    unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
        }
        rmdir($dir);
    }
}