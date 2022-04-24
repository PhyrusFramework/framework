<?php

class VueParser {

    public static function process(string $file) {
        $js = $file . '.js';
        $scss = $file . '.scss';

        $jscached = Cacher::getPath($js);
        if (self::isExpired($file, $jscached)) {
            return self::analyze($file);
        }

        $result = [];
        $jscached = Cacher::hasFile($js);
        $csscached = Cacher::hasFile($scss);
        if ($jscached) $result['js'] = $jscached;
        if ($csscached) $result['css'] = $csscached;

        return $result;
    }

    private static function isExpired(string $file, string $cached) {
        if (!file_exists($cached)) return true;
        $t1 = new Time(last_modification_date($file));
        $t2 = new Time(last_modification_date($cached));

        $f = File::instance($cached);

        if ($t2->isBefore($t1)) {
            $f->delete();
            return true;
        }

        return false;
    }

    private static function analyze($file) {
        if (!file_exists($file)) return [];
        $content = file_get_contents($file);

        $script = '';
        $template = '';
        $style = '';

        $state = 'out';
        $state2 = 'intag';
        $buffer = '';

        for($i = 0; $i < strlen($content); ++$i) {
            $c = $content[$i];

            $buffer .= $c;

            $restricted = [' ', "\n", "\r", "\t"];
            foreach($restricted as $r) {
                if ($buffer == $r) {
                    $buffer = '';
                    break;
                }
            }

            if ($state == 'out') {

                // If template was started and we find </template> after another </template>
                if ($template != '' && 
                strlen($buffer) > 10 && 
                substr($buffer, strlen($buffer) - 11, 11) == '</template>') {
                    $template .= substr($buffer, 0, strlen($buffer) - 11);
                    $buffer = '';
                }
                
                // If we find template beginning
                else if ($buffer == '<template>') {
                    $state = 'template';
                    $buffer = '';
                }

                // If we find script beginning
                else if ($buffer == '<script') {
                    $state = 'script';
                    $state2 = 'intag';
                    $buffer = '';
                }

                // If we find style beginning
                else if ($buffer == '<style') {
                    $state = 'style';
                    $state2 = 'inbody';
                    $buffer = '';
                }

            } else if ($state == 'template') {

                $l = strlen('</template>');

                if (strlen($buffer) > 10 && substr($buffer, strlen($buffer) - $l, $l) == '</template>') {
                    $template .= substr($buffer, 0, strlen($buffer) - $l);
                    $state = 'out';
                    $buffer = '';
                }


            } else if ($state == 'script') {

                if ($state2 == 'intag') {
                    if ($c == '>') {
                        $state2 = 'inbody';
                        $buffer = '';
                    }
                } else {
                    $l = strlen('</script>');

                    if (strlen($buffer) > 10 && substr($buffer, strlen($buffer) - $l, $l) == '</script>') {
                        $script .= substr($buffer, 0, strlen($buffer) - $l);
                        $state = 'out';
                        $buffer = '';
                    }
                }

            } else if ($state == 'style') {

                if ($state2 == 'intag') {
                    if ($c == '>') {
                        $state2 = 'inbody';
                        $buffer = '';
                    }
                } else {
                    $l = strlen('</style>');

                    if (strlen($buffer) > 10 && substr($buffer, strlen($buffer) - $l, $l) == '</style>') {
                        $style .= substr($buffer, 1, strlen($buffer) - $l - 1);
                        $state = 'out';
                        $buffer = '';
                    }
                }

            }
        }

        $sc = $script;

        // Insert template
        if ($template != '') {
            $buffer = '';
            $i = 0;
            while ($i < strlen($sc)) {
                $c = $sc[$i];

                if ($c == '{') {

                    $sc = $buffer . $c . "\n\ttemplate: `" . $template . "`,\n\t" . substr($sc, $i + 1);
                    break;

                } else {
                    $buffer .= $c;
                }

                ++$i;
            }
        }

        $result = [];

        if ($sc != '') {
            $path = Cacher::write($file . '.js', $sc);

             /// Minify output
             if (!Config::get('project.development_mode')) {
                $min = new Minifier();
                $min->addFile($path);
                $min->minify($path);
             }
             /////
            $result['js'] = $path;
        }

        if ($style) {
            $path = Cacher::write($file . '.scss', $style);

             /// Minify output
             if (!Config::get('project.development_mode')) {
                $min = new Minifier();
                $min->addFile($path);
                $min->minify($path);
             }
             /////
            $result['css'] = $path;
        }

        return $result;
    }

}