<?php

namespace ProcessWire;

use Sse\Iterator;

class SseDemo extends WireData implements Module, ConfigurableModule
{
  public function __construct()
  {
    parent::__construct();
    /** @var Sse $sse */
    $sse = wire()->modules->get('Sse');
    $sse->addStream('ssedemo-server-time', $this, 'serverTime');
    $sse->addStream('ssedemo-create-pages', $this, 'createPages');
    $sse->addStream('ssedemo-trash-pages', $this, 'trashPages');
  }

  public static function getModuleInfo()
  {
    return array(
      'title' => __('SSE Demo', __FILE__),
      'summary' => __('Demo module to show how to use the SSE module.', __FILE__),
      'version' => '0.0.1',
      'singular' => true,
      'autoload' => true,
      'icon' => 'code',
      'requires' => [
        'Sse',
      ],
    );
  }

  /** --- SSE streams --- */

  public function serverTime(Sse $sse)
  {
    $sse->send(date('Y-m-d H:i:s'));
  }

  public function createPages(Sse $sse, Iterator $iterator)
  {
    // first run
    if (!$iterator->count) $iterator->max = (int)$_GET['count'];

    // abort if max is reached
    if ($iterator->num > $iterator->max) {
      $sse->send("SSE_STOP");
      return;
    }

    // create page
    $p = wire()->pages->new([
      'parent' => 1,
      'template' => 'basic-page',
      'name' => 'tmp-' . uniqid(),
    ]);

    // send progress
    $sse->send($iterator->num . '/' . $iterator->max . ': ' . $p->name);

    // no sleep to instantly run next iteration
    $sse->sleep = 0;
  }

  /** --- regular methods --- */

  /**
   * Config inputfields
   * @param InputfieldWrapper $inputfields
   */
  public function getModuleConfigInputfields($inputfields)
  {
    $inputfields->add([
      'type' => 'markup',
      'label' => 'Show current server time',
      'value' => wire()->files->render(__DIR__ . '/demo/server-time.php'),
      'icon' => 'clock-o',
      'notes' => 'This will show the current server time every second.',
    ]);
    $inputfields->add([
      'type' => 'markup',
      'label' => 'Create Pages',
      'value' => wire()->files->render(__DIR__ . '/demo/create.php'),
      'icon' => 'plus',
      'notes' => 'This will create pages using template "basic-page" and set a custom name "tmp-xxx"',
    ]);
    $count = wire()->pages->count([
      'parent' => 1,
      'name^=' => 'tmp-',
    ]);
    $inputfields->add([
      'type' => 'markup',
      'label' => 'Trash Created Pages',
      'value' => wire()->files->render(__DIR__ . '/demo/trash-pages.php'),
      'icon' => 'trash-o',
      'notes' => "This will trash all $count pages with name tmp-...",
    ]);
    $inputfields->add([
      'type' => 'markup',
      'label' => 'Empty Trash',
      'value' => 'tbd',
      'icon' => 'trash',
    ]);
    return $inputfields;
  }
}
