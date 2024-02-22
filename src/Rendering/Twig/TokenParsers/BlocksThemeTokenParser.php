<?php
declare(strict_types=1);

namespace Averay\ExtensibleBlocks\Rendering\Twig\TokenParsers;

use Averay\ExtensibleBlocks\Rendering\BlocksRendererInterface;
use Averay\ExtensibleBlocks\Rendering\Twig\Expressions\BlocksThemeNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * @psalm-suppress PropertyNotSetInConstructor -- Set during parsing.
 */
final class BlocksThemeTokenParser extends AbstractTokenParser
{
  /**
   * @param class-string<BlocksRendererInterface> $rendererClass
   */
  public function __construct(private readonly string $rendererClass, private readonly string $tagPrefix)
  {
  }

  public function parse(Token $token): Node
  {
    $lineno = $token->getLine();
    $stream = $this->parser->getStream();

    $object = $this->parser->getExpressionParser()->parseExpression();
    \assert($object instanceof Node);

    $only = false;
    if ($this->parser->getStream()->test(Token::NAME_TYPE, 'with')) {
      $this->parser->getStream()->next();
      $resources = $this->parser->getExpressionParser()->parseExpression();
      \assert($resources instanceof Node);

      if ($this->parser->getStream()->nextIf(Token::NAME_TYPE, 'only')) {
        $only = true;
      }
    } else {
      $resources = new ArrayExpression([], $stream->getCurrent()->getLine());
      do {
        $resource = $this->parser->getExpressionParser()->parseExpression();
        \assert($resource instanceof AbstractExpression);
        $resources->addElement($resource);
      } while (!$stream->test(Token::BLOCK_END_TYPE));
    }

    $stream->expect(Token::BLOCK_END_TYPE);

    return new BlocksThemeNode($this->rendererClass, $object, $resources, $lineno, $this->getTag(), $only);
  }

  public function getTag(): string
  {
    return $this->tagPrefix . '_theme';
  }
}
