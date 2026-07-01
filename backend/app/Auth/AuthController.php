<?php

namespace App\Auth;

use App\Controllers\BaseController;

class AuthController extends BaseController
{
    public function __construct(private ?AuthService $authService = null)
    {
        parent::__construct();
    }
}