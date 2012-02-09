<?php
/**
 * @package     Molajo
 * @subpackage  Base
 * @copyright   Copyright (C) 2012 Amy Stephen. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('MOLAJO') or die;

/**
 * Application
 *
 * @package     Molajo
 * @subpackage  Base
 * @since       1.0
 */
class MolajoApplication
{
    /**
     * Application static instance
     *
     * @var    object
     * @since  1.0
     */
    protected static $instance;

    /**
     * Configuration
     *
     * @var    object
     * @since  1.0
     */
    protected $_configuration = null;

    /**
     * Input Object
     *
     * @var    object
     * @since  1.0
     */
    protected $_input;

    /**
     * Service Connections
     *
     * @var object
     * @since 1.0
     */
    protected $_service;

    /**
     * Metadata
     *
     * @var object
     * @since 1.0
     */
    protected $_metadata;

    /**
     * Custom Fields
     *
     * @var object
     * @since 1.0
     */
    protected $_custom_fields;

    /**
     * getInstance
     *
     * @static
     * @param  Input|null $input
     *
     * @return bool|object
     * @since  1.0
     */
    public static function getInstance(Input $input = null)
    {
        if (empty(self::$instance)) {
            self::$instance = new MolajoApplication (
                $input
            );
        }
        return self::$instance;
    }

    /**
     * Class constructor.
     *
     * @param  mixed   $input
     *
     * @return  null
     * @since   1.0
     */
    public function __construct(Input $input = null)
    {
        if ($input instanceof Input) {
            $this->_input = $input;
        }
        /** return to site class */
        return;
    }

    /**
     * load
     *
     * Controls Page Rendering and Task Logic Flow
     *
     * @return  mixed
     * @since   1.0
     */
    public function load()
    {
        /** initiate application services */
        $this->initiateApplicationServices();

        /** responder: instantiate class to listen for output */
        $res = Molajo::Responder();

        /** configuration: ssl check for application */
        if ($this->get('force_ssl') >= 1) {
           if (isset($_SERVER['HTTPS'])) {
           } else {
               $res->redirect((string)'https' .
                       substr(MOLAJO_BASE_URL, 4, strlen(MOLAJO_BASE_URL) - 4) .
                       MOLAJO_APPLICATION_URL_PATH .
                       '/' .
                       MOLAJO_PAGE_REQUEST
               );
           }
        }

        /** request: define processing instructions in page_request object */
        $req = Molajo::Request();
        $req->process();

        /**
         * Display Task
         *
         * Input Statement Loop until no more <input statements found
         *
         * 1. Parser: parses theme and rendered output for <input:renderer statements
         *
         * 2. Renderer: each input statement processed by extension renderer in order
         *    to collect task object for use by the MVC
         *
         * 3. MVC: executes task/controller which handles model processing and
         *    renders template and wrap views
         */

        if ($req->get('mvc_task') == 'add'
            || $req->get('mvc_task') == 'edit'
            || $req->get('mvc_task') == 'display'
        ) {
            Molajo::Parser();

            /**
             * Action Task
             */

        } else {

            //$this->_processTask();
        }

        /** responder: process rendered output */
        $res->respond();

        return;
    }

    /**
     * initiateApplicationServices
     *
     * loads all services defined in the services.xml file
     *
     * @param null|Registry $config
     *
     * @return mixed
     * @since 1.0
     */
    protected function initiateApplicationServices()
    {
        $services = simplexml_load_file(
            MOLAJO_APPLICATIONS_CORE . '/services/services.xml'
        );
        if (count($services) == 0) {
            return;
        }
        $this->_service = new Registry();

        foreach ($services->service as $s) {
            $serviceName = (string)$s->name;
            $connection = $this->connectService ($s);
            if ($connection === false) {
            } else {
                $this->set($serviceName, $connection, 'service');
                echo $serviceName.'<br />';
            }
        }

        /** Store Configuration data in Application Object  */
        $config = $this->get('Configuration', '', 'service');
        $this->_metadata = $config->metadata;
        $this->_custom_fields = $config->custom_fields;
        $this->_configuration = $config->configuration;

        var_dump($this->get('Date', '', 'service'));
    }

