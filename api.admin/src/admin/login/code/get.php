<?php

use Logic\Admin\BaseController;
use \Logic\Captcha\Captcha;

return new class extends BaseController {
    public function run(): array
    {
        return (new Captcha( $this->ci ))->getImageCode(4);
    }
};
