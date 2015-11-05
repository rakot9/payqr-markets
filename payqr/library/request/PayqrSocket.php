<?php

/**
 * Замена cURL в отправке запросов в PayQR, если cURL отсутствует
 */

class PayqrSocket
{
  public  $headers;

  /**
   * Отправка GET-запроса
   * @param $url
   * @param $getdata
   * @return bool|PayqrCurl_response
   */
  public function get($url, $getdata)
  {
    if (!empty($getdata)) {
      $url .= (stripos($url, '?') !== false) ? '&' : '?';
      $url .= (is_string($getdata)) ? $getdata : PayqrBase::http_build_query($getdata, '', '&');
    }
    $url_obj = parse_url($url);
    if (!isset($url_obj['host'])) {
      PayqrLog::log(__FILE__."\n\r".__METHOD__."\n\r L:".__LINE__."\n\r Неверный параметр url: " . $url ." не удалось получить host для запроса");
      return false;
    }
    $host = $url_obj['host'];

    $fp = fsockopen("ssl://" . $host, 443, &$errno, &$errstr, intval(PayqrConfig::$maxTimeOut));
    if (!$fp) {
      echo "$errstr ($errno)<br />\n";
    } else {
      $out = "GET $url HTTP/1.1\r\n";
      $out .= "User-Agent: PayQr Lib\r\n";
      $out .= "Host: $host\r\n";
      $out .= "Accept: */*\r\n";
      if (is_array($this->headers)) {
        foreach ($this->headers as $key => $value) {
          $out .= "$key: $value\r\n";
        }
      }
      $out .= "Connection: Close\r\n\r\n";
      fwrite($fp, $out);
      $output = "";
      while (!feof($fp)) {
        $output .= fgets($fp, 1024);
      }
      fclose($fp);
      $response = false;
      if ($output) {
        $response = new PayqrCurl_response($output);
      } else {
        PayqrLog::log(__FILE__."\n\r".__METHOD__."\n\r L:".__LINE__."\n\r".'Ошибка при запросе ' . $url . ' пустой или неправильный ответ ' . print_r($output, true) . "\n");
      }
      return $response;
    }
  }

  /**
   * Отправка POST-запроса
   * @param $url
   * @param $postdata
   * @return bool|PayqrCurl_response
   */
  public function post($url, $postdata)
  {
    if (is_array($postdata)) {
      $postdata = PayqrBase::http_build_query($postdata, '', '&');
    }
    $url_obj = parse_url($url);
    if (!isset($url_obj['host'])) {
      PayqrLog::log(__FILE__."\n\r".__METHOD__."\n\r L:".__LINE__."\n\r"." Неверный параметр url: " . $url." не удалось определить host для запроса");
      return false;
    }
    $host = $url_obj['host'];
    $errno = "";
    $errstr = "";
    $fp = fsockopen("ssl://" . $host, 443, &$errno, &$errstr, intval(PayqrConfig::$maxTimeOut));
    if (!$fp) {
      PayqrLog::log(__FILE__."\n\r".__METHOD__."\n\r L:".__LINE__."\n\r".'Ошибка при запросе ' . $url . ' ' . $errstr ($errno) . "\n");
    } else {
      $out = "POST $url HTTP/1.1\r\n";
      $out .= "User-Agent: PayQr Lib\r\n";
      $out .= "Host: $host\r\n";
      $out .= "Accept: */*\r\n";
      if (is_array($this->headers)) {
        foreach ($this->headers as $key => $value) {
          $out .= "$key: $value\r\n";
        }
      }
      $out .= "Connection: Close\r\n\r\n";
      $out .= "$postdata\n\n";
      $out .= "\r\n";
      fwrite($fp, $out);
      $output = "";
      while (!feof($fp)) {
        $output .= fgets($fp, 1024);
      }
      fclose($fp);
      $response = false;
      if ($output) {
        $response = new PayqrCurl_response($output);
      } else {
        PayqrLog::log(__FILE__."\n\r".__METHOD__."\n\r L:".__LINE__."\n\r".'Ошибка при запросе ' . $url . ' пустой или неправильный ответ ' . print_r($output, true) . "\n");
      }
      return $response;
    }
  }
} 