<?php
declare(strict_types=1);

namespace Averay\ExtensibleBlocks\Rendering\Twig\Expressions;

use Averay\ExtensibleBlocks\Rendering\BlocksRendererInterface;
use Twig\Compiler;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Node;

abstract class AbstractRenderBlockNode extends FunctionExpression
{
  /** @return class-string<BlocksRendererInterface> */
  abstract protected function getBlockRendererClass(): string;

  protected function getRenderFunction(): string
  {
    return 'renderBlock';
  }

  public function compile(Compiler $compiler): void
  {
    $compiler->addDebugInfo($this);
    $compiler->raw(
      '$this->env->getRuntime(\'' . $this->getBlockRendererClass() . '\')->' . $this->getRenderFunction() . '(',
    );

    /** @var list<Node> $arguments */
    $arguments = iterator_to_array($this->getNode('arguments'));
    if (isset($arguments[0])) {
      $value = array_shift($arguments);
      $compiler->subcompile($value);

      $blockName = (string) $this->getAttribute('name');
      $compiler->raw(', \'' . $blockName . '\'');

      $this->compileSubsequentArguments($compiler, $arguments);
    }

    $compiler->raw(')');
  }

  /**
   * @param list<Node> $arguments
   */
  protected function compileSubsequentArguments(Compiler $compiler, array $arguments): void
  {
    foreach ($arguments as $argument) {
      $compiler->raw(', ');
      $compiler->subcompile($argument);
    }
  }
}
