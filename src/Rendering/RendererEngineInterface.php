<?php
declare(strict_types=1);

namespace Averay\ExtensibleBlocks\Rendering;

use Averay\ExtensibleBlocks\ViewAccessorInterface;

/**
 * @template TView
 * @template TResource
 * @template TTheme
 */
interface RendererEngineInterface
{
  /**
   * @return ViewAccessorInterface<TView>
   */
  public function getViewAccessor(): ViewAccessorInterface;

  /**
   * @param TView $view
   * @param TTheme|list<TTheme> $themes
   */
  public function setTheme(mixed $view, mixed $themes, bool $useDefaultThemes = true): void;

  /**
   * @param TView $view
   * @return TResource|false The engine-specific resource for the requested block, or false if none found.
   */
  public function getResourceForBlockName(mixed $view, string $blockName): mixed;

  /**
   * @param TView $view
   * @param list<string> $blockNameHierarchy
   * @return TResource|false The engine-specific resource for the requested block, or false if none found.
   */
  public function getResourceForBlockNameHierarchy(mixed $view, array $blockNameHierarchy, int $hierarchyLevel): mixed;

  /**
   * @param TView $view
   * @param list<string> $blockNameHierarchy
   */
  public function getResourceHierarchyLevel(mixed $view, array $blockNameHierarchy, int $hierarchyLevel): int|false;

  /**
   * @param TView $view
   * @param TResource $resource The engine-specific resource for the requested block.
   * @param array<string,mixed> $variables
   */
  public function renderBlock(mixed $view, mixed $resource, string $blockName, array $variables = []): string;
}
