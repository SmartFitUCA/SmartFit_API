<?php

class Helpers
{
    static public function validJson(string $json, array $keys): bool
    {
        if (Helpers::isJson($json)) {
            if (!Helpers::expectedArrayKeys(json_decode($json, true), $keys)) {
                return false;
            }
            return true;
        }

        return false;
    }

    static public function isJson(string $json): bool
    {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }

    static public function expectedArrayKeys(array $json, array $keys): bool
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $json)) return false;
        }

        return true;
    }
}
