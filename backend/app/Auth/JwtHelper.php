<?php

namespace App\Auth;

use LogicException;

class JwtHelper
{
    public function issue(array $claims): string
    {
        throw new LogicException('JWT issuing is not implemented.');
    }

    public function decode(string $token): array
    {
        throw new LogicException('JWT decoding is not implemented.');
    }
}