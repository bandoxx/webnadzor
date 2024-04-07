<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TimeAgoExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return array(
            'timeAgo' => new TwigFilter('timeAgo', [$this, 'timeAgo']),
        );
    }

    public function timeAgo($date): string
    {
        $date = new \DateTime($date);
        $second = 1;
        $minute = 60 * $second;
        $hour = 60 * $minute;
        $day = 24 * $hour;
        $month = 30 * $day;
        $year = 365 * $day;

        $increments = [
            [$second, ['sekunda', 'sekunde', 'sekundi']],
            [$minute, ['minuta', 'minute', 'minuta']],
            [$hour, ['sat', 'sata', 'sati']],
            [$day, ['dan', 'dana', 'dana']],
            [$month, ['mjesec', 'mjeseca', 'mjeseci']],
            [$year, ['godina', 'godine', 'godina']]
        ];

        $diff = time() - $date->format('U');

        $units = ceil($diff / $increments[count($increments) - 1][0]);
        $unit = $this->plural($units, $increments[count($increments) - 1][1]);

        foreach ($increments as $i => $iValue) {
            if(array_key_exists($i-1, $increments) && $increments[$i-1][0] <= $diff && $diff < $iValue[0]){
                $units = ceil($diff/$increments[$i-1][0]);
                $unit = $this->plural($units, $increments[$i-1][1]);
                break;
            }
        }

        return sprintf("%d %s", $units, $unit);
    }

    private function plural($n, array $f)
    {
        return $n % 10 == 1 && $n % 100 != 11 ? $f[0] : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? $f[1] : $f[2]);
    }

}