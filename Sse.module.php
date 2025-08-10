<?php

namespace ProcessWire;

use Sse\Iterator;

class Sse extends WireData implements Module, ConfigurableModule
{
  const SSE_STOP = 'SSE_STOP';

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

  public function init(): void
  {
    wire()->addHook('/sse-user-token', $this, 'getUserToken');
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

  public function getUserToken(HookEvent $event): string
  {
    $key = (new WireRandom())->alphanumeric(0, [
      'minLength' => 30,
      'maxLength' => 50,
    ]);
    wire()->cache->save(
      "sse-user-token-$key",
      wire()->user->id,
      // token is valid for 30 seconds
      30
    );
    return $key;
  }

  public function send(string $message): void
  {
    echo "data: $message\n\n";
    echo str_pad('', 8186) . "\n";
    flush();
  }

  public function stop(): void
  {
    $this->send(self::SSE_STOP);
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

    // set user from token
    $userToken = "sse-user-token-" . $_GET['user'];
    $userId = wire()->cache->get($userToken);
    if (!$userId) return;
    wire()->user = wire()->users->get($userId);
    if (!wire()->user->id) return;

    // delete user token to make sure it is not used again
    wire()->cache->delete($userToken);

    // disable tracy for the SSE stream
    wire()->config->tracy = ['enabled' => false];

    // we dont want warnings in the stream
    // for debugging you can uncomment this line
    error_reporting(E_ALL & ~E_WARNING);

    header('Cache-Control: no-cache');
    header('Content-Type: text/event-stream');

    // init iterator
    require_once __DIR__ . '/classes/Iterator.php';
    $iterator = new Iterator();

    // call the method on the object in an endless loop
    while (true) {
      // reset sleep to default 1s
      $this->sleep = 1;

      // execute the callback
      // it can set a custom sleep via $sse->sleep = 0;
      $object->$method($this, $iterator);

      // flush output buffer
      while (ob_get_level() > 0) @ob_end_flush();

      // stop loop when connection is aborted
      if (connection_aborted()) break;

      // tell the iterator that we've done one iteration
      $iterator->increment();

      // sleep for the amount of seconds set by the callback
      // or for the default 1s
      sleep($this->sleep);
    }
  }
}
