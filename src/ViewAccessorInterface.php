<?php
declare(strict_types=1);

namespace Averay\ExtensibleBlocks;

/**
 * @template TView
 */
interface ViewAccessorInterface
{
  /**
   * @param TView $view
   */
  public function getCacheKey(mixed $view): string;

  /**
   * @param TView $view
   * @return TView|null The view to inherit themes from.
   */
  public function getParentView(mixed $view): mixed;

  /**
   * @param TView $view
   * @return string|null The prefix (without trailing underscore) of the base view type’s block, which is replaced by type extensions to allow overriding.
   */
  public function getRootBlockPrefix(mixed $view): ?string;

  /**
   * @param TView $view
   * @return list<string> The potential block prefixes for the view, in reverse order of priority (i.e. most generic at index 0, most specific at final index).
   */
  public function getBlockPrefixes(mixed $view): array;

  /**
   * @param TView $view
   * @return array<string,mixed>
   */
  public function getVars(mixed $view): array;
}
