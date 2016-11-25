<?php

namespace Pdx\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PdxUserBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSUserBundle';
    }

}
