<?php

namespace studypeers\CanvasApi\Clients;

class Base implements CanvasApiClientInterface
{
    public function makeCallToRawUrl($url, $type = 'get')
    {
        return [
            $url,
            $type,
            [],
            true
        ];
    }
}
