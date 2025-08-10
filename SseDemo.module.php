<?php

namespace ProcessWire;

class SseDemo extends WireData implements Module, ConfigurableModule
{
  public function __construct()
  {
    parent::__construct();
    /** @var Sse $sse */
    $sse = wire()->modules->get('Sse');
    $sse->addStream('ssedemo-server-time', $this, 'serverTime');
    $sse->addStream('ssedemo-create-pages', $this, 'createPages');
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
    $inputfields->add([
      'type' => 'markup',
      'label' => 'Trash Created Pages',
      'value' => 'tbd',
      'icon' => 'trash-o',
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
