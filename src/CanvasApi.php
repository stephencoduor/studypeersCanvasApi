<?php

namespace studypeers\CanvasApi;

use studypeers\CanvasApi\Traits\SetsCallParameters;
use studypeers\CanvasApi\Clients\CanvasApiClientInterface;
use studypeers\CanvasApi\Adapters\CanvasApiAdapterInterface;
use studypeers\CanvasApi\Exceptions\CanvasApiClientException;
use studypeers\CanvasApi\Exceptions\CanvasApiConfigException;
use studypeers\CanvasApi\Exceptions\CanvasApiAdapterException;

class CanvasApi
{
    use SetsCallParameters;

    protected $client;
    protected $adapter;
    protected $config;

    protected $tempClient;

    public function __construct(array $setup = [])
    {
        $this->setClient($setup['client'] ?: null);
        $this->setAdapter($setup['adapter'] ?: null);
        $this->setConfig($setup['config'] ?: null);
    }

    public function setClient($client)
    {
        if (is_null($client)) {
            return;
        }

        if (is_string($client) && class_exists($client)) {
            $client = new $client;
        }

        if ($client instanceof CanvasApiClientInterface) {
            $this->client = $client;
            return $this;
        }

        throw new CanvasApiClientException('Unknown or invalid Canvas API Client class.');
    }

    public function setAdapter($adapter)
    {
        if (is_null($adapter)) {
            return;
        }


        if (is_string($adapter) && class_exists($adapter)) {
            $adapter = new $adapter;
        }

        if ($adapter instanceof CanvasApiAdapterInterface) {
            $this->adapter = $adapter;
            return $this;
        }

        throw new CanvasApiAdapterException('Unknown or invalid Canvas API Adapter.');
    }

    public function setConfig($config)
    {
        if (is_null($config)) {
            return;
        }

        if (is_string($config) && class_exists($config)) {
            $config = new $config;
        }

        if (is_a($config, CanvasApiConfig::class)) {
            $this->config = $config;
            return $this;
        }

        throw new CanvasApiConfigException('Client class must receive CanvasApiConfig object or class name in constructor');
    }

    public function using($client)
    {
        // assume default client location in package unless we were given something that looks namespaced
        if (strpos($client, '\\') === false) {
            $client = '\\Uncgits\\CanvasApi\\Clients\\' . str_replace(' ', '', ucwords($client));
        }

        if (!class_exists($client)) {
            throw new \Exception('Client class ' . $client . ' not found');
        }

        $this->tempClient = new $client;
        return $this;
    }

    public function __call($method, $arguments)
    {
        // is it a method on the Config class?
        if (method_exists($this->config, $method)) {
            $this->config->$method(...$arguments);
            return $this;
        }

        // determine active client
        $activeClient = $this->tempClient ?: $this->client;

        // do we have a valid client set?
        if (is_null($activeClient)) {
            throw new CanvasApiClientException('Client is not set on API class');
        }

        $this->tempClient = null; // reset

        $endpointParameters = $activeClient->$method(...$arguments);
        $endpoint = (new CanvasApiEndpoint(...$endpointParameters))
        ->setFinalEndpoint($this->config);

        return $this->execute($activeClient, $endpoint, $method, $arguments);
    }

    public function execute(CanvasApiClientInterface $client, CanvasApiEndpoint $endpoint, $method, $arguments)
    {
        return new CanvasApiResult(
            $this->adapter->usingConfig($this->config)->transaction($endpoint),
            $this->config
        );
    }
}
