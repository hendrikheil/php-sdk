<?php

namespace Dapr;

use Dapr\exceptions\DaprException;

/**
 * Helper functions for accessing the dapr api.
 * @package Dapr
 */
abstract class DaprClient
{

    private static $trace;
    private static $added_trace = false;

    /**
     * Composes an URI for accessing the API.
     *
     * @param string $path The path to access.
     * @param array|null $params Query params for the API call.
     *
     * @return string The API URI
     */
    public static function get_api(string $path, ?array $params = null): string
    {
        $params = $params ? http_build_query($params) : '';
        $params = $params ? '?'.$params : '';

        return DaprClient::get_api_base().$path.$params;
    }

    /**
     * Get the API base uri
     *
     * @return string The base uri
     */
    public static function get_api_base(): string
    {
        $port = getenv('DAPR_HTTP_PORT') ?: 3500;

        return "http://localhost:$port/v1.0";
    }

    /**
     * Get a uri.
     *
     * @param string $url The URL to get.
     *
     * @return DaprResponse The parsed response.
     * @throws DaprException
     */
    public static function get(string $url): DaprResponse
    {
        $curl = curl_init($url);
        curl_setopt_array(
            $curl,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => self::get_headers(),
                CURLINFO_HEADER_OUT    => true,
            ]
        );
        $result       = curl_exec($curl);
        $return       = new DaprResponse();
        $return->data = Deserializer::maybe_deserialize(json_decode($result, true));
        $return->code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        self::detect_trace_from_response($curl);

        if($return->data instanceof \Exception) {
            throw $return->data;
        }

        return $return;
    }

    private static function get_headers(): array
    {
        return array_merge(["Accept: application/json"], self::detect_trace());
    }

    private static function detect_trace()
    {
        if (isset(self::$trace)) {
            return self::$trace;
        }

        $existing_headers = getallheaders();
        self::$trace      = isset($existing_headers['Traceparent']) ? ['Traceparent: '.$existing_headers['Traceparent']] : [];

        if ( ! self::$added_trace && isset($existing_headers['Traceparent'])) {
            header('Traceparent: '.$existing_headers['Traceparent']);
            self::$added_trace = true;
        }

        return self::$trace;
    }

    /**
     * @param \CurlHandle|false $curl
     */
    private static function detect_trace_from_response(mixed $curl): void
    {
        if ($curl === false) {
            return;
        }

        $header = curl_getinfo($curl, CURLINFO_HEADER_OUT);
        $header = array_filter(
            explode("\r\n", $header),
            function ($ii) {
                return strpos($ii, 'Traceparent:') === 0;
            }
        );
        if ( ! empty($header)) {
            self::$trace = $header;
            if ( ! self::$added_trace) {
                header('Traceparent: '.$header[0]);
                self::$added_trace = true;
            }
        }
    }

    /**
     * Post a uri.
     *
     * @param string $url The url to post to.
     * @param array $data The data to post as a JSON document.
     *
     * @return DaprResponse The parsed response.
     * @throws DaprException
     */
    public static function post(string $url, mixed $data): DaprResponse
    {
        $curl = curl_init($url);
        curl_setopt_array(
            $curl,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => self::as_json(self::get_headers()),
                CURLOPT_POSTFIELDS     => json_encode($data),
                CURLINFO_HEADER_OUT    => true,
            ]
        );
        $response       = new DaprResponse();
        $response->data = curl_exec($curl);
        $response->data = Deserializer::maybe_deserialize(json_decode($response->data, true));
        $response->code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        self::detect_trace_from_response($curl);

        if($response->data instanceof \Exception) {
            throw $response->data;
        }

        return $response;
    }

    private static function as_json(array $headers): array
    {
        return array_merge($headers, ["Content-type: application/json"]);
    }

    /**
     * Delete a uri
     *
     * @param string $url The url to delete
     *
     * @return DaprResponse The response
     * @throws DaprException
     */
    public static function delete(string $url): DaprResponse
    {
        $curl = curl_init($url);
        curl_setopt_array(
            $curl,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'DELETE',
                CURLOPT_HTTPHEADER     => self::as_json(self::get_headers()),
                CURLINFO_HEADER_OUT    => true,
            ]
        );
        $response       = new DaprResponse();
        $response->data = Deserializer::maybe_deserialize(json_decode(curl_exec($curl), true));
        $response->code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        self::detect_trace_from_response($curl);

        if($response->data instanceof \Exception) {
            throw $response->data;
        }

        return $response;
    }
}