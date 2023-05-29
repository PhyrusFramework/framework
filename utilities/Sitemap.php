<?php

class Sitemap {

    /**
     * Generate the sitemap
     * 
     * @param array $map
     */
    public static function generate(array $map) {

        $path = Path::root() . '/sitemap';
        if (!is_dir($path))
            mkdir($path);

        $index = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        
        $indexUrls = [];
        $indexMaps = [];

        foreach($map as $name => $set) {
            if (!is_array($set)) {
                $indexUrls[] = $set;
                continue;
            }

            $indexMaps[] = $name;
            $content = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

            foreach($set as $url) {
                $content .= "<url><loc>$url</loc></url>\n";
            }

            $content .= '</urlset>';

            $file = $path . "/sitemap_$name.xml";
            file_put_contents($file, $content);
        }

        if (sizeof($indexUrls) > 0) {
            $index .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
            foreach($indexUrls as $url) {
                $index .= "<url><loc>$url</loc></url>\n";
            }
            $index .= '</urlset>'."\n";
        }

        if (sizeof($indexMaps) > 0) {
            $index .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

            foreach($indexMaps as $map) {
                $index .= '<sitemap><loc>'.URL::host().'/sitemap/sitemap_'.$map.'.xml</loc><lastmod>'.now().'</lastmod></sitemap>'."\n";
            }

            $index .= '</sitemapindex>';
        }

        file_put_contents($path . '/sitemap_index.xml', $index);

    }

}