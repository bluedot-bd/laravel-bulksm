<?php

namespace BluedotBd\LaravelBulksms;

use Exception;
use Illuminate\Support\Facades\Facade;
use Log;

/**
 * @see \BluedotBd\LaravelBulksms\Skeleton\SkeletonClass
 */
class LaravelBulksmsFacade extends Facade
{
    /**
     * SMS API Details
     *
     * @var arrayu
     */
    protected $config;

    /**
     * The phone number notifications should be sent to.
     *
     * @var string
     */
    protected $to;

    /**
     * Full Message
     *
     * @var string
     */
    protected $message;

    /**
     * Message Lines
     *
     * @var array
     */
    protected $lines;

    /**
     * Create a new LaravelBulksms instance
     *
     * @param string $config config file name
     */
    public function __construct($config = null)
    {
        if ($config) {
            $this->loadConfig($config);
        }
        if (!is_dir(app('path.storage') . DIRECTORY_SEPARATOR . 'magic-sms')) {
            mkdir(app('path.storage') . DIRECTORY_SEPARATOR . 'magic-sms');
        }
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-bulksms';
    }

    /**
     * SMS to Number
     * @param string $to
     */
    public function to($to): self
    {
        $this->to = $to;
        return $this;
    }

    /**
     * SMS Text
     * @param string $message
     */
    public function message($message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Add new Line
     * @param string $line
     */
    public function line($line = ''): self
    {
        $this->lines[] = $line;
        return $this;
    }

    /**
     * Load Config from Storage Path
     * @param  [type] $config [description]
     * @return [type]         [description]
     */
    public function loadConfig($config)
    {
        $file = app('path.storage') . DIRECTORY_SEPARATOR . 'magic-sms' . DIRECTORY_SEPARATOR . $config;
        if (file_exists($file)) {
            $this->config = json_decode(file_get_contents($file), true);
        } else {
            throw new Exception('Magic SMS Config File Not Found!', 1);
        }
    }

    /**
     * Export Config as Array
     * @return Array
     */
    public function config()
    {
        return $this->config;
    }

    /**
     * HTTP GET Request
     * @param string $url     api url
     * @param array  $data    params
     * @param array  $headers headers
     * @return object/string GET Response
     */
    private function httpGet($url, $data, $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($response);
        return ($json) ? $json : $response;
    }

    /**
     * HTTP POST Request
     * @param string $url     api url
     * @param array  $data    params
     * @param array  $headers headers
     * @return object/string POST Response
     */
    private function httpPost($url, $data, $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($response);
        return ($json) ? $json : $response;
    }

    /**
     * Check SMS Balance
     * @return float SMS Balance
     */
    public function balance()
    {
        $url = explode('?', $this->config['balance_url']);
        parse_str($url[1], $data);
        if (preg_match('/get/i', $this->config['balance_method'])) {
            $response = $this->httpGet($url[0], $data, explode(',', $this->config['balance_header']));
        } else {
            $response = $this->httpPost($url[0], $data, explode(',', $this->config['balance_header']));
        }
        $balance = [];
        if (is_array($response) || is_object($response)) {
            array_walk_recursive($response, function ($v, $k) use (&$balance) {
                if ($k === $this->config['balance_key']) {
                    $balance[] = $v;
                }
            });
        }
        return (float) @$balance[0];
    }

    /**
     * Send SMS
     * @return object SMS API Response
     */
    public function send()
    {
        $mobileNumber = $this->to;
        $message      = (empty($this->lines)) ? $this->message : implode("\n", $this->lines);
        $url          = explode('?', $this->config['send_url']);
        parse_str($url[1], $data);
        $headers  = explode(',', $this->config['send_header']);
        $response = [];

        $data[$this->config['send_key_mobile']]  = $mobileNumber;
        $data[$this->config['send_key_message']] = $message;

        if (preg_match('/get/i', $this->config['send_method'])) {
            if ($this->config['api_mode'] == 'dry') {
                Log::info(json_encode(['method' => 'GET', 'url' => $url[0], 'params' => $data, 'headers' => $headers]));
            } else {
                $response = $this->httpGet($url[0], $data, $headers);
            }
        } else {
            if ($this->config['api_mode'] == 'dry') {
                Log::info(json_encode(['method' => 'POST', 'url' => $url[0], 'params' => $data, 'headers' => $headers]));
            } else {
                $response = $this->httpPost($url[0], $data, $headers);
            }
        }

        if ($this->config['send_success']) {
            if (!preg_match("/{$this->config['send_success']}/i", json_encode($response))) {
                Log::error(json_encode($response));
                throw new Exception("SMS Sending Failed!\nAPI Response:" . json_encode($response), 1);
            }
        }

        return $response;
    }

    /**
     * Check API Url & Save as JSON in storage_path
     * @param  arrau  $params params
     * @param  string $url    API URL (Send)
     * @param  string $config config name
     * @return array  formated data
     */
    public function checkAndSave($params, $url, $config)
    {
        $url                = htmlspecialchars_decode(urldecode($url));
        $params['send_url'] = htmlspecialchars_decode(urldecode($params['send_url']));
        if (array_key_exists('balance_url', $params)) {
            $params['balance_url'] = htmlspecialchars_decode(urldecode($params['balance_url']));
        }
        $file         = app('path.storage') . DIRECTORY_SEPARATOR . 'magic-sms' . DIRECTORY_SEPARATOR . $config;
        $formattedUrl = $this->checkAndFormatUrl($url);
        if ($formattedUrl && $formattedUrl['status']) {
            $params['send_url']         = $formattedUrl['url'];
            $params['send_key_mobile']  = $formattedUrl['send_key_mobile'];
            $params['send_key_message'] = $formattedUrl['send_key_message'];
        } else {
            throw new Exception('Invalid Send URL', 1);
        }
        // dd($params);
        file_put_contents($file, json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $formattedUrl;
    }

    /**
     * Check and Format API URL (Send)
     * @param  string $BulkSMS_url API URL (Send)
     * @return array  formated data
     */
    public function checkAndFormatUrl($BulkSMS_url)
    {
        $match            = 0;
        $url              = explode('?', $BulkSMS_url);
        $send_key_mobile  = '';
        $send_key_message = '';
        $return           = ['status' => false, 'url' => '', 'send_key_mobile' => '', 'send_key_message' => ''];
        if (count($url) == 2) {
            $params = [];
            parse_str($url[1], $searchParams);
            foreach ($searchParams as $key => $value) {
                if (preg_match('/message_type/', $key)) {
                    $params[$key] = $value;
                } elseif (preg_match('/contacts|number|mobile|^to$|toUser|toCustomer|(^user$)|msisdn|receiver|recipient/i', $key)) {
                    $match += 1;
                    $params[$key]    = '##NUMBER##';
                    $send_key_mobile = $key;
                } elseif (preg_match('/msg|message|text|Body|(^sms$)/i', $key)) {
                    $match += 1;
                    $params[$key]     = '##SMS##';
                    $send_key_message = $key;
                } elseif (!preg_match('/schedule|delay/i', $key)) {
                    $params[$key] = $value;
                }
            }
            if ($match == 2) {
                $BulkSMS_url = $url[0] . '?' . http_build_query($params);
                $BulkSMS_url = urldecode($BulkSMS_url);
                $return      = ['status' => true, 'url' => $BulkSMS_url, 'send_key_mobile' => $send_key_mobile, 'send_key_message' => $send_key_message];
            }
        }
        return $return;
    }
}
