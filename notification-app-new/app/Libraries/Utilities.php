<?php

namespace App\Libraries;

use DateInterval;

class Utilities
{
    public function getDateTime($when = 'now')
    {
        $datetime = new \DateTime($when);

        $interval = 'PT15M';

        $datetime->sub(new DateInterval($interval));
    }


}
