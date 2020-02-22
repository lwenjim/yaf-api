<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 4/19/2019
 * Time: 4:55 PM
 */

namespace Lwenjim\Yaf;

use GuzzleHttp\Client;


class Http extends Client
{
    use Instance;

    public function request($method, $uri = '', array $options = [])
    {
        $response = parent::request($method, $uri, $options);
        $rs   = (string)$response->getBody();
        $result   = json_decode($rs, true);
        debug(compact('uri', 'options', 'result'), "http.{$method}");
        if ($result['code'] != 200) {
            throw new RequestException(sprintf("http error! url:%s, errInfo:%s", $uri, isset($result['message'])?$result['message']:$result['msg']));
        }
        return $result;
    }

    public function __call($method, $args)
    {
        if (count($args) < 1) {
            throw new \InvalidArgumentException('Magic request methods require a URI and optional options array');
        }
        $uri  = $args[0];
        $opts = isset($args[1]) ? $args[1] : [];
        if (in_array($method, ['put', 'delete'])) {
            $userAgent       = "GuzzleHttp/6.3.3 curl/" . phpversion('curl') . " PHP/" . phpversion() . ' Method/' . strtoupper($method);
            $opts['headers'] = array_merge($opts['headers'] ?? [], ['User-Agent' => $userAgent, 'X-HTTP-Method-Override' => strtoupper($method)]);
            $method          = 'post';
        }
        return substr($method, -5) === 'Async'
            ? $this->requestAsync(substr($method, 0, -5), $uri, $opts)
            : $this->request($method, $uri, $opts);
    }
}
