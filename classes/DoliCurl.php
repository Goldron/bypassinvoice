<?php
/**
 * Copyright since 2024 SILADEL.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to (GPL-3.0-or-later)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @author      David IGREJA. <david@siladel.fr> SILADEL
 * @copyright   Since 2024 SILADEL
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, Version 3
 *
 */

namespace Bypassinvoice;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Bypassinvoice\DoliLogger;
use Bypassinvoice\DoliTools;

//require_once _PS_MODULE_DIR_.'bypassinvoice/classes/Logger.php';
//require_once _PS_MODULE_DIR_.'bypassinvoice/classes/Tools.php';

class DoliCurl
{
    /**
     * $URL CURL resource URL.
     *
     * @var string
     */
    private $url;

    /**
     * $key.
     *
     * @var string
     */
    private $key;

    /**
     * content type.
     *
     * @var string
     */
    private $contentType = '';

    /**
     * $header option.
     *
     * @var array
     */
    private $header;

    /**
     * $post data
     *
     * @var string
     */
    private $post;

    /**
     * $opts option CURL.
     *
     * @var array
     */
    public $opts;

    /**
     * return curl info.
     *
     * @var array
     */
    public $returnHttpInfo;


    public $authName;

    public function __construct($url, $key, $namekey = 'DOLAPIKEY: ')
    {
        $this->setKeyApi($key);

        $this->setHeader($namekey, $this->getKeyApi());

        $this->setUrlApi($url);

        $this->opts[CURLOPT_RETURNTRANSFER] = true;
        $this->opts[CURLOPT_FAILONERROR] = true;
        $this->opts[CURLOPT_SSL_VERIFYPEER] = false;
        $this->opts[CURLOPT_SSL_VERIFYHOST] = false;

    }

    /**
     * set post data
     *
     */
    public function setPost($data): void
    {
        if (!empty(json_encode($data))) {
            $this->post = json_encode($data);
        } else {
            throw new \Exception("File json false in setPost function");
        }
    }

    /**
     * get post data
     */
    public function getPost(): string
    {
        return $this->post;
    }

    /**
     * clean post data
     */
    public function delPost(): void
    {
        $this->post = null;
    }


    public function getOpts(): array
    {
        return $this->opts;
    }

    /**
     * set curl option.
     *
     * @param string $opt   CONSTANTE CURL
     * @param bool   $value valeur des constantes
     *
     * @return bool
     */
    public function setOpts($opt, $value): string
    {
        return $this->opts[$opt] = $value;
    }

    /**
     * get header option.
     *
     * @return array
     */
    public function getHeader(): array
    {
        return $this->header;
    }

    /**
     * set header option.
     *
     * @param string $name  option
     * @param string $value value
     */
    public function setHeader($name, $value = ''): void
    {
        $this->header['name'] = $name;
        $this->header['value'] = $value;
    }

    /**
     * return content type document.
     *
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * set Content-Type.
     *
     * @param string $type content type ex: 'Content-Type:application/json'
     *
     * @return string
     */
    public function setContentType($type): string
    {
        return $this->contentType = $type;
    }

    /**
     * set api url.
     *
     * @return string
     */
    public function setUrlApi($url): string
    {
        return $this->url = $url;
    }

    /**
     * set key api.
     *
     * @return string
     */
    public function setKeyApi($key): string
    {
        return $this->key = $key;
    }

    /**
     * set auth name.
     *
     * @param string $value
     *
     * @return string
     */
    public function setAuthName($value): string
    {
        return $this->authName = $value;
    }

    /**
     * return value auth name.
     *
     * @return string
     */
    public function getAuthName(): string
    {
        return $this->authName;
    }

    /**
     * Return header.
     *
     * @return array
     */
    public function getHttpHeader(): array
    {
        return [$this->getHeader()['name'] . $this->getHeader()['value'], $this->getContentType()];
    }

    /**
     * return url api.
     *
     * @return string
     */
    public function getUrlApi(): string
    {
        return $this->url;
    }

    /**
     * Return key api.
     *
     * @return string
     */
    public function getKeyApi(): string
    {
        return $this->key;
    }

    /**
     * init to curl.
     *
     * @param string $resource
     *
     * @return array
     */
    protected function iniCurlOpts($resource): array
    {
        $this->opts[CURLOPT_URL] = $this->getUrlApi() . $resource;

        $this->opts[CURLOPT_HTTPHEADER] = $this->getHttpHeader();

        return $this->getOpts();
    }

