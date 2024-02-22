<?php
declare(strict_types=1);

namespace Averay\ExtensibleBlocks\Rendering;

use Averay\ExtensibleBlocks\Exceptions\LogicException;
use Averay\ExtensibleBlocks\ViewAccessorInterface;

/**
 * @template TView
 * @template TResource
 * @template TTheme
 * @template-implements BlocksRendererInterface<TView, TTheme>
 */
class BlocksRenderer implements BlocksRendererInterface
{
  /** @var ViewAccessorInterface<TView> */
  private readonly ViewAccessorInterface $viewAccessor;
  /** @var array<string, list<string>> */
  private array $blockNameHierarchyMap = [];
  /** @var array<string, int> */
  private array $hierarchyLevelMap = [];
  /** @var array<string, list<array<string, mixed>>> */
  private array $variableStack = [];

  /**
   * @param RendererEngineInterface<TView, TResource, TTheme> $renderEngine
   */
  public function __construct(private readonly RendererEngineInterface $renderEngine)
  {
    $this->viewAccessor = $this->renderEngine->getViewAccessor();
  }

  /**
   * @param TView $view
   * @param TTheme|list<TTheme> $themes
   */
  public function setTheme(mixed $view, mixed $themes, bool $useDefaultThemes = true): void
  {
    $this->renderEngine->setTheme($view, $themes, $useDefaultThemes);
  }

  /**
   * @param TView $view
   * @param array<string, mixed> $variables
   */
  public function renderBlock(mixed $view, string $blockName, array $variables = []): string
  {
    $resource = $this->renderEngine->getResourceForBlockName($view, $blockName);
    if ($resource === false) {
      throw new LogicException(sprintf('No block "%s" found while rendering the view.', $blockName));
    }

    [
      'variables' => $variables,
      'cleanup' => $cleanupVariables,
    ] = $this->processScopeVariables($view, $variables);

    try {
      return $this->renderEngine->renderBlock($view, $resource, $blockName, $variables);
    } finally {
      $cleanupVariables();
    }
  }

  /**
   * @param TView $view
   * @param array<string, mixed> $variables
   */
  public function searchAndRenderBlock(mixed $view, string $blockNameSuffix, array $variables = []): string
  {
    [
      'resource' => $resource,
      'blockName' => $blockName,
      'cleanup' => $cleanupHierarchy,
    ] = $this->processBlockHierarchy($view, $blockNameSuffix);

    [
      'variables' => $variables,
      'cleanup' => $cleanupVariables,
    ] = $this->processScopeVariables($view, $variables);

    try {
      return $this->renderEngine->renderBlock($view, $resource, $blockName, $variables);
    } finally {
      $cleanupHierarchy();
      $cleanupVariables();
    }
  }

  /**
   * @param TView $view
   * @param array<string, mixed> $variables
   * @return array{
   *   variables: array<string, mixed>,
   *   cleanup: callable():void,
   * }
   */
  private function processScopeVariables(mixed $view, array $variables): array
  {
    $viewCacheKey = $this->viewAccessor->getCacheKey($view);

    // Load scope variables
    if (!isset($this->variableStack[$viewCacheKey])) {
      $varInit = true;
      $scopeVariables = $this->viewAccessor->getVars($view);
      $this->variableStack[$viewCacheKey] = [];
    } else {
      $varInit = false;
      $scopeVariables = end($this->variableStack[$viewCacheKey]);
    }

    // Merge scope variables into provided variables
    $variables = $this->mergeScopeVariables($variables, $scopeVariables);

    // Store variables for other blocks of current view
    $this->variableStack[$viewCacheKey][] = $variables;

    // Prepare clearing stack after rendering
    $cleanup = function () use ($viewCacheKey, $varInit): void {
      array_pop($this->variableStack[$viewCacheKey]);
      if ($varInit) {
        unset($this->variableStack[$viewCacheKey]);
      }
    };

    return [
      'variables' => $variables,
      'cleanup' => $cleanup,
    ];
  }

