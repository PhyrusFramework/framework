<?php

class Log {

    const EXTENSION = '.log';

    /**
     * Write a log in the error directory.
     * 
     * @param string|array Content to write. Array is converted to JSON.
     * @param array Options to customize log
     */
    public static function info(string|array $content, array $options = []) {
        $options['caller'] = caller();
        self::write('info', $content, $options);
    }

    /**
     * Write a log in the error directory.
     * 
     * @param string|array Content to write. Array is converted to JSON.
     * @param array Options to customize log
     */
    public static function error(string|array $content, array $options = []) {
        $options['caller'] = caller();
        self::write('error', $content, $options);
    }

    /**
     * Write a log file for today.
     * 
     * @param string Directory name
     * @param string|array Content to write. Array is converted to JSON.
     * @param array Options to customize log
     */
    public static function write(string $name, string|array $content, array &$options = []) {
        $val = is_string($content) ? $content : JSON::stringify($content, true);
        $dir = Path::root() . '/' . Definition('logs') . "/" . $name;
        create_folder($dir);
        
        $now = now();
        $today = explode(' ', $now)[0];
        $file = "$dir/$today" . self::EXTENSION;

        $current = '';
        if (file_exists($file)) {
            $current = file_get_contents($file);
        }

        $line = $options['caller'] ?? caller();
        $line = str_replace(Path::root(), '', $line);

        // Create content:

        $txt = '';

        if (!empty($options['logger'])) {
            $txt = $options['logger']($val);
        }
        else {
            if (!empty($options['plain'])) {
                $txt = $val;
            }
            else {
                if (!isset($options['topSeparator']) || $options['topSeparator'] === true)
                    $txt .= "***********************************\n";
    
                if (!isset($options['when']) || $options['when'] === true)
                    $txt .= "WHEN: $now\n";
    
                if (!isset($options['where']) || $options['where'] === true)
                    $txt .= "WHERE: $line\n";
    
                $txt .= "\n";
                $txt .= $val;
    
                if (!isset($options['bottomSeparator']) || $options['bottomSeparator'] === true)
                    $txt .= "\n***********************************\n\n";
            }
        }

        $current .= $txt;
        file_put_contents($file, $current);
    }

    /**
     * Delete log file or whole directory.
     * 
     * @param string Directory name
     * @param string Specific file name (date)
     */
    public static function clear(string $name, string $date = '') {

        $dir = Path::root() . '/' . Definition('logs') . "/" . $name;

        if (empty($date)) {
            return Folder::instance($dir)->delete();
        }

        $file = "$dir/$date" . self::EXTENSION;
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
}