<?php

namespace Imamuseum\Harvester\Traits;

trait TimerTrait {

  public function timer($begin, $end)
  {
    $total = round(microtime(true) - $begin, 1, PHP_ROUND_HALF_UP);
    $unit = "seconds";
    if ($total > 60) {
        $total = round($total/60, 1, PHP_ROUND_HALF_UP);
        $unit = "minutes";
    }
    return "$total $unit";
  }
}
