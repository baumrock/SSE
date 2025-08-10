<div>
  <textarea name='empty-trash-status' rows=5 class='uk-textarea uk-margin-small-bottom'></textarea>
  <button id="empty-trash" class='uk-button uk-button-primary'>Start</button>
  <button id="stop-empty-trash" class='uk-button uk-button-secondary'>Stop</button>
</div>
<script>
  (() => {
    // create stream
    const stream = ProcessWire.Sse.stream(
      'ssedemo-empty-trash',
      (event) => {
        const textarea = document.querySelector('textarea[name="empty-trash-status"]');
        textarea.value = event.data + "\n" + textarea.value;
      }
    );
    // click on start button
    document.querySelector('#empty-trash').addEventListener(
      'click',
      (e) => {
        e.preventDefault();
        stream.start();
      });
    // click on stop button
    document.querySelector('#stop-empty-trash').addEventListener(
      'click',
      (e) => {
        e.preventDefault();
        stream.stop();
      });
  })()
</script>