    /**
     * execute curl.
     *
     * @param string $url
     *
     * @return mixed
     */
    public function runCurl($url, $method = 'GET', $post = null)
    {
        switch ($method) {
            case "POST":
                $this->opts[CURLOPT_POST] = 1;
                $this->setContentType('Content-Type: application/json');

                if (!empty($post)) {
                    try {
                        $this->setPost($post);
                    } catch (\Exception $e) {
                        DoliTools::printLog($e->getMessage(), 'warning');
                        \PrestaShopLogger::addLog($e->getMessage(), 2, null, 'bypassinvoice');
                    }

                    if (!empty($this->getPost())) {
                        $this->opts[CURLOPT_POSTFIELDS] = $this->getPost();
                    }
                }
                break;

            case "PUT":
                $this->opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
                $this->setContentType('Content-Type: application/json');

                if (!empty($post)) {
                    try {
                        $this->setPost($post);
                    } catch (\Exception $e) {
                        DoliTools::printLog($e->getMessage(), 'warning');
                        \PrestaShopLogger::addLog($e->getMessage(), 2, null, 'bypassinvoice');
                    }

                    if (!empty($this->getPost())) {
                        $this->opts[CURLOPT_POSTFIELDS] = $this->getPost();
                    }
                }
                break;

            case "DELETE":
                $this->delPost();
                unset($this->opts[CURLOPT_POSTFIELDS]);
                unset($this->opts[CURLOPT_POST]);
                $this->opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                $this->setContentType('Content-Type: application/json');

                break;

            default:
                $this->delPost();
                unset($this->opts[CURLOPT_POSTFIELDS]);
                unset($this->opts[CURLOPT_POST]);
                unset($this->opts[CURLOPT_CUSTOMREQUEST]);
        }

        $curl = curl_init();

        curl_setopt_array($curl, $this->iniCurlOpts($url));

        $data = json_decode(curl_exec($curl), true);

        $this->returnHttpInfo = curl_getinfo($curl);

        $type = 'info';

        switch ($this->returnHttpInfo['http_code']) {
            case '200':
                $msg = $this->returnHttpInfo['http_code'] . ' | ' . 'Successful request'
                    . ' | ' . \Configuration::get('BYPASSINVOICE_URL') . $url;
                $type = 'info';
                break;

            case '201':
                $msg = $this->returnHttpInfo['http_code'] . ' | ' . 'Successful request'
                    . ' | ' . \Configuration::get('BYPASSINVOICE_URL') . $url;
                $type = 'info';
                break;

            case '204':
                $msg = $this->returnHttpInfo['http_code'] . ' | ' . 'Successful request'
                    . ' | ' . \Configuration::get('BYPASSINVOICE_URL') . $url;
                $type = 'info';
                break;

            case '301':
                $msg = $this->returnHttpInfo['http_code'] . ' | ' . 'Permanently redirect'
                    . ' | ' . \Configuration::get('BYPASSINVOICE_URL') . $url;
                $type = 'warning';
                break;

            case '400':
                $msg = $this->returnHttpInfo['http_code'] . ' | ' . 'Bad request'
                    . ' | ' . \Configuration::get('BYPASSINVOICE_URL') . $url;
                $type = 'error';
                break;

            case '401':
                $msg = $this->returnHttpInfo['http_code'] . ' | ' . 'Unauthorized'
                    . ' | ' . \Configuration::get('BYPASSINVOICE_URL') . $url;
                $type = 'warning';
                break;

            case '403':
                $msg = $this->returnHttpInfo['http_code'] . ' | ' . 'Forbidden'
                    . ' | ' . \Configuration::get('BYPASSINVOICE_URL') . $url;
                $type = 'warning';
                break;

            case '404':
                $msg = $this->returnHttpInfo['http_code'] . ' | ' . 'Not found'
                    . ' | ' . \Configuration::get('BYPASSINVOICE_URL') . $url;
                $type = 'info';
                break;


            case '500':
                $msg = $this->returnHttpInfo['http_code'] . ' | ' . 'Server error'
                    . ' | ' . \Configuration::get('BYPASSINVOICE_URL') . $url;
                $type = 'error';
                break;

            case '503':
                $msg = $this->returnHttpInfo['http_code'] . ' | ' . 'Service unavailable'
                    . ' | ' . \Configuration::get('BYPASSINVOICE_URL') . $url;
                $type = 'error';
                break;

            default:
                $msg = $this->returnHttpInfo['http_code'] . ' | ' . 'Server error'
                    . ' | ' . \Configuration::get('BYPASSINVOICE_URL') . $url;
                $type = 'error';
                break;
        }

        DoliTools::printLog($method . ' | ' . $msg, $type);

        curl_close($curl);

        if (empty($data)) {
            return null;
        }

        return $data;
    }
}
