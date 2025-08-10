## SSE Module for ProcessWire

Lightweight Server‑Sent Events (SSE) for ProcessWire with a tiny frontend helper, token‑based auth, and convenient helpers for long‑running or iterative tasks.

### What you get
- **Streams**: Register named streams on the server and push messages to the browser.
- **Iteration helpers**: Built‑in `Sse\Iterator` with `num/index/max` for progress reporting.
- **Auth**: Short‑lived user token to identify the `wire()->user` before `Session::init`.
- **Frontend helper**: `ProcessWire.Sse` to start/stop streams and handle messages.

The demo module `SseDemo` shows working examples in the module config screen.

## Usage

1) Install/enable the `Sse` module. Optionally install `SseDemo` to see examples.
2) Register one or more streams in your module or site code.
3) Use the frontend helper to connect and listen to messages.

## Backend

### Register streams
Register streams early (in the module constructor - `init` is too late). Each stream maps a name to a callable on your object.

```php
/** @var \ProcessWire\Sse $sse */
$sse = wire()->modules->get('Sse');
$sse->addStream('my-stream', $this, 'myStreamCallback');
```

### Write a stream callback
You can implement a simple one‑shot message or a multi‑iteration task. The stream runs in a loop and sleeps 1s between iterations unless you change `$sse->sleep`.

Minimal example (push once per iteration):

```php
use ProcessWire\Sse;

public function myStreamCallback(Sse $sse)
{
  if (!wire()->user->hasRole('whatever')) die('no access');
  $sse->send('Hello from server @ ' . date('H:i:s'));
}
```

Iterative task with progress and fast loops:

```php
use ProcessWire\Sse;
use Sse\Iterator;

public function myBatchTask(Sse $sse, Iterator $iterator)
{
  // first iteration: initialize work based on URL params
  if ($iterator->num === 1) {
    $iterator->max = (int) ($_GET['count'] ?? 0);
  }

  // stop when done
  if ($iterator->num > $iterator->max) return $sse->stop();

  // do work ...
  $sse->send($iterator->num . '/' . $iterator->max . ' processed');

  // run next iteration immediately
  $sse->sleep = 0;
}
```

### API inside callbacks
- `$sse->send(string $message)`: push a line to the client
- `$sse->stop()`: signal the client to close the stream
- `$sse->sleep = int`: seconds to wait before next iteration (default 1)
- `Iterator $iterator` (optional second arg):
  - `$iterator->num`: 1‑based run counter
  - `$iterator->index`: 0‑based run counter
  - `$iterator->max`: set/read your planned max iterations

Access URL params via `$_GET['...']`.

## Frontend

### Load the helper
- In the admin (`template=admin`) the module auto‑loads `Sse.js`.
- On the frontend include it yourself, e.g.:

```html
<script src="/site/modules/Sse/src/Sse.js"></script>
```

### Start/stop a stream

```html
<textarea id="out" rows="5"></textarea>
<button id="start">Start</button>
<button id="stop">Stop</button>

<script>
  // create a stream and define the message handler
  const stream = ProcessWire.Sse.stream('my-stream', (event) => {
    const textarea = document.getElementById('out');
    textarea.value = event.data + "\n" + textarea.value;
  });

  document.getElementById('start').addEventListener('click', (e) => {
    e.preventDefault();
    stream.start(); // optional: pass URL params (see below)
  });

  document.getElementById('stop').addEventListener('click', (e) => {
    e.preventDefault();
    stream.stop();
  });
</script>
```

The client automatically:
- Fetches a short‑lived user token from `/sse-user-token`.
- Opens an `EventSource` with `?sse=<name>&user=<token>&...params`.
- Closes when the server sends the special `SSE_STOP` message or when you call `stream.stop()`.

## Payload

### Via URL params
Pass values when starting the stream; they arrive in PHP under `$_GET`.

```js
// Will request: /?sse=my-batch&user=<token>&count=500
ProcessWire.Sse.stream('my-batch', onMessage).start({ count: 500 });
```

```php
// In your callback
$count = (int) ($_GET['count'] ?? 0);
```

### Via separate POST request
Currently not implemented. Future update.

## Authentication

- Streams are started before `Session::init` to keep them non‑blocking.
- The client requests a short‑lived token from `GET /sse-user-token` (valid ~30s).
- The server looks up the token, sets `wire()->user` accordingly, and deletes the token.
- Enforce authorization inside your callbacks (see `SseDemo::serverTime()` for an example).

## Limitations

- HTTP/1.1: Chrome allows ~6 concurrent connections per domain per client.
  - Example: 6 tabs with one stream each, or 3 tabs when each tab uses 2 streams (e.g. LiveReload + SSE).
- HTTP/2: Typically supports 100+ concurrent connections.

## Examples (from `SseDemo`)

- `ssedemo-server-time`: Emits current server time every second; only for superusers.
- `ssedemo-create-pages`: Creates `basic-page` pages under root; takes `count` via URL param.
- `ssedemo-trash-pages`: Moves previously created `sse-tmp-*` pages to trash.
- `ssedemo-empty-trash`: Permanently deletes pages from trash.

See the rendered UI in the `SseDemo` module config for ready‑to‑use snippets (files in `public/site/modules/Sse/demo/`).

<img src=https://i.imgur.com/bzCdwYI.png class=blur>

## Progress Bar Feature

The SSE module includes a built-in progress bar component that makes it easy to display real-time progress for long-running tasks.

### Backend

All you have to do is to pass the `Iterator` as second parameter to the `$sse->send()` call:

```php
$sse->send(
  "your message",
  $iterator
);
```

The frontend SSE script will then be able to read the progress while it is running!

### Frontend

UIkit offers a progressbar component that we can use to show the progress of the long running task. All we have to do is set a value and a maximum:

```html
<progress id="trash-pages-progress" class="uk-progress" value="0" max="100"></progress>
```

Then all we have to do in our frontend is to update the progress bar's value:

```js
const stream = ProcessWire.Sse.stream(...);

// update progress bar
const progressBar = document.querySelector('#trash-pages-progress');
stream.onProgress((progress) => {
  progressBar.value = progress.percent;
});
```

Note: The `progress` object does not only contain the percent, it also contains the `num` (current 1-based iteration count) and `max` properties.

## sse.prepend()

When writing SSE event data to a textarea the content of the textarea can quickly grow to a lot of lines and this will cause a lot of work for the browser.

That's why it's recommended to use the `sse.prepend()` helper:

```js
const stream = ProcessWire.Sse.stream(
  'ssedemo-trash-pages',
  (event) => {
    const textarea = document.querySelector('textarea[name="trash-status"]');
    stream.prepend(textarea, event.data, 100);
  }
);
```

This will write `event.data` to the textarea and the final parameter `100` will tell it to cut off every line after the 100th, keeping the textarea performant and readable.
