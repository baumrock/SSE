<?php

namespace Sse;

use ProcessWire\Wire;

class Iterator extends Wire
{
  public int $index = 0;
  public int $num = 1;
  public int $max = 0;

  public function increment(): void
  {
    $this->index++;
    $this->num++;
  }
}
