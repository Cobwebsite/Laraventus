<?php

namespace Aventus\Laraventus\Helpers;

use Aventus\Laraventus\Traits\AventusSerializable;
use BackedEnum;
use JsonSerializable;
use Symfony\Component\HttpFoundation\Response;

/**
 * @template T of BackedEnum<int>
 * @property int $code
 * @property string $message
 * @property string[] $details
 */
class AventusError implements JsonSerializable
{
    use AventusSerializable;

    public int $code;
    /**
     * @param T $code
     * @param string $message
     * @param string[] $details
     * @param ?int $httpCode
     */
    public function __construct(
        BackedEnum|int $code,
        public string $message,
        public array $details = [],
        private ?int $httpCode = null
    ) {
        if (is_int($code)) {
            $this->code = $code;
        } else {
            $this->code = $code->value;
        }
    }

    public function getHttpCode()
    {
        if (isset($this->httpCode)) {
            return $this->httpCode;
        }
        if (array_key_exists($this->code, Response::$statusTexts)) {
            return $this->code;
        }
        return 500;
    }
}
