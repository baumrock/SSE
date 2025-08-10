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
    $sse->addStream('ssedemo-empty-trash', $this, 'emptyTrash');
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
    $user = wire()->user;
    if (!$user->isSuperuser()) die('no access');
    $sse->send("User #$user @ " . date('Y-m-d H:i:s'));
  }

  public function createPages(Sse $sse, Iterator $iterator)
  {
    // first run
    if ($iterator->num === 1) $iterator->max = (int)$_GET['count'];

    // abort if max is reached
    if ($iterator->num > $iterator->max) return $sse->stop();

    // create page
    $p = wire()->pages->new([
      'parent' => 1,
      'template' => 'basic-page',
      'name' => 'sse-tmp-' . uniqid(),
    ]);

    // send progress
    $sse->send($iterator->num . '/' . $iterator->max . ': created ' . $p->name);

    // no sleep to instantly run next iteration
    $sse->sleep = 0;
  }

  public function trashPages(Sse $sse, Iterator $iterator)
  {
    $selector = [
      'parent' => 1,
      'name^=' => 'sse-tmp-',
      'include' => 'all',
    ];

    // first run
    if ($iterator->num === 1) {
      $iterator->max = wire()->pages->count($selector);
    }

    // trash one page at a time
    $p = wire()->pages->get($selector);
    if ($p->id) $p->trash();
    else {
      $sse->send('No more pages to trash');
      return $sse->stop();
    }

    // send data to client
    $sse->send(
      $iterator->num . '/' . $iterator->max . ': trashed ' . $p->name,
      $iterator
    );

    // no sleep to instantly run next iteration
    $sse->sleep = 0;
  }


  public function emptyTrash(Sse $sse, Iterator $iterator)
  {
    $selector = [
      'parent' => wire()->config->trashPageID,
      'include' => 'all',
    ];

    // first run
    if ($iterator->num === 1) {
      $iterator->max = wire()->pages->count($selector);
    }

    // trash one page at a time
    $p = wire()->pages->get($selector);
    if ($p->id) $p->delete(true);
    else {
      $sse->send('No more pages to delete');
      return $sse->stop();
    }

    // send progress
    $sse->send(
      $iterator->num . '/' . $iterator->max . ': deleted ' . $p->name,
      $iterator
    );

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
      'value' => wire()->files->render(__DIR__ . '/demo/create-pages.php'),
      'icon' => 'plus',
      'notes' => 'This will create pages using template "basic-page" and set a custom name "sse-tmp-xxx"',
    ]);

    $toTrash = wire()->pages->count([
      'parent' => 1,
      'name^=' => 'sse-tmp-',
    ]);
    $inputfields->add([
      'type' => 'markup',
      'label' => 'Trash Created Pages',
      'value' => wire()->files->render(__DIR__ . '/demo/trash-pages.php'),
      'icon' => 'trash-o',
      'notes' => "This will trash all $toTrash pages with name sse-tmp-...",
    ]);

    $inTrash = wire()->pages->count([
      'parent' => wire()->config->trashPageID,
      'include' => 'all',
    ]);
    $inputfields->add([
      'type' => 'markup',
      'label' => 'Empty Trash',
      'value' => wire()->files->render(__DIR__ . '/demo/empty-trash.php'),
      'icon' => 'trash',
      'notes' => "This will delete all $inTrash pages in trash",
    ]);

    return $inputfields;
  }
}
