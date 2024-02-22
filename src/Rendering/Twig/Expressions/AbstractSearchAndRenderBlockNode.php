<?php
declare(strict_types=1);

namespace Averay\ExtensibleBlocks\Rendering\Twig\Expressions;

abstract class AbstractSearchAndRenderBlockNode extends AbstractRenderBlockNode
{
  protected function getRenderFunction(): string
  {
    return 'searchAndRenderBlock';
  }
}
