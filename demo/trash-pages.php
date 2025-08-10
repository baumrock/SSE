<div>
  <textarea name='trash-status' rows=5 class='uk-textarea uk-margin-small-bottom'></textarea>
  <button id="trash-pages" class='uk-button uk-button-primary'>Start</button>
  <button id="stop-trash-pages" class='uk-button uk-button-secondary'>Stop</button>
</div>
<script>
  (() => {
    // create stream
    const stream = ProcessWire.Sse.stream(
      'ssedemo-trash-pages',
      (event) => {
        const textarea = document.querySelector('textarea[name="trash-status"]');
        textarea.value = event.data + "\n" + textarea.value;
      }
    );
    // click on start button
    document.querySelector('#trash-pages').addEventListener(
      'click',
      (e) => {
        e.preventDefault();
        stream.start({
          count: document.querySelector('#count').value,
        });
      });
    // click on stop button
    document.querySelector('#stop-trash-pages').addEventListener(
      'click',
      (e) => {
        e.preventDefault();
        stream.stop();
      });
  })()
</script>