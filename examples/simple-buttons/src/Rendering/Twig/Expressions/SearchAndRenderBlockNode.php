<?php
declare(strict_types=1);

namespace Acme\ButtonKit\Rendering\Twig\Expressions;

use Acme\ButtonKit\Rendering\ButtonsRenderer;
use Averay\ExtensibleBlocks\Rendering\Twig\Expressions\AbstractSearchAndRenderBlockNode;

final class SearchAndRenderBlockNode extends AbstractSearchAndRenderBlockNode
{
  protected function getBlockRendererClass(): string
  {
    return ButtonsRenderer::class;
  }
}
