<?php
require_once(__DIR__ . '/mobile_detect.php');

class MobileDetect {

    /**
     * Check if the user is using a phone or tablet.
     * 
     * @return bool
     */
    public static function isMobile() : bool {
        $detect = new Mobile_Detect();
        return $detect->isMobile();
    }

    /**
     * Check if the user is using a phone, not a tablet.
     * 
     * @return bool
     */
    public static function isPhone() : bool {
        $detect = new Mobile_Detect();
        return ( $detect->isMobile() && !$detect->isTablet() );
    }

    /**
     * Check if the user is using a tablet.
     * 
     * @return bool
     */
    public static function isTablet() : bool {
        $detect = new Mobile_Detect();
        return $detect->isTablet();
    }

    /**
     * Check if user is using iOS
     * 
     * @return bool
     */
    public static function isiOS($orMac = true) : bool {

        if ($orMac) {
            $user_agent = getenv('HTTP_USER_AGENT');
            $mac = (strpos($user_agent, 'Mac') !== FALSE);
            if ($mac) return true;
        }

        $detect = new Mobile_Detect();
        return $detect->isiOS();
    }

    /**
     * Check if user is using Android
     * 
     * @return bool
     */
    public static function isAndroid() : bool {
        $detect = new Mobile_Detect();
        return $detect->isAndroidOS();
    }

    /**
     * [Beta] Check the version of the user device.
     * 
     * @return string
     */
    public static function version() : string {
        $detect = new Mobile_Detect();
        if ($detect->isAndroidOS()) {
            return $detect->version('Android');
        } else if ($detect->isiOS()) {
            if ($detect->isTablet()) {
                return $detect->version('iPad');
            }
            return $detect->version('iPhone');
        }
        return '';
    }

}