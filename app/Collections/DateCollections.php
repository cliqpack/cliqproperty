<?php

namespace App\Collections;

use DatePeriod;
use Illuminate\Database\Eloquent\Collection;

class DateCollections extends Collection
{
    public function withDefaults()
    {
        $date = $this->sortBy('day')[0]->day->firstOfYear();
        $days = array_map(function($day) {
            return $day->format('Y-m-d');
        }, [...new DatePeriod("R11/{$date->toIso8601ZuluString()}/P1D")]); // e.g. 2021-01-01T00:00:00Z
        $collection = array_fill_keys(array_fill_keys($days, []));
        foreach($this as $item) {
            $collection[$item->day->format('Y-m-d')] = $item;
        }
        return collect($collection);
    }
}
