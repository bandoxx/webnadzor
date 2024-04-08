<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Long2IpExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return array(
            'long2ip' => new TwigFilter('long2ip', [$this, 'long2ip']),
        );
    }

    public function long2ip($intIp): string
    {
        return long2ip($intIp);
    }
}