<?php

namespace ProcessWire;

class Sse extends WireData implements Module, ConfigurableModule
{
  private array $streams = [];
  public $sleep;

  public function __construct()
  {
    parent::__construct();
    wire()->addHookBefore('Session::init', $this, 'stream', [
      // execute stream very late so that other modules
      // can add their own streams before this one
      'priority' => 9999999999,
    ]);
  }

  public static function getModuleInfo()
  {
    return array(
      'title' => __('SSE', __FILE__),
      'summary' => __('Use Server Sent Events in ProcessWire.', __FILE__),
      'version' => '0.0.1',
      'singular' => true,
      'autoload' => true,
      'icon' => 'refresh',
    );
  }

  public function ready(): void
  {
    if (wire()->page->template == 'admin') {
      $url = wire()->config->urls($this) . 'src/Sse.js';
      $url = wire()->config->versionUrl($url);
      wire()->config->scripts->add($url);
    }
  }

  /** --- regular methods --- */

  public function addStream(string $name, $object, string $method): void
  {
    $this->streams[$name] = [
      'object' => $object,
      'method' => $method,
    ];
  }

  /**
   * Config inputfields
   * @param InputfieldWrapper $inputfields
   */
  public function getModuleConfigInputfields($inputfields)
  {
    return $inputfields;
  }

  public function send(string $message): void
  {
    echo "data: $message\n\n";
    echo str_pad('', 8186) . "\n";
    flush();
  }

  protected function stream(HookEvent $event): void
  {
    // $this->log('stream');

    // is this an sse request?
    // sse requests use ?sse=name-of-stream
    if (!isset($_GET['sse'])) return;

    // get the stream name
    $stream = $_GET['sse'];

    // get the stream object and method from the streams array
    if (!isset($this->streams[$stream])) return;
    $object = $this->streams[$stream]['object'];
    $method = $this->streams[$stream]['method'];

    // disable tracy for the SSE stream
    wire()->config->tracy = ['enabled' => false];

    // we dont want warnings in the stream
    // for debugging you can uncomment this line
    error_reporting(E_ALL & ~E_WARNING);

    header('Cache-Control: no-cache');
    header('Content-Type: text/event-stream');

    // call the method on the object in an endless loop
    while (true) {
      // reset sleep to default 1s
      $this->sleep = 1;

      // execute the callback
      // it can set a custom sleep via $sse->sleep = 0;
      $object->$method($this);

      // flush output buffer
      while (ob_get_level() > 0) @ob_end_flush();

      // stop loop when connection is aborted
      if (connection_aborted()) break;

      // sleep for the amount of seconds set by the callback
      // or for the default 1s
      sleep($this->sleep);
    }
  }
}
