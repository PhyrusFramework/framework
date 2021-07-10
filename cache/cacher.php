<?php

if (Config::get('cache.enabled')) {

    $checkfolder = Path::src() . '/cache';
    $checkfile = $checkfolder . '/last.log';
    $now = new DateTime();

    create_folder($checkfolder);

    if (file_exists($checkfile))
    {
        $last = file_get_contents($checkfile);
        $date = new DateTime($last);
        $d = $now->diff($date);

        $seconds = $d->y*365*24*60*60 
        + $d->m*30*24*60*60 
        + $d->d*24*60*60 
        + $d->h*60*60 
        + $d->i*60 
        + $d->s;

        $min = Config::get('cache.days') *24*60*60
        + Config::get('cache.hours') *60*60
        + Config::get('cache.minutes')*60
        + Config::get('cache.seconds');

        if ($seconds > $min) {

            Cache::clear();

            $txt = $now->format('Y-m-d H:i:s');
            file_put_contents($checkfile, $txt);

        }

    } else {
        $txt = $now->format('Y-m-d H:i:s');
        file_put_contents($checkfile, $txt);
    }
}