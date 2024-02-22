<?php
declare(strict_types=1);

namespace Acme\ButtonKit\Buttons;

class ButtonCollection
{
  public ?string $id = null;

  /** @param list<AbstractButton> $buttons */
  public function __construct(public array $buttons)
  {
    foreach ($this->buttons as $button) {
      $button->collection = $this;
    }
  }
}
