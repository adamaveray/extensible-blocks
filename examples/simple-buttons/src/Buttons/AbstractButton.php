<?php
declare(strict_types=1);

namespace Acme\ButtonKit\Buttons;

abstract class AbstractButton
{
  public ?string $id = null;
  public ?string $label = null;
  public ?ButtonCollection $collection = null;

  final public static function create(...$args): static
  {
    $instance = new static();
    foreach ($args as $key => $value) {
      $instance->{$key} = $value;
    }
    return $instance;
  }
}
