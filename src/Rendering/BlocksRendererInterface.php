<?php
declare(strict_types=1);

namespace Averay\ExtensibleBlocks\Rendering;

/**
 * @template TView
 * @template TTheme
 */
interface BlocksRendererInterface
{
  /**
   * @param TView $view
   * @param TTheme|list<TTheme> $themes
   */
  public function setTheme(mixed $view, mixed $themes, bool $useDefaultThemes = true): void;

  /**
   * @param TView $view
   * @param array<string,mixed> $variables
   */
  public function renderBlock(mixed $view, string $blockName, array $variables = []): string;

  /**
   * @param TView $view
   * @param array<string,mixed> $variables
   */
  public function searchAndRenderBlock(mixed $view, string $blockNameSuffix, array $variables = []): string;
}