    /**
     * connectService
     *
     * @param $service
     * @return bool
     */
    protected function connectService ($service)
    {
        $serviceName = (string)$service->name;

        if (trim($serviceName) == '') {
            return false;
        }

        if (substr($serviceName, 0, 4) == 'HOLD') {
            return false;
        }

        $serviceClass = (string)$service->serviceClass;
        if (trim($serviceClass == '')) {
            $serviceClass = 'Molajo'.ucfirst($serviceName).'Service';
        }

        /** getInstance Method Parameters */
        $instanceParameters = array();
        if (isset($service->getInstance->parameters->parameter)) {
            foreach ($service->getInstance->parameters->parameter as $p) {
                $name = (string)$p['name'];
                $value = (string)$p['value'];
                $instanceParameters[$name] = $value;
            }
        }

        /** connect Method Parameters */
        $connectParameters = array();
        if (isset($service->connect->parameters->parameter)) {
            foreach ($service->connect->parameters->parameter as $p) {
                $name = (string)$p['key'];
                $value = (string)$p['value'];
                $connectParameters[$name] = $value;
            }
        }

        /** instantiate a static instance of the class */
        if (method_exists($serviceClass, 'getInstance')) {
            $results = call_user_func(
                array($serviceClass, 'getInstance'),
                $instanceParameters
            );
            if ($results === false) {
                return false;
            }
        }

        /** connect in object context */
        if (method_exists($serviceClass, 'connect')) {

            /** parameters from array to string */
            $cp = '';
            foreach ($connectParameters as $key => $value) {
                if ($cp !== '') {
                    $cp .= ',';
                }
                $cp .= '$' . $key . '="' . $value . '"';
            }

            /** connect */
            $connection = '';
            $objectContext = new $serviceClass ();
            $execute = '$connection = $objectContext->connect(' . $cp . ');';
            eval($execute);

            if ($connection == false) {
                return false;
            } else {
                return $connection;
            }
        }
    }

    /**
     * get
     *
     * Retrieves values, or establishes the value with a default, if not available
     *
     * @param  string  $key      The name of the property.
     * @param  string  $default  The default value (optional) if none is set.
     * @param  string  $type     custom, metadata, languageObject, config
     *
     * @return  mixed
     *
     * @since   1.0
     */
    public function get($key, $default = null, $type = 'config')
    {
        if ($type == 'custom') {
            return $this->_custom_fields->get($key, $default);

        } else if ($type == 'metadata') {
            return $this->_metadata->get($key, $default);

        } else if ($type == 'log') {
            return $this->_log->get($key, $default);

        } else if ($type == 'input') {
            return $this->_input;

        } else if ($type == 'service') {
            return $this->_service->get($key);

        } else {
            return $this->_configuration->get($key, $default);
        }
    }

    /**
     * set
     *
     * Modifies a property, creating it and establishing a default if not existing
     *
     * @param  string  $key    The name of the property.
     * @param  mixed   $value  The default value to use if not set (optional).
     * @param  string  $type   Custom, metadata, config
     *
     * @return  mixed
     * @since   1.0
     */
    public function set($key, $value = null, $type = 'config')
    {
        if ($type == 'custom') {
            return $this->_custom_fields->set($key, $value);

        } else if ($type == 'metadata') {
            return $this->_metadata->set($key, $value);

        } else if ($type == 'log') {
            return $this->_log->set($key, $value);

        } else if ($type == 'input') {
            return $this->_input;

        } else if ($type == 'service') {
            return $this->_service->set($key, $value);

        } else {
            return $this->_configuration->set($key, $value);
        }
    }

    /**
     * getHash
     *
     * Provides a secure hash based on a seed
     *
     * @param   string   $seed  Seed string.
     *
     * @return  string   A secure hash
     * @since  1.0
     */
    public static function getHash($seed)
    {
        return md5(self::get('secret') . $seed);
    }

    /**
     * loadSession
     *
     * Method to create a session for the Web application.  The logic and options for creating this
     * object are adequately generic for default cases but for many applications it will make sense
     * to override this method and create _session objects based on more specific needs.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function loadSession()
    {
        // Generate a _session name.
        $name = md5($this->get('secret') .
            $this->get('_session_name', get_class($this)));

        // Calculate the _session lifetime.
        $lifetime = (($this->get('_session_lifetime'))
            ? $this->get('_session_lifetime') * 60 : 900);

        // Get the _session handler from the configuration.
        $handler = $this->get('_session_handler', 'none');

        // Initialize the options for Session.
        $options = array(
            'name' => $name,
            'expire' => $lifetime,
            'force_ssl' => $this->get('force_ssl')
        );

        // Instantiate the _session object.
        $_session = MolajoSession::getInstance($handler, $options);

        if ($_session->getState() == 'expired') {
            $_session->restart();
        }

        // If the _session is new, load the user and registry objects.
        if ($_session->isNew()) {
            $_session->set('registry', new Registry);
            $_session->set('user', new MolajoUser);
        }

        // Set the _session object.
        $this->_session = $_session;
    }

    /**
     * getSession
     *
     * Method to get the application _session object.
     *
     * @return  Session  The _session object
     *
     * @since   1.0
     */
    public function getSession()
    {
        return $this->_session;
    }
}
