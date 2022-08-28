<?php

namespace App\Models;

use Illuminate\Support\Str;

class Request
{
    private string $url;

    private string $method;

    private array $headers = [];

    private array $data = [];

    private bool $jsonData = false;

    private bool $multipartFormData = false;

    private function __construct($url, $method)
    {
        $this->url = $url;
        $this->method = strtoupper($method);
    }

    public static function create(array $data): self
    {
        $request = new self($data['url'], $data['method']);

        if (!empty($data['headers'])) {
            $request->headers = collect($data['headers'])
                ->mapWithKeys(function ($header) {
                    [$key, $value] = explode(':', $header, 2);

                    return [trim($key) => self::convertDataType(trim($value))];
                })
                ->all();
        }

        if (!empty($data['data'])) {
            if (count($data['data']) === 1 && Str::startsWith($data['data'][0], '{')) {
                $request->data = $data['data'];
                $request->jsonData = true;
            } else {
                parse_str(implode('&', $data['data']), $request->data);
                array_walk_recursive($request->data, function (&$value) {
                    if (is_string($value)) {
                        $value = self::convertDataType($value);
                    }
                });
            }
        }

        if (!empty($data['fields'])) {
            $request->data = $data['fields'];
            $request->multipartFormData = true;
        }

        return $request;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function isJsonData(): bool
    {
        return $this->jsonData;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function isMultipartFormData(): bool
    {
        return $this->multipartFormData;
    }

    private static function convertDataType(string $value)
    {
        return preg_match('/^[1-9]\d*$/', $value) ? intval($value) : $value;
    }
}
