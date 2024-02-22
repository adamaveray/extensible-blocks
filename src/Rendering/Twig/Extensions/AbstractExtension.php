<?php
declare(strict_types=1);

namespace Averay\ExtensibleBlocks\Rendering\Twig\Extensions;

use Averay\ExtensibleBlocks\Rendering\BlocksRendererInterface;
use Averay\ExtensibleBlocks\Rendering\Twig\Expressions\AbstractRenderBlockNode;
use Averay\ExtensibleBlocks\Rendering\Twig\Expressions\AbstractSearchAndRenderBlockNode;
use Averay\ExtensibleBlocks\Rendering\Twig\TokenParsers\BlocksThemeTokenParser;
use Twig\Extension\AbstractExtension as TwigAbstractExtension;
use Twig\TwigFunction;

abstract class AbstractExtension extends TwigAbstractExtension
{
  /**
   * @return class-string<BlocksRendererInterface> The unique class name for the block implementation's renderer.
   */
  abstract protected function getRendererClass(): string;

  /**
   * @return string The prefix for the generated tag allowing specifying per-view alternate themes, supporting `{% {prefix}_theme %}` usage.
   */
  abstract protected function getThemeTagPrefix(): string;

  /**
   * @return list<string> A list of block names to generate functions for, which when called will render their corresponding blocks directly with the view they are called with.
   */
  abstract protected function getBlockFunctionNames(): array;

  /**
   * @return list<string> A list of block names to generate functions for, which when called will render their best-matching block after traversing the hierarchy of the view they are called with.
   */
  abstract protected function getSearchBlockFunctionNames(): array;

  /**
   * @return class-string<AbstractRenderBlockNode> The block-rendering expression class name that renders a corresponding block directly.
   */
  abstract protected function getRenderBlockNodeExpressionClass(): string;

  /**
   * @return class-string<AbstractSearchAndRenderBlockNode> The block-rendering expression class name that renders the best-matching block after traversing a view’s hierarchy..
   */
  abstract protected function getSearchAndRenderBlockNodeExpressionClass(): string;

  /**
   * @return array Options to pass to the TwigFunction for generated block functions.
   * @see TwigFunction::__construct
   */
  protected function getCommonBlockFunctionOptions(): array
  {
    return ['is_safe' => ['html']];
  }

  /** @inheritDoc */
  public function getTokenParsers(): array
  {
    return [new BlocksThemeTokenParser($this->getRendererClass(), $this->getThemeTagPrefix())];
  }

  /** @inheritDoc */
  public function getFunctions(): array
  {
    $commonOptions = $this->getCommonBlockFunctionOptions();

    $blockFunctionOptions = $commonOptions + [
      'node_class' => $this->getRenderBlockNodeExpressionClass(),
    ];
    $searchBlockFunctionOptions = $commonOptions + [
      'node_class' => $this->getSearchAndRenderBlockNodeExpressionClass(),
    ];

    return array_merge(
      array_map(
        static fn(string $functionName): TwigFunction => new TwigFunction($functionName, null, $blockFunctionOptions),
        $this->getBlockFunctionNames(),
      ),

      array_map(
        static fn(string $functionName): TwigFunction => new TwigFunction(
          $functionName,
          null,
          $searchBlockFunctionOptions,
        ),
        $this->getSearchBlockFunctionNames(),
      ),
    );
  }
}
