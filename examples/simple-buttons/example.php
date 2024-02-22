<?php
declare(strict_types=1);

use Acme\ButtonKit\Buttons;
use Acme\ButtonKit\Buttons\ButtonCollection;
use Acme\ButtonKit\Rendering;
use Acme\ButtonKit\ViewAccessor;
use Averay\ExtensibleBlocks\Rendering\Twig\TwigRendererEngine;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

require_once __DIR__ . '/vendor/autoload.php';

function loadTwig(): TwigEnvironment
{
  $twig = new TwigEnvironment(new TwigFilesystemLoader([__DIR__]));

  // Configure extensions
  $engine = new TwigRendererEngine(new ViewAccessor(), ['src/theme-views/buttons_theme.twig'], $twig);
  $twig->addExtension(new Rendering\Twig\ButtonsExtension());
  $twig->addRuntimeLoader(
    new FactoryRuntimeLoader([
      Rendering\ButtonsRenderer::class => static function () use ($engine): Rendering\ButtonsRenderer {
        return new Rendering\ButtonsRenderer($engine);
      },
    ]),
  );

  return $twig;
}

echo loadTwig()->render('views/example.twig', [
  'buttons' => new ButtonCollection([
    Buttons\SubmitButton::create(name: 'action', value: 'save', label: 'Save'),
    Buttons\SubmitButton::create(name: 'action', value: 'delete', label: 'Delete'),
    Buttons\LinkButton::create(url: 'https://www.example.com/', label: 'View Details'),
    Buttons\LinkButton::create(id: 'special_cta', url: 'https://www.example.com/', label: 'Open Full'),
  ]),
]);
