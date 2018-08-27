<?php

namespace Algolia\AlgoliaSearch\Internals;

use Algolia\AlgoliaSearch\Exceptions\BadRequestException;
use Algolia\AlgoliaSearch\Exceptions\RetriableException;
use Algolia\AlgoliaSearch\Exceptions\UnreachableException;
use Algolia\AlgoliaSearch\Http\HttpClientInterface;
use Algolia\AlgoliaSearch\RequestOptions\RequestOptions;
use Algolia\AlgoliaSearch\RequestOptions\RequestOptionsFactory;
use Algolia\AlgoliaSearch\Support\ClientConfig;
use Algolia\AlgoliaSearch\Support\Debug;

class ApiWrapper
{
    /**
     * @var HttpClientInterface
     */
    private $http;

    /**
     * @var \Algolia\AlgoliaSearch\Support\ClientConfig
     */
    private $config;

    /**
     * @var RequestOptionsFactory
     */
    private $requestOptionsFactory;

    public function __construct(
        HttpClientInterface $http,
        ClientConfig $config,
        RequestOptionsFactory $RqstOptsFactory = null
    ) {
        $this->http = $http;
        $this->config = $config;
        $this->requestOptionsFactory = $RqstOptsFactory ? $RqstOptsFactory : new RequestOptionsFactory($config);
    }

    public function read($method, $path, $requestOptions = array(), $defaults = array())
    {
        if ('GET' == strtoupper($method)) {
            $requestOptions = $this->requestOptionsFactory->createBodyLess($requestOptions, $defaults);
        } else {
            $requestOptions = $this->requestOptionsFactory->create($requestOptions, $defaults);
        }

        return $this->request(
            $method,
            $path,
            $requestOptions,
            $this->config->getHosts()->read(),
            $requestOptions->getReadTimeout()
        );
    }

    public function write($method, $path, $data = array(), $requestOptions = array(), $defaults = array())
    {
        if ('DELETE' == strtoupper($method)) {
            $requestOptions = $this->requestOptionsFactory->createBodyLess($requestOptions, $defaults);
            $data = array();
        } else {
            $requestOptions = $this->requestOptionsFactory->create($requestOptions, $defaults);
        }

        return $this->request(
            $method,
            $path,
            $requestOptions,
            $this->config->getHosts()->write(),
            $requestOptions->getWriteTimeout(),
            $data
        );
    }

    public function send($method, $path, $requestOptions = array(), $hosts = null)
    {
        $requestOptions = $this->requestOptionsFactory->create($requestOptions);

        if (null === $hosts) {
            $hosts = $this->config->getHosts()->write();
        } elseif (!is_array($hosts)) {
            $hosts = array($hosts);
        }

        return $this->request(
            $method,
            $path,
            $requestOptions,
            $hosts,
            $requestOptions->getWriteTimeout()
        );
    }

    private function request($method, $path, RequestOptions $requestOptions, $hosts, $timeout, $data = array())
    {
        $uri = $this->http
            ->createUri($path)
            ->withQuery($requestOptions->getBuiltQueryParameters())
            ->withScheme('https');

        $body = array_merge($data, $requestOptions->getBody());

        $retry = 1;
        foreach ($hosts as $host) {
            try {
                $request = $this->http->createRequest(
                    $method,
                    $uri->withHost($host),
                    $requestOptions->getHeaders(),
                    $body
                );

                $responseBody = $this->http->sendRequest(
                    $request,
                    $timeout * $retry,
                    $requestOptions->getConnectTimeout() * $retry
                );

                return $responseBody;
            } catch (RetriableException $e) {
                if (Debug::isEnabled()) {
                    Debug::handle("The host [$host] failed, retrying with another host.");
                }

                $this->config->getHosts()->failed($host);
            } catch (BadRequestException $e) {
                if (Debug::isEnabled()) {
                    Debug::handle('The following request returned a 4xx error: ', $request);
                }

                throw $e;
            } catch (\Exception $e) {
                throw $e;
            }

            $retry++;
        }

        throw new UnreachableException();
    }

    public function setExtraHeader($headerName, $headerValue)
    {
        $this->requestOptionsFactory->setCustomHeader($headerName, $headerValue);

        return $this;
    }
}