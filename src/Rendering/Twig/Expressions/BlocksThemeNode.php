<?php
declare(strict_types=1);

namespace Averay\ExtensibleBlocks\Rendering\Twig\Expressions;

use Averay\ExtensibleBlocks\Rendering\BlocksRendererInterface;
use Twig\Compiler;
use Twig\Node\Node;

/** @internal */
final class BlocksThemeNode extends Node
{
  /** @param class-string<BlocksRendererInterface> $rendererClass */
  public function __construct(
    private readonly string $rendererClass,
    Node $view,
    Node $resources,
    int $lineno,
    ?string $tag = null,
    bool $only = false,
  ) {
    parent::__construct(['view' => $view, 'resources' => $resources], ['only' => $only], $lineno, $tag);
  }

  public function compile(Compiler $compiler): void
  {
    $compiler
      ->addDebugInfo($this)
      ->write('$this->env->getRuntime(')
      ->string($this->rendererClass)
      ->raw(')->setTheme(')
      ->subcompile($this->getNode('view'))
      ->raw(', ')
      ->subcompile($this->getNode('resources'))
      ->raw(', ')
      ->raw($this->getAttribute('only') === false ? 'true' : 'false')
      ->raw(");\n");
  }
}
