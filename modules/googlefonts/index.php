<?php
class GoogleFonts {

    /**
     * Add a Google Font to your website.
     * 
     * @param string $name
     * @param array $options
     */
    public static function use(string $name, array $options = []) {

        $ops = arr($options)->force([
            'weight' => '300;400;700',
            'style' => 'sans-serif',
            'selector' => '*',
            'default_weight' => '',
            'default_size' => ''
        ]);

        $weight = ':wght@' . str_replace(' ', '', $ops['weight']);
        
        $n = urlencode($name) . $weight;

        $style = $ops['style'];
        $sel = $ops['selector'];

        $def_weight = '';
        $def_size = '';
        
        if (!empty($ops['default_weight']))
            $def_weight = 'font-weight: ' . $ops['default_weight'] . '; ';

        if (!empty($ops['default_size']))
            $def_size = 'font-size: ' . $ops['default_size'] . '; ';

        Head::add(
            '<link rel="preconnect" href="https://fonts.gstatic.com">',
            '<link href="https://fonts.googleapis.com/css2?family=' . $n . '&display=swap" rel="stylesheet">',
            "<style>$sel{ font-family: '$name', $style; $def_weight $def_size}</style>"
        );

    }

}