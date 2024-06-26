<?php

namespace QuickScraper\Main;

use QuickScraper\Constants\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class QuickScraperClass
{
  private $accessToken = '';
  private $parseUrl;
  private $DEFAULT = array(
    'Client' => 'PHP_CLIENT_LIB',
    'HOST' => '',
    'Client-Version' => '3.4'
  );

  /**
   * @param string $accessToken
   */
  public function __construct($accessToken = '')
  {
    $this->loadPackageFiles(__DIR__ . '/../');
    $this->DEFAULT['HOST'] = (new Config)->getBaseUrl();
    $this->parseUrl = $this->DEFAULT['HOST'] . 'parse';
    if ($accessToken) {
      $this->setAccessToken($accessToken);
    }
  }

  public function setHost(string $host): string
  {
    if ($host) {
      return $this->parseUrl = $host . 'parse';
    }
  }

  public function setAccessToken(string $accessToken): string
  {
    return $this->accessToken = $accessToken;
  }
  /**
   * @return mixed
   */
  public function getHtml(string $url, array $parseOptions = [])
  {

    $customHeaders = null;
    if (isset($parseOptions['customRequestHeaders'])) {
      $customHeaders = $parseOptions['customRequestHeaders'];
    }
    $headers = $this->prepareHeaders($customHeaders, $parseOptions);
    $gotOptions = array(
      'headers' => $headers,
      'verify' => false
    );
    try {
      $httpClient = new Client();

      if ((isset($parseOptions['customSelectors']) && sizeof($parseOptions['customSelectors']) > 0) || isset($parseOptions['customRequestHeaders']) || isset($parseOptions['formData']) || ((isset($parseOptions['dynamicInputs'])) && isset($parseOptions['parserSubscriptionId']))) {
        $bodyData = $this->prepareRequestBodyOrUrl($url, $parseOptions, 'POST');
        $gotOptions['form_params'] = $bodyData;
        $response = $httpClient->postAsync($this->parseUrl, [
          'headers' => $gotOptions['headers'],
          'body' =>  json_encode($bodyData)
        ])->wait();
        $responseObject = (object) array(
          'data' => $response->getBody()->getContents()
        );
        return json_encode($responseObject, JSON_HEX_TAG);
      }
      $requestUrl = $this->prepareRequestBodyOrUrl($url, $parseOptions, 'GET');
      $response = $httpClient->getAsync($requestUrl, $gotOptions)->wait();
      $responseObject = (object) array(
        'data' => $response->getBody()->getContents()
      );
      return json_encode($responseObject, JSON_HEX_TAG);
    } catch (RequestException $exception) {
      $response = $exception->getResponse();
      $responseError = json_decode((string) $response->getBody());
      if ($responseError->message && $responseError->statusCode) {
        $statusCode = $responseError->statusCode ? $responseError->statusCode : 530;
        http_response_code($statusCode);
        throw new \Exception($responseError->message, $statusCode);
      }
      $statusCode = 530;
      $message = 'Failed to process request';
      $type = 'UNKNOWN';
      $throwError = array('message' => $message, 'status' => $statusCode);
      http_response_code($statusCode);
      // return json_encode($throwError);
      throw new \Exception($throwError['message'], $statusCode);
    }
  }
  public function post(string $url, array $parseOptions = [])
  {
    $response = $this->getHtml($url, $parseOptions);
    return $response;
  }

  private function put(string $url, array $parseOptions = [])
  {
    $response = $this->getHtml($url, $parseOptions);
    return $response;
  }

  public function writeHtmlToFile(string $url, string $filePath)
  {
    $isFileExits = fopen($filePath, 'w');
    if (!$isFileExits) {
      $message = 'File does not exits.';
      $statusCode = 400;
      $throwError = array('message' => $message, 'status' => $statusCode);
      http_response_code($statusCode);
      return json_encode($throwError);
    }
    $current = file_get_contents($filePath);
    $getHtml = $this->getHtml($url);
    $extractJson = json_decode($getHtml);
    if (property_exists($extractJson, 'data')) {
      file_put_contents($filePath, $extractJson->data);
    }
    fclose($isFileExits);
    return $getHtml;
  }

  /**
   * @param array|null $customHeaders
   * @param array|null $parseOptions
   */
  private function prepareHeaders(array $customHeaders = null, array $parseOptions = null)
  {
    $headers = array(
      'Client' => $this->DEFAULT['Client'],
      'Client-Version' => $this->DEFAULT['Client-Version'],
      'accept' => 'application/json, text/plain, */*',
      'content-type' => 'application/json'
    );
    $mergedHeaders = null;
    if ($customHeaders !== null) {
      $mergedHeaders = array_merge($headers, $customHeaders);
    }
    if ($parseOptions !== null && isset($parseOptions['isKeepHeaders']) && $parseOptions['isKeepHeaders'] === 'true') {
      return $mergedHeaders;
    }
    return $headers;
  }

  public function getData(string $url, array $parseOptions = [])
  {
    $getHtml = $this->getHtml($url, $parseOptions);
    return $getHtml;
  }

  public function account()
  {
    $requestUrl = $this->DEFAULT['HOST'] . 'account?access_token=' . $this->accessToken;
    try {
      $httpClient = new Client();
      $response = $httpClient->getAsync($requestUrl)->wait();
      $responseObject = (object) array(
        'data' => $response->getBody()->getContents()
      );
      return json_encode($responseObject, JSON_HEX_TAG);
    } catch (RequestException $exception) {
      $response = $exception->getResponse();
      $responseError = json_decode((string) $response->getBody());
      if ($responseError->message && $responseError->statusCode) {
        $statusCode = $responseError->statusCode ? $responseError->statusCode : 530;
        http_response_code($statusCode);
        // return json_encode($responseError);
        throw new \Exception($responseError->message, $statusCode);
      }
      $statusCode = 530;
      $message = 'Failed to process request';
      $type = 'UNKNOWN';
      $throwError = array('message' => $message, 'status' => $statusCode);
      http_response_code($statusCode);
      // return json_encode($throwError);
      throw new \Exception($throwError['message'], $statusCode);
    }
  }

  // Load all package from the project
  public function loadPackageFiles($dir)
  {
    $composer = json_decode(file_get_contents($dir . '/composer.json'), 1);
    $namespaces = $composer['autoload']['psr-4'];
    // Foreach namespace specified in the composer, load the given classes
    foreach ($namespaces as $namespace => $classpaths) {
      if (!is_array($classpaths)) {
        $classpaths = array($classpaths);
      }
      spl_autoload_register(function ($classname) use ($namespace, $classpaths, $dir) {
        // Check if the namespace matches the class we are looking for
        if (preg_match('#^' . preg_quote($namespace) . '#', $classname)) {
          // Remove the namespace from the file path since it's psr4
          $classname = str_replace($namespace, '', $classname);
          $filename = preg_replace('#\\\\#', '/', $classname) . '.php';
          foreach ($classpaths as $classpath) {
            $fullpath = $dir . '/' . $classpath . '/$filename';
            if (file_exists($fullpath)) {
              include_once $fullpath;
            }
          }
        }
      });
    }
  }

  private function prepareRequestBodyOrUrl($url, $parseOptions, $requestType)
  {
    $urlOptions = array(
      'access_token' => $this->accessToken,
      'url' => $url
    );

    $urlOptions['premium'] = (isset($parseOptions['premium']) && $parseOptions['premium'] !== '') ? $parseOptions['premium'] : null;
    $urlOptions['render'] = (isset($parseOptions['render']) && $parseOptions['render'] !== '') ? $parseOptions['render'] : null;
    $urlOptions['isKeepHeaders'] = (isset($parseOptions['isKeepHeaders']) && $parseOptions['isKeepHeaders'] !== '') ? $parseOptions['isKeepHeaders'] : null;
    $urlOptions['isZapier'] = (isset($parseOptions['isZapier']) && $parseOptions['isZapier'] !== '') ? $parseOptions['isZapier'] : null;
    $urlOptions['isPabbly'] = (isset($parseOptions['isPabbly']) && $parseOptions['isPabbly'] !== '') ? $parseOptions['isPabbly'] : null;
    $urlOptions['isPabbly'] = (isset($parseOptions['isPabbly']) && $parseOptions['isPabbly'] !== '') ? $parseOptions['isPabbly'] : null;
    $urlOptions['customRequestHeaders'] = (isset($parseOptions['customRequestHeaders']) && $parseOptions['customRequestHeaders'] !== '' &&
      isset($parseOptions['isKeepHeaders']) && $parseOptions['isKeepHeaders'] !== '') ? $parseOptions['customRequestHeaders'] : null;

    if (isset($parseOptions['session_number']) && $parseOptions['session_number'] !== '') {
      $urlOptions['session_number'] = $parseOptions['session_number'];
    }
    if (isset($parseOptions['country_code']) && $parseOptions['country_code'] !== '') {
      $urlOptions['country_code'] = $parseOptions['country_code'];
    }
    if (isset($parseOptions['device_type']) && $parseOptions['device_type'] !== null) {
      $urlOptions['device_type'] = $parseOptions['device_type'];
    }
    if (isset($parseOptions['parserSubscriptionId']) && $parseOptions['parserSubscriptionId'] !== null) {
      $urlOptions['parserSubscriptionRequestId'] = $parseOptions['parserSubscriptionId'];
    }
    if (isset($parseOptions['webhookRequestId']) && $parseOptions['webhookRequestId'] !== null) {
      $urlOptions['webhookRequestId'] = $parseOptions['webhookRequestId'];
    }
    if (isset($parseOptions['isScroll'])) {
      $urlOptions['isScroll'] = $parseOptions['isScroll'];
    }
    if (isset($parseOptions['scrollTimeout'])) {
      $urlOptions['scrollTimeout'] = $parseOptions['scrollTimeout'];
    }
    if (isset($parseOptions['customSelectors'])) {
      $urlOptions['customSelectors'] = json_encode($parseOptions['customSelectors']);
    }
    if (isset($parseOptions['keepSelection'])) {
      $urlOptions['keepSelection'] = $parseOptions['keepSelection'];
    }
    if (isset($parseOptions['formData'])) {
      $urlOptions['formData'] = json_encode($parseOptions['formData']);
    }
    if (isset($parseOptions['isKeepFormDataSelection'])) {
      $urlOptions['isKeepFormDataSelection'] = $parseOptions['isKeepFormDataSelection'];
    }
    if (isset($parseOptions['dynamicInputs'])) {
      $urlOptions['dynamicInputs'] = json_encode($parseOptions['dynamicInputs']);
    }
    if (isset($parseOptions['parserResponseType'])) {
      $urlOptions['parserResponseType'] = $parseOptions['parserResponseType'];
    }
    if ($requestType == 'GET') {
      $requestUrl = $this->parseUrl . '?' . http_build_query($urlOptions, '', '&');
      return $requestUrl;
    }
    return $urlOptions;
  }
}
