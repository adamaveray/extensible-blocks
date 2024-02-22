<?php
declare(strict_types=1);

namespace Acme\ButtonKit;

use Acme\ButtonKit\Buttons\AbstractButton;
use Acme\ButtonKit\Buttons\ButtonCollection;
use Averay\ExtensibleBlocks\ViewAccessorInterface;

/**
 * @template-extends ViewAccessorInterface<AbstractButton|ButtonCollection>
 * @internal
 */
final class ViewAccessor implements ViewAccessorInterface
{
  /** @param AbstractButton|ButtonCollection $view */
  public function getCacheKey(mixed $view): string
  {
    return $view::class . ':' . $view->id;
  }

  /** @param AbstractButton|ButtonCollection $view */
  public function getParentView(mixed $view): AbstractButton|ButtonCollection|null
  {
    return $view instanceof AbstractButton ? $view->collection : null;
  }

  /** @param AbstractButton|ButtonCollection $view */
  public function getRootBlockPrefix(mixed $view): ?string
  {
    return null;
  }

  /**
   * @param AbstractButton|ButtonCollection $view
   * @return list<string>
   */
  public function getBlockPrefixes(mixed $view): array
  {
    $suffix = $view instanceof AbstractButton ? '_button' : '';

    $prefixes = [];
    if ($view->id !== null) {
      $prefixes[] = '_' . $view->id;
    }
    for (
      $reflectionClass = new \ReflectionClass($view);
      $reflectionClass !== false;
      $reflectionClass = $reflectionClass->getParentClass()
    ) {
      $blockName =
        $reflectionClass->getParentClass() === false ? '' : self::getBlockNameForClass($reflectionClass->getName());
      if (str_ends_with($blockName, $suffix)) {
        $blockName = substr($blockName, 0, -\strlen($suffix));
      }
      $prefixes[] = $blockName;
    }
    return array_reverse($prefixes);
  }

  /**
   * @param AbstractButton|ButtonCollection $view
   * @return array<string,mixed>
   */
  public function getVars(mixed $view): array
  {
    $key = match (true) {
      $view instanceof AbstractButton => 'button',
      $view instanceof ButtonCollection => 'buttons',
    };
    return array_merge([$key => $view], (array) $view);
  }

  private static function getBlockNameForClass(string $class): string
  {
    $parts = explode('\\', $class);
    $name = end($parts);
    return strtolower(preg_replace('~([a-z])([A-Z])~', '$1_$2', $name));
  }
}
