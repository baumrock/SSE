<textarea name='server-time' rows=5 class='uk-textarea uk-margin-small-bottom uk-text-small'></textarea>
<button id='start-time' class='uk-button uk-button-primary'>Start</button>
<button id='stop-time' class='uk-button uk-button-secondary'>Stop</button>
<script>
  (() => {
    // create stream
    const stream = ProcessWire.Sse.stream(
      'ssedemo-server-time',
      (event) => {
        const textarea = document.querySelector('textarea[name="server-time"]');
        textarea.value = event.data + "\n" + textarea.value;
      }
    );
    // click on start button
    document.querySelector('#start-time').addEventListener(
      'click',
      (e) => {
        e.preventDefault();
        stream.start();
      });
    // click on stop button
    document.querySelector('#stop-time').addEventListener(
      'click',
      (e) => {
        e.preventDefault();
        stream.stop();
      });
  })()
</script>