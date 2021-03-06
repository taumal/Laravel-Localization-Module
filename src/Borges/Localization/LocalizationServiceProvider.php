<?php namespace Borges\Localization;

use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\FileLoader;
use Event;

class LocalizationServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('borges/localization');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerEvents();
        $this->registerLoader();
        $this->registerTranslator();
        $this->registerLocator();
    }

    /**
     * Register the locator line loader.
     *
     * @return void
     */
    protected function registerLocator()
    {
        $this->app->bindShared('locale', function($app)
        {
            $locale = new Locale($app['translator'], $app['config'], $app['translator']->locale());

            return $locale;
        });
    }

    /**
     * Register the translation line loader.
     *
     * @return void
     */
    protected function registerTranslator()
    {
        $this->app->bindShared('translator', function($app)
        {
            $loader = $app['translation.loader'];

            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $this->getDefaultLocale($app);

            $system_locale = false;
            if($app['config']['localization::useDefault']) {
                $system_locale = $locale;
            }

            $trans = new Translator($loader, $locale, $system_locale);

            return $trans;
        });
    }

    /**
     * Register the translation line loader.
     *
     * @return void
     */
    protected function registerLoader()
    {
        $this->app->bindShared('translation.loader', function($app)
        {
            return new FileLoader($app['files'], $app['path'].'/lang');
        });
    }

    /**
     * Register event subscriber.
     * 
     * @return void
     */
    protected function registerEvents() {
        $this->app['events']->listen('locale.changed', function($locale) {
            $this->app['locale']->setLocale($locale);
        });
    }

    protected function getDefaultLocale($app)
    {
        if(is_callable($app['config']['localization::useUserLanguage'])
                and in_array($app['config']['localization::useUserLanguage'], $app['config']['localization::availableLocales'])) {
            return $app['config']['localization::useUserLanguage'];
        } elseif($app['config']['localization::useSessionLanguage'] 
                and app('session')->has('locale') and in_array(app('session')->get('locale'), $app['config']['localization::availableLocales'])) {
            return app('session')->get('locale');
        } elseif ($app['config']['localization::useBrowserLanguage']) {
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 5);
            if (in_array($lang, $app['config']['localization::availableLocales'])) {
                return $lang;
            } elseif (in_array(substr($lang, 0, 2), $app['config']['localization::availableLocales'])) {
                return substr($lang, 0, 2);
            }
        }

        return $app['config']['localization::locale'];
    }

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
        return array('locale', 'translator', 'translation.loader');
	}

}