  /**
   * @param TView $view
   * @return array{
   *   resource: TResource,
   *   blockName: string,
   *   cleanup: callable():void,
   * }
   */
  private function processBlockHierarchy(mixed $view, string $blockName): array
  {
    $viewCacheKey = $this->viewAccessor->getCacheKey($view);

    $viewAndSuffixCacheKey = $viewCacheKey . ':' . $blockName;
    /** @var list<string> $blockNameHierarchy */
    if (!isset($this->blockNameHierarchyMap[$viewAndSuffixCacheKey])) {
      // Initial call
      $hierarchyInit = true;
      $blockNameHierarchy = $this->buildBlockNameHierarchy($view, $blockName);
      $hierarchyLevel = \count($blockNameHierarchy) - 1;
    } else {
      // Recursive call
      $hierarchyInit = false;
      $blockNameHierarchy = $this->blockNameHierarchyMap[$viewAndSuffixCacheKey];
      $hierarchyLevel = $this->hierarchyLevelMap[$viewAndSuffixCacheKey] - 1;
    }

    $resource = $this->renderEngine->getResourceForBlockNameHierarchy($view, $blockNameHierarchy, $hierarchyLevel);
    $hierarchyLevel = $this->renderEngine->getResourceHierarchyLevel($view, $blockNameHierarchy, $hierarchyLevel);
    if ($resource === false || $hierarchyLevel === false) {
      if (\count($blockNameHierarchy) !== \count(array_unique($blockNameHierarchy))) {
        throw new LogicException(
          sprintf(
            'Unable to render the view because the block names array contains duplicates: %s.',
            implode(
              ', ',
              array_map(
                static fn(string $blockName): string => '"' . $blockName . '"',
                array_reverse($blockNameHierarchy),
              ),
            ),
          ),
        );
      }

      throw new LogicException(
        sprintf(
          'Unable to render the view as none of the following blocks exist: %s.',
          implode(
            ', ',
            array_map(
              static fn(string $blockName): string => '"' . $blockName . '"',
              array_reverse($blockNameHierarchy),
            ),
          ),
        ),
      );
    }

    // Store hierarchy details for recursive calls
    $this->blockNameHierarchyMap[$viewAndSuffixCacheKey] = $blockNameHierarchy;
    $this->hierarchyLevelMap[$viewAndSuffixCacheKey] = $hierarchyLevel;

    // Prepare clearing hierarchy after rendering
    $cleanup = function () use ($hierarchyInit, $viewAndSuffixCacheKey): void {
      if ($hierarchyInit) {
        unset($this->blockNameHierarchyMap[$viewAndSuffixCacheKey], $this->hierarchyLevelMap[$viewAndSuffixCacheKey]);
      }
    };

    return [
      'resource' => $resource,
      'blockName' => $blockNameHierarchy[$hierarchyLevel],
      'cleanup' => $cleanup,
    ];
  }

  /**
   * @param TView $view
   * @return list<string>
   */
  protected function buildBlockNameHierarchy(mixed $view, string $blockName): array
  {
    $blockNameSuffix = $blockName;

    $rootBlockPrefix = $this->viewAccessor->getRootBlockPrefix($view) ?? '';
    if ($rootBlockPrefix !== '') {
      $blockNameSuffix = \substr($blockNameSuffix, \strlen($rootBlockPrefix . '_'));
    }

    $hierarchy = [];
    foreach ($this->viewAccessor->getBlockPrefixes($view) as $blockNamePrefix) {
      $hierarchy[] = ($blockNamePrefix === '' ? '' : $blockNamePrefix . '_') . $blockNameSuffix;
    }
    return $hierarchy;
  }

  /**
   * @param array<string, mixed> $variables
   * @param array<string, mixed> $scopeVariables
   * @return array<string, mixed>
   */
  protected function mergeScopeVariables(array $variables, array $scopeVariables): array
  {
    return array_replace($scopeVariables, $variables);
  }
}
