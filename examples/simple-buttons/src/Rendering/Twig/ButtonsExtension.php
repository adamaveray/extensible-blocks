<?php
declare(strict_types=1);

namespace Acme\ButtonKit\Rendering\Twig;

use Acme\ButtonKit\Rendering\ButtonsRenderer;
use Averay\ExtensibleBlocks\Rendering\Twig\Extensions\AbstractExtension;

final class ButtonsExtension extends AbstractExtension
{
  protected function getRendererClass(): string
  {
    return ButtonsRenderer::class;
  }

  protected function getRenderBlockNodeExpressionClass(): string
  {
    return Expressions\RenderBlockNode::class;
  }

  protected function getSearchAndRenderBlockNodeExpressionClass(): string
  {
    return Expressions\SearchAndRenderBlockNode::class;
  }

  protected function getThemeTagPrefix(): string
  {
    return 'button';
  }

  /** @return list<string> */
  protected function getBlockFunctionNames(): array
  {
    return ['buttons'];
  }

  /** @return list<string> */
  protected function getSearchBlockFunctionNames(): array
  {
    return ['button', 'button_label'];
  }
}
