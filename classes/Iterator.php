<?php

namespace Sse;

use ProcessWire\Wire;

class Iterator extends Wire
{
  public int $index = 0;
  public int $num = 1;
  public int $max = 0;
  public float $percent = 0;
  public bool $first = true;
  public bool $last = false;

  public function increment(): void
  {
    $this->index++;
    $this->num++;
    $this->first = $this->num === 1;
    $this->last = $this->num === $this->max;

    $this->percent = 0;
    if ($this->max > 0) {
      $this->percent = round($this->num / $this->max * 100, 2);
    }
  }
}
