<?php

$configcheck = Config::get('translate');
if (!is_array($configcheck)) {
    require_once(__DIR__.'/install.php');
}

class Translate {

    /**
     * Current Translate objects by language.
     * 
     * @var array $stack
     */
    private static array $stack = [];

    /**
     * Translations arrays decoded from JSON files.
     *
     * @var array
     */
    private static array $translations = [];

    /**
     * Language of this translation object.
     *
     * @var string $language
     */
    private string $language;

    /**
     * Set the current user language.
     *
     * @param  string  $language
     */
    public static function setLanguage(string $language) {
        $l = Translate::resolveLanguage($language);
        if (Config::get('translations.use_cookies'))
            Cookie::set('language', $l);
        else
            SESSION::set('language', $l);
    }

    /**
     * Get the current user language
     *
     * @return string
     */
    public static function getLanguage() : string {

        $useCookies = Config::get('translations.use_cookies');
        $lang = $useCookies ? Cookie::get('language') : SESSION::get('language');

        if (!empty($lang)) {
            return Translate::resolveLanguage($lang);
        }
        return Translate::browserSupportedLanguage();
    }

    public static function instance(string $language) : Translate {

        if (isset(self::$stack[$language])) {
            return self::$stack[$language];
        }

        $translate = new Translate($language);
        self::$stack[$language] = $translate;
        return $translate;
    }

    /**
     * Instance Translate object using a specific language.
     *
     * @param  string  $language
     * 
     * @return Translate
     */
    public static function use(string $language) : Translate {

        if ($language == 'user') {
            $lang = self::getLanguage();
        }
        else if ($language == 'default') {
            $lang = Translate::defaultLanguage();
        } else {
            $lang = self::resolveLanguage($language);
        }
        
        return self::instance($lang);

    }

    /**
     * Get the default language specified in the configuration.
     *
     * @return string
     */
    public static function defaultLanguage() : string {
        $lang = Config::get('translations.default_language');
        return empty($lang) ? self::resolveLanguage($lang) : $lang;
    }

    /**
     * Get array of supported language specified in the configuration file.
     *
     * @return array
     */
    public static function supportedLanguages() : array {
        return Config::get('translations.supported_languages');
    }

    /**
     * Get a usable language depending on the supported languages.
     *
     * @param  string  $language
     * 
     * @return string
     */
    public static function resolveLanguage(string $language) : string {
        $supported = self::supportedLanguages();

        if (in_array($language, $supported)) return $language;

        $inherit = Config::get('translations.inherit');
        $def = self::defaultLanguage();

        if (is_array($inherit)) {
            foreach($inherit as $k => $v) {
                if ($language == $k) {
                    if (in_array($v, $supported)) return $v;
                    return $def;
                }
            }
        }
        return $def;
    }

    /**
     * Get the language of the user's browser.
     *
     * @return string
     */
    public static function browserLanguage() : string {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) return 'en';
        return substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    }

    /**
     * Convert the user's language into one of your supported languages.
     *
     * @return string
     */
    public static function browserSupportedLanguage() : string {
        $lang = Translate::browserLanguage();
        return Translate::resolveLanguage($lang);
    }

    /**
     * Directory for translations json files.
     *
     * @return string
     */
    public static function translationsDir() : string {
        return Config::get('translations.directory');
    }

    public function __construct($language = null) {

        $this->language = $language == null ? self::defaultLanguage() : $language;

        if (!isset(self::$translations[$this->language])) {

            $file = Path::root() . self::translationsDir() . '/' . $this->language . '.json';
            self::$translations[$this->language] = arr(JSON::fromFile($file)->asArray());

        }

    }

    /**
     * Get translation using dot notation.
     *
     * @param string $key
     * @param array $parameters
     * 
     * @return mixed
     */
    public function get($key = null, array $parameters = []) {

        if (!isset(self::$translations[$this->language])) {
            return $key;
        }

        if (empty($key)) {
            return self::$translations[$this->language]->getArray();
        }

        $translation = self::$translations[$this->language]->get($key);
        if (empty($translation)) return $key;

        if (is_array($parameters)) {
            foreach($parameters as $k => $v) {
                $translation = str_replace('{{'.$k.'}}', $v, $translation);
            }
        }

        return $translation;

    }

    /**
     * Change translation for the current language in runtime.
     *
     * @param  string  $key
     * @param  string  $value
     * @return Translate self
     */
    public function set(string $key, string $value) : Translate {
        self::$translations[$this->language]->set($key, $value);
        return $this;
    }

    /**
     * Merge your own array of translations into the current translations.
     *
     * @param  array  $array
     * @return Translate self
     */
    public function merge(array $arr) : Translate {
        self::$translations[$this->language]->merge($arr);
        return $this;
    }

    /**
     * Overwrite the JSON file with the current translations.
     *
     * @return Translate self
     */
    public function save() : Translate {
        // TODO

        return $this;
    }

    /**
     * Add translations to front-end page using javascript.
    */
    public function addJavascript() {
        Footer::add(function() {
            Javascript::define([
                'translations' => self::$translations[$this->language]
            ]);
        });
    }

}