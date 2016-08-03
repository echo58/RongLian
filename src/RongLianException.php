<?php
namespace Huying\RongLian;

use Exception;

class RongLianException extends Exception
{
    protected $result;

    public function __construct($result)
    {
        $this->result = $result;
        $statusMsg = isset($result['statusMsg']) ? $result['statusMsg'] : '容联接口错误';
        $statusCode = $result['statusCode'];
        parent::__construct($statusMsg, $statusCode);
    }
}