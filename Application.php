<?php
/**
 * @package    Molajo
 * @copyright  2012 Individual Molajo Contributors. All rights reserved.
 * @license    GNU GPL v 2, or later and MIT, see License folder
 */
namespace Molajo;

use Molajo\Service\Services;
use Molajo\Service\Services\Request\RequestService;
use Molajo\Service\Services\Configuration\ConfigurationService;

use Molajo\Helpers;

defined('MOLAJO') or die;

/**
 * Application
 *
 * Front Controller for the Molajo Application
 *
 * 1. Initialise
 * 2. Route
 * 3. Authorise
 * 4. Execute (Display or Action)
 * 5. Respond
 *
 * @package     Molajo
 * @subpackage  Application
 * @since       1.0
 */
Class Application
{
    /**
     * Application::Services
     *
     * @var    object  Services
     * @since  1.0
     */
    protected static $services = null;

    /**
     * Application::Helpers
     *
     * @var    object  Helper
     * @since  1.0
     */
    protected $helpers = null;

    /**
     * $request
     *
     * @var    object  Request
     * @since  1.0
     */
    protected $request = null;

    /**
     * $requested_resource_for_route
     *
     * ex. articles/article-1/index.php?tag=xyz
     *
     * @var    object  Request
     * @since  1.0
     */
    protected $requested_resource_for_route = null;

    /**
     * $base_url_path_for_application
     *
     * ex. http://site1/admin/
     *
     * @var    object  Request
     * @since  1.0
     */
    protected $base_url_path_for_application = null;

    /**
     * $rendered_output
     *
     * @var    object
     * @since  1.0
     */
    protected $rendered_output = null;

    /**
     * $exception_handler
     *
     * @var    object
     * @since  1.0
     */
    protected $exception_handler = null;

    /**
     * Override normal processing with these parameters
     *
     * @param   string  $override_url_request
     * @param   string  $override_catalog_id
     * @param   string  $override_parse_sequence
     * @param   string  $override_parse_final
     *
     * @return  mixed
     * @since   1.0
     */
    public function process(
        $override_url_request = false,
        $override_catalog_id = false,
        $override_parse_sequence = false,
        $override_parse_final = false
    ) {

        /** 1. Initialise */
        $results = $this->initialise(
            $override_url_request,
            $override_catalog_id,
            $override_parse_sequence,
            $override_parse_final
        );

        /** 2. Route */
        try {
            if ($results === true) {
                $results = $this->route();
            }
            if ($results === false) {
                return false;
            }
        } catch (\Exception $e) {
            throw new \Exception('Route Error: ' . $e->getMessage(), $e->getCode());
        }

        /** 3. Authorise */
        try {
            $this->authorise();

        } catch (\Exception $e) {
            throw new \Exception('Authorisation Error: ' . $e->getMessage(), $e->getCode());
        }

        /** 4. Execute */
        try {
            $this->execute();

        } catch (\Exception $e) {
            throw new \Exception('Execute Error: ' . $e->getMessage(), $e->getCode());
        }

        /** 5. Response */
        try {
            $this->response();

        } catch (\Exception $e) {
            throw new \Exception('Response Error: ' . $e->getMessage(), $e->getCode());
        }

        exit(0);
    }

    /**
     * Initialise Site, Application, and Services
     *
     * @param   string  $override_url_request
     * @param   string  $override_catalog_id
     * @param   string  $override_parse_sequence
     * @param   string  $override_parse_final
     *
     * @return  boolean
     * @since   1.0
     */
    protected function initialise(
        $override_url_request = false,
        $override_catalog_id = false,
        $override_parse_sequence = false,
        $override_parse_final = false
    ) {

        set_exception_handler(array($this, 'handleException'));
        set_error_handler(array($this, 'createExceptionFromError'), E_ALL);

        $results = version_compare(PHP_VERSION, '5.3', '<');
        if ($results == 1) {
            throw new \Exception('PHP version: ' . PHP_VERSION . ' does not meet 5.3 minimum.', 500);
        }

        /** Request */
        $this->request = new RequestService();

        /** HTTP class */
        $results = $this->setBaseURL();
        if ($results === false) {
            return false;
        }

        /** PHP constants */
        $results = $this->setDefines();
        if ($results === false) {
            return false;
        }

        /** Site determination and paths */
        $results = $this->setSite();
        if ($results === false) {
            return false;
        }

        /** Application determination and paths */
        $results = $this->setApplication();
        if ($results === false) {
            return false;
        }

        /** Installation check */
        $results = $this->installCheck();
        if ($results === false) {
            return false;
        }

        /** Services */
        $results = Application::Services()->initiate();
        if ($results === false) {
            return false;
        }

        /** SSL */
        $results = $this->sslCheck();
        if ($results === false) {
            return false;
        }

        /** Site authorised for application */
        $results = $this->verifySiteApplication();
        if ($results === false) {
            return false;
        }

        /** Helpers */
        $results = Application::Helpers();
        if ($results === false) {
            return false;
        }

        /** LAZY LOAD Session */
        //Services::Session()->create(
        //        Services::Session()->getHash(get_class($this))
        //  );
        // Services::Profiler()
        // ->set('Services::Session()->create complete, 'Application');

        Services::Registry()->set('Override', 'url_request', $override_url_request);
        Services::Registry()->set('Override', 'catalog_id', $override_catalog_id);
        Services::Registry()->set('Override', 'parse_sequence', $override_parse_sequence);
        Services::Registry()->set('Override', 'parse_final', $override_parse_final);

        if ($results === true) {
            Services::Profiler()->set('Application Schedule Event onAfterInitialise', LOG_OUTPUT_PLUGINS);
            $results = Services::Event()->scheduleEvent('onAfterInitialise');
            if (is_array($results)) {
                $results = true;
            }
        }

        if ($results === false) {
            Services::Profiler()->set('Initialise failed', LOG_OUTPUT_APPLICATION);
            //throw error
            die;
        }

        Services::Profiler()->set('Initialise succeeded', LOG_OUTPUT_APPLICATION);

        return true;
    }

    /**
     * Custom PHP Exception Handler for Molajo
     *
     * @param   null  $title
     * @param   null  $message
     * @param   null  $code
     * @param   int   $display_file
     * @param   int   $display_line
     * @param   int   $display_stack_trace
     * @param   int   $terminate
     *
     * @return  void
     * @since   1.0
     */
    public function handleException($title = null, $message = null, $code = null,
        $display_file = 1, $display_line = 1, $display_stack_trace = 1, $terminate = 1)
    {
        $x = Services::Exception();

        return $x->formatMessage($title, $message, $code,
            $display_file, $display_line, $display_stack_trace, $terminate);
    }

    /**
     * Custom PHP Error Handler - turns Errors into PHP Exceptions
     *
     * @param   $errno
     * @param   $errstr
     * @param   $errfile
     * @param   $errline
     *
     * @throws  \ErrorException
     * @since   1.0
     */
    public function createExceptionFromError($errno, $errstr, $errfile, $errline )
    {
        echo $errstr . ' ' . $errno . '<br />';
        echo $errfile . '<br />';
        echo $errline . '<br />';

        throw new \ErrorException ($errstr, $errno);
    }

    /**
     * Evaluates HTTP Request to determine routing requirements, including:
     *
     * - Normal page request: populates Registry for Request
     * - Issues redirect request for "home" duplicate content (i.e., http://example.com/index.php, etc.)
     * - Checks for 'Application Offline Mode', sets a 503 error and registry values for View
     * - For 'Page not found', sets 404 error and registry values for Error Template/View
     * - For defined redirect with Catalog, issues 301 Redirect to new URL
     * - For 'Logon requirement' situations, issues 303 redirect to configured login page
     *
     * @return  boolean
     * @since   1.0
     */
    protected function route()
    {
//$results = Services::Install()->content();
//$results = Services::Install()->testCreateExtension('Data Dictionary', 'Resources');
//$results = Services::Install()->testDeleteExtension('Test', 'Resources');

        Services::Profiler()->set(START_ROUTING, LOG_OUTPUT_APPLICATION);

        $results = Services::Route()->process(
            $this->requested_resource_for_route,
            $this->base_url_path_for_application
        );

        if (Services::Redirect()->url === null
            && (int)Services::Redirect()->code == 0
        ) {
            $results = $this->onAfterRouteEvent();
        }

        if ($results === false
            || Services::Registry()->get('Parameters', 'error_status', 0) == 1
        ) {
            Services::Profiler()->set('Route failed', LOG_OUTPUT_APPLICATION);
            return false;
        }

        if ($results === true
            && Services::Redirect()->url === null
            && (int)Services::Redirect()->code == 0
        ) {
            Services::Profiler()->set('Route succeeded', LOG_OUTPUT_APPLICATION);
            return true;
        }

        Services::Profiler()->set('Route redirected ' . Services::Redirect()->url, LOG_OUTPUT_APPLICATION);

        return true;
    }

    /**
     * Schedule Event onAfterRoute
     *
     * @return  boolean
     * @since   1.0
     */
    protected function onAfterRouteEvent()
    {
        Services::Profiler()->set(
            'Application Schedules onAfterRoute',
            LOG_OUTPUT_PLUGINS,
            VERBOSE
        );

        $arguments = array(
            'parameters' => Services::Registry()->getArray('Parameters'),
            'model_type' => Services::Registry()->get('Parameters', 'model_type'),
            'model_name' => Services::Registry()->get('Parameters', 'model_name'),
            'data' => array()
        );

        $arguments = Services::Event()->scheduleEvent('onAfterRoute', $arguments);

        if ($arguments === false) {
            Services::Profiler()->set(
                'Application->onAfterRouteEvent ' . ' failure ',
                LOG_OUTPUT_PLUGINS
            );

            return false;
        }

        Services::Registry()->delete('Parameters');
        Services::Registry()->createRegistry('Parameters');
        Services::Registry()->loadArray('Parameters', $arguments['parameters']);
        Services::Registry()->sort('Parameters');

        return true;
    }

    /**
     * Verify user authorization
     *
     * OnAfterAuthorise Event is invoked regardless of normal authorisation results (fail or succeed)
     *      in order to allow overriding the finding and/or providing other methods of authorisation
     *
     * @return  boolean
     * @since   1.0
     */
    protected function authorise()
    {
        Services::Profiler()->set(START_AUTHORISATION, LOG_OUTPUT_APPLICATION);

        Services::Profiler()->set('Application Schedule Event onAfterAuthorise', LOG_OUTPUT_PLUGINS);

        $results = Services::Event()->scheduleEvent('onAfterAuthorise');
        if (is_array($results)) {
            $results = true;
        }

        if ($results === false) {
            Services::Profiler()->set('Authorise failed', LOG_OUTPUT_APPLICATION);
            throw new \Exception('Authorisation Failed', 403);
        }

        Services::Profiler()->set('Authorise succeeded', LOG_OUTPUT_APPLICATION);

        return true;
    }

    /**
     * Execute the action requested
     *
     * @return  boolean
     * @since   1.0
     */
    protected function execute()
    {
        $action = Services::Registry()->get('Parameters', 'request_action', ACTION_VIEW);
        if (trim($action) == '') {
            $action = ACTION_VIEW;
        }

        $action = strtolower($action);
        if ($action == ACTION_VIEW || $action == ACTION_EDIT || $action == ACTION_CREATE) {
            $results = $this->display();
        } else {
            $results = $this->action();
        }

        if ($results === true) {
            Services::Profiler()->set('Application Schedule Event onAfterExecute', LOG_OUTPUT_PLUGINS);

            $results = Services::Event()->scheduleEvent('onAfterExecute');
            if (is_array($results)) {
                $results = true;
            }
        }

        if ($results === false) {
            Services::Profiler()->set('Execute ' . $action . ' failed', LOG_OUTPUT_APPLICATION);
            throw new \Exception('Execute ' . $action . ' Failed', 500);
            return false;
        }

        Services::Profiler()->set('Execute ' . $action . ' succeeded', LOG_OUTPUT_APPLICATION);

        return true;
    }

    /**
     * Executes a view action
     *
     * 1. Parse: recursively parses theme and then rendered output for <include:type statements
     *
     * 2. Includer: each include statement is processed by the associated extension includer
     *      which retrieves data needed by the MVC, passing control and data into the Controller
     *
     * 3. MVC: executes actions, invoking model processing and rendering of views
     *
     * Continues until no more <include:type statements are found in the Theme and rendered output
     *
     * @since   1.0
     * @return  Application
     */
    protected function display()
    {
        if (file_exists(Services::Registry()->get('Parameters', 'theme_path_include'))) {
        } else {
            Services::Error()->set(500, 'Theme Not found');
            echo 'Theme not found - application stopped before parse. Parameters follow:';
            Services::Registry()->get('Parameters', '*');
            die;
        }

        $parms = Services::Registry()->getArray('Parameters');
        $page_request = Services::Cache()->get('page', implode('', $parms));

        if ($page_request === false) {
            $results = Services::Parse()->process();
            Services::Cache()->set('page', implode('', $parms), $results);
        } else {
            $results = $page_request;
        }

        $this->rendered_output = $results;

        return true;
    }

    /**
     * Execute action (other than Display)
     *
     * @return  boolean
     * @since   1.0
     */
    protected function action()
    {

// -> sessions Services::Message()->set('Status updated', MESSAGE_TYPE_SUCCESS);

// 	What action and Controller (authorisation should be okay)

// what redirect for good and bad

// what parameters

        if (Services::Registry()->get('Configuration', 'url_sef', 1) == 1) {
            $url = Services::Registry()->get('Parameters', 'catalog_url_sef_request');

        } else {
            $url = Services::Registry()->get('Parameters', 'catalog_url_request');
        }

        Services::Redirect()->redirect(Services::Url()->getApplicationURL($url), '301')->send();

        return true;
    }

    /**
     * Return HTTP response
     *
     * @return  object
     * @since   1.0
     */
    protected function response()
    {
        Services::Profiler()->set(START_RESPONSE, LOG_OUTPUT_APPLICATION);

        if (Services::Redirect()->url === null
            && (int)Services::Redirect()->code == 0
        ) {

            Services::Profiler()
                ->set('Response Code 200', LOG_OUTPUT_APPLICATION);

            Services::Response()
                ->setContent($this->rendered_output)
                ->setStatusCode(200)
                ->send();

            $results = Services::Response()
                ->getStatusCode();

        } else {

            Services::Profiler()
                ->set(
                'Response Code:' . Services::Redirect()->code
                    . 'Redirect to: ' . Services::Redirect()->url
                    . LOG_OUTPUT_APPLICATION
            );

            Services::Redirect()
                ->redirect()
                ->send();

            $results = Services::Redirect()->code;
        }

        if ($results == 200) {
            Services::Profiler()->set('Application Schedule Event onAfterResponse', LOG_OUTPUT_PLUGINS);
            $results = Services::Event()->scheduleEvent('onAfterResponse');
            if (is_array($results)) {
                $results = true;
            }
        } else {
            throw new \Exception('Response failed', $results);
        }

        Services::Language()->logUntranslatedStrings();

        Services::Profiler()
            ->set('Response exit ' . $results, LOG_OUTPUT_APPLICATION);

        return true;
    }

    /**
     * Populate BASE_URL using scheme, host, and base URL
     *
     * Note: The Application::Request object is used instead of the Application::Request due to where
     * processing is at this point
     *
     * @return  boolean
     * @since   1.0
     */
    protected function setBaseURL()
    {

        if (defined('BASE_URL')) {
        } else {
            /**
             * BASE_URL - root of the website with a trailing slash
             */
            define('BASE_URL', $this->request->get('base_url') . '/');
        }

        return true;
    }

    /**
     * Folders and subfolders can be relocated outside of the Apache htdocs for increased security.
     * To do so, create a defines file and override the Autoload.php file for the new namespaces.
     *
     * Note: SITES contains content that must be accessible by the Website and thus cannot be moved.
     *
     * @return  boolean
     * @since   1.0
     */
    protected function setDefines()
    {
        if (file_exists(BASE_FOLDER . '/defines.php')) {
            include_once BASE_FOLDER . '/defines.php';
        }

        if (defined('EXTENSIONS')) {
        } else {
            define('EXTENSIONS', BASE_FOLDER . '/Extension');
        }

        if (defined('EXTENSIONS_MENUITEMS')) {
        } else {
            define('EXTENSIONS_MENUITEMS', EXTENSIONS . '/Menuitem');
        }
        if (defined('EXTENSIONS_RESOURCES')) {
        } else {
            define('EXTENSIONS_RESOURCES', EXTENSIONS . '/Resource');
        }
        if (defined('EXTENSIONS_THEMES')) {
        } else {
            define('EXTENSIONS_THEMES', EXTENSIONS . '/Theme');
        }
        if (defined('EXTENSIONS_VIEWS')) {
        } else {
            define('EXTENSIONS_VIEWS', EXTENSIONS . '/View');
        }

        if (defined('EXTENSIONS_URL')) {
        } else {
            define('EXTENSIONS_URL', BASE_URL . 'Extension');
        }
        if (defined('EXTENSIONS_THEMES_URL')) {
        } else {
            define('EXTENSIONS_THEMES_URL', BASE_URL . 'Extension/Theme');
        }
        if (defined('EXTENSIONS_VIEWS_URL')) {
        } else {
            define('EXTENSIONS_VIEWS_URL', BASE_URL . 'Extension/View');
        }

        if (defined('SERVICES')) {
        } else {
            define('SERVICES', PLATFORM_FOLDER . '/Service');
        }
        if (defined('CORE_THEMES')) {
        } else {
            define('CORE_THEMES', PLATFORM_FOLDER . '/Theme');
        }
        if (defined('CORE_VIEWS')) {
        } else {
            define('CORE_VIEWS', PLATFORM_FOLDER . '/MVC/View');
        }
        if (defined('CORE_LANGUAGES')) {
        } else {
            define('CORE_LANGUAGES', PLATFORM_FOLDER . '/Language');
        }

        if (defined('CORE_SYSTEM_URL')) {
        } else {
            define('CORE_SYSTEM_URL', BASE_URL . 'Vendor/Molajo/System');
        }
        if (defined('CORE_THEMES_URL')) {
        } else {
            define('CORE_THEMES_URL', BASE_URL . 'Vendor/Molajo/Theme');
        }
        if (defined('CORE_VIEWS_URL')) {
        } else {
            define('CORE_VIEWS_URL', BASE_URL . 'Vendor/Molajo/MVC/View');
        }

        if (defined('SITES')) {
        } else {
            define('SITES', BASE_FOLDER . '/Site');
        }

        /** Defines used to help ensure consistency of literal values in application */
        $defines = ConfigurationService::getFile('Application', 'Defines');
        foreach ($defines->define as $item) {
            if (defined((string)$item['name'])) {
            } else {
                $value = (string)$item['value'];
                define((string)$item['name'], $value);
            }
        }

        return true;
    }

    /**
     * Identifies the specific site and sets site paths for use in the application
     *
     * @return  boolean
     * @since   1.0
     */
    protected function setSite()
    {
        if (defined('SITES')) {
        } else {
            define('SITES', BASE_FOLDER . '/Site');
        }

        if (defined('SITES_MEDIA_FOLDER')) {
        } else {
            define('SITES_MEDIA_FOLDER', SITES . '/media');
        }

        if (defined('SITES_MEDIA_URL')) {
        } else {
            define('SITES_MEDIA_URL', BASE_URL . 'Site/media');
        }

        if (defined('SITES_DATAOBJECT_FOLDER')) {
        } else {
            define('SITES_DATAOBJECT_FOLDER', BASE_URL . 'Site/media');
        }

        $site_base_url = $this->request->get('base_url_path');

        if (defined('SITE_BASE_URL')) {
        } else {

            $sites = ConfigurationService::getFile('Site', 'Sites');

            foreach ($sites->site as $single) {
                if (strtolower((string)$single->site_base_url) == strtolower($site_base_url)) {
                    define('SITE_BASE_URL', (string)$single->site_base_url);
                    define('SITE_BASE_PATH', BASE_FOLDER . (string)$single->site_base_folder);
                    define('SITE_BASE_URL_RESOURCES', SITE_BASE_URL . (string)$single->site_base_folder);
                    define('SITE_DATAOBJECT_FOLDER', SITE_BASE_PATH . '/' . 'Dataobject');
                    define('SITE_ID', $single->id);
                    define('SITE_NAME', $single->name);
                    break;
                }
            }
            if (defined('SITE_BASE_URL')) {
            } else {
                echo 'Fatal Error: Cannot identify site for: ' . $site_base_url;
                die;
            }
        }

        return true;
    }

    /**
     * Identify current application and page request
     *
     * @return  boolean
     * @since   1.0
     */
    protected function setApplication()
    {
        $p1 = $this->request->get('path_info');
        $t2 = $this->request->get('query_string');

        if (trim($t2) == '') {
            $requestURI = $p1;
        } else {
            $requestURI = $p1 . '?' . $t2;
        }

        $requestURI = substr($requestURI, 1, 9999);

        /** extract first node for testing as application name  */
        if (strpos($requestURI, '/')) {
            $applicationTest = substr($requestURI, 0, strpos($requestURI, '/'));
        } else {
            $applicationTest = $requestURI;
        }

        $requested_resource_for_route = '';

        if (defined('APPLICATION')) {
            /* to override - must also define $this->request->get('requested_resource_for_route') */
        } else {

            $apps = ConfigurationService::getFile('Application', 'Applications');

            foreach ($apps->application as $app) {

                $xml_name = (string)$app->name;
                ;

                if (strtolower(trim($xml_name)) == strtolower(trim($applicationTest))) {

                    define('APPLICATION', $app->name);
                    define('APPLICATION_URL_PATH', APPLICATION . '/');
                    define('APPLICATION_ID', $app->id);

                    $requested_resource_for_route = substr(
                        $requestURI,
                        strlen(APPLICATION) + 1,
                        strlen($requestURI) - strlen(APPLICATION) + 1
                    );
                    break;
                }
            }

            if (defined('APPLICATION')) {
            } else {
                define('APPLICATION', $apps->default->name);
                define('APPLICATION_URL_PATH', '');
                define('APPLICATION_ID', $apps->default->id);

                $requested_resource_for_route = $requestURI;
            }
        }

        /*  Page Request used in Application::Request */
        if (strripos($requested_resource_for_route, '/') == (strlen($requested_resource_for_route) - 1)) {
            $requested_resource_for_route
                = substr($requested_resource_for_route, 0, strripos($requested_resource_for_route, '/'));
        }

        $this->requested_resource_for_route = $requested_resource_for_route;

        $this->base_url_path_for_application
            = $this->request->get('base_url_path_with_scheme')
            . '/'
            . APPLICATION_URL_PATH;

        return true;
    }

    /**
     * Determine if the site has already been installed
     *
     * return  boolean
     * @since  1.0
     */
    protected function installCheck()
    {
        if (defined('SKIP_INSTALL_CHECK')) {
            return true;
        }

        if (APPLICATION == 'installation') {
            return true;
        }

        if (file_exists(SITE_BASE_PATH . '/Dataobject/Database.xml')
            && filesize(SITE_BASE_PATH . '/Dataobject/Database.xml') > 10
        ) {
            return true;
        }
//todo - install		/** Redirect to Installation Application */
        $redirect = BASE_URL . 'installation/';
        header('Location: ' . $redirect);

        exit();
    }

    /**
     * Check to see if secure access to the application is required by configuration
     *
     * @return  bool
     * @since   1.0
     */
    protected function sslCheck()
    {
        Services::Registry()->get('ApplicationsParameters');

        if ((int)Services::Registry()->get('Configuration', 'url_force_ssl', 0) > 0) {

            if (($this->request->get('connection')->isSecure() === true)) {

            } else {

                $redirectTo = (string)'https' .
                    substr(BASE_URL, 4, strlen(BASE_URL) - 4) .
                    APPLICATION_URL_PATH .
                    '/' . $this->request->get('requested_resource_for_route');

                Services::Redirect()
                    ->set($redirectTo, 301);

                return false;
            }
        }

        return true;
    }

    /**
     * Verify that this site is authorised to access this application
     *
     * @return  boolean
     * @since   1.0
     */
    protected function verifySiteApplication()
    {
        $authorise = Services::Authorisation()->verifySiteApplication();
        if ($authorise === false) {
            //todo: redirect to error page
            $message = '304: ' . BASE_URL;
            echo $message;
            die;
        }

        return true;
    }

    /**
     * Application::Services
     *
     * @static
     * @return  Services
     * @throws  \RuntimeException
     * @since   1.0
     */
    public static function Services()
    {
        if (self::$services) {
        } else {
            try {
                self::$services = new Services();
            } catch (\RuntimeException $e) {
                echo 'Instantiate Service Exception : ', $e->getMessage(), "\n";
                die;
            }
        }

        return self::$services;
    }

    /**
     * Application::Helpers
     *
     * @return  Helpers
     * @throws  \RuntimeException
     * @since   1.0
     */
    public function Helpers()
    {
        if ($this->helpers) {
        } else {
            try {
                $this->helpers = new Helpers();
            } catch (\Exception $e) {
                echo 'Instantiate Helpers Exception : ', $e->getMessage(), "\n";
                die;
            }
        }

        return $this->helpers;
    }
}
