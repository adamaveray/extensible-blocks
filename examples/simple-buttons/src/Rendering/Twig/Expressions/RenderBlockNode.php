<?php
declare(strict_types=1);

namespace Acme\ButtonKit\Rendering\Twig;

namespace Acme\ButtonKit\Rendering\Twig\Expressions;

use Acme\ButtonKit\Rendering\ButtonsRenderer;
use Averay\ExtensibleBlocks\Rendering\Twig\Expressions\AbstractRenderBlockNode;

final class RenderBlockNode extends AbstractRenderBlockNode
{
  protected function getBlockRendererClass(): string
  {
    return ButtonsRenderer::class;
  }
}
