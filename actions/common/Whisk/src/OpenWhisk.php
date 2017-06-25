<?php

namespace Akrabat;

use GuzzleHttp\Client;

class OpenWhisk
{
    const ACTIONS_PATH = '/api/v1/namespaces/%s/actions/%s';
    const TRIGGERS_PATH = '/api/v1/namespaces/%s/triggers/%s';

    public function __construct(Client $client = null)
    {
        $this->client = $client;
    }

    /**
     * Invoke an OpenWhisk action
     *
     * @param  string       $action     action name (e.g. "/whisk.system/utils/echo")
     * @param  array        $parameters paramters to send to the action
     * @param  bool.        $blocking   Should the invocation block? (default: true)
     * @return array                    Result
     */
    public function invoke(string $action, array $parameters = [], bool $blocking = true) : array
    {
        $path = vsprintf(static::ACTIONS_PATH, $this->parseQualifiedName($action));
        $path .= '?blocking=' . ($blocking ? 'true' : 'false');

        return  $this->post($path, $parameters);
    }

    /**
     * Fire an OpenWhisk trigger event
     *
     * @param  string       $event      event name (e.g. "locationUpdate")
     * @param  array        $parameters paramters to send to the event
     * @param  bool.        $blocking   Should the invocation block? (default: true)
     * @return array                    Result
     */
    public function trigger(string $event, array $parameters = []) : array
    {
        $path = vsprintf(static::TRIGGERS_PATH, $this->parseQualifiedName($event));
        $path .= '?blocking=true';

        return  $this->post($path, $parameters);
    }

    public function post($path, $parameters) : array
    {
        list($host, $authKey) = $this->getCommunicationDetails($_ENV);

        $client = $this->getClient();

        try {
            $response = $client->request(
                'POST',
                "$host$path",
                [
                    'json' => $parameters,
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => "Basic $authKey",
                    ],
                ]
            );
            return json_decode($response->getBody(), true);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            if ($e->getCode() == 502) {
                // if we get a 502, then we also get a valid response body
                return json_decode($e->getResponse()->getBody(), true);
            }
            throw new \RuntimeException("It failed", $e->getCode(), $e);
        }
    }


    public function parseQualifiedName(string $qualifiedName) : array
    {
        $DEFAULT_NAMESPACE = '_';
        $DELIMTER = '/';

        $segments = explode($DELIMTER, trim($qualifiedName, $DELIMTER));
        if (count($segments) > 1) {
            $namespace = array_shift($segments);
            return [$namespace, implode($DELIMTER, $segments)];
        }

        return [$DEFAULT_NAMESPACE, $segments[0]];
    }

    protected function getClient()
    {
        if (!$this->client) {
            $config = [];
            if (strpos($_ENV['__OW_API_HOST'], '172.17.0.1') !== false
                && strpos($_ENV['__OW_API_KEY'], '23bc46b1-71f6-4ed5-8c54-816aa4f8c502') === 0
            ) {
                // disable SSL checks for development systems using the guest namespace
                $config = ['curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ]];
            }
            $this->client = new Client($config);
        }
        return $this->client;
    }

    protected function getCommunicationDetails($env)
    {
        $host = $env['__OW_API_HOST'] ?? '';
        if (!$host) {
            throw new \RuntimeException("__OW_API_HOST environment variable was not set.");
        }

        $authKey = $env['__OW_API_KEY'] ?? 'authKey';

        return [$host, base64_encode($authKey)];
    }
}
