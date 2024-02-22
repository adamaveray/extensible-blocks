<?php
declare(strict_types=1);

namespace Averay\ExtensibleBlocks\Rendering;

use Averay\ExtensibleBlocks\ViewAccessorInterface;

/**
 * @template TView
 * @template TResource
 * @template TTheme
 * @template-implements RendererEngineInterface<TView, TResource, TTheme>
 */
abstract class AbstractRendererEngine implements RendererEngineInterface
{
  /** @var array<string, list<TTheme>> */
  public array $themes = [];

  /** @var array<string, bool> */
  protected array $useDefaultThemes = [];

  /** @var array<string, array<array-key, TResource|false>> */
  protected array $resources = [];

  /** @var array<string, array<string, int|false>> */
  private array $resourceHierarchyLevels = [];

  /**
   * @param ViewAccessorInterface<TView> $viewAccessor
   * @param list<TTheme> $defaultThemes
   */
  public function __construct(
    protected readonly ViewAccessorInterface $viewAccessor,
    protected array $defaultThemes = [],
  ) {
  }

  /**
   * @return ViewAccessorInterface<TView>
   */
  public function getViewAccessor(): ViewAccessorInterface
  {
    return $this->viewAccessor;
  }

  /**
   * @param TView $view
   * @param TTheme|list<TTheme> $themes
   */
  public function setTheme(mixed $view, mixed $themes, bool $useDefaultThemes = true): void
  {
    $cacheKey = $this->viewAccessor->getCacheKey($view);

    $this->themes[$cacheKey] = \is_array($themes) ? array_values($themes) : [$themes];
    $this->useDefaultThemes[$cacheKey] = $useDefaultThemes;

    unset($this->resources[$cacheKey], $this->resourceHierarchyLevels[$cacheKey]);
  }

  /**
   * @param TView $view
   * @return TResource|false
   */
  final public function getResourceForBlockName(mixed $view, string $blockName): mixed
  {
    $cacheKey = $this->viewAccessor->getCacheKey($view);

    if (!isset($this->resources[$cacheKey][$blockName])) {
      $this->loadResourceForBlockName($cacheKey, $view, $blockName);
    }

    return $this->resources[$cacheKey][$blockName];
  }

  /**
   * @param TView $view
   * @param array<int,string> $blockNameHierarchy
   * @return TResource|false
   */
  final public function getResourceForBlockNameHierarchy(
    mixed $view,
    array $blockNameHierarchy,
    int $hierarchyLevel,
  ): mixed {
    $cacheKey = $this->viewAccessor->getCacheKey($view);
    $blockName = $blockNameHierarchy[$hierarchyLevel];

    if (!isset($this->resources[$cacheKey][$blockName])) {
      $this->loadResourceForBlockNameHierarchy($cacheKey, $view, $blockNameHierarchy, $hierarchyLevel);
    }

    return $this->resources[$cacheKey][$blockName];
  }

  /**
   * @param TView $view
   * @param array<int,string> $blockNameHierarchy
   */
  public function getResourceHierarchyLevel(mixed $view, array $blockNameHierarchy, int $hierarchyLevel): int|false
  {
    $cacheKey = $this->viewAccessor->getCacheKey($view);
    $blockName = $blockNameHierarchy[$hierarchyLevel];

    if (!isset($this->resources[$cacheKey][$blockName])) {
      $this->loadResourceForBlockNameHierarchy($cacheKey, $view, $blockNameHierarchy, $hierarchyLevel);
    }

    $this->resourceHierarchyLevels[$cacheKey][$blockName] ??= $hierarchyLevel;
    return $this->resourceHierarchyLevels[$cacheKey][$blockName];
  }

  /**
   * @param TView $view
   */
  abstract protected function loadResourceForBlockName(string $cacheKey, mixed $view, string $blockName): bool;

  /**
   * @param TView $view
   * @param array<int,string> $blockNameHierarchy
   */
  private function loadResourceForBlockNameHierarchy(
    string $cacheKey,
    mixed $view,
    array $blockNameHierarchy,
    int $hierarchyLevel,
  ): bool {
    $blockName = $blockNameHierarchy[$hierarchyLevel];

    if ($this->loadResourceForBlockName($cacheKey, $view, $blockName)) {
      $this->resourceHierarchyLevels[$cacheKey][$blockName] = $hierarchyLevel;
      return true;
    }

    if ($hierarchyLevel > 0) {
      $parentLevel = $hierarchyLevel - 1;
      $parentBlockName = $blockNameHierarchy[$parentLevel];

      $parentExists = isset($this->resources[$cacheKey][$parentBlockName]);
      if ($parentExists) {
        $this->resourceHierarchyLevels[$cacheKey][$parentBlockName] ??= $parentLevel;
      }

      // Copy resources from parent
      if (
        $parentExists ||
        $this->loadResourceForBlockNameHierarchy($cacheKey, $view, $blockNameHierarchy, $parentLevel)
      ) {
        $this->resources[$cacheKey][$blockName] = $this->resources[$cacheKey][$parentBlockName];
        $this->resourceHierarchyLevels[$cacheKey][$blockName] =
          $this->resourceHierarchyLevels[$cacheKey][$parentBlockName];
        return true;
      }
    }

    $this->resources[$cacheKey][$blockName] = false;
    $this->resourceHierarchyLevels[$cacheKey][$blockName] = false;
    return false;
  }

  public function reset(): void
  {
    $this->themes = [];
    $this->useDefaultThemes = [];
    $this->resources = [];
    $this->resourceHierarchyLevels = [];
  }
}
