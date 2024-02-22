<?php
declare(strict_types=1);

namespace Averay\ExtensibleBlocks\Rendering\Twig;

use Averay\ExtensibleBlocks\Exceptions\LogicException;
use Averay\ExtensibleBlocks\Rendering\AbstractRendererEngine;
use Averay\ExtensibleBlocks\ViewAccessorInterface;
use Twig\Environment;
use Twig\Template;
use Twig\TemplateWrapper;

/**
 * @template TView
 * @template-extends AbstractRendererEngine<TView, string, Template|TemplateWrapper|string>
 */
class TwigRendererEngine extends AbstractRendererEngine
{
  private ?Template $template = null;

  /**
   * @param ViewAccessorInterface<TView> $viewAccessor
   * @param list<Template|TemplateWrapper|string> $defaultThemes
   */
  public function __construct(
    ViewAccessorInterface $viewAccessor,
    array $defaultThemes,
    private readonly Environment $environment,
  ) {
    parent::__construct($viewAccessor, $defaultThemes);
  }

  /** @inheritDoc */
  public function renderBlock(mixed $view, mixed $resource, string $blockName, array $variables = []): string
  {
    $template =
      $this->template ?? throw new LogicException('The renderer engine default template has not been initialised.');

    $cacheKey = $this->viewAccessor->getCacheKey($view);
    $context = $this->environment->mergeGlobals($variables);

    ob_start();
    $template->displayBlock($blockName, $context, $this->resources[$cacheKey]);
    return ob_get_clean();
  }

  /**
   * @param TView $view
   */
  protected function loadResourceForBlockName(string $cacheKey, mixed $view, string $blockName): bool
  {
    if (isset($this->resources[$cacheKey])) {
      $this->resources[$cacheKey][$blockName] = false;
      return false;
    }

    // Load view-specific resources
    for ($i = \count($this->themes[$cacheKey] ?? []) - 1; $i >= 0; --$i) {
      $this->loadResourcesFromTheme($cacheKey, $this->themes[$cacheKey][$i]);
    }

    $viewAccessor = $this->viewAccessor;
    $parent = $viewAccessor->getParentView($view);
    if ($parent === null) {
      // Fall back to default themes
      if ($this->useDefaultThemes[$cacheKey] ?? true) {
        for ($i = \count($this->defaultThemes) - 1; $i >= 0; --$i) {
          $this->loadResourcesFromTheme($cacheKey, $this->defaultThemes[$i]);
        }
      }
    } else {
      // Load parent views
      $parentCacheKey = $viewAccessor->getCacheKey($parent);
      if (!isset($this->resources[$parentCacheKey])) {
        $this->loadResourceForBlockName($parentCacheKey, $parent, $blockName);
      }

      // Copy unset resources from parent to this view
      foreach ($this->resources[$parentCacheKey] as $nestedBlockName => $resource) {
        $this->resources[$cacheKey][$nestedBlockName] ??= $resource;
      }
    }

    $this->resources[$cacheKey][$blockName] ??= false;
    return $this->resources[$cacheKey][$blockName] !== false;
  }

  /**
   * @param-out Template $theme
   */
  protected function loadResourcesFromTheme(string $cacheKey, Template|TemplateWrapper|string &$theme): void
  {
    if (!($theme instanceof Template)) {
      $theme = $this->environment->load($theme)->unwrap();
    }
    $this->template ??= $theme;

    $context = $this->environment->mergeGlobals([]);
    for (
      $currentTheme = $theme;
      $currentTheme instanceof Template;
      $currentTheme = $currentTheme->getParent($context)
    ) {
      /** @var string $resource */
      foreach ($currentTheme->getBlocks() as $block => $resource) {
        $this->resources[$cacheKey][$block] ??= $resource;
      }
    }
  }
}
